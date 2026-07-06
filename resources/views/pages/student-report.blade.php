@extends('layouts.admin')

@section('title', 'EcoCollect - Student\'s Report')
@section('page-title', 'Student\'s Report')
@section('page-subtitle', 'Generate and manage reports')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <div class="filter-card">
        <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
            <i class="fas fa-filter"></i> Filters
        </div>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.student-report') }}" class="filter-form">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <div class="filter-search">
                    <label>Grade Level</label>
                    <select name="grade_level">
                        <option value="">Grade Level</option>
                        @foreach($gradeLevels ?? [] as $gl)
                            <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="">Gender</option>
                        <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
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
                    <a href="{{ route('admin.student-report') }}" class="btn btn-reset">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="summary-cards">
        <div class="stat-card">
            <div class="stat-icon green">👥</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalStudents }}</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">♀</div>
            <div class="stat-info">
                <div class="stat-value">{{ $femaleCount }}</div>
                <div class="stat-label">Female</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">♂</div>
            <div class="stat-info">
                <div class="stat-value">{{ $maleCount }}</div>
                <div class="stat-label">Male</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Class</th>
                    <th>Bottles Collected</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $s)
                <tr>
                    <td>{{ $students->firstItem() + $i }}</td>
                    <td>{{ $s->lrn }}</td>
                    <td>{{ $s->full_name }}</td>
                    <td>{{ $s->gender }}</td>
                    <td>{{ $s->grade_level }}</td>
                    <td>{{ number_format($s->bottles_collected ?? 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No student records found for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} entries</span>
            <div class="page-btns">
                @for ($p = 1; $p <= $students->lastPage(); $p++)
                    <a href="{{ $students->url($p) }}" class="page-btn {{ $students->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
                @endfor
            </div>
        </div>
    </div>
@endsection
