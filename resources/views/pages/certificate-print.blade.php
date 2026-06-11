<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print - {{ $award->award_title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:#fff; padding:40px; }
        .certificate-print-area {
            max-width:800px;
            width:100%;
            position:relative;
            overflow:hidden;
            border:3px solid #00C853;
            border-radius:12px;
            padding:50px 40px;
            text-align:center;
            background:#fff;
        }
        @if($award->template_file_path && in_array(pathinfo($award->template_file_path, PATHINFO_EXTENSION), ['jpg','jpeg','png']))
        .certificate-print-area {
            background-image:url('{{ asset('storage/'.$award->template_file_path) }}');
            background-size:cover;
            background-position:center;
        }
        .certificate-print-area::before {
            content:'';
            position:absolute;
            inset:0;
            background:rgba(255,255,255,0.88);
            z-index:0;
        }
        @endif
        .print-content { position:relative; z-index:1; }
        .cert-logo {
            width:80px;height:80px;border-radius:50%;
            background:linear-gradient(135deg,#00C853,#00AEEF);
            display:flex;align-items:center;justify-content:center;
            margin:0 auto 16px;font-size:28px;font-weight:800;color:#fff;
        }
        h1 { font-size:26px;font-weight:800;color:#111827;margin-bottom:6px;letter-spacing:1px; }
        .subtitle { font-size:15px;color:#6B7280;margin-bottom:20px; }
        .student-name { font-size:32px;font-weight:700;color:#071126;margin-bottom:8px; }
        .desc { font-size:14px;color:#6B7280;max-width:500px;margin:0 auto 24px;line-height:1.6; }
        .signatures { display:flex;justify-content:space-between;margin-top:32px;padding:0 20px;font-size:13px;color:#6B7280; }
        .meta { margin-top:16px;font-size:13px;color:#9CA3AF; }
        .meta strong { color:#6B7280; }
        .badge { display:inline-block;background:#E9FBEF;color:#22C55E;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:12px; }
        @media print {
            body { padding:0; }
            @page { margin:0.5in; }
        }
    </style>
</head>
<body>
    <div class="certificate-print-area">
        <div class="print-content">
            <div class="cert-logo">EC</div>
            <div class="badge">{{ $award->certificate_type ?? 'Certificate' }}</div>
            <h1>{{ $award->certificate_title ?? $award->award_title }}</h1>
            <div class="subtitle">Presented to</div>
            <div class="student-name">{{ $award->student->full_name }}</div>
            <p class="desc">{{ $award->award_description ?? 'For outstanding achievement and dedication.' }}</p>
            <div class="signatures">
                <div>{{ $award->school_principal_name ?? '___________________' }}<br><strong>School Principal</strong></div>
                <div>{{ $award->program_coordinator_name ?? '___________________' }}<br><strong>Program Coordinator</strong></div>
            </div>
            <div class="meta">
                Awarded on <strong>{{ $award->awarded_date->format('F d, Y') }}</strong>
                @if($award->awarded_by)
                    by <strong>{{ $award->awarded_by }}</strong>
                @endif
            </div>
        </div>
    </div>
    <script>window.print();</script>
</body>
</html>
