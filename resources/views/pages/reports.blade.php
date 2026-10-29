@extends('layouts.admin')

@section('title', 'EcoCollect - Reports')
@section('page-title', 'Reports')
@section('page-subtitle', 'Generate and manage reports')

@section('content')
    <div class="stat-cards" style="margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon green">👥</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalStudents }}</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">🧴</div>
            <div class="stat-info">
                <div class="stat-value">{{ number_format($totalBottles) }}</div>
                <div class="stat-label">Total Bottles</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">🏆</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalAwards }}</div>
                <div class="stat-label">Total Certificate</div>
            </div>
        </div>
    </div>

    <div class="report-cards">
        <div class="report-card">
            <div class="report-icon" style="background:rgba(0,200,83,0.12);color:var(--green);">👥</div>
            @if(auth()->user()->isTeacher())
            <h4>Student Ranking</h4>
            <p>Ranking of students in your assigned class based on points and bottle collections.</p>
            @else
            <h4>Student's Report</h4>
            <p>View detailed student performance and collection activity reports.</p>
            @endif
            <a href="{{ route('admin.student-report') }}" class="btn btn-primary btn-sm" aria-label="View student report">View</a>
        </div>
        <div class="report-card">
            <div class="report-icon" style="background:rgba(0,174,239,0.12);color:var(--blue);">🧴</div>
            <h4>Bottle Collection Report</h4>
            <p>Track bottle collection data with daily, weekly, and monthly summaries.</p>
            <a href="{{ route('admin.bottle-report') }}" class="btn btn-primary btn-sm" aria-label="View bottle collection report">View</a>
        </div>
        <div class="report-card">
            <div class="report-icon" style="background:rgba(255,193,7,0.12);color:var(--yellow);">🔐</div>
            @if(auth()->user()->isTeacher())
            <h4>Item Claims</h4>
            <p>Summary of item claims made by students in your assigned class.</p>
            @else
            <h4>Student Activities</h4>
            <p>Summary of student activity and ranking grouped by teacher.</p>
            @endif
            <a href="{{ route('admin.admin-activities') }}" class="btn btn-primary btn-sm" aria-label="View student activities report">View</a>
        </div>
    </div>

    <div class="charts-row">
        @if(auth()->user()->isAdminLevel())
        <div class="chart-card">
            <h4>Gender Contribution</h4>
            @php
                $totalContributors = $genderContribution['total'] ?? 0;
                $femalePct = $genderContribution['femalePct'] ?? 0;
                $malePct = $genderContribution['malePct'] ?? 0;
            @endphp
            @if($totalContributors > 0)
            <div class="donut-placeholder" style="background: conic-gradient(var(--green) 0% {{ $malePct }}%, #E0E0E0 {{ $malePct }}% 100%);">
                <div class="donut-inner">
                    <span>{{ $totalContributors }}</span>
                    <span style="font-size:10px;color:#6b7280;">contributors</span>
                </div>
            </div>
            <div class="donut-legend">
                <div class="legend-item"><span class="legend-dot" style="background:var(--green)"></span> Female ({{ $genderContribution['female'] }} · {{ $femalePct }}%)</div>
                <div class="legend-item"><span class="legend-dot" style="background:var(--blue)"></span> Male ({{ $genderContribution['male'] }} · {{ $malePct }}%)</div>
            </div>
            @else
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                <div style="font-size:32px;margin-bottom:8px;">👥</div>
                <div>No contributors this quarter</div>
            </div>
            @endif
        </div>
        <div class="chart-card">
            <h4>Quarterly Bottle Collections</h4>
            <div class="chart-placeholder">
                @php
                    $maxBottles = 0;
                    foreach ($quarterlyCollections as $qc) {
                        if ($qc['bottles'] > $maxBottles) {
                            $maxBottles = $qc['bottles'];
                        }
                    }
                @endphp
                @foreach($quarterlyCollections as $qc)
                @php
                    $barHeight = $maxBottles > 0 ? max(20, round($qc['bottles'] / $maxBottles * 160)) : 20;
                @endphp
                <div class="bar-group">
                    <div class="bar" style="height:{{ $barHeight }}px" title="{{ $qc['quarter'] }}: {{ number_format($qc['bottles']) }} bottles"></div>
                    <span class="bar-label">{{ $qc['quarter'] }}</span>
                    <span class="bar-label" style="font-size:10px;margin-top:2px;">{{ number_format($qc['bottles']) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

        <div class="table-container">
        <div class="table-header">
            <h4 style="font-size:14px;font-weight:600;">Top Students</h4>
            <label class="ts-filter-label" style="font-size:12px;color:var(--text-medium);margin-right:6px;">Quarter:</label>
            <select id="tsQuarter" class="ts-filter" style="font-size:12px;padding:4px 8px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fff;color:var(--text-dark);">
                <option value="1">Q1</option>
                <option value="2">Q2</option>
                <option value="3">Q3</option>
                <option value="4">Q4</option>
            </select>
            <label class="ts-filter-label" style="font-size:12px;color:var(--text-medium);margin-left:8px;margin-right:6px;">Year:</label>
            <select id="tsYear" class="ts-filter" style="font-size:12px;padding:4px 8px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fff;color:var(--text-dark);">
                @php
                    $currentYear = now()->year;
                    for ($y = $currentYear - 5; $y <= $currentYear + 1; $y++):
                        $selected = $y == $currentYear ? 'selected' : '';
                        echo "<option value=\"$y\" $selected>$y</option>";
                    endfor;
                @endphp
            </select>
            <a href="#" id="tsPrintBtn" class="btn btn-filter" style="font-size:12px;padding:6px 14px;text-decoration:none;margin-left:10px;" aria-label="Print top students report">🖨️ Print Report</a>
        </div>
        <div class="table-responsive">
        <table id="tsTable">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Grade</th>
                    <th scope="col">Bottles This Quarter</th>
                </tr>
            </thead>
            <tbody id="tsTableBody">
                @if($topStudents->isEmpty())
                <tr><td colspan="4" style="text-align:center;padding:30px;color:#94a3b8;">No students have collected bottles this quarter.</td></tr>
                @else
                @foreach($topStudents as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $s->full_name }}</td>
                    <td>{{ $s->grade_level }}</td>
                    <td>{{ number_format($s->quarter_bottles ?? 0) }}</td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>
        </div>
    </div>

    <script>
        (function() {
            const quarterSelect = document.getElementById('tsQuarter');
            const yearSelect = document.getElementById('tsYear');
            const printBtn = document.getElementById('tsPrintBtn');
            const tableBody = document.getElementById('tsTableBody');

            function updatePrintUrl() {
                const quarter = quarterSelect.value;
                const year = yearSelect.value;
                const url = "{{ route('reports.top-students.print') }}?quarter=" + quarter + "&year=" + year;
                printBtn.href = url;
            }

            function loadTopStudents() {
                const quarter = quarterSelect.value;
                const year = yearSelect.value;
                const url = "{{ route('reports.top-students.data') }}?quarter=" + quarter + "&year=" + year;

                fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:30px;color:#94a3b8;">Failed to load data.</td></tr>';
                        return;
                    }

                    updatePrintUrl();

                    if (data.students.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:30px;color:#94a3b8;">No students have collected bottles this quarter.</td></tr>';
                        return;
                    }

                    let html = '';
                    data.students.forEach((s, i) => {
                        html += `
                            <tr>
                                <td>${i + 1}</td>
                                <td>${s.full_name}</td>
                                <td>${s.grade_level}</td>
                                <td>${Number(s.quarter_bottles).toLocaleString()}</td>
                            </tr>
                        `;
                    });
                    tableBody.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading top students:', error);
                    tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:30px;color:#94a3b8;">Failed to load data.</td></tr>';
                });
            }

            quarterSelect.addEventListener('change', loadTopStudents);
            yearSelect.addEventListener('change', loadTopStudents);

            // Initialize print URL on load
            updatePrintUrl();
        })();
    </script>
@endsection
