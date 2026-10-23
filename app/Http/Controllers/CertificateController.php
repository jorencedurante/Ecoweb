<?php

namespace App\Http\Controllers;

use App\Models\CertificateAward;
use App\Models\CertificateTemplate;
use App\Models\Achievement;
use App\Models\Student;
use App\Models\User;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $quests = Achievement::whereNull('student_id')
            ->with('creator')
            ->latest()
            ->get();

        $teachers = User::whereIn('role', ['teacher', 'admin', 'super_admin'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'position']);

        return view('pages.certificate', compact('quests', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'certificate_title' => 'required|string|max:255',
            'award_description' => 'nullable|string',
            'awarded_date' => 'required|date',
            'certificate_file' => 'required|file|mimes:pdf,jpg,jpeg,png,docx|max:10240',
        ]);

        if ($request->filled('student_id')) {
            $studentQuery = Student::query();
            if (Auth::user()->isTeacher()) {
                $studentQuery->whereHas('enrollments', function ($q) {
                    $q->where('teacher_id', Auth::id());
                });
            }
            $studentQuery->findOrFail($validated['student_id']);
        }

        $filePath = $request->file('certificate_file')->store('certificates', 'public');

        $award = CertificateAward::create([
            'student_id' => $validated['student_id'] ?? null,
            'teacher_id' => Auth::user()->isTeacher() ? Auth::id() : null,
            'award_title' => $validated['certificate_title'],
            'award_description' => $validated['award_description'] ?? null,
            'awarded_date' => $validated['awarded_date'],
            'certificate_file' => $filePath,
            'status' => 'Active',
            'issued_by' => Auth::id(),
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Uploaded Certificate',
            'description' => "Uploaded certificate {$award->award_title}.",
            'module' => 'Certificate',
        ]);

        return redirect()->route('admin.certificate')
            ->with('success', 'Certificate uploaded successfully!');
    }

    public function create()
    {
        return redirect()->route('admin.certificate');
    }

    public function saveCanvas(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'adviser_id' => 'nullable|exists:users,id',
            'award_title' => 'nullable|string|max:255',
            'award_description' => 'nullable|string',
            'template_file' => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'canvas_data' => 'nullable|string',
        ]);

        if ($request->filled('student_id')) {
            $studentQuery = Student::query();
            if (Auth::user()->isTeacher()) {
                $studentQuery->whereHas('enrollments', function ($q) {
                    $q->where('teacher_id', Auth::id());
                });
            }
            $studentQuery->findOrFail($validated['student_id']);
        }

        $adviserName = null;
        if ($request->filled('adviser_id')) {
            $adviserName = User::whereKey($validated['adviser_id'])->value('name');
        }

        $templatePath = $request->file('template_file')->store('certificate-templates', 'public');

        $award = CertificateAward::create([
            'student_id' => $validated['student_id'] ?? null,
            'teacher_id' => Auth::user()->isTeacher() ? Auth::id() : null,
            'issued_by' => Auth::id(),
            'award_title' => $validated['award_title'] ?: 'Certificate',
            'award_description' => $validated['award_description'] ?? null,
            'awarded_date' => now()->toDateString(),
            'template_file' => $templatePath,
            'canvas_data' => $validated['canvas_data'] ? json_decode($validated['canvas_data'], true) : null,
            'signed_by' => $adviserName,
            'status' => 'Active',
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Designed Certificate',
            'description' => "Designed certificate {$award->award_title}.",
            'module' => 'Certificate',
        ]);

        return redirect()->route('admin.certificate')
            ->with('success', 'Certificate saved successfully!');
    }

    public function edit(CertificateAward $award)
    {
        $user = Auth::user();
        if ($user->isTeacher() && $award->teacher_id !== $user->id) {
            abort(403);
        }

        $templates = CertificateTemplate::where('status', 'active')
            ->where(function ($q) use ($user) {
                if ($user->isTeacher()) {
                    $q->where('visibility', 'global')->orWhere('uploaded_by', Auth::id());
                }
            })
            ->orderBy('template_name')
            ->get();

        return view('pages.certificate-editor', compact('templates', 'award'));
    }

    public function updateCanvas(Request $request, CertificateAward $award)
    {
        $user = Auth::user();
        if ($user->isTeacher() && $award->teacher_id !== $user->id) {
            abort(403);
        }

        $rules = [
            'student_id' => 'nullable|exists:students,id',
            'award_title' => 'nullable|string|max:255',
            'award_description' => 'nullable|string',
            'canvas_data' => 'nullable|string',
        ];

        if ($request->hasFile('template_file')) {
            $rules['template_file'] = 'file|mimes:jpg,jpeg,png|max:10240';
        }

        $validated = $request->validate($rules);

        $data = [
            'award_title' => $validated['award_title'] ?? $award->award_title,
            'award_description' => $validated['award_description'] ?? $award->award_description,
            'canvas_data' => $validated['canvas_data'] !== null ? json_decode($validated['canvas_data'], true) : $award->canvas_data,
        ];

        if ($request->filled('student_id')) {
            $studentQuery = Student::query();
            if (Auth::user()->isTeacher()) {
                $studentQuery->whereHas('enrollments', function ($q) {
                    $q->where('teacher_id', Auth::id());
                });
            }
            $studentQuery->findOrFail($validated['student_id']);
            $data['student_id'] = $validated['student_id'];
        }

        if ($request->hasFile('template_file')) {
            $data['template_file'] = $request->file('template_file')->store('certificate-templates', 'public');
        }

        $award->update($data);

        return redirect()->route('admin.certificate')
            ->with('success', 'Certificate updated successfully!');
    }

    public function templatesList()
    {
        $user = Auth::user();
        $templates = CertificateTemplate::where('status', 'active')
            ->where(function ($q) use ($user) {
                if ($user->isTeacher()) {
                    $q->where('visibility', 'global')->orWhere('uploaded_by', Auth::id());
                }
            })
            ->orderBy('template_name')
            ->get(['id', 'template_name', 'file_path', 'file_type']);

        return response()->json($templates);
    }

    public function download(CertificateAward $award)
    {
        $user = Auth::user();
        if ($user->isTeacher() && $award->teacher_id !== $user->id) {
            abort(403);
        }

        $filePath = $award->template_file ?? $award->certificate_file ?? $award->template_file_path;
        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            abort(404, 'Certificate file not found.');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $filename = Str::slug($award->award_title ?? 'certificate') . '.' . $extension;

        return Storage::disk('public')->download($filePath, $filename);
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_file' => 'required|file|mimes:jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('template_file');
        $path = $file->store('certificate-templates', 'public');

        $template = CertificateTemplate::create([
            'template_name' => $request->title,
            'description' => $request->description,
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'status' => 'active',
            'visibility' => Auth::user()->isAdminLevel() ? 'global' : 'private',
            'template_type' => 'uploaded',
            'uploaded_by' => Auth::id(),
            'created_by' => Auth::id(),
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Uploaded Template',
            'description' => "Uploaded certificate template: {$request->title}.",
            'module' => 'Certificate',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $template->id,
                'template_name' => $template->template_name,
                'file_path' => $template->file_path,
                'message' => 'Template uploaded successfully!',
            ]);
        }

        return redirect()->route('admin.certificate')
            ->with('success', 'Certificate template uploaded successfully.');
    }

    public function print(CertificateAward $award)
    {
        $award->load('issuer', 'teacher');
        return view('pages.certificate-print', compact('award'));
    }

    public function archive(CertificateAward $award)
    {
        $user = Auth::user();
        if ($user->isTeacher() && $award->teacher_id !== $user->id) {
            abort(403);
        }
        $award->update(['status' => 'Archived']);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Archived Certificate',
            'description' => "Archived certificate {$award->award_title}.",
            'module' => 'Certificate',
        ]);

        return redirect()->route('admin.certificate')
            ->with('success', 'Certificate archived successfully.');
    }

    public function destroy(CertificateAward $award)
    {
        if (!Auth::user()->isAdminLevel()) {
            abort(403);
        }

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Deleted Certificate',
            'description' => "Deleted certificate {$award->award_title}.",
            'module' => 'Certificate',
        ]);

        $award->delete();

        return redirect()->route('admin.certificate')
            ->with('success', 'Certificate deleted successfully.');
    }
}
