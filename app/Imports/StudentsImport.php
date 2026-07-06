<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\User;
use App\Models\QrCode;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode as QrCodeGenerator;
use Endroid\QrCode\Writer\SvgWriter;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

class StudentsImport
{
    public array $errors = [];
    public int $imported = 0;
    public int $skipped = 0;
    public int $qrGenerated = 0;
    public int $newStudents = 0;
    public int $reusedStudents = 0;
    public int $assignmentsCreated = 0;
    public int $duplicateAssignmentsSkipped = 0;

    private string $section = '';
    private string $schoolYear = '';

    public function import(string $filePath, ?User $authUser = null, ?string $fallbackGradeLevel = null): void
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $rows = match ($extension) {
            'xlsx' => $this->readXlsx($filePath),
            'xls' => $this->readXls($filePath),
            default => null,
        };

        if ($rows === null) {
            $this->errors[] = "Unsupported file format: .{$extension}. Please upload an .xlsx or .xls file.";
            return;
        }

        if (empty($rows)) {
            $this->errors[] = 'Could not read any data from the file.';
            return;
        }

        if ($this->detectSf1Format($rows)) {
            $this->importSf1($rows, $authUser, $fallbackGradeLevel);
        } else {
            $this->importStandard($rows, $authUser);
        }
    }

    private function readXlsx(string $filePath): array
    {
        $reader = new XLSXReader();
        $reader->open($filePath);

        $allRows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    try {
                        $cells[] = trim((string) $cell->getFormattedValue());
                    } catch (\Throwable $e) {
                        $cells[] = $this->cellToString($cell->getValue());
                    }
                }
                $allRows[] = $cells;
            }
            break;
        }

        $reader->close();
        return $allRows;
    }

    private function cellToString($value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return implode(' ', array_map([$this, 'cellToString'], $value));
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return trim((string) $value);
            }

            return '';
        }

        return trim((string) $value);
    }

    private function readXls(string $filePath): array
    {
        if (!class_exists(\Shuchkin\SimpleXLS::class)) {
            $this->errors[] = 'XLS reader is not available. Please re-save the file as .xlsx and try again.';
            return [];
        }

        $xls = \Shuchkin\SimpleXLS::parse($filePath);
        if (!$xls) {
            $this->errors[] = 'Failed to read .xls file: ' . \Shuchkin\SimpleXLS::parseError();
            return [];
        }

        return $xls->rows();
    }

    private function detectSf1Format(array $rows): bool
    {
        $limit = min(12, count($rows));
        $sf1Hints = 0;

        for ($i = 0; $i < $limit; $i++) {
            $fullText = '';
            foreach ($rows[$i] as $cell) {
                $fullText .= strtolower(trim((string) $cell)) . ' ';
            }

            if (str_contains($fullText, 'school form 1') || str_contains($fullText, 'sf1')) {
                $sf1Hints += 3;
            }
            if (str_contains($fullText, 'school id')) {
                $sf1Hints++;
            }
            if (str_contains($fullText, 'learner reference number') || preg_match('/\blrn\b/', $fullText)) {
                $sf1Hints++;
            }

            if (preg_match('/\bsex\b/', $fullText)) {
                $sf1Hints++;
            }

            if (preg_match('/\bbirth\b/', $fullText) && preg_match('/\bdate\b/', $fullText)) {
                $sf1Hints++;
            }
        }

        return $sf1Hints >= 4;
    }

    private function importSf1(array $rows, ?User $authUser, ?string $fallbackGradeLevel): void
    {
        $gradeLevel = $this->extractGradeFromSf1($rows) ?? $fallbackGradeLevel;

        if (!$gradeLevel) {
            $this->errors[] = 'Could not detect grade level from the SF1 file. Please select a grade level and try again.';
            return;
        }

        $this->section = $this->extractSectionFromSf1($rows);
        $this->schoolYear = $this->extractSchoolYearFromSf1($rows);

        $headerIdx = $this->findSf1HeaderRow($rows);
        if ($headerIdx === null) {
            $this->errors[] = 'Could not find the student data table in the SF1 file (expected columns: LRN, Name, Sex).';
            return;
        }

        $headerRow = $rows[$headerIdx];
        $colMap = $this->mapSf1Columns($headerRow);

        if (!isset($colMap['lrn']) || !isset($colMap['name'])) {
            $this->errors[] = 'Could not find LRN or NAME columns in the SF1 file.';
            return;
        }

        $isTeacher = $authUser && $authUser->isTeacher();
        $teacherId = $isTeacher ? $authUser->id : null;
        $fileName = basename($authUser ? request()->file('file')->getClientOriginalName() : 'import.xlsx');

        $hasDetails = isset($colMap['birth_date']) || isset($colMap['mother_tongue']) || isset($colMap['father_name']);

        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowNumber = $i + 1;

            $lrn = isset($row[$colMap['lrn']]) ? trim((string) $row[$colMap['lrn']]) : '';
            $lrn = preg_replace('/\D/', '', $lrn);

            if (empty($lrn) || strlen($lrn) < 10) {
                $this->errors[] = "Row {$rowNumber}: Missing or invalid LRN.";
                $this->skipped++;
                continue;
            }

            $fullText = implode(' ', array_map('strtolower', array_map('trim', $row)));
            if (str_contains($fullText, 'total') || str_contains($fullText, 'prepared by')) {
                continue;
            }

            $rawName = isset($row[$colMap['name']]) ? trim((string) $row[$colMap['name']]) : '';
            if (empty($rawName)) {
                $this->errors[] = "Row {$rowNumber} (LRN: {$lrn}): Missing name.";
                $this->skipped++;
                continue;
            }

            $nameData = $this->parseStudentName($rawName);
            if (!$nameData) {
                $this->errors[] = "Row {$rowNumber} (LRN: {$lrn}): Could not parse name from '{$rawName}'.";
                $this->skipped++;
                continue;
            }

            $rawGender = isset($colMap['sex'], $row[$colMap['sex']])
                ? trim((string) $row[$colMap['sex']])
                : '';
            $gender = $this->normalizeGender($rawGender);

            if (empty($gender)) {
                $this->errors[] = "Row {$rowNumber} (LRN: {$lrn}): Invalid gender value '{$rawGender}'.";
                $this->skipped++;
                continue;
            }

            $sf1Data = [];
            if ($hasDetails) {
                $sf1Data = $this->extractSf1Details($row, $colMap, $rowNumber);
            }

            $student = Student::where('lrn', $lrn)->first();

            if ($student) {
                $this->reusedStudents++;
                $this->updateStudentFromSf1($student, $nameData, $gender, $gradeLevel, $sf1Data);
            } else {
                $nextId = (Student::max('id') ?? 0) + 1;
                $student = Student::create(array_merge([
                    'student_id' => 'STU' . str_pad($nextId, 3, '0', STR_PAD_LEFT),
                    'lrn' => $lrn,
                    'first_name' => $nameData['first_name'],
                    'middle_name' => $nameData['middle_name'],
                    'last_name' => $nameData['last_name'],
                    'full_name' => $nameData['full_name'],
                    'grade_level' => $gradeLevel,
                    'gender' => $gender,
                    'qr_code' => 'QR-' . $lrn,
                    'total_points' => 0,
                    'status' => 'Active',
                ], $sf1Data));
                $this->newStudents++;
            }

            $this->createOrUpdateEnrollment($student, $teacherId, $gradeLevel, $fileName);
            $this->imported++;
            $this->createQrForImportedStudent($student);
        }
    }

    private function extractSf1Details(array $row, array $colMap, int $rowNumber): array
    {
        $data = [];

        if (isset($colMap['birth_date'])) {
            $raw = trim((string) ($row[$colMap['birth_date']] ?? ''));
            if ($raw !== '') {
                $parsed = $this->parseDate($raw);
                if ($parsed) {
                    $data['birth_date'] = $parsed;
                    $data['age'] = $this->calculateAge($parsed);
                }
            }
        }

        if (isset($colMap['age'])) {
            $raw = trim((string) ($row[$colMap['age']] ?? ''));
            if ($raw !== '' && is_numeric($raw)) {
                $data['age'] = (int) $raw;
            }
        }

        $textFields = [
            'mother_tongue' => 'mother_tongue',
            'ip_ethnic_group' => 'ip_ethnic_group',
            'religion' => 'religion',
            'house_street' => 'house_street',
            'barangay' => 'barangay',
            'municipality_city' => 'municipality_city',
            'province' => 'province',
            'father_name' => 'father_name',
            'mother_maiden_name' => 'mother_maiden_name',
            'guardian_name' => 'guardian_name',
            'guardian_relationship' => 'guardian_relationship',
            'contact_number' => 'contact_number',
            'learning_modality' => 'learning_modality',
        ];

        foreach ($textFields as $colKey => $field) {
            if (isset($colMap[$colKey])) {
                $val = trim((string) ($row[$colMap[$colKey]] ?? ''));
                if ($val !== '') {
                    $data[$field] = $val;
                }
            }
        }

        if (isset($colMap['remarks'])) {
            $val = trim((string) ($row[$colMap['remarks']] ?? ''));
            if ($val !== '') {
                $data['remarks'] = $val;
            }
        }

        return $data;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        $formats = [
            'm/d/Y', 'm/d/y', 'Y/m/d',
            'd/m/Y', 'd/m/y',
            'F j, Y', 'j F Y',
            'd F Y', 'F d, Y',
            'd-m-Y', 'm-d-Y',
        ];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt) {
                return $dt->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        if (is_numeric($value) && $value > 40000) {
            $unix = ($value - 25569) * 86400;
            return date('Y-m-d', $unix);
        }

        return null;
    }

    private function calculateAge(string $birthDate): int
    {
        $birth = new \DateTime($birthDate);
        $now = new \DateTime();
        return $now->diff($birth)->y;
    }

    private function updateStudentFromSf1(Student $student, array $nameData, string $gender, string $gradeLevel, array $sf1Data): void
    {
        $update = [];

        if (empty($student->first_name) && $nameData['first_name']) {
            $update['first_name'] = $nameData['first_name'];
        }
        if (empty($student->middle_name) && $nameData['middle_name']) {
            $update['middle_name'] = $nameData['middle_name'];
        }
        if (empty($student->last_name) && $nameData['last_name']) {
            $update['last_name'] = $nameData['last_name'];
        }
        if (empty($student->full_name) && $nameData['full_name']) {
            $update['full_name'] = $nameData['full_name'];
        }
        if (empty($student->gender)) {
            $update['gender'] = $gender;
        }
        if (empty($student->grade_level)) {
            $update['grade_level'] = $gradeLevel;
        }

        $fieldsToFill = [
            'birth_date', 'age', 'mother_tongue', 'ip_ethnic_group', 'religion',
            'house_street', 'barangay', 'municipality_city', 'province',
            'father_name', 'mother_maiden_name', 'guardian_name', 'guardian_relationship',
            'contact_number', 'learning_modality', 'remarks',
        ];

        foreach ($fieldsToFill as $field) {
            if (isset($sf1Data[$field]) && (empty($student->{$field}) || $student->{$field} === null)) {
                $update[$field] = $sf1Data[$field];
            }
        }

        if (!empty($update)) {
            $student->update($update);
        }
    }

    private function createOrUpdateEnrollment(Student $student, ?int $teacherId, string $gradeLevel, string $fileName): void
    {
        $teacherId = $teacherId ?? 1;

        try {
            StudentEnrollment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'teacher_id' => $teacherId,
                    'grade_level' => $gradeLevel,
                    'section' => $this->section ?: null,
                    'school_year' => $this->schoolYear ?: null,
                ],
                [
                    'imported_by' => Auth::id(),
                    'imported_from_file' => $fileName,
                    'status' => 'active',
                ]
            );
            $this->assignmentsCreated++;
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() == 23000) {
                $this->duplicateAssignmentsSkipped++;
            } else {
                throw $e;
            }
        }
    }

    private function extractGradeFromSf1(array $rows): ?string
    {
        $limit = min(10, count($rows));

        for ($i = 0; $i < $limit; $i++) {
            $fullText = '';
            foreach ($rows[$i] as $cell) {
                $fullText .= ' ' . trim((string) $cell);
            }

            $lower = strtolower($fullText);

            $kindergartenPatterns = ['kindergarten', 'kinder', 'grade k', 'grade kinder'];
            foreach ($kindergartenPatterns as $pattern) {
                if (str_contains($lower, $pattern)) {
                    return 'Kindergarten';
                }
            }

            if (preg_match('/Grade\s+(\d+)/i', $fullText, $m)) {
                $n = (int) $m[1];
                if ($n >= 1 && $n <= 6) {
                    return 'Grade ' . $n;
                }
            }
        }

        return null;
    }

    private function extractSectionFromSf1(array $rows): string
    {
        $limit = min(10, count($rows));

        for ($i = 0; $i < $limit; $i++) {
            $fullText = '';
            foreach ($rows[$i] as $cell) {
                $fullText .= ' ' . trim((string) $cell);
            }

            if (preg_match('/Section[:\s]+(.+)/i', $fullText, $m)) {
                $section = trim($m[1]);
                $section = preg_replace('/\s+/', ' ', $section);
                if (strlen($section) < 50) {
                    return $section;
                }
            }
        }

        return '';
    }

    private function extractSchoolYearFromSf1(array $rows): string
    {
        $limit = min(10, count($rows));

        for ($i = 0; $i < $limit; $i++) {
            $fullText = '';
            foreach ($rows[$i] as $cell) {
                $fullText .= ' ' . trim((string) $cell);
            }

            if (preg_match('/School\s*Year[:\s]+([\d\-–—\/]+)/i', $fullText, $m)) {
                $sy = trim($m[1]);
                $sy = preg_replace('/[–—]/', '-', $sy);
                return $sy;
            }
        }

        return '';
    }

    private function findSf1HeaderRow(array $rows): ?int
    {
        foreach ($rows as $i => $row) {
            $cellText = array_map(function ($c) {
                return strtolower(trim((string) $c));
            }, $row);

            $hasLrn = false;
            $hasName = false;
            $hasSex = false;

            foreach ($cellText as $text) {
                if (preg_match('/\blrn\b/', $text) || str_contains($text, 'learner reference')) {
                    $hasLrn = true;
                }
                if ($text === 'name' || str_contains($text, 'name')) {
                    $hasName = true;
                }
                if ($text === 'sex' || $text === 'gender') {
                    $hasSex = true;
                }
            }

            if ($hasLrn && $hasName && $hasSex) {
                return $i;
            }
        }

        return null;
    }

    private function mapSf1Columns(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $cell) {
            $text = strtolower(trim((string) $cell));

            if (preg_match('/\blrn\b/', $text) || str_contains($text, 'learner reference')) {
                $map['lrn'] = $index;
            } elseif ($text === 'name' || str_contains($text, 'name')) {
                $map['name'] = $index;
            } elseif ($text === 'sex' || $text === 'gender') {
                $map['sex'] = $index;
            } elseif (str_contains($text, 'birth') && str_contains($text, 'date')) {
                $map['birth_date'] = $index;
            } elseif ($text === 'age') {
                $map['age'] = $index;
            } elseif (str_contains($text, 'mother tongue')) {
                $map['mother_tongue'] = $index;
            } elseif (str_contains($text, 'ip') || str_contains($text, 'ethnic')) {
                $map['ip_ethnic_group'] = $index;
            } elseif (str_contains($text, 'religion')) {
                $map['religion'] = $index;
            } elseif (str_contains($text, 'house') || str_contains($text, 'street') || str_contains($text, 'sitio') || str_contains($text, 'purok')) {
                $map['house_street'] = $index;
            } elseif (str_contains($text, 'barangay')) {
                $map['barangay'] = $index;
            } elseif (str_contains($text, 'municipality') || str_contains($text, 'city')) {
                $map['municipality_city'] = $index;
            } elseif (str_contains($text, 'province')) {
                $map['province'] = $index;
            } elseif (str_contains($text, 'father')) {
                $map['father_name'] = $index;
            } elseif (str_contains($text, 'mother') && (str_contains($text, 'maiden') || str_contains($text, 'name'))) {
                $map['mother_maiden_name'] = $index;
            } elseif (str_contains($text, 'guardian') && !str_contains($text, 'relationship')) {
                $map['guardian_name'] = $index;
            } elseif (str_contains($text, 'guardian') && str_contains($text, 'relationship')) {
                $map['guardian_relationship'] = $index;
            } elseif (str_contains($text, 'contact') || str_contains($text, 'tel') || str_contains($text, 'phone') || str_contains($text, 'mobile')) {
                $map['contact_number'] = $index;
            } elseif (str_contains($text, 'modality') || str_contains($text, 'learning')) {
                $map['learning_modality'] = $index;
            } elseif (str_contains($text, 'remarks') || str_contains($text, 'remark')) {
                $map['remarks'] = $index;
            }
        }

        return $map;
    }

    private function parseStudentName(string $name): ?array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        if ($name === '') {
            return null;
        }

        if (str_contains($name, ',')) {
            $parts = array_map('trim', explode(',', $name));

            $lastName = $parts[0] ?? '';
            $firstName = $parts[1] ?? '';
            $middleName = $parts[2] ?? '';

            if ($firstName === '' || $lastName === '') {
                return null;
            }

            $firstName = $this->properName($firstName);
            $middleName = $this->properName($middleName);
            $lastName = $this->properName($lastName);

            return [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'full_name' => trim($firstName . ' ' . $middleName . ' ' . $lastName),
            ];
        }

        $parts = explode(' ', $name);

        if (count($parts) < 2) {
            return null;
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        $middleName = implode(' ', $parts);

        $firstName = $this->properName($firstName);
        $middleName = $this->properName($middleName);
        $lastName = $this->properName($lastName);

        return [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'full_name' => trim($firstName . ' ' . $middleName . ' ' . $lastName),
        ];
    }

    private function properName(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }

    private function importStandard(array $rows, ?User $authUser): void
    {
        if (count($rows) < 2) {
            return;
        }

        $headerIndex = null;
        foreach ($rows as $i => $row) {
            $cellValues = [];
            $fullText = '';
            foreach ($row as $cell) {
                $val = strtolower(trim((string) $cell));
                $cellValues[] = $val;
                $fullText .= $val . ' ';
            }
            $fullText = trim($fullText);

            if (count($cellValues) <= 2 && strlen($fullText) > 20) {
                continue;
            }

            $skipWords = ['sample', 'guide', 'instruction', 'example', 'ecocollect', 'note:'];
            $shouldSkip = false;
            foreach ($skipWords as $word) {
                if (str_contains($fullText, $word)) {
                    $shouldSkip = true;
                    break;
                }
            }
            if ($shouldSkip) {
                continue;
            }

            $hasLrn = str_contains($fullText, 'lrn');
            $headerTerms = ['name', 'grade', 'gender', 'student', 'first', 'last'];
            $headerCount = 0;
            foreach ($headerTerms as $term) {
                if (str_contains($fullText, $term)) {
                    $headerCount++;
                }
            }

            if ($hasLrn || $headerCount >= 2) {
                $headerIndex = $i;
                break;
            }
        }

        if ($headerIndex === null) {
            $headerIndex = 0;
        }

        $headerRow = $rows[$headerIndex];
        $headers = $this->normalizeHeaders($headerRow);

        $isTeacher = $authUser && $authUser->isTeacher();
        $teacherId = $isTeacher ? $authUser->id : null;
        $fileName = basename(request()->file('file')->getClientOriginalName());

        $rowNumber = $headerIndex + 2;
        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowData = $this->mapRowToHeaders($headers, $row);

            if ($this->isEmptyRow($rowData)) {
                $rowNumber++;
                continue;
            }

            $rowErrors = $this->validateRow($rowData);
            if (!empty($rowErrors)) {
                $this->errors[] = "Row {$rowNumber} (LRN: " . ($rowData['lrn'] ?? 'N/A') . '): ' . implode(' ', $rowErrors);
                $this->skipped++;
                $rowNumber++;
                continue;
            }

            $fullName = trim($rowData['first_name'] . ' ' . ($rowData['middle_name'] ?? '') . ' ' . $rowData['last_name']);

            $existingByLrn = Student::where('lrn', $rowData['lrn'])->first();

            if ($existingByLrn) {
                $this->reusedStudents++;
                $updateData = [];
                if (empty($existingByLrn->first_name)) $updateData['first_name'] = $rowData['first_name'];
                if (empty($existingByLrn->last_name)) $updateData['last_name'] = $rowData['last_name'];
                if (empty($existingByLrn->middle_name) && !empty($rowData['middle_name'])) $updateData['middle_name'] = $rowData['middle_name'];
                if (empty($existingByLrn->full_name)) $updateData['full_name'] = $fullName;
                if (empty($existingByLrn->grade_level) && !empty($rowData['grade_level'])) $updateData['grade_level'] = $rowData['grade_level'];
                if (empty($existingByLrn->gender) && !empty($rowData['gender'])) $updateData['gender'] = $rowData['gender'];
                if (!empty($updateData)) {
                    $existingByLrn->update($updateData);
                }
                $student = $existingByLrn;
            } else {
                $nextId = (Student::max('id') ?? 0) + 1;
                $student = Student::create([
                    'student_id' => $rowData['student_id'] ?? 'STU' . str_pad($nextId, 3, '0', STR_PAD_LEFT),
                    'lrn' => $rowData['lrn'],
                    'first_name' => $rowData['first_name'],
                    'middle_name' => $rowData['middle_name'] ?? null,
                    'last_name' => $rowData['last_name'],
                    'full_name' => $fullName,
                    'grade_level' => $rowData['grade_level'],
                    'gender' => $rowData['gender'],
                    'qr_code' => 'QR-' . $rowData['lrn'],
                    'total_points' => 0,
                    'status' => 'Active',
                ]);
                $this->newStudents++;
            }

            $this->createOrUpdateEnrollment($student, $teacherId, $rowData['grade_level'], $fileName);
            $this->imported++;
            $this->createQrForImportedStudent($student);
            $rowNumber++;
        }
    }

    private function normalizeHeaders(array $headerRow): array
    {
        $headers = [];
        foreach ($headerRow as $index => $value) {
            $value = trim((string) $value);
            $key = strtolower(preg_replace('/[\s\-\.]+/', '_', $value));
            $headers[$index] = $key;
        }
        return $headers;
    }

    private function mapRowToHeaders(array $headers, array $row): array
    {
        $data = [];
        foreach ($row as $index => $value) {
            $header = $headers[$index] ?? 'col_' . $index;
            $data[$header] = trim((string) $value);
        }

        $result = [
            'first_name' => $this->pickField($data, ['first_name', 'firstname', 'first', 'given_name', 'givenname']),
            'middle_name' => $this->pickField($data, ['middle_name', 'middlename', 'middle', 'middle_initial', 'middleinitial']),
            'last_name' => $this->pickField($data, ['last_name', 'lastname', 'last', 'family_name', 'familyname', 'surname']),
            'lrn' => $this->pickField($data, ['lrn', 'student_lrn', 'students_lrn', 'learner_reference_number']),
            'grade_level' => $this->pickField($data, ['grade_level', 'gradelevel', 'grade', 'class']),
            'gender' => $this->pickField($data, ['gender', 'sex']),
            'student_id' => $this->pickField($data, ['student_id', 'studentid', 'id_number', 'idnumber']),
            'teacher_email' => $this->pickField($data, ['teacher_email', 'teacheremail', 'teacher', 'teacher_mail']),
        ];

        $studentName = $this->pickField($data, ['student_name', 'studentname', 'name', 'full_name', 'fullname', 'complete_name', 'completename']);
        if ($studentName && (!$result['first_name'] || !$result['last_name'])) {
            $parts = preg_split('/\s+/', trim($studentName));
            $result['first_name'] = $parts[0] ?? '';
            $result['last_name'] = count($parts) > 1 ? end($parts) : '';
            if (count($parts) > 2) {
                $result['middle_name'] = implode(' ', array_slice($parts, 1, -1));
            }
        }

        if (!empty($result['lrn'])) {
            $result['lrn'] = preg_replace('/\D/', '', (string) $result['lrn']);
        }

        $result['grade_level'] = $this->normalizeGrade($result['grade_level']);
        $result['gender'] = $this->normalizeGender($result['gender']);

        return $result;
    }

    private function pickField(array $data, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            if (isset($data[$alias]) && $data[$alias] !== '') {
                return $data[$alias];
            }
        }
        return null;
    }

    private function normalizeGender(?string $gender): ?string
    {
        if (!$gender) return null;
        return match (strtolower(trim($gender))) {
            'm', 'male' => 'Male',
            'f', 'female' => 'Female',
            default => null,
        };
    }

    private function normalizeGrade(?string $grade): ?string
    {
        if (!$grade) return null;

        $grade = trim((string) $grade);
        $gradeLower = strtolower($grade);

        $kindergartenValues = [
            'k',
            'kg',
            'kinder',
            'kindergarten',
            'grade k',
            'grade kinder',
            'grade kindergarten',
        ];

        if (in_array($gradeLower, $kindergartenValues)) {
            return 'Kindergarten';
        }

        if (preg_match('/\d+/', $grade, $matches)) {
            $n = (int) $matches[0];
            if ($n >= 1 && $n <= 6) {
                return 'Grade ' . $n;
            }
        }

        $valid = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        return in_array($grade, $valid) ? $grade : null;
    }

    private function isEmptyRow(array $data): bool
    {
        return empty($data['lrn'])
            && empty($data['first_name'])
            && empty($data['last_name']);
    }

    private function validateRow(array $data): array
    {
        $errors = [];
        if (empty($data['first_name'])) $errors[] = 'First name is required.';
        if (empty($data['last_name'])) $errors[] = 'Last name is required.';
        if (empty($data['lrn'])) $errors[] = 'LRN is required.';
        if (empty($data['grade_level'])) $errors[] = 'Grade level is required or invalid.';
        if (empty($data['gender'])) $errors[] = 'Gender must be Male or Female.';
        return $errors;
    }

    private function createQrForImportedStudent(Student $student): void
    {
        $qrValue = "LRN: " . $student->lrn . "\nName: " . $student->full_name;

        $exists = QrCode::where('student_id', $student->id)
            ->where('qr_type', 'lrn')
            ->where('qr_value', $qrValue)
            ->exists();

        if ($exists) {
            return;
        }

        $fileName = 'student-lrn-' . Str::slug($student->full_name) . '-' . time() . '.svg';
        $filePath = 'qr_codes/' . $fileName;

        $qrCode = new QrCodeGenerator(
            data: $qrValue,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 400,
            margin: 20,
        );
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        Storage::disk('public')->put($filePath, $result->getString());

        QrCode::create([
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'qr_type' => 'lrn',
            'qr_value' => $qrValue,
            'qr_image_path' => $filePath,
            'created_by' => auth()->id(),
        ]);

        $this->qrGenerated++;
    }
}
