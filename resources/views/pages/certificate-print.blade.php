<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print - {{ $award->award_title ?: 'Certificate' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @php
        $filePath = $award->template_file ?? $award->certificate_file ?? $award->template_file_path;
        $fileExt = $filePath ? strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) : null;
        $isImage = $fileExt && in_array($fileExt, ['jpg','jpeg','png']);
        $fileUrl = $filePath ? asset('storage/'.$filePath) : null;
        $rawCanvas = $award->canvas_data;
        $canvasData = is_string($rawCanvas) ? json_decode($rawCanvas, true) : $rawCanvas;
    @endphp
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
        }

        * {
            box-sizing: border-box;
        }

        .print-toolbar {
            text-align: center;
            padding: 20px;
        }

        .print-btn {
            background: #22c55e;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px 32px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .print-btn:hover {
            opacity: 0.9;
        }

        .certificate-print-page {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
        }

        .certificate-canvas {
            position: relative;
            width: 1123px;
            height: 794px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        }

        .certificate-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
        }

        .certificate-text-print {
            position: absolute;
            z-index: 10;
            transform: translate(-50%, -50%);
            white-space: nowrap;
            line-height: 1.2;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        @media print {
            html, body {
                width: 297mm;
                height: 210mm;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: hidden !important;
            }

            .no-print,
            .print-toolbar,
            button,
            .btn {
                display: none !important;
            }

            .certificate-print-page {
                width: 297mm;
                height: 210mm;
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                background: #ffffff !important;
            }

            .certificate-canvas {
                width: 297mm !important;
                height: 210mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                overflow: hidden !important;
                page-break-after: avoid;
                page-break-before: avoid;
                page-break-inside: avoid;
            }

            .certificate-bg {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
            }

            .certificate-text-print {
                display: block !important;
                position: absolute !important;
                z-index: 10 !important;
                transform: translate(-50%, -50%) !important;
                white-space: nowrap !important;
            }
        }
    </style>
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}">
</head>
<body>
    <div class="print-toolbar no-print">
        <button onclick="window.print()" class="print-btn">Print</button>
    </div>

    @if($canvasData && is_array($canvasData) && count($canvasData) > 0 && $fileUrl && $isImage)
    <div class="certificate-print-page">
        <div class="certificate-canvas">
            <img src="{{ $fileUrl }}" alt="Certificate Template" class="certificate-bg">
            @foreach($canvasData as $box)
            <div class="certificate-text-print"
                 style="left:{{ ($box['x'] ?? 50) }}%;top:{{ ($box['y'] ?? 50) }}%;
                        font-size:{{ ($box['fontSize'] ?? 36) }}px;
                        color:{{ $box['color'] ?? '#000000' }};
                        font-weight:{{ $box['fontWeight'] ?? 'normal' }};
                        text-align:{{ $box['textAlign'] ?? 'center' }};">
                {{ $box['text'] ?? '' }}
            </div>
            @endforeach
        </div>
    </div>
    @elseif($fileUrl && $isImage)
    <div class="certificate-print-page">
        <div class="certificate-canvas">
            <img src="{{ $fileUrl }}" alt="Certificate" class="certificate-bg">
        </div>
    </div>
    @elseif($fileUrl)
    <div class="certificate-print-page">
        <div class="certificate-canvas" style="display:flex;align-items:center;justify-content:center;">
            <iframe src="{{ $fileUrl }}" style="width:100%;height:100%;border:none;"></iframe>
        </div>
    </div>
    @else
    <div class="certificate-print-page">
        <div class="certificate-canvas" style="display:flex;align-items:center;justify-content:center;">
            <h3 style="color:#9CA3AF;">{{ $award->award_title ?: 'Certificate' }}</h3>
        </div>
    </div>
    @endif

    <script>
        window.onload = function () {
            var params = new URLSearchParams(window.location.search);
            if (params.get('print') === '1') {
                setTimeout(function () { window.print(); }, 500);
            }
        };
    </script>
</body>
</html>
