@extends('layouts.admin')

@if(auth()->user()->isTeacher())
@section('title', 'EcoCollect - Student Ranking')
@section('page-title', 'Student Ranking')
@section('page-subtitle', 'Ranking of students in your assigned class based on points and bottle collections.')
@else
@section('title', 'EcoCollect - Student\'s Report')
@section('page-title', 'Student\'s Report')
@section('page-subtitle', 'Generate and manage reports')
@endif

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    @if(auth()->user()->isTeacher())
        {{-- ==================== TEACHER: STUDENT RANKING ==================== --}}
        <div class="filter-card">
            <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
                <i class="fas fa-filter"></i> Filters
            </div>
            <div class="filter-body">
                <form method="GET" action="{{ route('admin.student-report') }}" class="filter-form">
                    <div class="filter-search">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Search by name or LRN..." value="{{ request('search') }}">
                    </div>
                    <div class="filter-search">
                        <label>&nbsp;</label>
                        <small class="text-muted" style="color:var(--text-light);font-size:12px;padding-top:10px;">Ranking covers only students from your assigned class.</small>
                    </div>
                    <div class="filter-controls">
                        <button class="btn btn-filter" type="submit">Filter</button>
                        <a href="{{ route('admin.student-report') }}" class="btn btn-reset">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h4 style="font-size:14px;font-weight:600;">Student Ranking</h4>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student Name</th>
                        <th>LRN</th>
                        <th>Grade Level</th>
                        <th>Section</th>
                        <th>Total Bottles</th>
                        <th>Total Points</th>
                        <th>Total Item Claims</th>
                        <th>Latest Collection</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $i => $s)
                    @php
                        $enr = $s->enrollments->first(function ($e) {
                            return $e->teacher_id === auth()->id() && $e->status === 'active';
                        });
                    @endphp
                    <tr>
                        <td><strong>{{ $students->firstItem() + $i }}</strong></td>
                        <td>{{ $s->full_name }}</td>
                        <td>{{ $s->lrn }}</td>
                        <td>{{ $s->grade_level }}</td>
                        <td>{{ $enr->section ?? '—' }}</td>
                        <td>{{ number_format($s->total_bottles ?? 0) }}</td>
                        <td>{{ number_format($s->total_points ?? 0) }}</td>
                        <td>{{ $s->total_claims ?? 0 }}</td>
                        <td>{{ $s->latest_collection_date ? \Carbon\Carbon::parse($s->latest_collection_date)->format('M d, Y') : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-light);">No students found in your assigned class for the selected filters.</td></tr>
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
    @else
        {{-- ==================== ADMIN / SUPER ADMIN: STUDENT REPORT ==================== --}}
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
    @endif
@endsection
