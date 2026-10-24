<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print - {{ $award->award_title ?: 'Certificate' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @php
        // The file route serves the completed certificate from whichever disk
        // holds it (final file, or template image for canvas reconstruction).
        $fileUrl = route('admin.certificate.file', $award);
        $rawCanvas = $award->canvas_data;
        $canvasData = is_string($rawCanvas) ? json_decode($rawCanvas, true) : $rawCanvas;
        $hasCanvas = is_array($canvasData) && count($canvasData) > 0;
        $certificateExt = $award->certificate_file ? strtolower(pathinfo($award->certificate_file, PATHINFO_EXTENSION)) : null;
        $certificateIsImage = $certificateExt && in_array($certificateExt, ['jpg', 'jpeg', 'png']);
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
            height: auto;
            background: #ffffff;
            overflow: hidden;
        }

        .certificate-bg {
            display: block;
            width: 100%;
            height: auto;
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

        .certificate-student-name-print,
        .certificate-adviser-print {
            position: absolute;
            z-index: 11;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            box-sizing: border-box;
            line-height: 1.15;
            padding: 2px 8px;
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
                height: auto !important;
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
                height: auto !important;
                object-fit: contain !important;
            }

            .certificate-text-print {
                display: block !important;
                position: absolute !important;
                z-index: 10 !important;
                transform: translate(-50%, -50%) !important;
                white-space: nowrap !important;
            }

            .certificate-student-name-print,
            .certificate-adviser-print {
                display: flex !important;
                position: absolute !important;
                z-index: 11 !important;
            }
        }
    </style>
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}">
</head>
<body>
    @if($award->generated_file_path)
    {{-- Final stored output: names are already baked in, so print the exact
         awarded image instead of reconstructing a different layout. --}}
    <div class="certificate-print-page">
        <div class="certificate-canvas">
            <img src="{{ route('admin.certificate.file', $award) }}" alt="Certificate" class="certificate-bg">
        </div>
    </div>
    @elseif($hasCanvas)
    <div class="certificate-print-page">
        <div class="certificate-canvas">
            <img src="{{ $fileUrl }}" alt="Certificate Template" class="certificate-bg">
            @foreach($canvasData as $box)
            @php
                $boxType = $box['type'] ?? '';
                $isNameBox = in_array($boxType, ['student_name', 'adviser_name']);
                $boxWidth = $box['width'] ?? ($boxType === 'adviser_name' ? 23 : 30);
                $defaultHeight = $boxType === 'adviser_name' ? 5 : 7;
                $boxHeight = $box['height'] ?? $defaultHeight;
                if (isset($box['left'])) {
                    $boxLeft = $box['left'];
                    $boxTop = $box['top'] ?? 50;
                } else {
                    $boxLeft = ($box['x'] ?? 50) - ($boxWidth / 2);
                    $boxTop = ($box['y'] ?? 50) - ($boxHeight / 2);
                }
                $boxNameClass = $boxType === 'adviser_name' ? 'certificate-adviser-print' : 'certificate-student-name-print';
            @endphp
            @if($isNameBox)
            <div class="{{ $boxNameClass }}"
                 data-width="{{ $boxWidth }}"
                 data-pref="{{ $box['fontSizePref'] ?? ($box['fontSize'] ?? ($boxType === 'adviser_name' ? 26 : 40)) }}"
                 data-min="{{ $box['fontSizeMin'] ?? ($boxType === 'adviser_name' ? 10 : 16) }}"
                 data-family="{{ $box['fontFamily'] ?? 'Georgia' }}"
                 data-bold="{{ !empty($box['fontWeight']) && $box['fontWeight'] !== 'normal' && $box['fontWeight'] !== '400' ? '1' : '0' }}"
                 style="left:{{ $boxLeft }}%;top:{{ $boxTop }}%;width:{{ $boxWidth }}%;height:{{ $boxHeight }}%;
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
    @elseif($award->pdf_file_path || ($award->certificate_file && !$certificateIsImage))
    <div class="certificate-print-page">
        <div class="certificate-canvas" style="display:flex;align-items:center;justify-content:center;min-height:794px;">
            <iframe src="{{ $fileUrl }}" style="width:100%;height:100%;border:none;"></iframe>
        </div>
    </div>
    @elseif($award->certificate_file)
    <div class="certificate-print-page">
        <div class="certificate-canvas">
            <img src="{{ $fileUrl }}" alt="Certificate" class="certificate-bg">
        </div>
    </div>
    @else
    <div class="certificate-print-page">
        <div class="certificate-canvas" style="display:flex;align-items:center;justify-content:center;min-height:794px;">
            <h3 style="color:#9CA3AF;">Completed certificate unavailable</h3>
        </div>
    </div>
    @endif

    <script>
        (function () {
            var REF_W = 1123;
            var LINE_HEIGHT = 1.15;
            var els = document.querySelectorAll('.certificate-student-name-print, .certificate-adviser-print');

            function fitAll() {
                if (!els.length) return;
                var wrap = document.querySelector('.certificate-canvas');
                var scale = ((wrap && wrap.clientWidth) || REF_W) / REF_W;
                var mc = document.createElement('canvas').getContext('2d');

                for (var i = 0; i < els.length; i++) {
                    var el = els[i];
                    var text = el.textContent.trim();
                    if (!text) continue;

                    var pref = (parseFloat(el.getAttribute('data-pref')) || 40) * scale;
                    var min = Math.max(1, (parseFloat(el.getAttribute('data-min')) || 16) * scale);
                    var family = el.getAttribute('data-family') || 'Georgia';
                    var weight = el.getAttribute('data-bold') === '1' ? '700' : '400';

                    var cs = window.getComputedStyle(el);
                    var availW = el.clientWidth - (parseFloat(cs.paddingLeft) || 0) - (parseFloat(cs.paddingRight) || 0);
                    var availH = el.clientHeight - (parseFloat(cs.paddingTop) || 0) - (parseFloat(cs.paddingBottom) || 0);
                    if (availW <= 0 || availH <= 0) continue;

                    var size = pref;
                    var fits = false;
                    while (size >= min) {
                        mc.font = weight + ' ' + size + 'px "' + family + '"';
                        if (mc.measureText(text).width <= availW && size * LINE_HEIGHT <= availH) {
                            fits = true;
                            break;
                        }
                        size -= 1;
                    }
                    if (!fits) size = min;

                    el.style.fontSize = size + 'px';
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
