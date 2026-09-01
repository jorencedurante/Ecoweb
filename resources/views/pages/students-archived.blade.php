@extends('layouts.admin')

@section('title', 'EcoCollect - Archived Students')
@section('page-title', 'Archived Students')
@section('page-subtitle', 'View archived student records')

@section('content')
    <div class="filter-card">
        <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
            <i class="fas fa-filter"></i> Filters
        </button>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.students.archived') }}" id="filterForm" class="filter-form">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search archived students..." value="{{ request('search') }}" aria-label="Search archived students">
                </div>
                @if(auth()->user()->isTeacher())
                <div class="filter-search">
                    <label>&nbsp;</label>
                    <small class="text-muted" style="color:var(--text-light);font-size:12px;padding-top:10px;">Showing students from your assigned class.</small>
                </div>
                @else
                <div class="filter-search">
                    <label>Grade Level</label>
                    <select name="grade_level" aria-label="Filter by grade level">
                        <option value="">All Grades</option>
                        @foreach($gradeLevels ?? [] as $gl)
                            <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="filter-search">
                    <label>Gender</label>
                    <select name="gender" aria-label="Filter by gender">
                        <option value="">All Genders</option>
                        <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div class="filter-controls">
                    <button class="btn btn-filter" type="submit">Filter</button>
                    <a href="{{ route('admin.students.archived') }}" class="btn btn-reset">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left"></div>
            <a href="{{ route('admin.students') }}" class="btn btn-outline btn-sm" style="font-size:13px;">← Back to Active Students</a>
        </div>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th scope="col">LRN</th>
                    <th scope="col">Name</th>
                    <th scope="col">Grade Level</th>
                    <th scope="col">QR Code</th>
                    <th scope="col">Total Points</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td>{{ $student->lrn }}</td>
                    <td><strong>{{ $student->full_name }}</strong></td>
                    <td>{{ $student->grade_level }}</td>
                    <td>{{ $student->qr_code ?? '—' }}</td>
                    <td>{{ $student->total_points }}</td>
                    <td><span style="color:var(--gray);font-weight:600;">{{ ucfirst($student->status) }}</span></td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('students.info', $student->id) }}" class="btn btn-view btn-xs" aria-label="View info for {{ $student->full_name }}">View Info</a>
                            @if(Auth::user()->role !== 'admin')
                            <form method="POST" action="{{ route('students.restore', $student->id) }}" style="display:inline;" onsubmit="return confirm('Restore this student to active students?');">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-restore btn-xs" aria-label="Restore {{ $student->full_name }}">Restore</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No archived students found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="pagination">
            <span class="page-info">Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} entries</span>
            <div class="page-btns">
                @for ($i = 1; $i <= $students->lastPage(); $i++)
                    <a href="{{ $students->url($i) }}" class="page-btn {{ $students->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                @endfor
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
</script>
@endpush
