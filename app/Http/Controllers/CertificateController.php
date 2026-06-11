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
    public function index()
    {
        $awards = CertificateAward::with('student', 'issuer')->latest()->get();
        $students = Student::where('status', '!=', 'Archived')->get();
        $latestAward = CertificateAward::with('student', 'issuer')->latest()->first();
        return view('pages.certificate', compact('awards', 'students', 'latestAward'));
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
        ]);

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
