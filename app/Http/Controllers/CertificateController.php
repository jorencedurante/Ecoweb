<?php

namespace App\Http\Controllers;

use App\Models\CertificateAward;
use App\Models\CertificateTemplate;
use App\Models\Achievement;
use App\Models\Student;
use App\Models\User;
use App\Models\AdminActivity;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        // Teachers manage their own quests; admins/super admins see all
        // quests read-only for monitoring.
        $quests = Achievement::whereNull('student_id')
            ->when(Auth::user()->isTeacher(), fn ($query) => $query->where('created_by', Auth::id()))
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
            'award_title' => ($validated['award_title'] ?? null) ?: 'Certificate',
            'award_description' => $validated['award_description'] ?? null,
            'awarded_date' => now()->toDateString(),
            'template_file' => $templatePath,
            'canvas_data' => !empty($validated['canvas_data']) ? json_decode($validated['canvas_data'], true) : null,
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

    public function award(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'adviser_id' => 'required|exists:users,id',
            'template_file' => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'generated_file' => 'required|file|mimes:jpg,jpeg,png|max:15360',
            'canvas_data' => 'required|string|max:200000',
        ]);

        $studentQuery = Student::query();
        if (Auth::user()->isTeacher()) {
            $studentQuery->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }
        $student = $studentQuery->findOrFail($validated['student_id']);

        $adviser = User::whereKey($validated['adviser_id'])
            ->whereIn('role', ['teacher', 'admin', 'super_admin'])
            ->where('status', 'active')
            ->first();
        if (!$adviser) {
            return response()->json([
                'message' => 'The selected adviser is not available to sign certificates.',
            ], 422);
        }

        $canvas = json_decode($validated['canvas_data'], true);
        if (!is_array($canvas)) {
            return response()->json(['message' => 'Invalid certificate layout data.'], 422);
        }

        $studentBox = null;
        $adviserBox = null;
        foreach ($canvas as $box) {
            if (!is_array($box)) {
                continue;
            }
            if (($box['type'] ?? '') === 'student_name') {
                $studentBox = $box;
            } elseif (($box['type'] ?? '') === 'adviser_name') {
                $adviserBox = $box;
            }
        }

        if (!$studentBox || trim((string) ($studentBox['text'] ?? '')) === ''
            || (int) ($studentBox['student_id'] ?? 0) !== (int) $student->id) {
            return response()->json([
                'message' => 'The student name on the certificate does not match the selected student.',
            ], 422);
        }
        if (!$adviserBox || trim((string) ($adviserBox['text'] ?? '')) === ''
            || (int) ($adviserBox['teacher_id'] ?? 0) !== (int) $adviser->id) {
            return response()->json([
                'message' => 'The adviser name on the certificate does not match the selected adviser.',
            ], 422);
        }
        foreach ([$studentBox, $adviserBox] as $box) {
            foreach (['left', 'top', 'width'] as $key) {
                if (!isset($box[$key]) || !is_numeric($box[$key]) || !is_finite((float) $box[$key])) {
                    return response()->json(['message' => 'Invalid certificate layout data.'], 422);
                }
            }
        }

        $template = $request->file('template_file');
        $generated = $request->file('generated_file');

        // Same payload (student, adviser, layout, template) must never create a
        // second record; a changed payload is a different certificate and passes.
        $fingerprint = md5(
            $student->id . '|' . $adviser->id . '|' . $validated['canvas_data'] . '|'
            . md5_file($template->getRealPath())
        );
        $fingerprintKey = 'certificate-award-fingerprint:' . Auth::id() . ':' . $fingerprint;

        try {
            return Cache::lock('certificate-award:' . Auth::id(), 10)->block(5, function () use (
                $request, $validated, $student, $adviser, $canvas, $template, $generated, $fingerprintKey
            ) {
                $existingId = Cache::get($fingerprintKey);
                if ($existingId && CertificateAward::whereKey($existingId)->exists()) {
                    return response()->json([
                        'success' => true,
                        'duplicate' => true,
                        'id' => $existingId,
                        'message' => 'This certificate has already been awarded to the student.',
                    ]);
                }

                $templatePath = $template->store('certificate-awards/templates', 'local');
                $generatedPath = $generated->store('certificate-awards/generated', 'local');
                $pdfPath = null;

                $extension = strtolower($generated->getClientOriginalExtension());
                if (in_array($extension, ['jpg', 'jpeg'], true)) {
                    $pdf = $this->buildCertificatePdf(Storage::disk('local')->path($generatedPath));
                    if ($pdf !== null) {
                        $pdfPath = 'certificate-awards/pdf/' . Str::random(40) . '.pdf';
                        Storage::disk('local')->put($pdfPath, $pdf);
                    }
                }

                try {
                    $award = CertificateAward::create([
                        'student_id' => $student->id,
                        'teacher_id' => Auth::user()->isTeacher() ? Auth::id() : null,
                        'issued_by' => Auth::id(),
                        'award_title' => 'Certificate',
                        'awarded_date' => now()->toDateString(),
                        'template_file' => $templatePath,
                        'generated_file_path' => $generatedPath,
                        'pdf_file_path' => $pdfPath,
                        'canvas_data' => $canvas,
                        'signed_by' => $adviser->name,
                        'status' => 'Active',
                    ]);
                } catch (\Throwable $e) {
                    Storage::disk('local')->delete(array_filter([$templatePath, $generatedPath, $pdfPath]));
                    throw $e;
                }

                AdminActivity::create([
                    'user_id' => Auth::id(),
                    'action' => 'Awarded Certificate',
                    'description' => "Awarded certificate to {$student->full_name}.",
                    'module' => 'Certificate',
                ]);

                Cache::put($fingerprintKey, $award->id, 300);

                return response()->json([
                    'success' => true,
                    'duplicate' => false,
                    'id' => $award->id,
                    'message' => 'Certificate successfully awarded to the student.',
                ]);
            });
        } catch (LockTimeoutException) {
            return response()->json([
                'message' => 'The certificate is still being processed. Please wait a moment and try again.',
            ], 409);
        }
    }

    public function edit(CertificateAward $award)
    {
        $user = Auth::user();
        if ($user->isTeacher() && $award->teacher_id !== $user->id) {
            abort(403);
        }

        return view('pages.certificate-editor', compact('award'));
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

    /**
     * Authenticated access policy for a certificate award.
     * Admin/Super Admin: full access. Teachers: certificates they issued
     * themselves, or certificates belonging to students they are actively
     * assigned to via student_enrollments (authoritative assignment).
     */
    private function authorizeAwardAccess(CertificateAward $award): void
    {
        $user = Auth::user();

        if ($user->isAdminLevel()) {
            return;
        }

        if ($user->isTeacher()) {
            if ((int) $award->teacher_id === (int) $user->id) {
                return;
            }

            $assigned = $award->student_id
                && $award->student
                && $award->student->enrollments()
                    ->where('teacher_id', $user->id)
                    ->where('status', 'active')
                    ->exists();

            if ($assigned) {
                return;
            }
        }

        abort(403, 'You are not authorized to access this certificate.');
    }

    /**
     * Public (signed URL) access policy: only Active awards tied to a
     * live student record. Mirrors the public student details policy.
     */
    private function authorizePublicAward(CertificateAward $award): void
    {
        $student = $award->student;

        if ($award->status !== 'Active'
            || !$award->student_id
            || !$student
            || in_array($student->status, ['Archived', 'archived'], true)) {
            abort(404, 'Certificate not found.');
        }
    }

    public function download(CertificateAward $award)
    {
        $this->authorizeAwardAccess($award);

        $studentPart = $award->student
            ? Str::slug($award->student->full_name)
            : Str::slug($award->award_title ?? 'certificate');
        $filename = 'Certificate-' . ($studentPart ?: 'certificate');

        $final = $award->finalCertificateFile();
        if (!$final) {
            abort(404, 'Completed certificate file is not available for download. Use Print to save the certificate as PDF.');
        }

        $extension = strtolower(pathinfo($final['path'], PATHINFO_EXTENSION)) ?: 'pdf';

        return Storage::disk($final['disk'])->download($final['path'], $filename . '.' . $extension);
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
        $this->authorizeAwardAccess($award);

        $award->load('issuer', 'teacher');
        return view('pages.certificate-print', compact('award'));
    }

    public function file(CertificateAward $award)
    {
        $this->authorizeAwardAccess($award);

        $display = $award->displayCertificateFile();
        if ($display) {
            return response()->file(Storage::disk($display['disk'])->path($display['path']));
        }

        // Legacy awards keep only the original template plus canvas_data;
        // serve the template image solely to power the on-page reconstruction.
        $template = $award->templateImagePath();
        if ($template && !empty($award->canvas_data)) {
            return response()->file(Storage::disk($template['disk'])->path($template['path']));
        }

        abort(404, 'Completed certificate file is not available.');
    }

    /**
     * Public signed certificate viewer page (opens in a new tab from the
     * public Student Details page). Shows the completed certificate with
     * Download and Print controls, or a clear unavailable message.
     */
    public function publicView(CertificateAward $award)
    {
        $this->authorizePublicAward($award);

        $display = $award->displayCertificateFile();
        $mode = 'unavailable';
        if ($display) {
            $mode = 'final';
        } elseif (!empty($award->canvas_data) && $award->templateImagePath()) {
            $mode = 'reconstruct';
        }

        $fileUrl = null;
        $downloadUrl = null;
        $finalIsPdf = false;

        if ($mode !== 'unavailable') {
            $expires = now()->addDays(7);
            $fileUrl = URL::temporarySignedRoute('public.certificate.file', $expires, ['award' => $award->id]);
            $finalIsPdf = $display
                && strtolower(pathinfo($display['path'], PATHINFO_EXTENSION)) === 'pdf';

            if ($award->hasFinalCertificateFile()) {
                $downloadUrl = URL::temporarySignedRoute('public.certificate.download', $expires, ['award' => $award->id]);
            }
        }

        $studentName = $award->student?->full_name;

        return view('pages.certificate-viewer', compact(
            'award', 'mode', 'fileUrl', 'downloadUrl', 'finalIsPdf', 'studentName'
        ));
    }

    /**
     * Public signed inline file endpoint backing the viewer. Serves the
     * completed certificate, or the template image strictly for legacy
     * canvas_data reconstruction. Never serves a blank template alone.
     */
    public function publicFile(CertificateAward $award)
    {
        $this->authorizePublicAward($award);

        $display = $award->displayCertificateFile();
        if ($display) {
            return response()->file(Storage::disk($display['disk'])->path($display['path']));
        }

        $template = $award->templateImagePath();
        if ($template && !empty($award->canvas_data)) {
            return response()->file(Storage::disk($template['disk'])->path($template['path']));
        }

        abort(404, 'Completed certificate file is not available.');
    }

    /**
     * Public signed download. Legacy canvas-only awards have no binary
     * completed file to stream, so they are redirected back to the viewer
     * where the certificate can be printed / saved as PDF.
     */
    public function publicDownload(CertificateAward $award)
    {
        $this->authorizePublicAward($award);

        $final = $award->finalCertificateFile();
        if (!$final) {
            return redirect()->to(
                URL::temporarySignedRoute('public.certificate.view', now()->addDays(7), ['award' => $award->id])
            );
        }

        $studentPart = $award->student
            ? Str::slug($award->student->full_name)
            : Str::slug($award->award_title ?? 'certificate');
        $filename = 'Certificate-' . ($studentPart ?: 'certificate');
        $extension = strtolower(pathinfo($final['path'], PATHINFO_EXTENSION)) ?: 'pdf';

        return Storage::disk($final['disk'])->download($final['path'], $filename . '.' . $extension);
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

    /**
     * Wrap a JPEG certificate image in a single-page A4-landscape PDF.
     * Returns null when the file is not a usable baseline JPEG so callers
     * can fall back to serving the image itself.
     */
    private function buildCertificatePdf(string $imagePath): ?string
    {
        $jpeg = @file_get_contents($imagePath);
        if ($jpeg === false || $jpeg === '') {
            return null;
        }

        $size = @getimagesize($imagePath);
        if (!$size || ($size['mime'] ?? '') !== 'image/jpeg') {
            return null;
        }

        $width = (int) $size[0];
        $height = (int) $size[1];
        $channels = (int) ($size['channels'] ?? 3);
        if ($width < 1 || $height < 1 || !in_array($channels, [1, 3], true)) {
            return null;
        }

        $colorSpace = $channels === 1 ? '/DeviceGray' : '/DeviceRGB';

        // A4 landscape in PDF points; image centered and scaled to fit.
        $pageWidth = 841.89;
        $pageHeight = 595.28;
        $scale = min($pageWidth / $width, $pageHeight / $height);
        $drawWidth = $width * $scale;
        $drawHeight = $height * $scale;
        $translateX = ($pageWidth - $drawWidth) / 2;
        $translateY = ($pageHeight - $drawHeight) / 2;
        $content = sprintf(
            'q %.4F 0 0 %.4F %.4F %.4F cm /Im0 Do Q',
            $drawWidth,
            $drawHeight,
            $translateX,
            $translateY
        );

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 841.89 595.28] '
                . '/Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>',
            4 => "<< /Type /XObject /Subtype /Image /Width {$width} /Height {$height} "
                . "/ColorSpace {$colorSpace} /BitsPerComponent 8 /Filter /DCTDecode /Length "
                . strlen($jpeg) . " >>\nstream\n" . $jpeg . "\nendstream",
            5 => '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
