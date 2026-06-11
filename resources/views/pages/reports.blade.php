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
                <div class="stat-label">Total Awards</div>
            </div>
        </div>
    </div>

    <div class="report-cards">
        <div class="report-card">
            <div class="report-icon" style="background:rgba(0,200,83,0.12);color:var(--green);">👥</div>
            <h4>Student's Report</h4>
            <p>View detailed student performance and collection activity reports.</p>
            <a href="{{ route('admin.student-report') }}" class="btn btn-primary btn-sm">View</a>
        </div>
        <div class="report-card">
            <div class="report-icon" style="background:rgba(0,174,239,0.12);color:var(--blue);">🧴</div>
            <h4>Bottle Collection Report</h4>
            <p>Track bottle collection data with daily, weekly, and monthly summaries.</p>
            <a href="{{ route('admin.bottle-report') }}" class="btn btn-primary btn-sm">View</a>
        </div>
        <div class="report-card">
            <div class="report-icon" style="background:rgba(255,193,7,0.12);color:var(--yellow);">🔐</div>
            <h4>Admin Activities</h4>
            <p>Monitor admin and teacher account activities and system changes.</p>
            <a href="{{ route('admin.admin-activities') }}" class="btn btn-primary btn-sm">View</a>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-card">
            <h4>Gender Distribution</h4>
            @php
                $total = $femaleCount + $maleCount;
                $femalePct = $total > 0 ? round($femaleCount / $total * 100) : 0;
                $malePct = $total > 0 ? round($maleCount / $total * 100) : 0;
            @endphp
            <div class="donut-placeholder" style="background: conic-gradient(var(--green) 0% {{ $malePct }}%, #E0E0E0 {{ $malePct }}% 100%);">
                <div class="donut-inner">
                    <span>{{ $malePct }}%</span>
                    <span style="font-size:10px;color:#999;">Male</span>
                </div>
            </div>
            <div class="donut-legend">
                <div class="legend-item"><span class="legend-dot" style="background:var(--green)"></span> Male ({{ $malePct }}%)</div>
                <div class="legend-item"><span class="legend-dot" style="background:#E0E0E0"></span> Female ({{ $femalePct }}%)</div>
            </div>
        </div>
        <div class="chart-card">
            <h4>Collection Overview</h4>
            <div class="chart-placeholder">
                @foreach(['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri'] as $day => $label)
                    <div class="bar-group">
                        <div class="bar" style="height:{{ rand(40, 120) }}px"></div>
                        <span class="bar-label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h4 style="font-size:14px;font-weight:600;">Top Students</h4>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>Bottles Collected</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topStudents as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $s->full_name }}</td>
                    <td>{{ $s->grade_level }}</td>
                    <td>{{ $s->bottle_collections_sum_bottle_count ?? $s->total_points }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
