@extends('layouts.admin')

@section('title', 'EcoCollect - Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Overview of waste collection statistics')

@section('content')
    <div class="stat-cards">
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
                <div class="stat-label">Total Bottles Collected</div>
            </div>
        </div>
        @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
        <div class="stat-card">
            <div class="stat-icon yellow">👨‍🏫</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalTeachers }}</div>
                <div class="stat-label">Total Teachers</div>
            </div>
        </div>
        @endif
        <div class="stat-card">
            <div class="stat-icon purple">🏆</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalAwards }}</div>
                <div class="stat-label">Certificates / Awards</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">🎁</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalClaimedItems }}</div>
                <div class="stat-label">Total Claimed Items</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">💳</div>
            <div class="stat-info">
                <div class="stat-value">{{ number_format($totalPointsRedeemed) }}</div>
                <div class="stat-label">Total Points Redeemed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📦</div>
            <div class="stat-info">
                <div class="stat-value">{{ $availableClaimItems }}</div>
                <div class="stat-label">Available Claim Items</div>
            </div>
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
            <h4>Bottle Collection Overview</h4>
            <div class="chart-placeholder">
                @foreach(['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri'] as $day => $label)
                    @php $height = max(20, ($collectionData[$day] ?? 0) * 2); @endphp
                    <div class="bar-group">
                        <div class="bar" style="height:{{ min($height, 160) }}px"></div>
                        <span class="bar-label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <a href="{{ route('admin.reports') }}" class="btn btn-primary">View Reports</a>
@endsection
