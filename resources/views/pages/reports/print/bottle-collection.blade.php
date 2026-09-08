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

        .bottles { font-weight: 700; color: #059669; }
        .summary { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .summary-card { flex: 1; min-width: 150px; padding: 15px; border-radius: 8px; background: #f0fdfa; border: 1px solid #ccfbf1; }
        .summary-card .label { font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .summary-card .value { font-size: 22px; font-weight: 700; color: #0f766e; }

        .no-data { text-align: center; padding: 40px; color: #94a3b8; font-size: 14px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; }

        @media print {
            @page { size: landscape; margin: 15mm; }
            body { padding: 0; }
            .report-header { margin-bottom: 20px; padding-bottom: 15px; }
            .summary { gap: 12px; }
            tr:hover { background: transparent; }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>🧴 {{ $title }}</h1>
        <div class="subtitle">EcoCollect Environmental Achievement Tracking System</div>
        <div class="generated">Generated: {{ now()->format('F j, Y \a\t g:i A') }} | {{ $collections->count() }} record(s)</div>
    </div>

    @php
        $dailyTotal = $collections->filter(fn($c) => $c->collection_date && $c->collection_date->isToday())->sum('bottle_count');
        $totalBottles = $collections->sum('bottle_count');
    @endphp

    <div class="summary">
        <div class="summary-card">
            <div class="label">Total Bottles</div>
            <div class="value">{{ number_format($totalBottles) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Records</div>
            <div class="value">{{ number_format($collections->count()) }}</div>
        </div>
    </div>

    @if($collections->isEmpty())
        <div class="no-data">No bottle collection data available.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Student Name</th>
                    <th scope="col">Bottle Count</th>
                    <th scope="col">Collection Date</th>
                    <th scope="col">Collection Time</th>
                    <th scope="col">Points Earned</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collections as $index => $collection)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $collection->student->full_name ?? 'N/A' }}</td>
                        <td class="bottles">{{ $collection->bottle_count }}</td>
                        <td>{{ $collection->collection_date ? $collection->collection_date->format('M d, Y') : 'N/A' }}</td>
                        <td>{{ $collection->collection_time ?? 'N/A' }}</td>
                        <td>{{ number_format($collection->bottle_count * 10) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        EcoCollect Report &mdash; {{ $title }} &mdash; {{ now()->format('Y') }}
    </div>

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 300); };
    </script>
</body>
</html>
