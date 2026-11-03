@extends('layouts.admin')

@section('title', 'EcoCollect - Archived Students')
@section('page-title', 'Archived Students')
@section('page-subtitle', 'View archived student records')

@section('content')
    <div class="archived-students-page">
        <div class="filter-card">
        <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
            <i class="fas fa-filter"></i> Filters
        </button>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.students.archived') }}" id="filterForm" class="filter-form archived-filter-form">
                <div class="archived-filter-row">
                    <div class="archived-filter-field archived-filter-wide">
                        <label for="archivedSearchInput">Search</label>
                        <input type="text" id="archivedSearchInput" name="search" placeholder="Search archived students..." value="{{ request('search') }}" aria-label="Search archived students">
                    </div>
                    <div class="archived-filter-field archived-filter-narrow">
                        <label for="archivedGenderSelect">Gender</label>
                        <select id="archivedGenderSelect" name="gender" aria-label="Filter by gender">
                            <option value="">All Genders</option>
                            <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                </div>
                @if(!auth()->user()->isTeacher())
                <div class="archived-filter-row archived-filter-row-full">
                    <div class="archived-filter-field archived-filter-full">
                        <label for="archivedGradeSelect">Grade Level</label>
                        <select id="archivedGradeSelect" name="grade_level" aria-label="Filter by grade level">
                            <option value="">All Grades</option>
                            @foreach($gradeLevels ?? [] as $gl)
                                <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
                @if(auth()->user()->isTeacher())
                <p class="archived-filter-note">Showing students from your assigned class.</p>
                @endif
            </form>
        </div>
    </div>

    <div class="table-container archived-table-container">
        <div class="table-header archived-table-header">
            <div class="table-header-left"></div>
            <a href="{{ route('admin.students') }}" class="btn btn-outline btn-sm btn-back" aria-label="Back to active students">← Back to Active Students</a>
        </div>

        <!-- Archive Tabs -->
        <div class="stu-archive-tabs" role="tablist" aria-label="Archive views">
            <button type="button" class="stu-archive-tab active" role="tab" id="tab-individually" aria-selected="true" aria-controls="panel-individually" data-archive-tab="individually">
                <x-icon name="file" :size="15" /> Individually Archived
            </button>
            <button type="button" class="stu-archive-tab" role="tab" id="tab-school-years" aria-selected="false" aria-controls="panel-school-years" tabindex="-1" data-archive-tab="school-years">
                <x-icon name="layers" :size="15" /> School-Year Archives
            </button>
        </div>

        <!-- Panel 1: Individually Archived -->
        <div class="stu-archive-panel active" role="tabpanel" id="panel-individually" data-archive-panel="individually" aria-labelledby="tab-individually">
            <div class="archived-search-bar">
                <input type="text" id="individuallySearchInput" class="archived-search-input" placeholder="Search individually archived students..." aria-label="Search individually archived students">
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

        <!-- Panel 2: School-Year Archives -->
        <div class="stu-archive-panel" role="tabpanel" id="panel-school-years" data-archive-panel="school-years" aria-labelledby="tab-school-years" hidden>
            <div class="archived-search-bar sy-search-container">
                <input type="text" id="sySearchInput" class="archived-search-input" placeholder="Search school-year archives..." aria-label="Search school-year archives">
            </div>
            <div id="syArchiveGroups" class="sy-archive-groups">
                <!-- School year groups loaded via AJAX -->
            </div>
            <div id="syEmptyState" style="text-align:center;padding:40px;color:var(--text-light);display:none;">
                <div style="font-size:32px;margin-bottom:8px;">📦</div>
                <div>No school-year archives found.</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const tabs = document.querySelectorAll('.stu-archive-tab');
    const panels = document.querySelectorAll('.stu-archive-panel');

    function activate(key) {
        tabs.forEach(tab => {
            const on = tab.dataset.archiveTab === key;
            tab.classList.toggle('active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
        });
        panels.forEach(panel => {
            const on = panel.dataset.archivePanel === key;
            panel.classList.toggle('active', on);
            panel.hidden = !on;
        });
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            activate(tab.dataset.archiveTab);
        });
    });

    // Individually archived search
    const individuallyInput = document.getElementById('individuallySearchInput');
    if (individuallyInput) {
        individuallyInput.addEventListener('input', function(){
            const term = this.value.toLowerCase();
            document.querySelectorAll('#panel-individually tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }

    // School-year search
    const syInput = document.getElementById('sySearchInput');
    if (syInput) {
        syInput.addEventListener('input', function(){
            const term = this.value.toLowerCase();
            document.querySelectorAll('#syArchiveGroups .sy-group').forEach(group => {
                group.style.display = group.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }
})();
</script>
@endpush
