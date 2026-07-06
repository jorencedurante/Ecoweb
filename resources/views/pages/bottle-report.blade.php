@extends('layouts.admin')

@section('title', 'EcoCollect - Bottle Collection Report')
@section('page-title', 'Bottle Collection Report')
@section('page-subtitle', 'Generate and manage reports')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <div class="filter-card">
        <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
            <i class="fas fa-filter"></i> Filters
        </div>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.bottle-report') }}" class="filter-form">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <div class="filter-search">
                    <label>Day</label>
                    <select name="day">
                        <option value="">Day</option>
                        @for($i = 1; $i <= 31; $i++)
                            <option value="{{ $i }}" {{ request('day') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="filter-search">
                    <label>Month</label>
                    <select name="month">
                        <option value="">Month</option>
                        @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Year</label>
                    <select name="year">
                        <option value="">Year</option>
                        @foreach(['2023','2024','2025','2026'] as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Quarter</label>
                    <select name="quarter">
                        <option value="">Quarter</option>
                        <option value="q1" {{ request('quarter') == 'q1' ? 'selected' : '' }}>Q1 (Jan–Mar)</option>
                        <option value="q2" {{ request('quarter') == 'q2' ? 'selected' : '' }}>Q2 (Apr–Jun)</option>
                        <option value="q3" {{ request('quarter') == 'q3' ? 'selected' : '' }}>Q3 (Jul–Sep)</option>
                        <option value="q4" {{ request('quarter') == 'q4' ? 'selected' : '' }}>Q4 (Oct–Dec)</option>
                        <option value="current" {{ request('quarter') == 'current' ? 'selected' : '' }}>Current</option>
                        <option value="previous" {{ request('quarter') == 'previous' ? 'selected' : '' }}>Previous</option>
                    </select>
                </div>
                <div class="filter-controls">
                    <button class="btn btn-filter" type="submit">Filter</button>
                    <a href="{{ route('admin.bottle-report') }}" class="btn btn-reset">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="summary-cards">
        <div class="stat-card">
            <div class="stat-icon blue">📅</div>
            <div class="stat-info">
                <div class="stat-value">{{ $dailyTotal }}</div>
                <div class="stat-label">Today</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📆</div>
            <div class="stat-info">
                <div class="stat-value">{{ $weeklyTotal }}</div>
                <div class="stat-label">This Week</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">📊</div>
            <div class="stat-info">
                <div class="stat-value">{{ $monthlyTotal }}</div>
                <div class="stat-label">This Month</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📦</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalBottles ?? 0 }}</div>
                <div class="stat-label">Total Collected</div>
            </div>
        </div>
    </div>

    <div class="chart-card" style="margin-bottom:24px;">
        <h4>Bottles Collected — Last 7 Days</h4>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;height:160px;padding:0 10px;gap:4px;">
            @php
                $chartDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                $maxVal = max(1, max($chartData));
            @endphp
            @foreach($chartDays as $day)
                @php
                    $val = $chartData[$day] ?? 0;
                    $h = max(4, ($val / $maxVal) * 150);
                @endphp
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end;">
                    <span style="font-size:11px;font-weight:700;color:var(--text-dark);margin-bottom:4px;">{{ $val }}</span>
                    <div style="width:100%;background:var(--blue);height:{{ $h }}px;border-radius:4px 4px 0 0;min-height:4px;"></div>
                </div>
            @endforeach
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 10px 0;font-size:11px;color:var(--text-medium);">
            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                <span>{{ $d }}</span>
            @endforeach
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
                @forelse($collections as $i => $c)
                <tr>
                    <td>{{ $collections->firstItem() + $i }}</td>
                    <td>{{ $c->student->full_name ?? 'Unknown' }}</td>
                    <td>{{ $c->collection_date }}</td>
                    <td>{{ $c->bottle_count }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-light);">No collection records found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing {{ $collections->firstItem() ?? 0 }} to {{ $collections->lastItem() ?? 0 }} of {{ $collections->total() }} entries</span>
            <div class="page-btns">
                @for ($p = 1; $p <= $collections->lastPage(); $p++)
                    <a href="{{ $collections->url($p) }}" class="page-btn {{ $collections->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
                @endfor
            </div>
        </div>
    </div>
@endsection
