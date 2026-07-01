<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\Student;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\QrCode as QrCodeGenerator;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeController extends Controller
{
    public function index()
    {
        $qrCodes = QrCode::with('student', 'generator')->latest()->paginate(10);
        $latestQrCode = QrCode::with('student')->latest()->first();

        return view('pages.qrcode', compact('qrCodes', 'latestQrCode'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'qr_type' => 'required|in:lrn,student_id,student_name',
            'qr_value' => 'required|string|max:255',
        ]);

        $qrValue = trim($validated['qr_value']);
        $qrType = $validated['qr_type'];

        if ($qrType === 'student_name') {
            $student = Student::where('full_name', 'LIKE', '%' . $qrValue . '%')
                ->where('status', '!=', 'Archived')
                ->first();
            if (!$student) {
                return redirect()->route('admin.qrcode')->with('error', 'No student record found for the entered QR value.');
            }
        } elseif ($qrType === 'lrn') {
            $student = Student::where('lrn', $qrValue)
                ->where('status', '!=', 'Archived')
                ->first();
            if (!$student) {
                return redirect()->route('admin.qrcode')->with('error', 'No student record found for the entered QR value.');
            }
        } elseif ($qrType === 'student_id') {
            $student = Student::where('student_id', $qrValue)
                ->where('status', '!=', 'Archived')
                ->first();
            if (!$student) {
                return redirect()->route('admin.qrcode')->with('error', 'No student record found for the entered QR value.');
            }
        }

        if (!isset($student) || !$student) {
            return redirect()->route('admin.qrcode')->with('error', 'No student record found for the entered QR value.');
        }

        $fileName = 'student-' . $qrType . '-' . Str::slug($qrValue) . '-' . time() . '.svg';
        $filePath = 'qr_codes/' . $fileName;

        $qrCode = new QrCodeGenerator($qrValue);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        $svgContent = $result->getString();

        Storage::disk('public')->put($filePath, $svgContent);

        $qrRecord = QrCode::create([
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'qr_type' => $qrType,
            'qr_value' => $qrValue,
            'qr_image_path' => $filePath,
            'created_by' => Auth::id(),
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Generated QR Code',
            'description' => 'Generated QR code for ' . $student->full_name . ' (' . $qrValue . ')',
            'module' => 'QR Code',
        ]);

        return redirect()->route('admin.qrcode')->with('success', 'QR Code generated successfully for ' . $student->full_name . '!');
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
        return view('pages.qrcode-print', compact('qrCode'));
    }
}
