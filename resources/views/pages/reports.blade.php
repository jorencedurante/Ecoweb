@extends('layouts.admin')

@section('title', 'EcoCollect - Reports')
@section('page-title', 'Reports')
@section('page-subtitle', 'Generate and manage reports')

@section('content')
    <!-- Report Cards -->
    <div class="report-cards">
        <div class="report-card">
            <div class="report-icon" style="background:rgba(0,200,83,0.12);color:var(--green);">👥</div>
            <h4>Student's Report</h4>
            <p>View detailed student performance and collection activity reports.</p>
            <button class="btn btn-primary btn-sm" onclick="viewReport('{{ route('admin.student-report') }}')">View</button>
        </div>
        <div class="report-card">
            <div class="report-icon" style="background:rgba(0,174,239,0.12);color:var(--blue);">🧴</div>
            <h4>Bottle Collection Report</h4>
            <p>Track bottle collection data with daily, weekly, and monthly summaries.</p>
            <button class="btn btn-primary btn-sm" onclick="viewReport('{{ route('admin.bottle-report') }}')">View</button>
        </div>
        <div class="report-card">
            <div class="report-icon" style="background:rgba(255,193,7,0.12);color:var(--yellow);">🔐</div>
            <h4>Admin Activities</h4>
            <p>Monitor admin and teacher account activities and system changes.</p>
            <button class="btn btn-primary btn-sm" onclick="viewReport('{{ route('admin.admin-activities') }}')">View</button>
        </div>
    </div>

    <!-- Charts Row -->
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
            <h4>Collection Overview</h4>
            <div class="chart-placeholder">
                <div class="bar-group"><div class="bar" style="height:80px"></div><span class="bar-label">Mon</span></div>
                <div class="bar-group"><div class="bar" style="height:60px"></div><span class="bar-label">Tue</span></div>
                <div class="bar-group"><div class="bar" style="height:100px"></div><span class="bar-label">Wed</span></div>
                <div class="bar-group"><div class="bar" style="height:70px"></div><span class="bar-label">Thu</span></div>
                <div class="bar-group"><div class="bar" style="height:90px"></div><span class="bar-label">Fri</span></div>
            </div>
        </div>
    </div>

    <!-- Student List Preview -->
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
                <!-- TODO: Fetch student records from database -->
                <tr><td>1</td><td>Jerence C. Tabadero</td><td>Grade 4</td><td>48</td></tr>
                <tr><td>2</td><td>Denver P. Tabadero</td><td>Grade 2</td><td>42</td></tr>
                <tr><td>3</td><td>Kathleen E. Tabadero</td><td>Grade 6</td><td>40</td></tr>
                <tr><td>4</td><td>Karen N. Tabadero</td><td>Grade 6</td><td>38</td></tr>
                <tr><td>5</td><td>Joy O. Tabadero</td><td>Grade 5</td><td>35</td></tr>
            </tbody>
        </table>
    </div>
@endsection
