<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - EcoCollect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; padding: 30px; color: #1a1a2e; background: #fff; }

        .report-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #0f766e; }
        .report-header h1 { font-size: 22px; color: #0f766e; margin-bottom: 4px; }
        .report-header .subtitle { font-size: 13px; color: #64748b; }
        .report-header .generated { font-size: 11px; color: #94a3b8; margin-top: 6px; }

        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: #0f766e; color: #fff; padding: 10px 12px; text-align: left; font-weight: 600; }
        td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        tr:hover { background: #e0f7f5; }

        .points { font-weight: 700; color: #d97706; }
        .claims-count { font-weight: 700; color: #7c3aed; }

        .no-data { text-align: center; padding: 40px; color: #94a3b8; font-size: 14px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; }

        .student-section { margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .student-section-header { background: #f1f5f9; padding: 10px 14px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .student-section-header:hover { background: #e2e8f0; }
        .student-info { font-weight: 600; color: #111827; }
        .student-meta { font-size: 11px; color: #6b7280; }
        .claims-toggle { font-size: 10px; color: #7c3aed; font-weight: 700; }
        .claims-detail { display: none; padding: 12px 14px; border-top: 1px solid #e2e8f0; background: #fff; }
        .claims-detail.expanded { display: block; }
        .claims-detail table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 6px; }
        .claims-detail th { background: #f8fafc; color: #64748b; padding: 6px 10px; text-align: left; font-size: 10px; text-transform: uppercase; font-weight: 600; }
        .claims-detail td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }
        .claims-detail tr:last-child td { border-bottom: none; }
        .no-claims { text-align: center; padding: 20px; color: #9ca3af; font-size: 12px; }

        @media print {
            @page { size: landscape; margin: 15mm; }
            body { padding: 0; }
            .report-header { margin-bottom: 20px; padding-bottom: 15px; }
            tr:hover { background: transparent; }
            .student-section { break-inside: avoid; }
            .claims-detail { display: block; }
            .claims-detail.expanded { display: block; }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>🎁 {{ $title }}</h1>
        <div class="subtitle">EcoCollect Environmental Achievement Tracking System</div>
        <div class="generated">Generated: {{ now()->format('F j, Y \a\t g:i A') }} | {{ $students->count() }} student(s)</div>
    </div>

    @if($students->isEmpty())
        <div class="no-data">No item claim data available.</div>
    @else
        @foreach($students as $student)
        @php
            $claims = $student->claims->sortByDesc('claim_date')->sortByDesc('id')->values();
            $totalPointsUsed = $claims->sum('points_deducted') ?? 0;
        @endphp
        <div class="student-section">
            <div class="student-section-header" onclick="this.nextElementSibling.classList.toggle('expanded')">
                <div class="student-info">{{ $student->full_name }}</div>
                <div class="student-meta">LRN: {{ $student->lrn }} &bull; Grade {{ $student->grade_level }} &bull; {{ $student->total_claims ?? 0 }} claims &bull; {{ number_format($totalPointsUsed) }} pts used</div>
                <span class="claims-toggle">View claims</span>
            </div>
            <div class="claims-detail" data-student-id="{{ $student->id }}">
                @if($claims->isEmpty())
                    <div class="no-claims">No claim history.</div>
                @else
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Item Claimed</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Points Used</th>
                            <th scope="col">Date Claimed</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($claims as $c)
                        <tr>
                            <td style="font-weight:500;color:#111827;">{{ $c->item_name }}</td>
                            <td>{{ $c->quantity }}</td>
                            <td style="color:#d97706;font-weight:600;">{{ number_format($c->points_deducted ?? 0) }}</td>
                            <td style="color:#6b7280;font-size:11px;">{{ $c->claim_date ? \Carbon\Carbon::parse($c->claim_date)->format('M d, Y') : 'N/A' }}</td>
                            <td style="color:#6b7280;font-size:11px;text-transform:uppercase;">{{ $c->status ?? 'Pending' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
        @endforeach
    @endif

    <div class="footer">
        EcoCollect Report &mdash; {{ $title }} &mdash; {{ now()->format('Y') }}
    </div>

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 300); };
    </script>
</body>
</html>
