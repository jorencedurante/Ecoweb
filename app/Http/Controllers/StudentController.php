<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\Achievement;
use App\Models\StudentAchievement;
use App\Models\CertificateAward;
use App\Models\AdminActivity;
use App\Models\StudentEnrollment;
use App\Imports\StudentsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Common\Entity\Row;

class StudentController extends Controller
{
    private function authorizeStudentAccess(Student $student): void
    {
        $user = Auth::user();

        if ($user->isAdminLevel()) {
            return;
        }

        if ($user->isTeacher()) {
            $hasAccess = $student->enrollments()
                ->where('teacher_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if ($hasAccess) {
                return;
            }
        }

        abort(403, 'Unauthorized access to this student record.');
    }

    public function index(Request $request)
    {
        $query = Student::query()->whereNotIn('status', ['Archived', 'archived']);
        $query->visibleTo(Auth::user());

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('grade_level', 'like', "%{$search}%")
                  ->orWhere('qr_code', 'like', "%{$search}%");
            });
        }

        if ($gradeLevel = $request->get('grade_level')) {
            $query->where('grade_level', $gradeLevel);
        }

        if ($gender = $request->get('gender')) {
            $query->where('gender', $gender);
        }

        $students = $query->latest()->paginate(10)->withQueryString();
        $gradeLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        $teachers = User::where('role', 'teacher')->where('status', 'active')->orderBy('name')->get();

        return view('pages.students', compact('students', 'gradeLevels', 'teachers'));
    }

    public function archived(Request $request)
    {
        $query = Student::query()->whereIn('status', ['Archived', 'archived']);
        $query->visibleTo(Auth::user());

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('grade_level', 'like', "%{$search}%")
                  ->orWhere('qr_code', 'like', "%{$search}%");
            });
        }

        if ($gradeLevel = $request->get('grade_level')) {
            $query->where('grade_level', $gradeLevel);
        }

        if ($gender = $request->get('gender')) {
            $query->where('gender', $gender);
        }

        $students = $query->latest()->paginate(10)->withQueryString();
        $gradeLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

        return view('pages.students-archived', compact('students', 'gradeLevels'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'lrn' => 'required|unique:students,lrn',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'grade_level' => 'required|in:Kindergarten,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6',
            'gender' => 'required|in:Male,Female',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $fullName = trim(
            $validated['first_name'] . ' ' .
            ($validated['middle_name'] ?? '') . ' ' .
            $validated['last_name']
        );

        $nextId = (Student::max('id') ?? 0) + 1;

        if ($user->isTeacher()) {
            $validated['teacher_id'] = $user->id;
        } elseif (!$user->isAdminLevel()) {
            $validated['teacher_id'] = null;
        }

        $student = Student::create([
            'student_id' => 'STU' . str_pad($nextId, 3, '0', STR_PAD_LEFT),
            'lrn' => $validated['lrn'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'full_name' => $fullName,
            'grade_level' => $validated['grade_level'],
            'gender' => $validated['gender'],
            'qr_code' => 'QR-' . $validated['lrn'],
            'total_points' => 0,
            'status' => 'Active',
        ]);

        $teacherId = $validated['teacher_id'] ?? null;
        if (!$teacherId && $user->isTeacher()) {
            $teacherId = $user->id;
        }

        if ($teacherId) {
            StudentEnrollment::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
                'grade_level' => $validated['grade_level'],
                'status' => 'active',
                'imported_by' => Auth::id(),
            ]);
        }

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Added Student',
            'description' => "Added {$student->full_name} to {$student->grade_level}.",
            'module' => 'Students',
        ]);

        return redirect()->route('admin.students')->with('success', 'Student added successfully!');
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($student);

        $user = Auth::user();
        $rules = [
            'lrn' => ['required', Rule::unique('students', 'lrn')->ignore($student->id)],
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'grade_level' => 'required|in:Kindergarten,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6',
            'gender' => 'required|in:Male,Female',
        ];

        if ($user->isAdminLevel()) {
            $rules['teacher_id'] = 'nullable|exists:users,id';
        }

        $validated = $request->validate($rules);

        if (!$user->isAdminLevel()) {
            unset($validated['teacher_id']);
        }

        $student->update($validated);

        if (isset($validated['teacher_id'])) {
            StudentEnrollment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'teacher_id' => $validated['teacher_id'],
                ],
                [
                    'grade_level' => $student->grade_level,
                    'status' => 'active',
                ]
            );
        }

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Edited Student',
            'description' => "Edited {$student->full_name}'s information.",
            'module' => 'Students',
        ]);

        return redirect()->route('admin.students')->with('success', 'Student updated successfully!');
    }

    public function archive(Student $student)
    {
        $this->authorizeStudentAccess($student);

        $student->update(['status' => 'Archived']);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Archived Student',
            'description' => "Archived {$student->full_name}.",
            'module' => 'Students',
        ]);

        return redirect()->route('admin.students')->with('success', 'Student archived successfully!');
    }

    public function restore(Student $student)
    {
        $this->authorizeStudentAccess($student);

        $student->update(['status' => 'Active']);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Restored Student',
            'description' => "Restored {$student->full_name} to active students.",
            'module' => 'Students',
        ]);

        return redirect()->route('admin.students.archived')->with('success', 'Student restored successfully!');
    }

    public function info(Student $student)
    {
        $this->authorizeStudentAccess($student);

        $student->load([
            'bottleCollections',
            'enrollments.teacher',
            'qrCodes' => function ($q) {
                $q->latest()->limit(1);
            },
        ]);

        $currentEnrollment = $student->enrollments
            ->where('teacher_id', Auth::user()->isTeacher() ? Auth::id() : optional($student->enrollments->first())->teacher_id)
            ->first();

        return view('pages.student-info', compact('student', 'currentEnrollment'));
    }

    public function achievements(Student $student)
    {
        $this->authorizeStudentAccess($student);

        $quests = Achievement::whereNull('student_id')
            ->where('status', 'Active')
            ->latest()
            ->get();

        $earnedAchievements = StudentAchievement::with('quest')
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        $totalBottles = $student->bottleCollections()->sum('bottle_count');
        $earnedPoints = $student->total_points ?? $totalBottles;

        return view('pages.student-achievements', compact(
            'student', 'quests', 'earnedAchievements', 'totalBottles', 'earnedPoints'
        ));
    }

    public function updateAchievementProgress(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($student);

        $validated = $request->validate([
            'total_bottles' => 'required|integer|min:0',
            'earned_points' => 'required|integer|min:0',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'badge_name' => 'nullable|string|max:255',
            'current_milestone' => 'nullable|integer|min:0',
            'next_milestone' => 'required|integer|min:1',
            'progress_value' => 'required|integer|min:0',
            'status' => 'required|in:In Progress,Completed,Locked',
        ]);

        $achievement = $student->achievements()->latest()->first();

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'badge_name' => $validated['badge_name'] ?? null,
            'current_milestone' => $validated['current_milestone'] ?? 0,
            'next_milestone' => $validated['next_milestone'],
            'progress_value' => $validated['progress_value'],
            'status' => $validated['status'],
        ];

        if ($achievement) {
            $achievement->update($data);
        } else {
            $student->achievements()->create($data);
        }

        $student->update(['total_points' => $validated['earned_points']]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Updated Achievement Progress',
            'description' => "Updated achievement progress for {$student->full_name}.",
            'module' => 'Achievements',
        ]);

        return redirect()->route('students.achievements', $student)
            ->with('success', 'Achievement progress updated successfully.');
    }

    public function search(Request $request)
    {
        $search = $request->get('q');
        $user = Auth::user();

        $students = Student::query()
            ->whereNotIn('status', ['Archived', 'archived'])
            ->when($user->isTeacher(), function ($query) use ($user) {
                $query->whereHas('enrollments', function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)->where('status', 'active');
                });
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('lrn', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->limit(10)
            ->get();

        return response()->json($students->map(function ($student) {
            $name = $student->full_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

            return [
                'id' => $student->id,
                'name' => $name,
                'lrn' => $student->lrn,
                'student_id' => $student->student_id,
                'grade_level' => $student->grade_level,
                'total_points' => $student->total_points ?? 0,
            ];
        }));
    }

    public function awards(Student $student)
    {
        $this->authorizeStudentAccess($student);

        $awards = CertificateAward::where('student_id', $student->id)->get();
        return view('pages.student-awards', compact('student', 'awards'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'grade_level' => 'nullable|string|max:20',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->storeAs('imports', 'students_' . time() . '.' . $file->extension());

            $importer = new StudentsImport();
            $importer->import(Storage::path($path), Auth::user(), $request->grade_level);

            Storage::delete($path);

            AdminActivity::create([
                'user_id' => Auth::id(),
                'action' => 'Imported Students',
                'description' => "Imported {$importer->imported} students ({$importer->skipped} skipped).",
                'module' => 'Students',
            ]);

            $type = $importer->imported > 0 ? 'success' : 'warning';
            $message = 'Import Result:';
            $details = [];
            if ($importer->newStudents > 0) {
                $details[] = "New students created: {$importer->newStudents}";
            }
            if ($importer->reusedStudents > 0) {
                $details[] = "Existing students reused: {$importer->reusedStudents}";
            }
            if ($importer->assignmentsCreated > 0) {
                $details[] = "Class assignments created: {$importer->assignmentsCreated}";
            }
            if ($importer->duplicateAssignmentsSkipped > 0) {
                $details[] = "Duplicate assignments skipped: {$importer->duplicateAssignmentsSkipped}";
            }
            if ($importer->skipped > 0) {
                $details[] = "Rows skipped: {$importer->skipped}";
            }
            $details[] = "QR codes generated: {$importer->qrGenerated}";

            if (empty($details)) {
                $details[] = 'No data processed.';
            }

            return redirect()->route('admin.students')->with('import_result', [
                'type' => $type,
                'message' => implode(' | ', $details),
                'errors' => $importer->errors,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('admin.students')->with('import_result', [
                'type' => 'error',
                'message' => 'Import failed because duplicate data was found. Please check duplicate LRN or Student ID.',
                'errors' => [$e->getMessage()],
            ]);
        } catch (\Exception $e) {
            return redirect()->route('admin.students')->with('import_result', [
                'type' => 'error',
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [],
            ]);
        }
    }

    public function downloadTemplate()
    {
        $headers = ['LRN', 'Student Name', 'Grade Level', 'Gender', 'Student ID'];

        $writer = new XLSXWriter();
        $writer->openToBrowser('student_import_template.xlsx');

        $writer->addRow(Row::fromValues($headers));
        $writer->addRow(Row::fromValues(['123456789012', 'Juan Dela Cruz', 'Grade 6', 'Male', 'STU001']));
        $writer->addRow(Row::fromValues(['987654321098', 'Maria Clara Santos', 'Grade 5', 'Female', 'STU002']));

        $writer->close();
        exit;
    }
}
