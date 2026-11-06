<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $student->full_name }} - Performance Report - EcoCollect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; padding: 30px; color: #1a1a2e; background: #fff; }

        .report-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #0f766e; }
        .report-header h1 { font-size: 20px; color: #0f766e; margin-bottom: 4px; }
        .report-header .subtitle { font-size: 13px; color: #64748b; }
        .report-header .generated { font-size: 11px; color: #94a3b8; margin-top: 6px; }

        .student-info { margin-bottom: 24px; }
        .student-info h2 { font-size: 16px; color: #0f766e; margin-bottom: 8px; }
        .student-info table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .student-info td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        .student-info td.label { font-weight: 600; color: #64748b; width: 140px; }

        .section { margin-bottom: 24px; }
        .section h3 { font-size: 14px; color: #0f766e; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 2px solid #0f766e; }

        table.data-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 16px; }
        table.data-table th { background: #0f766e; color: #fff; padding: 8px 10px; text-align: left; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.data-table td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
        table.data-table tr:nth-child(even) { background: #f8fafc; }

        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .summary-card { padding: 14px; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .summary-card .label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 4px; }
        .summary-card .value { font-size: 20px; font-weight: 700; color: #1a1a2e; }

        .no-data { text-align: center; padding: 30px; color: #94a3b8; font-size: 13px; }

        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; }

        @media print {
            @page { size: A4; margin: 15mm; }
            body { padding: 0; }
            .report-header { margin-bottom: 20px; padding-bottom: 15px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>📊 Student Performance Report</h1>
        <div class="subtitle">EcoCollect Environmental Achievement Tracking System</div>
        <div class="generated">Generated: {{ now()->format('F j, Y \a\t g:i A') }} | Student: {{ $student->full_name }}</div>
    </div>

    {{-- Student Information --}}
    <div class="student-info">
        <h2>Student Information</h2>
        <table>
            <tr><td class="label">Full Name</td><td>{{ $student->full_name }}</td></tr>
            <tr><td class="label">LRN</td><td>{{ $student->lrn }}</td></tr>
            <tr><td class="label">Student ID</td><td>{{ $student->student_id ?? 'N/A' }}</td></tr>
            <tr><td class="label">Grade Level</td><td>{{ $student->grade_level ?? 'N/A' }}</td></tr>
            <tr><td class="label">Section</td><td>{{ $enrollment ? $enrollment->section : 'N/A' }}</td></tr>
            <tr><td class="label">Gender</td><td>{{ $student->gender ?? 'N/A' }}</td></tr>
            <tr><td class="label">Birth Date</td><td>{{ $student->birth_date ? $student->birth_date->format('F j, Y') : 'N/A' }}</td></tr>
            <tr><td class="label">Assigned Teacher</td><td>{{ $enrollment && $enrollment->teacher ? $enrollment->teacher->name : 'N/A' }}</td></tr>
        </table>
    </div>

    {{-- Performance Summary --}}
    <div class="section">
        <h3>Performance Summary</h3>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Bottles Collected</div>
                <div class="value">{{ number_format($totalBottles) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Total Points Earned</div>
                <div class="value">{{ number_format($totalPoints) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Points Redeemed</div>
                <div class="value">{{ number_format($pointsRedeemed) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Available Points</div>
                <div class="value">{{ number_format($availablePoints) }}</div>
            </div>
        </div>
    </div>

    {{-- Bottle Collection History --}}
    <div class="section">
        <h3>Bottle Collection History</h3>
        @if($bottleCollections->isEmpty())
            <div class="no-data">No collection activity recorded.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Collection Date</th>
                        <th>Time</th>
                        <th>Bottles Collected</th>
                        <th>Points Earned</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bottleCollections as $c)
                        <tr>
                            <td>{{ $c->collection_date ? $c->collection_date->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $c->collection_time ?? '—' }}</td>
                            <td>{{ number_format($c->bottle_count ?? 0) }}</td>
                            <td>{{ number_format($c->points_earned ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Points and Rewards --}}
    <div class="section">
        <h3>Points and Rewards</h3>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Total Points Earned</div>
                <div class="value">{{ number_format($totalPoints) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Points Redeemed</div>
                <div class="value">{{ number_format($pointsRedeemed) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Available Points</div>
                <div class="value">{{ number_format($availablePoints) }}</div>
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Points Used</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @if($claims->isEmpty())
                    <tr><td colspan="5" style="text-align:center;padding:20px;color:#94a3b8;">No reward claims recorded.</td></tr>
                @else
                    @foreach($claims as $c)
                        <tr>
                            <td>{{ $c->item_name ?? 'N/A' }}</td>
                            <td>{{ $c->quantity ?? 0 }}</td>
                            <td>{{ number_format($c->points_deducted ?? 0) }}</td>
                            <td>{{ $c->claim_date ? $c->claim_date->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $c->status ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    {{-- Achievements Earned --}}
    <div class="section">
        <h3>Achievements Earned</h3>
        @if($achievements->isEmpty())
            <div class="no-data">No achievements earned yet.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Achievement</th>
                        <th>Description</th>
                        <th>Badge</th>
                        <th>Date Earned</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($achievements as $a)
                        <tr>
                            <td>{{ $a->quest->title ?? 'N/A' }}</td>
                            <td>{{ $a->quest->description ?? 'N/A' }}</td>
                            <td>{{ $a->quest->badge_name ?? 'N/A' }}</td>
                            <td>{{ $a->awarded_date ? $a->awarded_date->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Certificates Earned --}}
    <div class="section">
        <h3>Certificates Earned</h3>
        @if($certificates->isEmpty())
            <div class="no-data">No certificates earned yet.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Certificate Title</th>
                        <th>Description</th>
                        <th>Date Awarded</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($certificates as $c)
                        <tr>
                            <td>{{ $c->award_title ?? 'N/A' }}</td>
                            <td>{{ $c->award_description ?? 'N/A' }}</td>
                            <td>{{ $c->awarded_date ? $c->awarded_date->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $c->status ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        EcoCollect Report &mdash; Student Performance &mdash; {{ now()->format('Y') }}
    </div>

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 300); };
    </script>
</body>
</html>