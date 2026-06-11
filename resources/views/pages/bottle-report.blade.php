@extends('layouts.admin')

@section('title', 'EcoCollect - Bottle Collection Report')
@section('page-title', 'Bottle Collection Report')
@section('page-subtitle', 'Generate and manage reports')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <div class="table-header" style="background:var(--card-bg);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:20px;padding:16px 20px;">
        <div class="table-header-left">
            <form method="GET" action="{{ route('admin.bottle-report') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <div class="dropdown-group">
                    <select name="day">
                        <option value="">Day</option>
                        @for($i = 1; $i <= 31; $i++)
                            <option value="{{ $i }}" {{ request('day') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    <select name="month">
                        <option value="">Month</option>
                        @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                    <select name="year">
                        <option value="">Year</option>
                        @foreach(['2023','2024','2025','2026'] as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <button class="filter-btn" type="submit">🔽 Filter</button>
                <button class="filter-btn" type="button" onclick="window.location='{{ route('admin.bottle-report') }}'">Clear</button>
            </form>
        </div>
    </div>

    <div class="summary-cards">
        <div class="stat-card">
            <div class="stat-icon blue">📅</div>
            <div class="stat-info">
                <div class="stat-value">{{ $dailyTotal }}</div>
                <div class="stat-label">Daily</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📆</div>
            <div class="stat-info">
                <div class="stat-value">{{ $weeklyTotal }}</div>
                <div class="stat-label">Weekly</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">📊</div>
            <div class="stat-info">
                <div class="stat-value">{{ $monthlyTotal }}</div>
                <div class="stat-label">Monthly</div>
            </div>
        </div>
    </div>

    <div class="chart-card" style="margin-bottom:24px;">
        <h4>Collection Trend</h4>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;height:160px;padding:0 10px;gap:4px;">
            @php
                $trendData = \App\Models\BottleCollection::selectRaw('DAYNAME(collection_date) as day, SUM(bottle_count) as total')
                    ->where('collection_date', '>=', now()->subDays(6))
                    ->groupBy('day', 'collection_date')
                    ->orderBy('collection_date')
                    ->get()->keyBy('day');
                $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            @endphp
            @foreach($days as $day)
                @php $h = max(20, ($trendData[$day]->total ?? 0) * 3); @endphp
                <div style="flex:1;background:var(--blue);height:{{ min($h, 160) }}px;border-radius:4px 4px 0 0;"></div>
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
