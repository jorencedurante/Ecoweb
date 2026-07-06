<?php

namespace App\Http\Controllers;

use App\Models\CertificateAward;
use App\Models\Student;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = CertificateAward::with(['student', 'issuer']);

        if (Auth::user()->isTeacher()) {
            $query->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_title', 'like', "%{$search}%")
                  ->orWhere('award_title', 'like', "%{$search}%")
                  ->orWhere('award_description', 'like', "%{$search}%")
                  ->orWhere('awarded_by', 'like', "%{$search}%")
                  ->orWhere('school_principal_name', 'like', "%{$search}%")
                  ->orWhere('program_coordinator_name', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($studentQuery) use ($search) {
                      $studentQuery->where('full_name', 'like', "%{$search}%")
                                   ->orWhere('lrn', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('certificate_type')) {
            $query->where('certificate_type', $request->certificate_type);
        }

        if ($request->filled('award_date')) {
            $query->whereDate('awarded_date', $request->award_date);
        }

        if ($request->filled('month')) {
            $monthNames = [
                'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
                'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
                'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
            ];
            $monthNum = is_numeric($request->month) ? $request->month : ($monthNames[$request->month] ?? null);
            if ($monthNum) {
                $query->whereMonth('awarded_date', $monthNum);
            }
        }

        if ($request->filled('year')) {
            $query->whereYear('awarded_date', $request->year);
        }

        $awards = $query->latest()->paginate(10)->withQueryString();
        $students = Student::query()->whereNotIn('status', ['Archived', 'archived']);
        if (Auth::user()->isTeacher()) {
            $students->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }
        $students = $students->get();
        $latestAward = CertificateAward::with('student', 'issuer')->latest()->first();
        $certificateTypes = CertificateAward::select('certificate_type')->distinct()->whereNotNull('certificate_type')->pluck('certificate_type');

        return view('pages.certificate', compact('awards', 'students', 'latestAward', 'certificateTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'certificate_type' => 'nullable|string|max:255',
            'certificate_title' => 'required|string|max:255',
            'award_title' => 'required|string|max:255',
            'award_description' => 'nullable|string',
            'school_principal_name' => 'nullable|string|max:255',
            'program_coordinator_name' => 'nullable|string|max:255',
            'awarded_by' => 'nullable|string|max:255',
            'awarded_date' => 'required|date',
            'template_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'show_logo' => 'nullable|boolean',
            'show_certificate_title' => 'nullable|boolean',
            'show_student_name' => 'nullable|boolean',
            'show_award_description' => 'nullable|boolean',
            'show_award_date' => 'nullable|boolean',
            'show_principal_name' => 'nullable|boolean',
            'show_program_coordinator_name' => 'nullable|boolean',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        if (Auth::user()->isTeacher() && $student->teacher_id !== Auth::id()) {
            abort(403, 'You cannot create awards for students assigned to another teacher.');
        }

        $templatePath = null;

        if ($request->hasFile('template_file')) {
            $templatePath = $request->file('template_file')
                ->store('certificate_templates', 'public');
        }

        $award = CertificateAward::create([
            'student_id' => $validated['student_id'],
            'certificate_type' => $validated['certificate_type'] ?? null,
            'certificate_title' => $validated['certificate_title'],
            'award_title' => $validated['award_title'],
            'award_description' => $validated['award_description'] ?? null,
            'school_principal_name' => $validated['school_principal_name'] ?? null,
            'program_coordinator_name' => $validated['program_coordinator_name'] ?? null,
            'awarded_by' => $validated['awarded_by'] ?? null,
            'awarded_date' => $validated['awarded_date'],
            'template_file_path' => $templatePath,
            'status' => 'Active',
            'issued_by' => Auth::id(),
            'show_logo' => $request->boolean('show_logo'),
            'show_certificate_title' => $request->boolean('show_certificate_title'),
            'show_student_name' => $request->boolean('show_student_name', true),
            'show_award_description' => $request->boolean('show_award_description'),
            'show_award_date' => $request->boolean('show_award_date'),
            'show_principal_name' => $request->boolean('show_principal_name'),
            'show_program_coordinator_name' => $request->boolean('show_program_coordinator_name'),
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Added Award',
            'description' => "Awarded {$award->award_title} to {$award->student->full_name}.",
            'module' => 'Certificate',
        ]);

        return redirect()->route('admin.certificate')->with('success', 'Certificate awarded successfully!');
    }

    public function print(CertificateAward $award)
    {
        $award->load('student', 'issuer');
        return view('pages.certificate-print', compact('award'));
    }
}
