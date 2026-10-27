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
        body { font-family: 'Inter', sans-serif; padding: 0; color: #1a1a2e; background: #fff; font-size: 12px; }

        .report-header { text-align: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #0f766e; }
        .report-header h1 { font-size: 18px; color: #0f766e; margin-bottom: 3px; }
        .report-header .subtitle { font-size: 11px; color: #64748b; }
        .report-header .generated { font-size: 10px; color: #94a3b8; margin-top: 4px; }
        .report-header .filters { font-size: 10px; color: #64748b; margin-top: 6px; }

        .teacher-section { margin-bottom: 18px; page-break-inside: auto; }
        .teacher-name { font-size: 14px; font-weight: 700; color: #0f766e; margin-bottom: 8px; padding: 6px 10px; background: #f0fdfa; border-left: 3px solid #0f766e; page-break-after: avoid; }

        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        thead { display: table-header-group; }
        th { background: #0f766e; color: #fff; padding: 6px 8px; text-align: left; font-weight: 600; font-size: 10px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr { page-break-inside: avoid; }
        tr:nth-child(even) { background: #f8fafc; }

        .points { font-weight: 700; color: #d97706; }
        .bottles { color: #059669; font-weight: 600; }
        .claims { color: #7c3aed; font-weight: 600; }

        .no-data { text-align: center; padding: 24px; color: #94a3b8; font-size: 13px; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; }

        @media print {
            @page { size: A4 landscape; margin: 12mm 10mm; }
            body { padding: 0; }
            .report-header { margin-bottom: 12px; padding-bottom: 10px; }
            tr:hover { background: transparent; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>📈 {{ $title }}</h1>
        <div class="subtitle">EcoCollect Environmental Achievement Tracking System</div>
        <div class="generated">Generated: {{ now()->format('F j, Y \a\t g:i A') }}</div>
        @if($teacher_id = request('teacher_id'))
        <div class="filters">Teacher: {{ $teacherGroups[0]['teacher']->name ?? '' }} @if($grade_level) · Grade: {{ $grade_level }} @endif @if($date_from || $date_to) · Date: {{ $date_from ?? '' }} to {{ $date_to ?? '' }} @endif</div>
        @endif
    </div>

    <!-- All Teachers Summary Table -->
    <div class="summary-table-section" style="margin-bottom:24px; page-break-inside: avoid;">
        <div class="summary-header" style="font-size:14px;font-weight:700;color:#0f766e;margin-bottom:12px;">All Teachers Summary Report</div>
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#0f766e;">
                    <th scope="col" style="padding:8px 10px;color:#fff;font-weight:600;text-align:left;font-size:10px;text-transform:uppercase;">Teacher</th>
                    <th scope="col" style="padding:8px 10px;color:#fff;font-weight:600;text-align:left;font-size:10px;text-transform:uppercase;">Students</th>
                    <th scope="col" style="padding:8px 10px;color:#fff;font-weight:600;text-align:left;font-size:10px;text-transform:uppercase;">Student Claimed</th>
                    <th scope="col" style="padding:8px 10px;color:#fff;font-weight:600;text-align:left;font-size:10px;text-transform:uppercase;">Bottles</th>
                    <th scope="col" style="padding:8px 10px;color:#fff;font-weight:600;text-align:left;font-size:10px;text-transform:uppercase;">Points</th>
                    <th scope="col" style="padding:8px 10px;color:#fff;font-weight:600;text-align:left;font-size:10px;text-transform:uppercase;">Claims</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teacherGroups as $group)
                <tr>
                    <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;font-weight:600;color:#111827;">{{ $group['teacher']->name }}</td>
                    <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;">{{ $group['student_count'] }}</td>
                    <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;">{{ $group['student_claimed'] }}</td>
                    <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;color:#059669;font-weight:600;">{{ number_format($group['total_bottles']) }}</td>
                    <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;color:#d97706;font-weight:600;">{{ number_format($group['total_points']) }}</td>
                    <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;color:#7c3aed;font-weight:600;">{{ $group['total_claims'] }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">No teacher data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(empty($teacherGroups))
        <div class="no-data">No student activity data available.</div>
    @else
        @foreach($teacherGroups as $group)
            <div class="teacher-section">
                <div class="teacher-name">👨‍🏫 {{ $group['teacher']->name }} ({{ $group['students']->count() }} students)</div>
                @if($group['students']->isEmpty())
                    <div class="no-data">No students assigned to this teacher.</div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">Grade</th>
                                <th scope="col">Total Points</th>
                                <th scope="col">Bottles</th>
                                <th scope="col">Claims</th>
                                <th scope="col">Latest Collection</th>
                                <th scope="col">Latest Claim</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['students'] as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->full_name }}</td>
                                    <td>{{ $student->grade_level }}</td>
                                    <td class="points">{{ number_format($student->total_points) }}</td>
                                    <td class="bottles">{{ number_format($student->total_bottles ?? 0) }}</td>
                                    <td class="claims">{{ $student->total_claims ?? 0 }}</td>
                                    <td>{{ $student->latest_collection_date ? \Carbon\Carbon::parse($student->latest_collection_date)->format('M d, Y') : 'N/A' }}</td>
                                    <td>{{ $student->latest_claim_date ? \Carbon\Carbon::parse($student->latest_claim_date)->format('M d, Y') : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
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
