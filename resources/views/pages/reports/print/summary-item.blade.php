<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary Item Report - EcoCollect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            color: #000;
            padding: 24px;
            font-size: 12px;
        }
        .report-header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .report-header h1 {
            font-size: 18px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .report-meta {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            font-size: 11px;
        }
        .report-meta span { white-space: nowrap; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        thead { display: table-header-group; }
        th {
            background: #f3f4f6;
            color: #111827;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 9px 12px;
            border: 1px solid #d1d5db;
        }
        th.qty, td.qty { text-align: right; }
        td {
            padding: 9px 12px;
            border: 1px solid #e5e7eb;
            color: #000;
        }
        tbody tr { break-inside: avoid; page-break-inside: avoid; }
        .total-row {
            text-align: right;
            font-weight: bold;
            padding: 10px 12px;
            border-top: 2px solid #111827;
            font-size: 13px;
        }
        .empty {
            text-align: center;
            padding: 28px 12px;
            color: #000;
        }
        .report-footer {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            text-align: center;
        }
        @page { size: A4 portrait; margin: 16mm; }
        @media print {
            body { padding: 0; }
            .report-footer { margin-top: 16px; }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>SUMMARY ITEM REPORT</h1>
        <div class="report-meta">
            <span>Teacher: {{ $teacherName }}</span>
            <span>Generated: {{ $generatedAt }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th class="qty">Total Quantity</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summaryItems as $summaryItem)
                <tr>
                    <td>{{ $summaryItem->item_name }}</td>
                    <td class="qty">{{ (int) $summaryItem->total_quantity }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="empty">No approved items claimed yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-row">Total Items Claimed: {{ (int) $summaryTotal }}</div>

    <div class="report-footer">EcoCollect — Approved item claims summary</div>
</body>
</html>
