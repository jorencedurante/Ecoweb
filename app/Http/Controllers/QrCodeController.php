<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\Student;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QrCodeController extends Controller
{
    public function index()
    {
        $qrCodes = QrCode::with('student', 'generator')->latest()->paginate(10);
        $latestQr = QrCode::with('student')->latest()->first();
        $latestQrValue = $latestQr?->qr_value;
        $latestQrId = $latestQr?->id;
        $latestQrStudentName = $latestQr?->student_name;
        return view('pages.qrcode', compact('qrCodes', 'latestQrValue', 'latestQrId', 'latestQrStudentName'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'qr_type' => 'required|in:lrn,student_id',
            'student_name' => 'required|string|max:150',
            'qr_value' => 'required|string|max:255',
        ]);

        $qrValue = trim($validated['qr_value']);
        $qrType = $validated['qr_type'];
        $studentName = trim($validated['student_name']);

        $existingQr = QrCode::where('qr_value', $qrValue)->first();
        if ($existingQr) {
            return redirect()->route('admin.qrcode')->with('error', 'QR code for this value already exists!');
        }

        $student = null;

        if ($qrType === 'lrn') {
            $student = Student::where('lrn', $qrValue)->first();
            if (!$student) {
                return redirect()->route('admin.qrcode')->with('error', 'No student found with this LRN.');
            }
        }

        if ($qrType === 'student_id') {
            $student = Student::where('student_id', $qrValue)->first();
            if (!$student) {
                return redirect()->route('admin.qrcode')->with('error', 'No student found with this Student ID.');
            }
        }

        QrCode::create([
            'student_id' => $student->id,
            'student_name' => $studentName,
            'qr_type' => $qrType,
            'qr_value' => $qrValue,
            'qr_image_path' => null,
            'created_by' => Auth::id(),
        ]);

        AdminActivity::create([
            'user_id' => Auth::id(),
            'action' => 'Generated QR Code',
            'description' => 'Generated QR code for ' . $studentName . ' (' . $qrValue . ')',
            'module' => 'QR Code',
        ]);

        return redirect()->route('admin.qrcode')->with('success', 'QR Code created successfully for ' . $studentName . '!');
    }

    public function download(QrCode $qrCode)
    {
        return redirect()->route('admin.qrcode')->with('info', 'QR code download will be available after installing a QR code library.');
    }

    public function printPdf(QrCode $qrCode)
    {
        return redirect()->route('admin.qrcode')->with('info', 'QR code printing will be available after installing a QR code library.');
    }
}
