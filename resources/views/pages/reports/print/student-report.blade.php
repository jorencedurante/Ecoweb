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
        .bottles { color: #059669; font-weight: 600; }

        .no-data { text-align: center; padding: 40px; color: #94a3b8; font-size: 14px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; }

        @media print {
            @page { size: landscape; margin: 15mm; }
            body { padding: 0; }
            .report-header { margin-bottom: 20px; padding-bottom: 15px; }
            tr:hover { background: transparent; }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>📊 {{ $title }}</h1>
        <div class="subtitle">EcoCollect Environmental Achievement Tracking System</div>
        <div class="generated">Generated: {{ now()->format('F j, Y \a\t g:i A') }} | {{ $students->count() }} student(s)</div>
    </div>

    @if($students->isEmpty())
        <div class="no-data">No student data available.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Student Name</th>
                    <th scope="col">Grade Level</th>
                    <th scope="col">Gender</th>
                    @if($isTeacher)
                        <th scope="col">Total Points</th>
                        <th scope="col">Bottles Collected</th>
                        <th scope="col">Total Claims</th>
                    @else
                        <th scope="col">Bottles Collected</th>
                        <th scope="col">Total Points</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($students as $index => $student)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $student->full_name }}</td>
                        <td>{{ $student->grade_level }}</td>
                        <td>{{ $student->gender }}</td>
                        @if($isTeacher)
                            <td class="points">{{ number_format($student->total_points) }}</td>
                            <td class="bottles">{{ number_format($student->total_bottles ?? 0) }}</td>
                            <td>{{ $student->total_claims ?? 0 }}</td>
                        @else
                            <td class="bottles">{{ number_format($student->bottles_collected ?? 0) }}</td>
                            <td class="points">{{ number_format($student->total_points) }}</td>
                        @endif
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
