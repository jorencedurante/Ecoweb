<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Achievement;
use App\Models\StudentAchievement;
use App\Models\CertificateAward;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query()->where('status', '!=', 'Archived');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('lrn', 'like', "%{$search}%")
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

        $students = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $gradeLevels = Student::where('status', '!=', 'Archived')->select('grade_level')->distinct()->pluck('grade_level');

        return view('pages.students', compact('students', 'gradeLevels'));
    }

    public function archived(Request $request)
    {
        $query = Student::query()->where('status', 'Archived');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('lrn', 'like', "%{$search}%")
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

        $students = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('pages.students-archived', compact('students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lrn' => 'required|unique:students,lrn',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'grade_level' => 'required|string|max:50',
            'gender' => 'required|in:Male,Female',
        ]);

        $fullName = trim(
            $validated['first_name'] . ' ' .
            ($validated['middle_name'] ?? '') . ' ' .
            $validated['last_name']
        );

        $nextId = (Student::max('id') ?? 0) + 1;

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
        $validated = $request->validate([
            'lrn' => ['required', Rule::unique('students', 'lrn')->ignore($student->id)],
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'grade_level' => 'required|string|max:50',
            'gender' => 'required|in:Male,Female',
        ]);

        $student->update($validated);

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
        $student->load('bottleCollections');
        return view('pages.student-info', compact('student'));
    }

    public function achievements(Student $student)
    {
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

    public function awards(Student $student)
    {
        $awards = CertificateAward::where('student_id', $student->id)->get();
        return view('pages.student-awards', compact('student', 'awards'));
    }
}
