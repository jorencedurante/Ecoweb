@extends('layouts.admin')

@section('title', 'EcoCollect - Bottle Collection Report')
@section('page-title', 'Bottle Collection Report')
@section('page-subtitle', 'Generate and manage reports')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <div class="table-header" style="background:var(--card-bg);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:20px;padding:16px 20px;">
        <div class="table-header-left">
            <div class="dropdown-group">
                <select>
                    <option>Day</option>
                    @for($i = 1; $i <= 31; $i++)
                        <option>{{ $i }}</option>
                    @endfor
                </select>
                <select>
                    <option>Month</option>
                    <option>January</option>
                    <option>February</option>
                    <option>March</option>
                    <option>April</option>
                    <option>May</option>
                    <option>June</option>
                    <option>July</option>
                    <option>August</option>
                    <option>September</option>
                    <option>October</option>
                    <option>November</option>
                    <option>December</option>
                </select>
                <select>
                    <option>Year</option>
                    <option>2023</option>
                    <option>2024</option>
                    <option selected>2025</option>
                    <option>2026</option>
                </select>
            </div>
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" placeholder="Search...">
            </div>
            <button class="filter-btn">🔽 Filter</button>
        </div>
    </div>

    <div class="summary-cards">
        <div class="stat-card">
            <div class="stat-icon blue">📅</div>
            <div class="stat-info">
                <div class="stat-value">10</div>
                <div class="stat-label">Daily</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📆</div>
            <div class="stat-info">
                <div class="stat-value">100</div>
                <div class="stat-label">Weekly</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">📊</div>
            <div class="stat-info">
                <div class="stat-value">110</div>
                <div class="stat-label">Monthly</div>
            </div>
        </div>
    </div>

    <!-- Line Chart Placeholder -->
    <div class="chart-card" style="margin-bottom:24px;">
        <h4>Collection Trend</h4>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;height:160px;padding:0 10px;gap:4px;">
            <!-- TODO: Replace with Chart.js line chart -->
            <div style="flex:1;background:var(--blue);height:40px;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--blue);height:70px;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--blue);height:100px;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--blue);height:65px;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--blue);height:120px;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--blue);height:90px;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--blue);height:110px;border-radius:4px 4px 0 0;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 10px 0;font-size:11px;color:var(--text-medium);">
            <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Total Bottles Collected</th>
                </tr>
            </thead>
            <tbody>
                <!-- TODO: Fetch collection records from database -->
                <tr><td>1</td><td>Kathleen E. Tabadero</td><td>2025-01-06</td><td>5</td></tr>
                <tr><td>2</td><td>Joy O. Tabadero</td><td>2025-01-06</td><td>3</td></tr>
                <tr><td>3</td><td>Jerence C. Tabadero</td><td>2025-01-06</td><td>7</td></tr>
                <tr><td>4</td><td>Patricia R. Tabadero</td><td>2025-01-07</td><td>4</td></tr>
                <tr><td>5</td><td>Denver P. Tabadero</td><td>2025-01-07</td><td>6</td></tr>
                <tr><td>6</td><td>Karen N. Tabadero</td><td>2025-01-08</td><td>8</td></tr>
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing 1 to 6 of 6 entries</span>
            <div class="page-btns">
                <button class="page-btn active">1</button>
            </div>
        </div>
    </div>
@endsection
