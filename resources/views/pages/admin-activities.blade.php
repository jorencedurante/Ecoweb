@extends('layouts.admin')

@if(auth()->user()->isTeacher())
@section('title', 'EcoCollect - Item Claims')
@section('page-title', 'Item Claims')
@section('page-subtitle', 'Summary of item claims made by students in your assigned class.')
@else
@section('title', 'EcoCollect - Student Activities')
@section('page-title', 'Student Activities')
@section('page-subtitle', 'Summary of student activity and ranking grouped by teacher.')
@endif

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    @if(auth()->user()->isTeacher())
        {{-- ==================== TEACHER: ITEM CLAIMS ==================== --}}
        <div class="filter-card">
            <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
                <i class="fas fa-filter"></i> Filters
            </div>
            <div class="filter-body">
                <form method="GET" action="{{ route('admin.admin-activities') }}" class="filter-form">
                    <div class="filter-search">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Search by name or LRN..." value="{{ request('search') }}">
                    </div>
                    <div class="filter-search">
                        <label>&nbsp;</label>
                        <small class="text-muted" style="color:var(--text-light);font-size:12px;padding-top:10px;">Item claims shown are from your assigned class only.</small>
                    </div>
                    <div class="filter-controls">
                        <button class="btn btn-filter" type="submit">Filter</button>
                        <a href="{{ route('admin.admin-activities') }}" class="btn btn-reset">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h4 style="font-size:14px;font-weight:600;">Item Claims</h4>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student Name</th>
                        <th>LRN</th>
                        <th>Grade Level</th>
                        <th>Section</th>
                        <th>Total Item Claims</th>
                        <th>Total Points Used</th>
                        <th>Claimed Items</th>
                        <th>Latest Claim Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $i => $s)
                    @php
                        $enr = $s->enrollments->first(function ($e) {
                            return $e->teacher_id === auth()->id() && $e->status === 'active';
                        });
                        $claimedItems = $s->claims->groupBy('item_name')->map(function ($group) {
                            return $group->first()->item_name . ' x' . $group->count();
                        })->implode(', ');
                    @endphp
                    <tr>
                        <td><strong>{{ $students->firstItem() + $i }}</strong></td>
                        <td>{{ $s->full_name }}</td>
                        <td>{{ $s->lrn }}</td>
                        <td>{{ $s->grade_level }}</td>
                        <td>{{ $enr->section ?? '—' }}</td>
                        <td>{{ $s->total_claims ?? 0 }}</td>
                        <td>{{ $s->total_points_used ? number_format($s->total_points_used) : '—' }}</td>
                        <td>{{ $claimedItems ?: '—' }}</td>
                        <td>{{ $s->latest_claim_date ? \Carbon\Carbon::parse($s->latest_claim_date)->format('M d, Y') : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-light);">No item claims found for your assigned students.</td></tr>
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
        {{-- ==================== ADMIN / SUPER ADMIN: STUDENT ACTIVITIES ==================== --}}
        <div class="filter-card">
            <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
                <i class="fas fa-filter"></i> Filters
            </div>
            <div class="filter-body">
                <form method="GET" action="{{ route('admin.admin-activities') }}" class="filter-form">
                    <div class="filter-search">
                        <label>Teacher</label>
                        <select name="teacher_id">
                            <option value="">All Teachers</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-search">
                        <label>Grade Level</label>
                        <select name="grade_level">
                            <option value="">Grade Level</option>
                            @foreach($gradeLevels as $gl)
                                <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-search">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Search student..." value="{{ request('search') }}">
                    </div>
                    <div class="filter-search">
                        <label>Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="filter-search">
                        <label>Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="filter-controls">
                        <button class="btn btn-filter" type="submit">Filter</button>
                        <a href="{{ route('admin.admin-activities') }}" class="btn btn-reset">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        @forelse($teacherGroups as $group)
        <div class="table-container" style="margin-bottom:24px;">
            <div class="table-header">
                <h4 style="font-size:14px;font-weight:600;">Teacher: {{ $group['teacher']->name }}</h4>
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
                        <th>Total Claims</th>
                        <th>Latest Activity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($group['students'] as $i => $s)
                    @php
                        $enr = $s->enrollments->first(function ($e) use ($group) {
                            return $e->teacher_id === $group['teacher']->id && $e->status === 'active';
                        });
                        $lastActivity = collect([$s->latest_collection_date, $s->latest_claim_date])->filter()->max();
                    @endphp
                    <tr>
                        <td><strong>{{ $i + 1 }}</strong></td>
                        <td>{{ $s->full_name }}</td>
                        <td>{{ $s->lrn }}</td>
                        <td>{{ $s->grade_level }}</td>
                        <td>{{ $enr->section ?? '—' }}</td>
                        <td>{{ number_format($s->total_bottles ?? 0) }}</td>
                        <td>{{ number_format($s->total_points ?? 0) }}</td>
                        <td>{{ $s->total_claims ?? 0 }}</td>
                        <td>{{ $lastActivity ? \Carbon\Carbon::parse($lastActivity)->format('M d, Y') : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-light);">No students found for this teacher.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @empty
        <div class="table-container">
            <div class="table-header">
                <h4 style="font-size:14px;font-weight:600;">Student Activities</h4>
            </div>
            <table>
                <tbody>
                    <tr><td style="text-align:center;padding:30px;color:var(--text-light);">No teacher summaries found for the selected filters.</td></tr>
                </tbody>
            </table>
        </div>
        @endforelse
    @endif
@endsection
