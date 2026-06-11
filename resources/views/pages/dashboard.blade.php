@extends('layouts.admin')

@section('title', 'EcoCollect - Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Overview of waste collection statistics')

@section('content')
    <!-- Stat Cards -->
    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-icon green">👥</div>
            <div class="stat-info">
                <div class="stat-value">156</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">🧴</div>
            <div class="stat-info">
                <div class="stat-value">1,000</div>
                <div class="stat-label">Total Bottles Collected</div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-row">
        <div class="chart-card">
            <h4>Gender Distribution</h4>
            <div class="donut-placeholder">
                <div class="donut-inner">
                    <span>75%</span>
                    <span style="font-size:10px;color:#999;">Male</span>
                </div>
            </div>
            <div class="donut-legend">
                <div class="legend-item"><span class="legend-dot" style="background:var(--green)"></span> Male (75%)</div>
                <div class="legend-item"><span class="legend-dot" style="background:#E0E0E0"></span> Female (25%)</div>
            </div>
        </div>
        <div class="chart-card">
            <h4>Bottle Collection Overview</h4>
            <div class="chart-placeholder">
                <div class="bar-group">
                    <div class="bar" style="height:80px"></div>
                    <span class="bar-label">Mon</span>
                </div>
                <div class="bar-group">
                    <div class="bar" style="height:60px"></div>
                    <span class="bar-label">Tue</span>
                </div>
                <div class="bar-group">
                    <div class="bar" style="height:100px"></div>
                    <span class="bar-label">Wed</span>
                </div>
                <div class="bar-group">
                    <div class="bar" style="height:70px"></div>
                    <span class="bar-label">Thu</span>
                </div>
                <div class="bar-group">
                    <div class="bar" style="height:90px"></div>
                    <span class="bar-label">Fri</span>
                </div>
            </div>
            <!-- TODO: Replace with Chart.js or similar library for dynamic charts -->
        </div>
    </div>

    <a href="{{ route('admin.reports') }}" class="btn btn-primary">View Reports</a>
@endsection
