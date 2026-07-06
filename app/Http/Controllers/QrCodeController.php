<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\Student;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode as QrCodeGenerator;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = QrCode::with(['student', 'creator']);

        if (Auth::user()->isTeacher()) {
            $query->whereHas('student.enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('qr_type', 'like', "%{$search}%")
                  ->orWhere('qr_value', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($studentQuery) use ($search) {
                      $studentQuery->where('full_name', 'like', "%{$search}%")
                                   ->orWhere('lrn', 'like', "%{$search}%")
                                   ->orWhere('student_id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                      $creatorQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('qr_type')) {
            $query->where('qr_type', $request->qr_type);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $qrCodes = $query->latest()->paginate(10)->withQueryString();

        $qrCode = null;
        if ($generatedId = session('generated_qr_id')) {
            $qrCode = QrCode::with('student')->find($generatedId);
        }

        return view('pages.qrcode', compact('qrCodes', 'qrCode'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $studentsQuery = Student::whereNotIn('status', ['Archived', 'archived']);
        if (Auth::user()->isTeacher()) {
            $studentsQuery->whereHas('enrollments', function ($q) {
                $q->where('teacher_id', Auth::id())->where('status', 'active');
            });
        }
        $student = $studentsQuery->findOrFail($validated['student_id']);

        $qrValue = "LRN: " . $student->lrn . "\nName: " . $student->full_name;

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
        $svgContent = $result->getString();

        Storage::disk('public')->put($filePath, $svgContent);

        $qrRecord = QrCode::create([
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'qr_type' => 'lrn',
            'qr_value' => $qrValue,
            'qr_image_path' => $filePath,
            'created_by' => Auth::id(),
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Generated QR Code',
            'description' => 'Generated QR code for ' . $student->full_name . ' (LRN: ' . $student->lrn . ')',
            'module' => 'QR Code',
        ]);

        return redirect()->route('admin.qrcode')->with('success', 'QR Code generated successfully for ' . $student->full_name . '!')->with('generated_qr_id', $qrRecord->id);
    }

    public function download(QrCode $qrCode)
    {
        if (!$qrCode->qr_image_path || !Storage::disk('public')->exists($qrCode->qr_image_path)) {
            return back()->withErrors(['qr' => 'QR code file not found.']);
        }

        return Storage::disk('public')->download($qrCode->qr_image_path);
    }

    public function printPdf(QrCode $qrCode)
    {
        $qrCode->load('student');

        $student = $qrCode->student;
        $studentName = $student
            ? trim($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . $student->last_name)
            : ($qrCode->student_name ?? '');
        $lrn = $student->lrn ?? '';

        $qrValue = "LRN: {$lrn}\nName: {$studentName}";

        $qrCodeObj = new QrCodeGenerator(
            data: $qrValue,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 400,
            margin: 20,
        );
        $writer = new SvgWriter();
        $result = $writer->write($qrCodeObj);
        $qrSvg = $result->getString();

        return view('pages.qrcode-print', compact('qrCode', 'qrValue', 'qrSvg'));
    }
}
