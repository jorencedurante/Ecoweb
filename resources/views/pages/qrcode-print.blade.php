<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f5f5f5;
        }
        .qr-print-card {
            width: 380px;
            margin: 40px auto;
            padding: 25px;
            background: #ffffff;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        .qr-code {
            margin-bottom: 15px;
        }
        .qr-code svg {
            width: 250px;
            height: 250px;
            display: inline-block;
        }
        .student-name {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .qr-label {
            font-size: 12px;
            color: #6b7280;
            margin: 0;
        }
        .qr-value {
            font-size: 13px;
            color: #374151;
            margin-top: 10px;
            white-space: pre-line;
        }
        .print-actions {
            margin-top: 18px;
        }
        .print-note {
            margin-top: 10px;
            font-size: 11px;
            color: #9ca3af;
            text-align: center;
        }
        .no-print {
            display: block;
        }
        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print,
            button,
            .print-btn,
            .close-btn,
            .print-actions {
                display: none !important;
                visibility: hidden !important;
            }
            @page {
                margin: 0;
            }
        }
    </style>
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}">
</head>
<body>
    <div class="qr-print-card">
        <div class="qr-code">
            {!! $qrSvg !!}
        </div>
        <h2 class="student-name">{{ $qrCode->student->full_name ?? $qrCode->student_name }}</h2>
        <div class="print-actions no-print">
            <button type="button" onclick="window.print()" style="padding:10px 24px;background:#22C55E;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;margin-right:6px;">🖨 Print</button>
            <button type="button" onclick="window.close()" style="padding:10px 24px;background:#EF4444;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;">Close</button>
        </div>
        <p class="print-note no-print">For clean printing, open More settings and uncheck Headers and footers.</p>
    </div>
</body>
</html>
