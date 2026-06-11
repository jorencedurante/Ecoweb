@extends('layouts.admin')

@section('title', 'EcoCollect - Student\'s Report')
@section('page-title', 'Student\'s Report')
@section('page-subtitle', 'Generate and manage reports')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <div class="table-header" style="background:var(--card-bg);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:20px;padding:16px 20px;">
        <div class="table-header-left">
            <form method="GET" action="{{ route('admin.student-report') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <select name="grade_level" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#fff;">
                    <option value="">Grade Level</option>
                    @foreach($gradeLevels ?? [] as $gl)
                        <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                    @endforeach
                </select>
                <select name="gender" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#fff;">
                    <option value="">Gender</option>
                    <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                </select>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <button class="filter-btn" type="submit">🔽 Filter</button>
                <button class="filter-btn" type="button" onclick="window.location='{{ route('admin.student-report') }}'">Clear</button>
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
                    <td>{{ $s->bottle_collections_sum_bottle_count ?? $s->total_points }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No students found.</td></tr>
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
