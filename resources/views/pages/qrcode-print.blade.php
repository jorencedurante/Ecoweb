<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print QR Code - EcoCollect</title>
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
        .qr-print-area {
            background: #fff;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            max-width: 400px;
        }
        .qr-print-area img {
            max-width: 260px;
            margin-bottom: 20px;
        }
        .qr-print-area h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #1F2937;
        }
        .qr-print-area .label {
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 2px;
        }
        .qr-print-area .value {
            font-size: 15px;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 12px;
        }
        .no-print { margin-top: 24px; }
        @media print {
            body { background: #fff; }
            .qr-print-area {
                box-shadow: none;
                border-radius: 0;
                padding: 20px;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="qr-print-area">
        @if($qrCode->qr_image_path)
            <img src="{{ asset('storage/' . $qrCode->qr_image_path) }}" alt="QR Code">
        @else
            <p>QR code image not available.</p>
        @endif
        <h2>{{ $qrCode->student->full_name ?? $qrCode->student_name }}</h2>
        <div class="label">QR Type</div>
        <div class="value">{{ ucfirst(str_replace('_', ' ', $qrCode->qr_type)) }}</div>
        <div class="label">QR Value</div>
        <div class="value">{{ $qrCode->qr_value }}</div>
        <div class="no-print">
            <button onclick="window.print()" style="padding:10px 24px;background:#22C55E;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;">🖨 Print</button>
            <button onclick="window.close()" style="padding:10px 24px;background:#EF4444;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;margin-left:8px;">Close</button>
        </div>
    </div>
</body>
</html>
