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
            'school_year' => 'nullable|string|max:20',
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

        $students = $query->with('enrollments')->latest()->paginate(10)->withQueryString();
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

        if (Auth::user()->isAdminLevel()) {
            if ($gradeLevel = $request->get('grade_level')) {
                $query->where('grade_level', $gradeLevel);
            }
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
        $this->ensureCanManageStudents();
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
                'teacher_name' => $student->teacher?->name,
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
