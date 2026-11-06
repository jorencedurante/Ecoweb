<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\StudentAchievement;
use App\Models\CertificateAward;
use App\Models\AdminActivity;
use App\Models\StudentEnrollment;
use App\Imports\StudentsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Writer\XLSX\Entity\SheetView;

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

    private function ensureCanManageStudents(): void
    {
        $role = Auth::user()->role;

        if ($role === 'admin') {
            abort(403, 'Admin accounts are view-only and cannot modify student records.');
        }

        if (!in_array($role, ['teacher', 'super_admin'])) {
            abort(403, 'You are not allowed to manage student records.');
        }
    }

    /**
     * Validation rules for every editable student profile field used by the
     * tabbed Add/Edit Student form. LRN uniqueness and teacher_id are added
     * by the callers because they differ between store and update.
     */
    private function studentProfileRules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'grade_level' => 'required|in:Kindergarten,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6',
            'gender' => 'required|in:Male,Female',
            'birth_date' => 'nullable|date',
            'section' => 'nullable|string|max:100',
            'school_year' => 'required|string|max:20|regex:/^\d{4}-\d{4}$/',
            'house_street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:100',
            'municipality_city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'mother_maiden_name' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_relationship' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:50',
            'ip_ethnic_group' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'learning_modality' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Profile columns that are stored on the students row (everything except
     * section/school_year, which live on student_enrollments).
     */
    private function studentProfilePayload(array $validated): array
    {
        return collect($validated)
            ->except(['lrn', 'teacher_id', 'section', 'school_year'])
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
    }

    /**
     * Create or update the enrollment row that links this student to a teacher.
     * The existing row (latest for that teacher) is updated in place so no
     * duplicate assignment records are created, and an existing enrollment's
     * status is preserved so editing never reactivates archived records.
     */
    private function syncEnrollment(Student $student, int $teacherId, array $validated): void
    {
        $enrollment = $student->enrollments()
            ->where('teacher_id', $teacherId)
            ->latest('id')
            ->first();

        if (!$enrollment) {
            $enrollment = new StudentEnrollment([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
                'status' => 'active',
            ]);
        }

        if (array_key_exists('grade_level', $validated)) {
            $enrollment->grade_level = $validated['grade_level'];
        }
        if (array_key_exists('section', $validated)) {
            $enrollment->section = ($validated['section'] ?? '') !== '' ? $validated['section'] : null;
        }
        if (array_key_exists('school_year', $validated)) {
            $enrollment->school_year = ($validated['school_year'] ?? '') !== '' ? $validated['school_year'] : null;
        }

        if (!$enrollment->exists) {
            $enrollment->imported_by = Auth::id();
        }

        $enrollment->save();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
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

        if (Auth::user()->isAdminLevel()) {
            if ($gradeLevel = $request->get('grade_level')) {
                $query->where('grade_level', $gradeLevel);
            }
        }

        if ($gender = $request->get('gender')) {
            $query->where('gender', $gender);
        }

        $students = $query->with('enrollments')->orderBy('first_name')->orderBy('last_name')->orderBy('lrn')->paginate(10)->withQueryString();
        $gradeLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        $teachers = User::where('role', 'teacher')->where('status', 'active')->orderBy('name')->get();

        // School years available for Archive All: distinct years from active
        // enrollments of students visible to the current user.
        $archiveSchoolYears = StudentEnrollment::where('status', 'active')
            ->whereHas('student', function ($q) use ($user) {
                $q->whereNotIn('status', ['Archived', 'archived']);
            })
            ->when($user->isTeacher(), function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })
            ->orderBy('school_year', 'desc')
            ->pluck('school_year')
            ->unique()
            ->filter()
            ->values();

        // Count active enrollments with NULL school_year for this teacher
        $nullSchoolYearCount = StudentEnrollment::whereNull('school_year')
            ->where('status', 'active')
            ->when($user->isTeacher(), function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })
            ->count();

        return view('pages.students', compact('students', 'gradeLevels', 'teachers', 'archiveSchoolYears', 'nullSchoolYearCount'));
    }

    public function filterStudents(Request $request)
    {
        $user = Auth::user();
        $query = Student::query()->whereNotIn('status', ['Archived', 'archived']);
        $query->visibleTo($user);

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

        if ($user->isAdminLevel()) {
            if ($gradeLevel = $request->get('grade_level')) {
                $query->where('grade_level', $gradeLevel);
            }
        }

        if ($gender = $request->get('gender')) {
            $query->where('gender', $gender);
        }

        $students = $query->orderBy('first_name')->orderBy('last_name')->orderBy('lrn')->paginate(10)->withQueryString();
        $actionsColClass = $user->role === 'admin' ? 'stu-actions-3' : 'stu-actions-5';

        // Count active enrollments with NULL school_year for this teacher
        $nullSchoolYearCount = StudentEnrollment::whereNull('school_year')
            ->where('status', 'active')
            ->when($user->isTeacher(), function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })
            ->count();

        $filterable = [
            'search' => 'Search',
            'grade_level' => 'Grade',
            'gender' => 'Gender',
        ];
        $appliedFilters = collect($filterable)
            ->map(function ($label, $key) use ($request) {
                return ['key' => $key, 'label' => $label, 'value' => $request->get($key)];
            })
            ->filter(fn ($filter) => $filter['value'] !== null && $filter['value'] !== '')
            ->values();

        return view('partials.students-table', compact(
            'students',
            'appliedFilters',
            'actionsColClass',
            'nullSchoolYearCount'
        ))->render();
    }

    public function archived(Request $request)
    {
        $query = Student::query()->whereIn('status', ['Archived', 'archived'])
            ->where('archive_type', 'individual');
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

        if (Auth::user()->isAdminLevel()) {
            if ($gradeLevel = $request->get('grade_level')) {
                $query->where('grade_level', $gradeLevel);
            }
        }

        if ($gender = $request->get('gender')) {
            $query->where('gender', $gender);
        }

        $students = $query->orderBy('first_name')->orderBy('last_name')->orderBy('lrn')->paginate(10)->withQueryString();
        $gradeLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

        return view('pages.students-archived', compact('students', 'gradeLevels'));
    }

    public function store(Request $request)
    {
        $this->ensureCanManageStudents();

        $user = Auth::user();

        $rules = $this->studentProfileRules();
        $rules['lrn'] = 'required|string|max:50|unique:students,lrn';
        $rules['teacher_id'] = ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator)
                ->with(['open_modal' => 'add']);
        }

        $validated = $validator->validated();

        // A teacher is always assigned to themselves; they can never enroll a
        // student into another teacher's class. Super Admin may still pass an
        // optional teacher_id (backend only — the form has no teacher dropdown).
        $teacherId = null;
        if ($user->isTeacher()) {
            $teacherId = $user->id;
        } elseif ($user->isSuperAdmin() && !empty($validated['teacher_id'])) {
            $teacherId = (int) $validated['teacher_id'];
        }
        unset($validated['teacher_id']);

        $fullName = trim(
            $validated['first_name'] . ' ' .
            ($validated['middle_name'] ?? '') . ' ' .
            $validated['last_name']
        );

        $nextId = (Student::max('id') ?? 0) + 1;
        $payload = $this->studentProfilePayload($validated);

        // Student row and enrollment row are saved together so a failure never
        // leaves a half-created record behind.
        $student = DB::transaction(function () use ($validated, $payload, $fullName, $nextId, $teacherId, $user) {
            $student = Student::create($payload + [
                'student_id' => 'STU' . str_pad($nextId, 3, '0', STR_PAD_LEFT),
                'lrn' => $validated['lrn'],
                'full_name' => $fullName,
                'grade_level' => $validated['grade_level'],
                'gender' => $validated['gender'],
                'qr_code' => 'QR-' . $validated['lrn'],
                'total_points' => 0,
                'status' => 'Active',
                'teacher_id' => $teacherId,
            ]);

            if ($teacherId) {
                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'teacher_id' => $teacherId,
                    'grade_level' => $validated['grade_level'],
                    'section' => ($validated['section'] ?? '') !== '' ? $validated['section'] : null,
                    'school_year' => ($validated['school_year'] ?? '') !== '' ? $validated['school_year'] : null,
                    'status' => 'active',
                    'imported_by' => Auth::id(),
                ]);
            }

            return $student;
        });

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
        $this->ensureCanManageStudents();
        $this->authorizeStudentAccess($student);

        $user = Auth::user();
        $rules = $this->studentProfileRules();
        $rules['lrn'] = ['required', 'string', 'max:50', Rule::unique('students', 'lrn')->ignore($student->id)];

        if ($user->isAdminLevel()) {
            $rules['teacher_id'] = ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator)
                ->with([
                    'open_modal' => 'edit',
                    'open_modal_student_id' => $student->id,
                ]);
        }

        $validated = $validator->validated();

        // Only an explicit super admin submission may reassign the teacher;
        // teachers can never change a student's class assignment.
        $newTeacherId = null;
        $reassignTeacher = false;
        if ($user->isSuperAdmin() && array_key_exists('teacher_id', $validated)) {
            $reassignTeacher = true;
            $newTeacherId = !empty($validated['teacher_id']) ? (int) $validated['teacher_id'] : null;
        }
        unset($validated['teacher_id']);

        $payload = $this->studentProfilePayload($validated);
        $payload['full_name'] = trim(
            ($validated['first_name'] ?? $student->first_name) . ' ' .
            ($validated['middle_name'] ?? $student->middle_name) . ' ' .
            ($validated['last_name'] ?? $student->last_name)
        );

        // Status, teacher assignment, points, and QR code are intentionally
        // not part of $payload, so editing details never reactivates an
        // archived student or wipes existing system-managed values.
        DB::transaction(function () use ($student, $validated, $payload, $user, $reassignTeacher, $newTeacherId) {
            if ($reassignTeacher) {
                $payload['teacher_id'] = $newTeacherId;
            }

            $student->update($payload);

            if ($reassignTeacher && $newTeacherId !== null) {
                $this->syncEnrollment($student, $newTeacherId, $validated);
            } elseif ($user->isTeacher()) {
                $this->syncEnrollment($student, $user->id, $validated);
            } else {
                // Super Admin without a teacher change: keep the current
                // assignment in sync so section / school year stay current.
                $currentEnrollment = $student->enrollments()->latest('id')->first();
                if ($currentEnrollment) {
                    $this->syncEnrollment($student, (int) $currentEnrollment->teacher_id, $validated);
                }
            }
        });

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
        $this->ensureCanManageStudents();
        $this->authorizeStudentAccess($student);

        $student->update(['status' => 'Archived', 'archive_type' => 'individual']);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Archived Student',
            'description' => "Archived {$student->full_name}.",
            'module' => 'Students',
        ]);

        return redirect()->route('admin.students')->with('success', 'Student archived successfully!');
    }

    public function archiveAll(Request $request)
    {
        $this->ensureCanManageStudents();

        $user = Auth::user();

        $isPreview = $request->boolean('preview');

        if (!$isPreview) {
            $request->validate([
                'school_year' => 'required|string|max:20',
                'confirm' => 'required|boolean',
            ]);

            if (!$request->boolean('confirm')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must confirm the archive action.',
                ], 422);
            }
        } else {
            $request->validate([
                'school_year' => 'required|string|max:20',
            ]);
        }

        // Archive only the logged-in teacher's eligible enrollments for the selected year.
        $students = Student::query()
            ->whereNotIn('status', ['Archived', 'archived'])
            ->visibleTo($user)
            ->whereHas('enrollments', function ($q) use ($request) {
                $q->where('school_year', $request->school_year);
            })
            ->get();

        $studentCount = $students->count();
        $totalBottles = (int) $students->sum(function ($s) {
            return (int) $s->bottleCollections()->sum('bottle_count');
        });

        if ($studentCount === 0) {
            return response()->json([
                'success' => false,
                'message' => "No active students found for school year '{$request->school_year}'.",
            ], 422);
        }

        if ($isPreview) {
            return response()->json([
                'success' => true,
                'student_count' => $studentCount,
                'total_bottles' => $totalBottles,
            ]);
        }

        DB::transaction(function () use ($students, $request) {
            $batchId = 'arch_' . $request->school_year . '_' . uniqid();
            foreach ($students as $student) {
                $student->update([
                    'status' => 'Archived',
                    'archive_type' => 'school_year',
                    'archive_school_year' => $request->school_year,
                    'archive_batch_id' => $batchId,
                ]);

                AdminActivity::create([
                    'user_id' => Auth::id(),
                    'action' => 'Archived Student (School Year)',
                    "description" => "Archived {$student->full_name} for school year {$request->school_year}.",
                    'module' => 'Students',
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Archived {$studentCount} student(s) for school year {$request->school_year}.",
            'student_count' => $studentCount,
            'total_bottles' => $totalBottles,
        ]);
    }

    public function fixSchoolYears(Request $request)
    {
        $this->ensureCanManageStudents();
        $user = Auth::user();

        $request->validate([
            'school_year' => 'required|string|max:20|regex:/^\d{4}-\d{4}$/',
            'confirm' => 'required|boolean',
        ]);

        if (!$request->boolean('confirm')) {
            return response()->json([
                'success' => false,
                'message' => 'You must confirm the school year assignment.',
            ], 422);
        }

        $schoolYear = trim($request->input('school_year'));

        // Validate consecutive years: YYYY-YYYY where second = first + 1
        if (!preg_match('/^(\d{4})-(\d{4})$/', $schoolYear, $m)) {
            return response()->json([
                'success' => false,
                'message' => 'School year must use YYYY-YYYY format (e.g. 2026-2027).',
            ], 422);
        }

        $start = (int) $m[1];
        $end = (int) $m[2];

        if ($end !== $start + 1) {
            return response()->json([
                'success' => false,
                'message' => 'School year must be consecutive: the second year must equal the first year plus one (e.g. 2026-2027).',
            ], 422);
        }

        $enrollments = StudentEnrollment::whereNull('school_year')
            ->where('status', 'active')
            ->whereHas('student', function ($q) use ($user) {
                $q->visibleTo($user);
            })
            ->get();

        $count = $enrollments->count();

        if ($count === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No NULL school year enrollments found for your class.',
            ], 422);
        }

        DB::transaction(function () use ($enrollments, $schoolYear) {
            foreach ($enrollments as $enrollment) {
                $enrollment->update(['school_year' => $schoolYear]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Assigned school year '{$schoolYear}' to {$count} student(s).",
            'student_count' => $count,
        ]);
    }

    public function schoolYearArchives(Request $request)
    {
        $user = Auth::user();

        $query = Student::query()->whereIn('status', ['Archived', 'archived'])
            ->where('archive_type', 'school_year');
        $query->visibleTo($user);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lrn', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('archive_school_year', 'like', "%{$search}%");
            });
        }

        $schoolYearFilter = $request->get('school_year');
        $page = (int) $request->get('page', 1);

        if ($schoolYearFilter) {
            $query->where('archive_school_year', $schoolYearFilter);
            $students = $query->with(['enrollments.teacher'])->orderBy('first_name')->orderBy('last_name')->orderBy('lrn')->paginate(10, ['*'], 'page', $page);

            $groups = [];
            $syKey = $schoolYearFilter;
            $groups[$syKey] = [
                'school_year' => $syKey,
                'students' => [],
                'total_bottles' => 0,
                'total' => $students->total(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
            ];

            foreach ($students as $student) {
                $totalBottles = (int) $student->bottleCollections()->sum('bottle_count');
                $enrollment = $student->enrollments->first(function ($e) use ($schoolYearFilter) {
                    return $e->school_year === $schoolYearFilter;
                }) ?? $student->enrollments->first();
                $groups[$syKey]['students'][] = [
                    'id' => $student->id,
                    'lrn' => $student->lrn,
                    'full_name' => $student->full_name,
                    'grade_level' => $student->grade_level,
                    'total_points' => (int) ($student->total_points ?? 0),
                    'total_bottles' => $totalBottles,
                    'status' => $student->status,
                    'archive_school_year' => $student->archive_school_year,
                    'teacher_name' => $enrollment?->teacher?->name ?? '—',
                ];
                $groups[$syKey]['total_bottles'] += $totalBottles;
            }

            return response()->json([
                'success' => true,
                'groups' => array_values($groups),
                'pagination' => [
                    'school_year' => $syKey,
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'from' => $students->firstItem(),
                    'to' => $students->lastItem(),
                    'total' => $students->total(),
                ],
            ]);
        }

        $students = $query->with(['enrollments.teacher'])->orderBy('archive_school_year')->orderBy('first_name')->orderBy('last_name')->orderBy('lrn')->get();

        $groups = [];
        foreach ($students as $student) {
            $schoolYear = $student->archive_school_year ?? 'Unknown';
            if (!isset($groups[$schoolYear])) {
                $groups[$schoolYear] = [
                    'school_year' => $schoolYear,
                    'students' => [],
                    'total_bottles' => 0,
                ];
            }
            $totalBottles = (int) $student->bottleCollections()->sum('bottle_count');
            $enrollment = $student->enrollments->first(function ($e) use ($schoolYear) {
                return $e->school_year === $schoolYear;
            }) ?? $student->enrollments->first();
            $groups[$schoolYear]['students'][] = [
                'id' => $student->id,
                'lrn' => $student->lrn,
                'full_name' => $student->full_name,
                'grade_level' => $student->grade_level,
                'total_points' => (int) ($student->total_points ?? 0),
                'total_bottles' => $totalBottles,
                'status' => $student->status,
                'archive_school_year' => $student->archive_school_year,
                'teacher_name' => $enrollment?->teacher?->name ?? '—',
            ];
            $groups[$schoolYear]['total_bottles'] += $totalBottles;
        }

        ksort($groups);

        return response()->json([
            'success' => true,
            'groups' => array_values($groups),
        ]);
    }

    public function historical(Student $student)
    {
        $this->authorizeStudentAccess($student);

        $student->load([
            'bottleCollections',
            'enrollments.teacher',
            'earnedAchievements.quest',
            'certificateAwards.teacher',
            'claims',
        ]);

        $enrollment = $student->enrollments->sortByDesc('id')->first();
        $totalBottles = (int) $student->bottleCollections()->sum('bottle_count');
        $totalPointsEarned = (int) $student->bottleCollections()->sum('points_earned');
        $totalPointsRedeemed = (int) $student->claims()->where('status', 'approved')->sum('points_deducted');
        $availablePoints = max(0, (int) ($student->total_points ?? 0) - $totalPointsRedeemed);

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'lrn' => $student->lrn,
                'full_name' => $student->full_name,
                'grade_level' => $enrollment?->grade_level ?? $student->grade_level,
                'school_year' => $enrollment?->school_year ?? 'N/A',
                'teacher_name' => optional($enrollment?->teacher)->name ?? 'N/A',
                'status' => $student->status,
                'total_points' => (int) ($student->total_points ?? 0),
                'available_points' => $availablePoints,
            ],
            'summary' => [
                'total_bottles' => $totalBottles,
                'points_earned' => $totalPointsEarned,
                'points_redeemed' => $totalPointsRedeemed,
                'available_points' => $availablePoints,
                'collections_count' => $student->bottleCollections->count(),
                'achievements_count' => $student->earnedAchievements->count(),
                'certificates_count' => $student->certificateAwards->count(),
                'claims_count' => $student->claims->count(),
            ],
            'collections' => $student->bottleCollections->map(function ($c) {
                return [
                    'collection_date' => optional($c->collection_date)->format('M d, Y'),
                    'bottle_count' => (int) $c->bottle_count,
                    'points_earned' => (int) ($c->points_earned ?? 0),
                ];
            })->values(),
            'achievements' => $student->earnedAchievements->map(function ($a) {
                return [
                    'title' => $a->quest?->title ?? 'Achievement',
                    'badge_name' => $a->quest?->badge_name,
                    'awarded_date' => optional($a->awarded_date)->format('M d, Y'),
                ];
            })->values(),
            'certificates' => $student->certificateAwards->map(function ($a) {
                return [
                    'title' => $a->award_title,
                    'type' => $a->certificate_type,
                    'awarded_date' => optional($a->awarded_date)->format('M d, Y'),
                    'teacher_name' => optional($a->teacher)->name,
                ];
            })->values(),
            'claims' => $student->claims->map(function ($c) {
                return [
                    'item_name' => $c->item_name,
                    'quantity' => (int) $c->quantity,
                    'points_deducted' => (int) ($c->points_deducted ?? 0),
                    'claim_date' => optional($c->claim_date)->format('M d, Y'),
                    'status' => $c->status,
                ];
            })->values(),
        ]);
    }

    public function reEnroll(Request $request, Student $student)
    {
        $this->ensureCanManageStudents();
        $this->authorizeStudentAccess($student);

        $user = Auth::user();

        $request->validate([
            'new_school_year' => 'required|string|max:20',
            'new_grade_level' => 'required|in:Kindergarten,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6',
            'new_section' => 'nullable|string|max:100',
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')],
        ]);

        // Determine the teacher for re-enrollment
        $newTeacherId = null;
        if ($user->isTeacher()) {
            $newTeacherId = $user->id;
        } elseif ($user->isSuperAdmin()) {
            $newTeacherId = $request->get('teacher_id') ? (int) $request->get('teacher_id') : null;
        }

        if (!$newTeacherId) {
            return response()->json([
                'success' => false,
                'message' => 'A teacher must be assigned for re-enrollment.',
            ], 422);
        }

        $previousEnrollment = $student->enrollments()->latest('id')->first();

        DB::transaction(function () use ($student, $request, $newTeacherId, $previousEnrollment) {
            // Reactivate the student
            $student->update(['status' => 'Active', 'archive_type' => null, 'archive_school_year' => null]);

            // Create a new enrollment for the new school year (bottle totals start at zero)
            StudentEnrollment::create([
                'student_id' => $student->id,
                'teacher_id' => $newTeacherId,
                'grade_level' => $request->new_grade_level,
                'section' => $request->new_section ?? null,
                'school_year' => $request->new_school_year,
                'status' => 'active',
                'imported_by' => Auth::id(),
            ]);

            AdminActivity::create([
                'user_id' => Auth::id(),
                'action' => 'Re-enrolled Student',
                "description" => "Re-enrolled {$student->full_name} for school year {$request->new_school_year}.",
                'module' => 'Students',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "{$student->full_name} has been re-enrolled for {$request->new_school_year}.",
        ]);
    }

    public function restore(Student $student)
    {
        $this->ensureCanManageStudents();
        $this->authorizeStudentAccess($student);

        $student->update(['status' => 'Active', 'archive_type' => null, 'archive_school_year' => null]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Restored Student',
            'description' => "Restored {$student->full_name} to active students.",
            'module' => 'Students',
        ]);

        return redirect()->route('admin.students.archived')->with('success', 'Student restored successfully!');
    }

    public function moveToSchoolYear(Request $request, Student $student)
    {
        $this->ensureCanManageStudents();

        $user = Auth::user();

        // Teacher authorization: must have an enrollment (any status) with this teacher
        if ($user->isTeacher()) {
            $hasAccess = $student->enrollments()
                ->where('teacher_id', $user->id)
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this student record.'
                ], 403);
            }
        }

        // Validate student is archived
        if (!in_array($student->status, ['Archived', 'archived'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only archived students can be moved to School-Year Archives.'
            ], 422);
        }

        // Validate student is individually archived (not already in school-year group)
        if ($student->archive_type !== 'individual') {
            return response()->json([
                'success' => false,
                'message' => 'Only individually archived students can be moved to School-Year Archives.'
            ], 422);
        }

        // Determine the original school year.
        // 1. Use school_year from request (teacher correction workflow).
        // 2. Fall back to archive_school_year on the student.
        // 3. Fall back to verified school year from historical enrollments.
        $requestedYear = trim($request->input('school_year', ''));
        $schoolYear = $requestedYear;

        if ($schoolYear === '') {
            $schoolYear = trim($student->archive_school_year ?? '');
        }

        if ($schoolYear === '') {
            $enrollmentSchoolYear = $student->enrollments()
                ->whereNotNull('school_year')
                ->where('school_year', '!=', '')
                ->orderByDesc('id')
                ->value('school_year');

            if ($user->isTeacher() && empty($enrollmentSchoolYear)) {
                $teacherEnrollment = $student->enrollments()
                    ->where('teacher_id', $user->id)
                    ->whereNotNull('school_year')
                    ->where('school_year', '!=', '')
                    ->orderByDesc('id')
                    ->first();
                if ($teacherEnrollment) {
                    $enrollmentSchoolYear = $teacherEnrollment->school_year;
                }
            }

            if (!empty($enrollmentSchoolYear)) {
                $schoolYear = $enrollmentSchoolYear;
                // Persist the verified year on the archived record.
                $student->update(['archive_school_year' => $schoolYear]);
            }
        }

        // If a school year was requested (correction workflow), validate format
        // and update only the affected archived enrollment's school_year.
        if ($requestedYear !== '') {
            if (!preg_match('/^\d{4}-\d{4}$/', $requestedYear)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid school year format. Use YYYY-YYYY (e.g. 2026-2027).',
                ], 422);
            }

            $schoolYear = $requestedYear;

            // Update only the teacher's enrollment school_year (correction).
            if ($user->isTeacher()) {
                $student->enrollments()
                    ->where('teacher_id', $user->id)
                    ->update(['school_year' => $schoolYear]);
            }

            // Persist on the archived record.
            $student->update(['archive_school_year' => $schoolYear]);

            AdminActivity::create([
                'user_id' => Auth::id(),
                'action' => 'Corrected School Year',
                "description" => "Corrected school year for {$student->full_name} to {$schoolYear}.",
                'module' => 'Students',
            ]);
        }

        if ($schoolYear === '') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot move: this student has no original school year recorded. Please specify the original school year to proceed.',
                'requires_school_year' => true,
            ], 422);
        }

        // Move: change archive_type from individual to school_year.
        // The student remains archived; archive_school_year, grade_level,
        // teacher_id, and all historical records are unchanged.
        $student->update(['archive_type' => 'school_year']);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Moved to School-Year Archives',
            "description" => "Moved {$student->full_name} to School-Year Archives (SY {$schoolYear}).",
            'module' => 'Students',
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$student->full_name} moved to School-Year Archives (SY {$schoolYear}).",
        ]);
    }

    public function unarchive(Request $request, Student $student)
    {
        $this->ensureCanManageStudents();

        $user = Auth::user();

        // Teacher authorization: must have an enrollment (any status) with this teacher
        if ($user->isTeacher()) {
            $hasAccess = $student->enrollments()
                ->where('teacher_id', $user->id)
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this student record.'
                ], 403);
            }
        }

        $archiveSchoolYear = $student->archive_school_year;

        // Check for conflicting active enrollment in a newer school year
        if ($archiveSchoolYear) {
            $newerActiveEnrollment = $student->enrollments()
                ->where('status', 'active')
                ->where('school_year', '>', $archiveSchoolYear)
                ->first();

            if ($newerActiveEnrollment) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot unarchive: {$student->full_name} already has an active enrollment for school year {$newerActiveEnrollment->school_year}."
                ], 422);
            }
        }

        $student->update([
            'status' => 'Active',
            'archive_type' => null,
            'archive_school_year' => null,
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Unarchived Student',
            "description" => "Unarchived {$student->full_name} for school year {$archiveSchoolYear}.",
            'module' => 'Students',
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$student->full_name} has been unarchived and restored to active students.",
            'student_id' => $student->id,
        ]);
    }

    public function unarchiveAll(Request $request)
    {
        $this->ensureCanManageStudents();

        $user = Auth::user();

        $request->validate([
            'school_year' => 'required|string|max:20',
        ]);

        $schoolYear = $request->school_year;

        // Find all students archived via Archive All for this school year and teacher.
        // archive_batch_id is server-generated and not required from the client;
        // school_year + archive_type + teacher ownership is sufficient for identification.
        $query = Student::query()
            ->whereIn('status', ['Archived', 'archived'])
            ->where('archive_type', 'school_year')
            ->where('archive_school_year', $schoolYear)
            ->visibleTo($user);

        $students = $query->get();

        if ($students->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => "No archived students found for school year '{$schoolYear}'.",
            ], 422);
        }

        // Check for conflicting active enrollments in newer school years
        $conflicts = [];
        foreach ($students as $student) {
            $newerActive = $student->enrollments()
                ->where('status', 'active')
                ->where('school_year', '>', $schoolYear)
                ->first();

            if ($newerActive) {
                $conflicts[] = [
                    'student_id' => $student->id,
                    'name' => $student->full_name,
                    'lrn' => $student->lrn,
                    'newer_school_year' => $newerActive->school_year,
                ];
            }
        }

        if (count($conflicts) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot unarchive: some students already have active enrollments in newer school years.',
                'conflicts' => $conflicts,
                'conflict_count' => count($conflicts),
            ], 422);
        }

        $restoredCount = 0;
        DB::transaction(function () use ($students, $schoolYear, &$restoredCount) {
            foreach ($students as $student) {
                $student->update([
                    'status' => 'Active',
                    'archive_type' => null,
                    'archive_school_year' => null,
                    'archive_batch_id' => null,
                ]);

                AdminActivity::create([
                    'user_id' => Auth::id(),
                    'action' => 'Unarchived Student (Bulk)',
                    "description" => "Unarchived {$student->full_name} from school year {$schoolYear} via bulk restore.",
                    'module' => 'Students',
                ]);

                $restoredCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Restored {$restoredCount} student(s) from school year {$schoolYear} to active students.",
            'restored_count' => $restoredCount,
            'school_year' => $schoolYear,
        ]);
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

        $quests = $student->assignedActiveQuests()
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
        $this->ensureCanManageStudents();
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

    public function overview(Student $student)
    {
        $this->authorizeStudentAccess($student);

        $user = Auth::user();

        $currentEnrollment = $student->enrollments()
            ->when($user->isTeacher(), fn ($query) => $query->where('teacher_id', $user->id))
            ->latest()
            ->first();

        $totalBottles = (int) $student->bottleCollections()->sum('bottle_count');
        $totalPoints = (int) ($student->total_points ?? 0);
        $pointsEarnedSum = (int) $student->bottleCollections()->sum('points_earned');
        $collectionsTotal = $student->bottleCollections()->count();

        $collectionsQuery = $student->bottleCollections()
            ->with('recorder:id,name')
            ->orderByDesc('collection_date')
            ->orderByDesc('id');

        $recentCollections = (clone $collectionsQuery)->limit(5)->get();
        $collections = (clone $collectionsQuery)->limit(30)->get();

        $earnedAchievements = $student->earnedAchievements()
            ->with('quest')
            ->orderByDesc('awarded_date')
            ->orderByDesc('id')
            ->get();

        $earnedQuestIds = $earnedAchievements->pluck('achievement_quest_id')->all();

        $questProgress = $student->assignedActiveQuests()
            ->get()
            ->filter(fn ($quest) => $quest->required_bottles > 0 || $quest->points_required > 0)
            ->map(function ($quest) use ($totalBottles, $totalPoints, $earnedQuestIds) {
                $usesBottles = $quest->required_bottles > 0;
                $required = $usesBottles ? (int) $quest->required_bottles : (int) $quest->points_required;
                $current = $usesBottles ? $totalBottles : $totalPoints;

                $isCompleted = in_array($quest->id, $earnedQuestIds)
                    || ($quest->required_bottles > 0 && $totalBottles >= $quest->required_bottles)
                    || ($quest->points_required > 0 && $totalPoints >= $quest->points_required);

                return [
                    'title' => $quest->title,
                    'description' => $quest->description,
                    'badge_name' => $quest->badge_name,
                    'metric' => $usesBottles ? 'bottles' : 'points',
                    'required' => $required,
                    'current' => $current,
                    'progress' => $required > 0 ? (int) min(100, round(($current / $required) * 100)) : 0,
                    'completed' => $isCompleted,
                ];
            })
            ->sortBy('required')
            ->values();

        $nextQuest = $questProgress->firstWhere('completed', false);
        $achievementProgress = [
            'current_bottles' => $totalBottles,
            'current_points' => $totalPoints,
            'next_goal' => $nextQuest ? [
                'title' => $nextQuest['title'],
                'metric' => $nextQuest['metric'],
                'required' => $nextQuest['required'],
                'current' => $nextQuest['current'],
                'remaining' => max(0, $nextQuest['required'] - $nextQuest['current']),
                'progress' => $nextQuest['progress'],
            ] : null,
            'remaining' => $nextQuest ? max(0, $nextQuest['required'] - $nextQuest['current']) : 0,
            'progress' => $nextQuest['progress'] ?? 0,
            'quests_total' => count($questProgress),
        ];

        $awardsQuery = $student->certificateAwards()
            ->with('teacher:id,name')
            ->orderByDesc('awarded_date')
            ->orderByDesc('id');

        $awards = (clone $awardsQuery)->limit(30)->get();
        $certificateCount = (clone $awardsQuery)->count();

        $mapCollection = fn ($collection) => [
            'id' => $collection->id,
            'collection_date' => optional($collection->collection_date)->format('M d, Y'),
            'collection_time' => $collection->collection_time,
            'bottle_count' => (int) $collection->bottle_count,
            'points_earned' => (int) $collection->points_earned,
            'recorded_by' => $collection->recorder?->name ?? 'System / IoT',
            // bottle_collections has no status column; "Recorded" is a derived display label.
            'status' => 'Recorded',
        ];

        $recentActivity = collect()
            ->merge($recentCollections->map(fn ($collection) => [
                'type' => 'collection',
                'label' => 'Bottle collection',
                'detail' => $collection->bottle_count . ' bottles • ' . $collection->points_earned . ' points',
                'date' => optional($collection->collection_date)->format('M d, Y'),
                'sort' => optional($collection->collection_date)->getTimestamp(),
            ]))
            ->merge($earnedAchievements->take(5)->map(fn ($record) => [
                'type' => 'achievement',
                'label' => $record->quest?->title ?? 'Achievement earned',
                'detail' => $record->quest?->badge_name,
                'date' => optional($record->awarded_date)->format('M d, Y'),
                'sort' => optional($record->awarded_date)->getTimestamp(),
            ]))
            ->merge($awards->take(5)->map(fn ($award) => [
                'type' => 'certificate',
                'label' => $award->award_title ?? 'Certificate issued',
                'detail' => $award->certificate_type,
                'date' => optional($award->awarded_date)->format('M d, Y'),
                'sort' => optional($award->awarded_date)->getTimestamp(),
            ]))
            ->filter(fn ($item) => $item['date'] !== null)
            ->sortByDesc('sort')
            ->take(6)
            ->map(fn ($item) => collect($item)->except('sort')->all())
            ->values();

        $initials = collect([$student->first_name, $student->last_name])
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr(trim($part), 0, 1)))
            ->implode('');

        return response()->json([
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'initials' => $initials !== '' ? $initials : 'ST',
                'lrn' => $student->lrn,
                'student_id' => $student->student_id,
                'grade_level' => $student->grade_level,
                'gender' => $student->gender,
                'status' => $student->status,
                'qr_code' => $student->qr_code,
                'birth_date' => optional($student->birth_date)->format('M d, Y'),
                'age' => $student->age,
                'mother_tongue' => $student->mother_tongue,
                'ip_ethnic_group' => $student->ip_ethnic_group,
                'religion' => $student->religion,
                'house_street' => $student->house_street,
                'barangay' => $student->barangay,
                'municipality_city' => $student->municipality_city,
                'province' => $student->province,
                'region' => $student->region,
                'father_name' => $student->father_name,
                'mother_maiden_name' => $student->mother_maiden_name,
                'guardian_name' => $student->guardian_name,
                'guardian_relationship' => $student->guardian_relationship,
                'contact_number' => $student->contact_number,
                'learning_modality' => $student->learning_modality,
                'nationality' => $student->nationality,
                'remarks' => $student->remarks,
                'section' => $currentEnrollment?->section,
                'school_year' => $currentEnrollment?->school_year,
                'teacher_name' => $currentEnrollment?->teacher?->name,
                'is_archived' => in_array($student->status, ['Archived', 'archived']),
                'details_url' => route('students.info', $student),
            ],
            'summary' => [
                'total_bottles' => $totalBottles,
                'total_points' => $totalPoints,
                'points_earned' => $pointsEarnedSum,
                'collections_count' => $collectionsTotal,
                'achievements' => $earnedAchievements->count(),
                'certificates' => $certificateCount,
            ],
            'recent_activity' => $recentActivity,
            'achievement_progress' => $achievementProgress,
            'recent_collections' => $recentCollections->map($mapCollection)->values(),
            'collections' => $collections->map($mapCollection)->values(),
            'achievements' => [
                'earned' => $earnedAchievements->map(fn ($record) => [
                    'id' => $record->id,
                    'title' => $record->quest?->title ?? 'Achievement',
                    'description' => $record->quest?->description,
                    'badge_name' => $record->quest?->badge_name,
                    'required_bottles' => (int) ($record->quest?->required_bottles ?? 0),
                    'points_required' => (int) ($record->quest?->points_required ?? 0),
                    'awarded_date' => optional($record->awarded_date)->format('M d, Y'),
                ])->values(),
                'progress' => $questProgress,
            ],
            'certificates' => $awards->map(function ($award) use ($user, $student) {
                $canAccess = ! $user->isTeacher()
                    || (int) $award->teacher_id === (int) $user->id
                    || $student->enrollments()
                        ->where('teacher_id', $user->id)
                        ->where('status', 'active')
                        ->exists();
                $hasFinal = $award->hasFinalCertificateFile();

                return [
                    'id' => $award->id,
                    'title' => $award->award_title,
                    'description' => $award->award_description,
                    'type' => $award->certificate_type,
                    'school_year' => $award->school_year,
                    'status' => $award->status,
                    'awarded_date' => optional($award->awarded_date)->format('M d, Y'),
                    'teacher_name' => $award->teacher?->name,
                    'view_url' => $canAccess && $hasFinal ? route('admin.certificate.download', $award) : null,
                    'print_url' => $canAccess && $award->canRenderCertificate() ? route('admin.certificate.print', $award) : null,
                ];
            })->values(),
            'meta' => [
                'collections_returned' => $collections->count(),
                'collections_total' => $collectionsTotal,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function import(Request $request)
    {
        $this->ensureCanManageStudents();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'grade_level' => 'required|string|max:20|in:Kindergarten,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->storeAs('imports', 'students_' . time() . '.' . $file->extension());

            $importer = new StudentsImport();

            // Detect SF1 grade conflict before importing
            $detectedGrade = $importer->detectSf1Grade(Storage::path($path));
            if ($detectedGrade && $detectedGrade !== $request->grade_level) {
                Storage::delete($path);
                return redirect()->route('admin.students')->with('import_result', [
                    'type' => 'error',
                    'message' => "Grade level mismatch: The SF1 file contains '{$detectedGrade}' but you selected '{$request->grade_level}'. Please select the correct grade level and try again.",
                    'errors' => [],
                ]);
            }

            DB::transaction(function () use ($importer, $path, $request) {
                $importer->import(Storage::path($path), Auth::user(), $request->grade_level);
            });

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
        $headers = [
            'LRN',
            'NAME (Last Name, First Name, Middle Name)',
            'SEX (M/F)',
            'BIRTH DATE (mm-dd-yyyy)',
            'AGE as of First Friday of June',
            'MOTHER TONGUE (Grade 1 to 3 only)',
            'IP (Ethnic Group)',
            'RELIGION',
            'ADDRESS - House # / Street / Sitio / Purok',
            'ADDRESS - Barangay',
            'ADDRESS - Municipality / City',
            'ADDRESS - Province',
            "FATHER'S NAME (Last, First, Middle)",
            "MOTHER'S MAIDEN NAME (Last, First, Middle)",
            'GUARDIAN NAME',
            'RELATIONSHIP',
            'CONTACT NUMBER OF PARENT / GUARDIAN',
            'LEARNING MODALITY',
            'REMARKS',
            'GRADE LEVEL',
            'SCHOOL YEAR',
            'SCHOOL NAME',
            'SCHOOL ID',
            'REGION',
            'DIVISION',
            'DISTRICT',
            'SECTION',
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'eco_collect_template_');
        if ($tempFile === false) {
            return back()->withErrors(['Failed to create temporary file for template.']);
        }

        $writer = new XLSXWriter();
        $writer->openToFile($tempFile);

        // Header style: bold, centered, white text on dark blue background, wrapped, bordered
        $headerStyle = new Style();
        $headerStyle->setFontBold();
        $headerStyle->setFontColor(Color::WHITE);
        $headerStyle->setCellAlignment(CellAlignment::CENTER);
        $headerStyle->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
        $headerStyle->setShouldWrapText();
        $headerStyle->setBackgroundColor(Color::toARGB(Color::DARK_BLUE));
        $headerStyle->setBorder(new Border(
            new BorderPart(Border::TOP, Color::toARGB(Color::BLACK), Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::BOTTOM, Color::toARGB(Color::BLACK), Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::LEFT, Color::toARGB(Color::BLACK), Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::RIGHT, Color::toARGB(Color::BLACK), Border::WIDTH_THIN, Border::STYLE_SOLID),
        ));

        // Build per-column header styles (LRN column gets text format hint)
        $columnStyles = [];
        foreach ($headers as $colIndex => $header) {
            $colStyle = clone $headerStyle;
            if ($colIndex === 0) {
                // LRN column header - also marked as text format
                $colStyle->setFormat('@');
            }
            $columnStyles[$colIndex] = $colStyle;
        }

        $writer->addRow(Row::fromValuesWithStyles($headers, null, $columnStyles));

        // Set column widths for readability
        $sheet = $writer->getCurrentSheet();
        $columnWidths = [
            0  => 15,   // LRN
            1  => 42,   // NAME
            2  => 10,   // SEX
            3  => 18,   // BIRTH DATE
            4  => 12,   // AGE
            5  => 20,   // MOTHER TONGUE
            6  => 20,   // IP
            7  => 15,   // RELIGION
            8  => 30,   // ADDRESS House
            9  => 20,   // ADDRESS Barangay
            10 => 22,   // ADDRESS Municipality
            11 => 20,   // ADDRESS Province
            12 => 36,   // FATHER'S NAME
            13 => 36,   // MOTHER'S MAIDEN
            14 => 28,   // GUARDIAN NAME
            15 => 18,   // RELATIONSHIP
            16 => 20,   // CONTACT NUMBER
            17 => 20,   // LEARNING MODALITY
            18 => 22,   // REMARKS
            19 => 12,   // GRADE LEVEL
            20 => 16,   // SCHOOL YEAR
            21 => 34,   // SCHOOL NAME
            22 => 14,   // SCHOOL ID
            23 => 14,   // REGION
            24 => 18,   // DIVISION
            25 => 18,   // DISTRICT
            26 => 14,   // SECTION
        ];
        foreach ($columnWidths as $colIndex => $width) {
            $sheet->setColumnWidthForRange($width, $colIndex + 1, $colIndex + 1);
        }

        // Freeze the header row
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));

        $writer->close();

        return response()->download(
            $tempFile,
            'EcoCollect_SF1_Student_Import_Template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }
}
