<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary Item Report - EcoCollect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; padding: 30px; color: #1a1a2e; background: #fff; }

        .report-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #0f766e; }
        .report-header h1 { font-size: 22px; color: #0f766e; margin-bottom: 4px; }
        .report-header .subtitle { font-size: 13px; color: #64748b; }
        .report-header .generated { font-size: 11px; color: #94a3b8; margin-top: 6px; }

        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th {
            background: #f8fafc;
            color: #64748b;
            padding: 6px 10px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
        }
        td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #111827; }
        tr:nth-child(even) { background: #f8fafc; }
        tr:hover { background: #e0f7f5; }

        .summary-table th:first-child,
        .summary-table td:first-child { padding-left: 18%; }
        .summary-table th:nth-child(2),
        .summary-table td:nth-child(2) {
            text-align: left;
            padding-left: 3%;
            font-variant-numeric: tabular-nums;
        }
        .summary-total {
            margin-top: 14px;
            padding: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: right;
            font-size: 12px;
            font-weight: 700;
            color: #1a1a2e;
            padding-right: 24px;
        }
        .no-data { text-align: center; padding: 40px; color: #94a3b8; font-size: 14px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; }

        @media print {
            @page { size: A4 landscape; margin: 15mm; }
            body { padding: 0; }
            .report-header { margin-bottom: 20px; padding-bottom: 15px; }
            tr:hover { background: transparent; }
            .summary-table, .summary-total { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>🎁 Summary Item Report</h1>
        <div class="subtitle">EcoCollect Environmental Achievement Tracking System</div>
        <div class="generated">Teacher: {{ $teacherName }}</div>
        <div class="generated">Generated: {{ $generatedAt }}</div>
    </div>

    <table class="summary-table">
        <colgroup>
            <col style="width:55%;">
            <col style="width:45%;">
        </colgroup>
        <thead>
            <tr>
                <th scope="col">Item Name</th>
                <th scope="col">Total Quantity</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summaryItems as $summaryItem)
                <tr>
                    <td style="font-weight:500;color:#111827;">{{ $summaryItem->item_name }}</td>
                    <td>{{ (int) $summaryItem->total_quantity }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="no-data">No approved items claimed yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-total">Total Items Claimed: {{ (int) $summaryTotal }}</div>

    <div class="footer">
        EcoCollect Report &mdash; Summary Item Report &mdash; {{ now()->format('Y') }}
    </div>

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 300); };
    </script>
</body>
</html>
