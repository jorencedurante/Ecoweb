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
            background: #ffffff;
        }

        * {
            box-sizing: border-box;
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
        }

        .certificate-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            z-index: 1;
        }

        .certificate-text-print {
            position: absolute;
            z-index: 10;
            transform: translate(-50%, -50%);
            white-space: nowrap;
            line-height: 1.2;
        }

        .certificate-student-name-print {
            position: absolute;
            z-index: 11;
            transform: translate(-50%, -50%);
            line-height: 1.2;
            text-align: center;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            html, body {
                width: 297mm;
                height: 210mm;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: hidden !important;
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
                object-fit: contain !important;
            }

            .certificate-text-print {
                display: block !important;
                position: absolute !important;
                z-index: 10 !important;
                transform: translate(-50%, -50%) !important;
                white-space: nowrap !important;
            }

            .certificate-student-name-print {
                display: block !important;
                position: absolute !important;
                z-index: 11 !important;
                transform: translate(-50%, -50%) !important;
            }
        }
    </style>
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}">
</head>
<body>
    @if($canvasData && is_array($canvasData) && count($canvasData) > 0 && $fileUrl && $isImage)
    <div class="certificate-print-page">
        <div class="certificate-canvas">
            <img src="{{ $fileUrl }}" alt="Certificate Template" class="certificate-bg">
            @foreach($canvasData as $box)
            @if(($box['type'] ?? '') === 'student_name')
            <div class="certificate-student-name-print"
                 data-width="{{ $box['width'] ?? 70 }}"
                 data-pref="{{ $box['fontSizePref'] ?? ($box['fontSize'] ?? 40) }}"
                 data-min="{{ $box['fontSizeMin'] ?? 16 }}"
                 data-family="{{ $box['fontFamily'] ?? 'Georgia' }}"
                 data-bold="{{ !empty($box['fontWeight']) && $box['fontWeight'] !== 'normal' && $box['fontWeight'] !== '400' ? '1' : '0' }}"
                 style="left:{{ ($box['x'] ?? 50) }}%;top:{{ ($box['y'] ?? 45) }}%;width:{{ ($box['width'] ?? 70) }}%;
                        font-family:'{{ $box['fontFamily'] ?? 'Georgia' }}',serif;
                        color:{{ $box['color'] ?? '#1F2937' }};
                        font-weight:{{ $box['fontWeight'] ?? '700' }};
                        font-size:{{ $box['fontSize'] ?? 40 }}px;">
                {{ $box['text'] ?? '' }}
            </div>
            @else
            <div class="certificate-text-print"
                 style="left:{{ ($box['x'] ?? 50) }}%;top:{{ ($box['y'] ?? 50) }}%;
                        font-size:{{ ($box['fontSize'] ?? 36) }}px;
                        color:{{ $box['color'] ?? '#000000' }};
                        font-weight:{{ $box['fontWeight'] ?? 'normal' }};
                        text-align:{{ $box['textAlign'] ?? 'center' }};">
                {{ $box['text'] ?? '' }}
            </div>
            @endif
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
        (function () {
            var REF_W = 1123;
            var els = document.querySelectorAll('.certificate-student-name-print');

            function fitAll() {
                if (!els.length) return;
                var mc = document.createElement('canvas').getContext('2d');
                for (var i = 0; i < els.length; i++) {
                    var el = els[i];
                    var widthPct = parseFloat(el.getAttribute('data-width')) || 70;
                    var pref = parseFloat(el.getAttribute('data-pref')) || 40;
                    var min = parseFloat(el.getAttribute('data-min')) || 16;
                    var family = el.getAttribute('data-family') || 'Georgia';
                    var weight = el.getAttribute('data-bold') === '1' ? '700' : '400';
                    var avail = (widthPct / 100) * REF_W - 24;
                    var text = el.textContent.trim();
                    var fitted = min;
                    for (var s = Math.round(pref); s >= min; s--) {
                        mc.font = weight + ' ' + s + 'px "' + family + '"';
                        if (mc.measureText(text).width <= avail) { fitted = s; break; }
                    }
                    mc.font = weight + ' ' + min + 'px "' + family + '"';
                    var fits = mc.measureText(text).width <= avail;
                    el.style.fontSize = fitted + 'px';
                    el.style.whiteSpace = fits ? 'nowrap' : 'normal';
                    el.style.wordBreak = fits ? 'normal' : 'break-word';
                }
            }

            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(fitAll);
            }
            window.addEventListener('load', function () {
                fitAll();
                setTimeout(fitAll, 300);
            });
            fitAll();
        })();

        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
