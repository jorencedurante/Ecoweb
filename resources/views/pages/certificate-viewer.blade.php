<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - {{ $studentName ?: ($award->award_title ?: 'Certificate') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <style>
        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #F3F4F6;
            font-family: 'Inter', system-ui, sans-serif;
            color: #111827;
        }

        .viewer-header {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 24px;
            background: #ffffff;
            border-bottom: 1px solid #E5E7EB;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .viewer-title {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .viewer-title img {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            object-fit: cover;
        }

        .viewer-title-text { min-width: 0; }

        .viewer-title-text h1 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .viewer-title-text p {
            margin: 2px 0 0;
            font-size: 12px;
            color: #6B7280;
        }

        .viewer-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .viewer-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: 8px;
            border: none;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            line-height: 1;
        }

        .viewer-btn svg { display: block; }

        .viewer-btn-download {
            background: #00AEEF;
            color: #ffffff;
        }

        .viewer-btn-download:hover { background: #0096C7; }

        .viewer-btn-print {
            background: #111827;
            color: #ffffff;
        }

        .viewer-btn-print:hover { background: #1F2937; }

        .viewer-body {
            display: flex;
            justify-content: center;
            padding: 28px 20px 48px;
        }

        .certificate-frame {
            background: #ffffff;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border-radius: 4px;
            overflow: hidden;
            max-width: 1123px;
            width: 100%;
        }

        .certificate-canvas {
            position: relative;
            width: 100%;
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

        .certificate-embed {
            display: block;
            width: 100%;
            height: 720px;
            border: none;
        }

        .certificate-student-name-view,
        .certificate-adviser-view {
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

        .certificate-text-view {
            position: absolute;
            z-index: 10;
            transform: translate(-50%, -50%);
            white-space: nowrap;
            line-height: 1.2;
        }

        .unavailable-card {
            max-width: 480px;
            margin: 80px auto;
            background: #ffffff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 36px 32px;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        .unavailable-card h2 {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 700;
        }

        .unavailable-card p {
            margin: 0;
            font-size: 13px;
            color: #6B7280;
            line-height: 1.5;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        @media print {
            body { background: #ffffff !important; }

            .no-print { display: none !important; }

            .viewer-body {
                padding: 0 !important;
                display: block !important;
            }

            .certificate-frame {
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: none !important;
                width: 297mm !important;
                margin: 0 !important;
            }

            .certificate-canvas,
            .certificate-embed {
                width: 297mm !important;
                height: auto !important;
                page-break-inside: avoid;
            }

            .certificate-bg {
                width: 100% !important;
                height: auto !important;
                object-fit: contain !important;
            }

            .certificate-student-name-view,
            .certificate-adviser-view {
                display: flex !important;
                position: absolute !important;
                z-index: 11 !important;
            }

            .certificate-text-view {
                display: block !important;
                position: absolute !important;
                z-index: 10 !important;
                transform: translate(-50%, -50%) !important;
            }
        }
    </style>
</head>
<body>
    <header class="viewer-header no-print">
        <div class="viewer-title">
            <img src="{{ asset('image/Page-logo.jpg') }}" alt="EcoCollect">
            <div class="viewer-title-text">
                <h1>{{ $award->award_title ?: 'Certificate' }}</h1>
                <p>
                    @if($studentName){{ $studentName }} · @endif
                    Awarded {{ $award->awarded_date ? $award->awarded_date->format('M d, Y') : $award->created_at?->format('M d, Y') }}
                </p>
            </div>
        </div>
        <div class="viewer-actions">
            @if($downloadUrl)
            <a class="viewer-btn viewer-btn-download" href="{{ $downloadUrl }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download
            </a>
            @endif
            @if($mode !== 'unavailable')
            <button type="button" class="viewer-btn viewer-btn-print" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print
            </button>
            @endif
        </div>
    </header>

    <main class="viewer-body">
        @if($mode === 'final')
        <div class="certificate-frame">
            @if($finalIsPdf)
            <iframe class="certificate-embed" src="{{ $fileUrl }}" title="Completed certificate"></iframe>
            @else
            <img class="certificate-bg" src="{{ $fileUrl }}" alt="Completed certificate for {{ $studentName ?: $award->award_title }}">
            @endif
        </div>
        @elseif($mode === 'reconstruct')
        @php
            $rawCanvas = $award->canvas_data;
            $canvasData = is_string($rawCanvas) ? json_decode($rawCanvas, true) : $rawCanvas;
        @endphp
        <div class="certificate-frame">
            <div class="certificate-canvas">
                <img src="{{ $fileUrl }}" alt="Certificate" class="certificate-bg">
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
                    $boxNameClass = $boxType === 'adviser_name' ? 'certificate-adviser-view' : 'certificate-student-name-view';
                @endphp
                @if($isNameBox)
                <div class="{{ $boxNameClass }}"
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
                <div class="certificate-text-view"
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
        @else
        <div class="unavailable-card">
            <h2>Completed certificate unavailable</h2>
            <p>This certificate record does not have a completed certificate file that can be displayed. Please ask the school administrator for assistance.</p>
        </div>
        @endif
    </main>

    @if($mode === 'reconstruct')
    <script>
        (function () {
            var LINE_HEIGHT = 1.15;
            var els = document.querySelectorAll('.certificate-student-name-view, .certificate-adviser-view');

            function fitAll() {
                if (!els.length) return;
                var wrap = document.querySelector('.certificate-canvas');
                var refW = (wrap && wrap.clientWidth) || 1123;
                var mc = document.createElement('canvas').getContext('2d');

                for (var i = 0; i < els.length; i++) {
                    var el = els[i];
                    var text = el.textContent.trim();
                    if (!text) continue;

                    var pref = parseFloat(el.getAttribute('data-pref')) || 40;
                    var min = Math.max(1, parseFloat(el.getAttribute('data-min')) || 16);
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
    </script>
    @endif
</body>
</html>
