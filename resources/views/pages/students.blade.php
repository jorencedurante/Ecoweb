@extends('layouts.admin')

@section('title', 'EcoCollect - Students')
@section('page-title', 'Students')
@section('page-subtitle', 'Manage student information')

@section('content')
    @php
        $filterable = [
            'search' => 'Search',
            'grade_level' => 'Grade',
            'gender' => 'Gender',
        ];
        $appliedFilters = collect($filterable)
            ->map(function ($label, $key) {
                return ['key' => $key, 'label' => $label, 'value' => request($key)];
            })
            ->filter(fn ($filter) => $filter['value'] !== null && $filter['value'] !== '')
            ->values();
        $actionsColClass = Auth::user()->role === 'admin' ? 'stu-actions-3' : 'stu-actions-5';
    @endphp

    <div class="students-page-wrapper">

    @if(Auth::user()->role === 'admin')
    <div role="status" class="stu-viewonly-notice">
        Admin account is view-only. Student import and record changes are restricted to Teachers and Super Admin.
    </div>
    @endif

    <div class="filter-card stu-filter-card">
        <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
            <x-icon name="funnel" :size="16" />
            Filters
            <x-icon name="chevron-down" :size="15" class="stu-filter-chevron" />
        </button>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.students') }}" id="filterForm" class="stu-toolbar-form">
                <div class="stu-field stu-field-search">
                    <label for="stuSearchInput">Search students</label>
                    <div class="stu-search">
                        <span class="stu-search-icon" aria-hidden="true"><x-icon name="search" :size="16" /></span>
                        <input type="text" id="stuSearchInput" name="search" autocomplete="off"
                               placeholder="Search by name, LRN, or student ID..."
                               value="{{ request('search') }}"
                               aria-label="Search students by name, LRN, or student ID">
                        <button type="button" class="stu-search-clear" id="stuSearchClear" aria-label="Clear search" {{ request('search') ? '' : 'hidden' }}>
                            <x-icon name="x" :size="13" />
                        </button>
                    </div>
                </div>

                @if(!auth()->user()->isTeacher())
                <div class="stu-field stu-field-select">
                    <label for="stuGradeSelect">Grade level</label>
                    <select id="stuGradeSelect" name="grade_level" aria-label="Filter by grade level">
                        <option value="">All grades</option>
                        @foreach($gradeLevels ?? [] as $gl)
                            <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="stu-field stu-field-select">
                    <label for="stuGenderSelect">Gender</label>
                    <select id="stuGenderSelect" name="gender" aria-label="Filter by gender">
                        <option value="">All genders</option>
                        <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>

                <div class="stu-field stu-field-actions">
                    <button class="btn btn-primary stu-apply-btn" type="submit">
                        <x-icon name="funnel" :size="15" /> Apply filters
                    </button>
                    <a href="{{ route('admin.students') }}" class="btn btn-outline stu-reset-btn">
                        <x-icon name="rotate-ccw" :size="15" /> Reset
                    </a>
                </div>

                @if(auth()->user()->isTeacher())
                <p class="stu-toolbar-note">Showing students from your assigned class.</p>
                @endif
            </form>

            @if($appliedFilters->isNotEmpty())
            <div class="stu-chips" role="status" aria-label="Active filters">
                <span class="stu-chips-label">Active filters</span>
                @foreach($appliedFilters as $filter)
                    @php
                        $remaining = request()->query();
                        unset($remaining[$filter['key']], $remaining['page']);
                    @endphp
                    <a class="stu-chip" href="{{ route('admin.students', $remaining) }}" title="Remove {{ strtolower($filter['label']) }} filter">
                        <span class="stu-chip-key">{{ $filter['label'] }}</span>
                        <span class="stu-chip-value">{{ $filter['value'] }}</span>
                        <span class="stu-chip-x" aria-hidden="true"><x-icon name="x" :size="12" /></span>
                    </a>
                @endforeach
                <a class="stu-chip stu-chip-clear" href="{{ route('admin.students') }}">Clear all</a>
            </div>
            @endif
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
                <span class="stu-result-count" role="status">
                    Showing {{ $students->total() }} student{{ $students->total() === 1 ? '' : 's' }}{{ $appliedFilters->isNotEmpty() ? ' matching your filters' : '' }}
                </span>
            </div>
            <div class="table-toolbar-actions">
                <a href="{{ route('admin.students.archived') }}" class="btn btn-outline btn-sm archived-btn">📦 Archived</a>
                @if(Auth::user()->role !== 'admin')
                <button type="button" class="btn btn-primary btn-sm" data-modal-target="importStudentModal">📥 Import</button>
                <button type="button" class="btn btn-primary" data-modal-target="addStudentModal">+ Add Student</button>
                @endif
            </div>
        </div>
        <div class="table-responsive">
        <table>
            <colgroup>
                <col class="stu-col-lrn">
                <col class="stu-col-name">
                <col class="stu-col-grade">
                <col class="stu-col-qr">
                <col class="stu-col-points">
                <col class="stu-col-status">
                <col class="stu-col-actions {{ $actionsColClass }}">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">LRN</th>
                    <th scope="col">Name</th>
                    <th scope="col">Grade Level</th>
                    <th scope="col">QR Code</th>
                    <th scope="col">Total Points</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="actions-th">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td class="cell-lrn">{{ $student->lrn }}</td>
                    <td class="cell-name">
                        <button type="button" class="stu-name-btn" title="Open full details"
                            data-overview-open
                            data-overview-url="{{ route('admin.students.overview', $student->id) }}"
                            data-student-key="{{ $student->id }}"
                            data-tab="full-details"
                            aria-label="Open full details for {{ $student->full_name }}">
                            <span class="stu-avatar" aria-hidden="true">{{ collect([$student->first_name, $student->last_name])->filter()->map(fn ($part) => mb_strtoupper(mb_substr(trim($part), 0, 1)))->implode('') ?: 'ST' }}</span>
                            <span class="stu-name-text">
                                <strong>{{ $student->full_name }}</strong>
                                @if($student->student_id)
                                <small>{{ $student->student_id }}</small>
                                @endif
                            </span>
                        </button>
                    </td>
                    <td>{{ $student->grade_level }}</td>
                    <td class="cell-qr">{{ $student->qr_code ?? '—' }}</td>
                    <td class="cell-points">{{ $student->total_points }}</td>
                    <td class="cell-status">
                        <span class="status-badge status-{{ strtolower($student->status) }}">{{ ucfirst($student->status) }}</span>
                    </td>
                    <td class="cell-actions">
                        <div class="student-actions">
                            <button type="button" class="action-icon-btn view" title="View Student" aria-label="View student overview"
                                data-overview-open
                                data-overview-url="{{ route('admin.students.overview', $student->id) }}"
                                data-student-key="{{ $student->id }}"
                                data-tab="overview"><x-icon name="eye" :size="16" /></button>
                            <button type="button" class="action-icon-btn achievements" title="View Achievements" aria-label="View achievements"
                                data-overview-open
                                data-overview-url="{{ route('admin.students.overview', $student->id) }}"
                                data-student-key="{{ $student->id }}"
                                data-tab="achievements"><x-icon name="trophy" :size="16" /></button>
                            <button type="button" class="action-icon-btn awards" title="View Certificates" aria-label="View certificates"
                                data-overview-open
                                data-overview-url="{{ route('admin.students.overview', $student->id) }}"
                                data-student-key="{{ $student->id }}"
                                data-tab="certificates"><x-icon name="award" :size="16" /></button>
                            @if(Auth::user()->role !== 'admin')
                            <button type="button" class="action-icon-btn edit" title="Edit Student" aria-label="Edit student" data-modal-target="editStudentModal"
                                data-id="{{ $student->id }}"
                                data-lrn="{{ $student->lrn }}"
                                data-first="{{ $student->first_name }}"
                                data-middle="{{ $student->middle_name }}"
                                data-last="{{ $student->last_name }}"
                                data-grade="{{ $student->grade_level }}"
                                data-gender="{{ $student->gender }}"
                                data-teacher-id="{{ $student->teacher_id }}"><x-icon name="pencil" :size="15" /></button>
                            @if($student->status !== 'Archived')
                            <form method="POST" action="{{ route('admin.students.archive', $student->id) }}" class="inline-action-form" onsubmit="return confirm('Archive this student?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="action-icon-btn archive" title="Archive Student" aria-label="Archive student"><x-icon name="archive" :size="15" /></button>
                            </form>
                            @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-row">No students found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($students->hasPages())
        <div class="pagination">
            <span class="page-info">Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} entries</span>
            <div class="page-btns">
                @for ($i = 1; $i <= $students->lastPage(); $i++)
                    <a href="{{ $students->url($i) }}" class="page-btn {{ $students->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                @endfor
            </div>
        </div>
        @endif
    </div>

    </div>

    @if(session('import_result'))
    @php $result = session('import_result'); $type = $result['type'] ?? 'success'; @endphp
    <div class="import-result-card import-result-{{ $type }}" id="importResult" role="alert">
        <div class="import-result-header">
            <span>📋 Import Result</span>
            <button type="button" onclick="document.getElementById('importResult').remove()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#6b7280;" aria-label="Close">×</button>
        </div>
        <div class="import-result-summary">
            @foreach(explode(' | ', $result['message']) as $line)
            <div>{{ $line }}</div>
            @endforeach
        </div>
        @if(count($result['errors']))
        <div class="import-result-errors">
            <strong>Skipped rows:</strong>
            <ul>
                @foreach($result['errors'] as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    {{-- Student Overview Modal --}}
    <div class="modal-overlay" id="studentOverviewModal" role="dialog" aria-modal="true" aria-labelledby="studentOverviewTitle" aria-hidden="true">
        <div class="modal-content student-overview-modal">
            <div class="modal-header so-header">
                <div class="so-student">
                    <span class="so-avatar" id="soAvatar" aria-hidden="true">ST</span>
                    <div class="so-heading">
                        <h3 id="studentOverviewTitle">Student overview</h3>
                        <p class="subtitle" id="studentOverviewSubtitle">Bottles, points, achievements, and certificates in one view.</p>
                        <div class="so-meta" id="studentOverviewMeta" hidden></div>
                    </div>
                </div>
                <button type="button" class="so-close" id="soCloseBtn" data-modal-close aria-label="Close student overview">
                    <x-icon name="x" :size="18" />
                </button>
            </div>

            <div class="so-tabs" role="tablist" aria-label="Student overview sections">
                <button type="button" class="so-tab active" role="tab" id="soTabOverview" aria-selected="true" aria-controls="soPanel" data-so-tab="overview">
                    <x-icon name="circle-user-round" :size="16" /> Overview
                </button>
                <button type="button" class="so-tab" role="tab" id="soTabCollections" aria-selected="false" aria-controls="soPanel" data-so-tab="collections" tabindex="-1">
                    <x-icon name="recycle" :size="16" /> Bottle Collections
                </button>
                <button type="button" class="so-tab" role="tab" id="soTabAchievements" aria-selected="false" aria-controls="soPanel" data-so-tab="achievements" tabindex="-1">
                    <x-icon name="trophy" :size="16" /> Achievements
                </button>
                <button type="button" class="so-tab" role="tab" id="soTabCertificates" aria-selected="false" aria-controls="soPanel" data-so-tab="certificates" tabindex="-1">
                    <x-icon name="award" :size="16" /> Certificates
                </button>
                <button type="button" class="so-tab" role="tab" id="soTabFullDetails" aria-selected="false" aria-controls="soPanel" data-so-tab="full-details" tabindex="-1">
                    <x-icon name="file-text" :size="16" /> Full Details
                </button>
            </div>

            <div class="modal-body so-body" id="soPanel" role="tabpanel" aria-labelledby="soTabOverview" tabindex="0">
                <div class="so-state" role="status">
                    <span class="so-spinner" aria-hidden="true"></span>
                    <p>Loading student details…</p>
                </div>
            </div>

            <div class="modal-footer so-footer">
                <button type="button" class="btn btn-danger" id="soCloseFooter" data-modal-close>Close</button>
            </div>
        </div>
    </div>

    {{-- Icon sources for content rendered by the overview modal script --}}
    <template id="tplIco-recycle"><x-icon name="recycle" :size="18" /></template>
    <template id="tplIco-star"><x-icon name="star" :size="18" /></template>
    <template id="tplIco-trophy"><x-icon name="trophy" :size="18" /></template>
    <template id="tplIco-award"><x-icon name="award" :size="18" /></template>
    <template id="tplIco-inbox"><x-icon name="inbox" :size="30" /></template>
    <template id="tplIco-circle-check"><x-icon name="circle-check" :size="18" /></template>
    <template id="tplIco-clock"><x-icon name="clock" :size="18" /></template>
    <template id="tplIco-download"><x-icon name="download" :size="16" /></template>
    <template id="tplIco-printer"><x-icon name="printer" :size="16" /></template>
    <template id="tplIco-circle-alert"><x-icon name="circle-alert" :size="30" /></template>

    @if(Auth::user()->role !== 'admin')
    <!-- Import Students Modal -->
    <div class="modal-overlay" id="importStudentModal">
        <div class="modal-content" style="max-width:520px;">
            <div class="modal-header">
                <h3>Import Students</h3>
                <p class="subtitle">Upload an Excel file (.xlsx) with student data.</p>
            </div>
            <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Excel File <span style="color:var(--red);">*</span></label>
                        <input type="file" name="file" accept=".xlsx,.xls" required aria-label="Upload CSV file">
                    </div>
                    <div class="form-group">
                        <label>Grade Level (fallback if not detected in file)</label>
                        <select name="grade_level">
                            <option value="">-- Auto-detect or select --</option>
                            <option value="Kindergarten">Kindergarten</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                        </select>
                    </div>
                    <p class="import-note">
                        Supports <strong>SF1 (School Form 1)</strong> files and simple Excel templates.
                        For SF1 files, the system auto-detects headers, parses student names (Last,First,Middle),
                        and reads the grade level from the file. Select a grade level above as fallback.
                        <br><br>
                        For simple templates, supported columns: <strong>LRN</strong>, <strong>Student Name</strong>,
                        <strong>Grade Level</strong>, <strong>Gender</strong>, <strong>Student ID</strong>.
                        Gender accepts M/F/Male/Female. Duplicate LRN rows are skipped.
                    </p>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.students.import.template') }}" class="btn btn-outline btn-template">📄 Download Template</a>
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal-overlay" id="addStudentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add student</h3>
                <p class="subtitle">Fill in the following information</p>
            </div>
            <form method="POST" action="{{ route('admin.students.store') }}">
                @csrf
                <div class="modal-body">
                    @if($errors->any())
                    <div style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:10px 14px;border-radius:var(--radius-sm);font-size:12px;margin-bottom:12px;" role="alert">
                        @foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach
                    </div>
                    @endif
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" aria-label="First name" placeholder="Enter first name" value="{{ old('first_name') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="optional">(Optional)</span></label>
                            <input type="text" name="middle_name" aria-label="Middle name" placeholder="Enter middle name" value="{{ old('middle_name') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" aria-label="Last name" placeholder="Enter last name" value="{{ old('last_name') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Student's LRN</label>
                            <input type="text" name="lrn" aria-label="LRN" placeholder="Enter LRN" value="{{ old('lrn') }}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Grade Level</label>
                            <select name="grade_level" aria-label="Grade level">
                                <option value="">Select grade</option>
                                @foreach(['Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                                    <option value="{{ $g }}" {{ old('grade_level') == $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <div class="radio-group">
                                <label><input type="radio" name="gender" value="Male" aria-label="Gender" {{ old('gender') == 'Male' ? 'checked' : '' }}> Male</label>
                                <label><input type="radio" name="gender" value="Female" aria-label="Gender" {{ old('gender') == 'Female' ? 'checked' : '' }}> Female</label>
                            </div>
                        </div>
                    </div>
                    @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
                    <div class="form-group">
                        <label>Assign Teacher</label>
                        <select name="teacher_id" aria-label="Assign teacher">
                            <option value="">No teacher</option>
                            @foreach($teachers ?? [] as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }} ({{ $teacher->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal-overlay" id="editStudentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit student</h3>
                <p class="subtitle">Fill in the following information</p>
            </div>
            <form method="POST" action="" id="editStudentForm">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" id="editFirstName" aria-label="First name" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name <span class="optional">(Optional)</span></label>
                            <input type="text" name="middle_name" id="editMiddleName" aria-label="Middle name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="editLastName" aria-label="Last name" required>
                        </div>
                        <div class="form-group">
                            <label>Student's LRN</label>
                            <input type="text" name="lrn" id="editLrn" aria-label="LRN" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Grade Level</label>
                            <select name="grade_level" id="editGradeLevel" aria-label="Grade level">
                                @foreach(['Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                                    <option value="{{ $g }}">{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <div class="radio-group">
                                <label><input type="radio" name="gender" value="Male" id="editGenderMale" aria-label="Gender"> Male</label>
                                <label><input type="radio" name="gender" value="Female" id="editGenderFemale" aria-label="Gender"> Female</label>
                            </div>
                        </div>
                    </div>
                    @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
                    <div class="form-group">
                        <label>Assign Teacher</label>
                        <select name="teacher_id" id="editTeacherId" aria-label="Assign teacher">
                            <option value="">No teacher</option>
                            @foreach($teachers ?? [] as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }} ({{ $teacher->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    initStudentToolbar();
    initStudentOverview();

    const editModal = document.getElementById('editStudentModal');
    document.querySelectorAll('[data-modal-target="editStudentModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const lrn = btn.dataset.lrn;
            const first = btn.dataset.first;
            const middle = btn.dataset.middle;
            const last = btn.dataset.last;
            const grade = btn.dataset.grade;
            const gender = btn.dataset.gender;

            const teacherId = btn.dataset.teacherId;
            document.getElementById('editStudentForm').action = '{{ url('/admin/students') }}/' + id;
            document.getElementById('editFirstName').value = first;
            document.getElementById('editMiddleName').value = middle || '';
            document.getElementById('editLastName').value = last;
            document.getElementById('editLrn').value = lrn;
            document.getElementById('editGradeLevel').value = grade;
            if (gender === 'Male') document.getElementById('editGenderMale').checked = true;
            else document.getElementById('editGenderFemale').checked = true;
            const editTeacher = document.getElementById('editTeacherId');
            if (editTeacher) editTeacher.value = teacherId || '';

            editModal.classList.add('show');
        });
    });
});

function initStudentToolbar() {
    const form = document.getElementById('filterForm');
    const input = document.getElementById('stuSearchInput');
    const clearBtn = document.getElementById('stuSearchClear');
    if (!form || !input) return;

    let timer = null;
    let lastValue = input.value.trim();

    const syncClear = () => {
        if (clearBtn) clearBtn.hidden = input.value.length === 0;
    };

    syncClear();

    input.addEventListener('input', () => {
        syncClear();
        if (timer) clearTimeout(timer);
        const value = input.value.trim();
        if (value === lastValue) return;
        timer = setTimeout(() => {
            timer = null;
            lastValue = value;
            form.submit();
        }, 450);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (timer) { clearTimeout(timer); timer = null; }
            lastValue = input.value.trim();
            form.submit();
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (timer) { clearTimeout(timer); timer = null; }
            input.value = '';
            lastValue = '';
            syncClear();
            form.submit();
        });
    }

    form.addEventListener('submit', () => {
        if (timer) { clearTimeout(timer); timer = null; }
        lastValue = input.value.trim();
    });
}

function initStudentOverview() {
    const modal = document.getElementById('studentOverviewModal');
    if (!modal) return;

    const panel = document.getElementById('soPanel');
    const subtitle = document.getElementById('studentOverviewSubtitle');
    const avatar = document.getElementById('soAvatar');
    const meta = document.getElementById('studentOverviewMeta');
    const tabs = Array.prototype.slice.call(modal.querySelectorAll('.so-tab'));

    const cache = {};
    let activeKey = null;
    let activeUrl = null;
    let activeTab = 'overview';
    let data = null;
    let lastTrigger = null;
    let seq = 0;
    let controller = null;

    const esc = (value) => String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    const icon = (name, size) => {
        const tpl = document.getElementById('tplIco-' + name);
        if (!tpl || !tpl.content.firstElementChild) return '';
        const node = tpl.content.firstElementChild.cloneNode(true);
        if (size) { node.setAttribute('width', size); node.setAttribute('height', size); }
        return new XMLSerializer().serializeToString(node);
    };

    const emptyState = (message) =>
        '<div class="so-state">' +
            '<span class="so-state-icon" aria-hidden="true">' + icon('inbox', 30) + '</span>' +
            '<p>' + esc(message) + '</p>' +
        '</div>';

    function setTab(tab) {
        activeTab = tab;
        tabs.forEach((t) => {
            const isActive = t.dataset.soTab === tab;
            t.classList.toggle('active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            t.tabIndex = isActive ? 0 : -1;
            if (isActive) panel.setAttribute('aria-labelledby', t.id);
        });
    }

    function renderHeader() {
        if (!data) return;
        const s = data.student;
        avatar.textContent = s.initials || 'ST';
        subtitle.textContent = s.full_name + (s.lrn ? ' • LRN ' + s.lrn : '');

        const statusKey = String(s.status || '').toLowerCase();
        const statusClass = ['active', 'archived', 'inactive'].indexOf(statusKey) >= 0 ? ' status-' + statusKey : '';
        const chips = [];
        if (s.grade_level) chips.push('<span class="so-meta-chip">' + esc(s.grade_level) + '</span>');
        if (s.status) chips.push('<span class="status-badge' + statusClass + '">' + esc(s.status) + '</span>');
        if (s.student_id) chips.push('<span class="so-meta-chip">' + esc(s.student_id) + '</span>');
        meta.innerHTML = chips.join('');
        meta.hidden = chips.length === 0;
    }

    function resetHeader() {
        avatar.textContent = '…';
        subtitle.textContent = 'Loading student details…';
        meta.innerHTML = '';
        meta.hidden = true;
    }

    function renderLoading() {
        resetHeader();
        panel.innerHTML = '<div class="so-state" role="status">' +
            '<span class="so-spinner" aria-hidden="true"></span>' +
            '<p>Loading student details…</p></div>';
    }

    function renderError() {
        avatar.textContent = '…';
        subtitle.textContent = 'Unable to load student details';
        meta.innerHTML = '';
        meta.hidden = true;
        panel.innerHTML = '<div class="so-state so-state-error">' +
            '<span class="so-state-icon" aria-hidden="true">' + icon('circle-alert', 30) + '</span>' +
            '<p>Unable to load student details.</p>' +
            '<button type="button" class="btn btn-outline" id="soRetryBtn">Try again</button></div>';
        const retry = document.getElementById('soRetryBtn');
        if (retry) retry.addEventListener('click', () => {
            delete cache[activeKey];
            data = null;
            renderLoading();
            load();
        });
    }

    function load() {
        if (controller) controller.abort();
        const mySeq = ++seq;
        controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

        fetch(activeUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined
        })
            .then((res) => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then((json) => {
                if (mySeq !== seq) return;
                cache[activeKey] = json;
                data = json;
                renderHeader();
                render();
            })
            .catch((err) => {
                if (err && err.name === 'AbortError') return;
                if (mySeq !== seq) return;
                data = null;
                renderError();
            });
    }

    function open(trigger) {
        activeKey = trigger.dataset.studentKey;
        activeUrl = trigger.dataset.overviewUrl;
        activeTab = trigger.dataset.tab || 'overview';
        lastTrigger = trigger;
        data = cache[activeKey] || null;

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        document.documentElement.style.overflow = 'hidden';

        setTab(activeTab);

        if (data) {
            renderHeader();
            render();
        } else {
            renderLoading();
            load();
        }

        const closeBtn = document.getElementById('soCloseBtn');
        if (closeBtn) closeBtn.focus();
    }

    function close() {
        seq += 1;
        if (controller) { controller.abort(); controller = null; }
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        document.documentElement.style.overflow = '';
        if (lastTrigger && document.contains(lastTrigger)) lastTrigger.focus();
        lastTrigger = null;
        activeKey = null;
        activeUrl = null;
        data = null;
    }

    function soCard(iconName, value, label, note, tone) {
        return '<div class="so-card">' +
            '<span class="so-card-icon so-tone-' + tone + '" aria-hidden="true">' + icon(iconName, 18) + '</span>' +
            '<span class="so-card-text">' +
                '<span class="so-card-value">' + esc(value) + '</span>' +
                '<span class="so-card-label">' + esc(label) + '</span>' +
                '<span class="so-card-note">' + esc(note) + '</span>' +
            '</span></div>';
    }

    function collectionTable(rows, withRecorder) {
        let html = '<div class="so-table-wrap"><table class="so-table">' +
            '<thead><tr><th>Date</th><th>Time</th><th>Bottles</th><th>Points</th><th>Status</th>' +
            (withRecorder ? '<th>Recorded by</th>' : '') +
            '</tr></thead><tbody>';
        rows.forEach((c) => {
            html += '<tr><td>' + esc(c.collection_date) + '</td>' +
                '<td>' + esc(c.collection_time || '—') + '</td>' +
                '<td><strong>' + esc(c.bottle_count) + '</strong></td>' +
                '<td class="so-points">' + esc(c.points_earned) + '</td>' +
                '<td><span class="status-badge status-active">' + esc(c.status || 'Recorded') + '</span></td>' +
                (withRecorder ? '<td>' + esc(c.recorded_by) + '</td>' : '') +
                '</tr>';
        });
        return html + '</tbody></table></div>';
    }

    function progressList(items) {
        if (!items.length) return emptyState('No active achievement quests yet.');
        return '<div class="so-ach-list">' + items.map((item) => {
            const metric = item.metric === 'bottles' ? 'bottles' : 'points';
            return '<div class="so-ach-item' + (item.completed ? ' so-ach-done' : '') + '">' +
                '<div class="so-ach-top">' +
                    '<span class="so-ach-title">' + esc(item.title) + '</span>' +
                    '<span class="so-ach-value">' + esc(item.current) + ' / ' + esc(item.required) + ' ' + metric + '</span>' +
                '</div>' +
                '<div class="so-progress" role="progressbar" aria-valuenow="' + esc(item.progress) + '" aria-valuemin="0" aria-valuemax="100" aria-label="' + esc(item.title) + ' progress">' +
                    '<span class="so-progress-bar" style="width:' + esc(item.progress) + '%"></span>' +
                '</div>' +
                '<div class="so-ach-bottom">' +
                    '<span>' + esc(item.description || '') + '</span>' +
                    (item.completed
                        ? '<span class="so-ach-tag">Completed</span>'
                        : '<span>' + esc(item.progress) + '%</span>') +
                '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function activityList(items) {
        if (!items || !items.length) return emptyState('No recent activity yet.');
        const ico = { collection: 'recycle', achievement: 'trophy', certificate: 'award' };
        return '<ul class="so-activity">' + items.map((it) =>
            '<li class="so-activity-item">' +
                '<span class="so-activity-icon" aria-hidden="true">' + icon(ico[it.type] || 'inbox', 16) + '</span>' +
                '<span class="so-activity-text">' +
                    '<strong>' + esc(it.label) + '</strong>' +
                    (it.detail ? '<span>' + esc(it.detail) + '</span>' : '') +
                '</span>' +
                '<span class="so-activity-date">' + esc(it.date) + '</span>' +
            '</li>'
        ).join('') + '</ul>';
    }

    function renderOverview(d) {
        const s = d.student;
        const sum = d.summary;
        const info = [
            ['Full name', s.full_name],
            ['Student ID', s.student_id],
            ['LRN', s.lrn],
            ['Grade level', s.grade_level],
            ['Gender', s.gender],
            ['Status', s.status],
            ['QR code', s.qr_code],
            ['Assigned teacher', s.teacher_name],
            ['Guardian', s.guardian_name]
        ].filter((pair) => pair[1]);

        let html = '<div class="so-cards">' +
            soCard('recycle', sum.total_bottles, 'Total Bottles', 'From bottle collections', 'blue') +
            soCard('star', sum.total_points, 'IoT Points', 'Synced from IoT scan', 'green') +
            soCard('trophy', sum.achievements, 'Achievements', 'Badges earned', 'amber') +
            soCard('award', sum.certificates, 'Certificates', 'Awards issued', 'purple') +
        '</div>';

        html += '<section class="so-block">' +
            '<div class="so-block-head"><h4 class="so-section-title">Student details</h4>' +
            '<button type="button" class="so-inline-link" data-so-goto="full-details">Full details</button></div>' +
            '<div class="so-info-grid">' +
            info.map((pair) =>
                '<div class="so-info-item">' +
                    '<span class="so-info-label">' + esc(pair[0]) + '</span>' +
                    '<span class="so-info-value">' + esc(pair[1]) + '</span>' +
                '</div>'
            ).join('') +
            '</div></section>';

        html += '<section class="so-block">' +
            '<div class="so-block-head">' +
                '<h4 class="so-section-title">Recent activity</h4>' +
                (d.collections.length ? '<button type="button" class="so-inline-link" data-so-goto="collections">View all collections</button>' : '') +
            '</div>' +
            activityList(d.recent_activity) +
        '</section>';

        html += '<section class="so-block">' +
            '<div class="so-block-head">' +
                '<h4 class="so-section-title">Achievement progress</h4>' +
                ((d.achievements.progress.length || d.achievements.earned.length)
                    ? '<button type="button" class="so-inline-link" data-so-goto="achievements">View details</button>' : '') +
            '</div>' +
            progressList(d.achievements.progress) +
        '</section>';

        return html;
    }

    function renderCollections(d) {
        if (!d.collections.length) return emptyState('No bottle collections recorded yet.');

        const total = (d.meta && d.meta.collections_total) || 0;
        const shown = (d.meta && d.meta.collections_returned) || d.collections.length;

        let html = collectionTable(d.collections, true);
        html += '<p class="so-note">' + (total > shown
            ? 'Showing the latest ' + shown + ' of ' + total + ' recorded collections.'
            : 'Showing all ' + shown + ' recorded collections.') + '</p>';
        html += '<p class="so-note">Bottle totals come from Scan Bottle records; points are calculated by the IoT system.</p>';
        return html;
    }

    function earnedList(earned) {
        if (!earned.length) return emptyState('No achievements earned yet.');

        return '<div class="so-earned-list">' + earned.map((a) => {
            const usesBottles = a.required_bottles > 0;
            const reqValue = usesBottles ? a.required_bottles : a.points_required;
            const reqType = usesBottles ? 'bottles' : 'points';
            const hasRequirement = a.required_bottles > 0 || a.points_required > 0;

            return '<div class="so-earned-item">' +
                '<span class="so-earned-icon" aria-hidden="true">' + icon('circle-check', 18) + '</span>' +
                '<div class="so-earned-body">' +
                    '<strong>' + esc(a.title) + '</strong>' +
                    (a.badge_name ? '<span class="so-earned-badge">' + esc(a.badge_name) + '</span>' : '') +
                    (hasRequirement ? '<span class="so-earned-req">' + esc(reqType) + ' • ' + esc(reqValue) + ' required</span>' : '') +
                '</div>' +
                '<div class="so-earned-right">' +
                    '<span class="status-badge status-active">Earned</span>' +
                    '<span class="so-earned-date">' + esc(a.awarded_date) + '</span>' +
                '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function renderAchievements(d) {
        const earned = d.achievements.earned;
        const progress = d.achievements.progress;

        let html = '<section class="so-block">' +
            '<div class="so-block-head">' +
                '<h4 class="so-section-title">Earned badges</h4>' +
                '<span class="so-count">' + earned.length + '</span>' +
            '</div>' +
            earnedList(earned) +
        '</section>';

        html += '<section class="so-block">' +
            '<div class="so-block-head"><h4 class="so-section-title">Quest progress</h4></div>' +
            progressList(progress) +
        '</section>';

        return html;
    }

    function renderFullDetails(d) {
        const s = d.student;
        const sum = d.summary;
        const ap = d.achievement_progress || {};

        const section = (title, inner) =>
            '<section class="so-block">' +
                '<div class="so-block-head"><h4 class="so-section-title">' + esc(title) + '</h4></div>' +
                inner +
            '</section>';

        const grid = (pairs) => {
            const list = pairs.filter((pair) => pair[1] !== null && pair[1] !== undefined && pair[1] !== '');
            if (!list.length) return emptyState('No details recorded yet.');
            return '<div class="so-info-grid">' + list.map((pair) =>
                '<div class="so-info-item">' +
                    '<span class="so-info-label">' + esc(pair[0]) + '</span>' +
                    '<span class="so-info-value">' + esc(pair[1]) + '</span>' +
                '</div>'
            ).join('') + '</div>';
        };

        let html = section('Student information', grid([
            ['Full name', s.full_name],
            ['Student ID', s.student_id],
            ['LRN', s.lrn],
            ['Grade level', s.grade_level],
            ['Section', s.section],
            ['School year', s.school_year],
            ['Gender', s.gender],
            ['Status', s.status],
            ['QR code', s.qr_code],
            ['Assigned teacher', s.teacher_name],
            ['Birth date', s.birth_date],
            ['Age', s.age],
            ['Mother tongue', s.mother_tongue],
            ['IP / ethnic group', s.ip_ethnic_group],
            ['Religion', s.religion],
            ['Learning modality', s.learning_modality]
        ]));

        html += section('Address', grid([
            ['House no. / street', s.house_street],
            ['Barangay', s.barangay],
            ['Municipality / city', s.municipality_city],
            ['Province', s.province]
        ]));

        html += section('Family & guardian', grid([
            ['Father', s.father_name],
            ['Mother', s.mother_maiden_name],
            ['Guardian', s.guardian_name],
            ['Relationship', s.guardian_relationship],
            ['Contact number', s.contact_number],
            ['Remarks', s.remarks]
        ]));

        html += section('Collection summary', '<div class="so-cards so-cards-2">' +
            soCard('recycle', sum.total_bottles, 'Total Bottles', 'Sum of Scan Bottle records', 'blue') +
            soCard('inbox', sum.collections_count, 'Collection Records', 'Records logged', 'amber') +
            soCard('star', sum.total_points, 'IoT Points', 'Stored total', 'green') +
            soCard('circle-check', sum.points_earned, 'Points Earned', 'Sum across collections', 'purple') +
        '</div>');

        const goal = ap.next_goal;
        let progressBlock = grid([
            ['Current bottles', ap.current_bottles],
            ['Next achievement goal', goal ? goal.title : (ap.quests_total ? 'All active quests completed' : null)],
            ['Required', goal ? goal.required + ' ' + goal.metric : null],
            ['Remaining', goal ? goal.remaining + ' ' + goal.metric : null],
            ['Progress', goal ? goal.progress + '%' : null]
        ]);
        if (goal) {
            progressBlock += '<div class="so-progress so-progress-lg" role="progressbar" aria-valuenow="' + esc(goal.progress) + '" aria-valuemin="0" aria-valuemax="100" aria-label="Next achievement progress">' +
                '<span class="so-progress-bar" style="width:' + esc(goal.progress) + '%"></span></div>';
        } else if (!ap.quests_total) {
            progressBlock += emptyState('No active achievement quests yet.');
        }
        html += section('Achievement progress', progressBlock);

        html += section('Achievement records',
            '<div class="so-subhead">Earned badges (' + d.achievements.earned.length + ')</div>' +
            earnedList(d.achievements.earned) +
            '<div class="so-subhead">Quest progress</div>' +
            progressList(d.achievements.progress));

        html += section('Certificate records',
            d.certificates.length ? certList(d.certificates) : emptyState('No certificates issued to this student yet.'));

        return html;
    }

    function certList(certs) {
        return '<div class="so-cert-list">' + certs.map((c) => {
            const statusKey = String(c.status || '').toLowerCase();
            const statusClass = ['active', 'archived', 'inactive'].indexOf(statusKey) >= 0 ? ' status-' + statusKey : '';
            const meta = [c.type, c.school_year, c.awarded_date ? 'Awarded ' + c.awarded_date : '']
                .filter(Boolean).map(esc).join(' • ');

            return '<div class="so-cert-card">' +
                '<div class="so-cert-body">' +
                    '<div class="so-cert-head">' +
                        '<strong>' + esc(c.title || 'Certificate') + '</strong>' +
                        (c.status ? '<span class="status-badge' + statusClass + '">' + esc(c.status) + '</span>' : '') +
                    '</div>' +
                    '<span class="so-cert-meta">' + meta + '</span>' +
                    (c.description ? '<p class="so-cert-desc">' + esc(c.description) + '</p>' : '') +
                '</div>' +
                '<div class="so-cert-actions">' +
                    (c.view_url
                        ? '<a class="action-icon-btn view" href="' + esc(c.view_url) + '" title="Download certificate" aria-label="Download certificate" target="_blank" rel="noopener">' + icon('download', 16) + '</a>'
                        : '') +
                    (c.print_url
                        ? '<a class="action-icon-btn awards" href="' + esc(c.print_url) + '" title="Print certificate" aria-label="Print certificate" target="_blank" rel="noopener">' + icon('printer', 16) + '</a>'
                        : '') +
                '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function renderCertificates(d) {
        if (!d.certificates.length) return emptyState('No certificates issued to this student yet.');
        return certList(d.certificates);
    }

    function render() {
        if (!data) return;
        if (activeTab === 'collections') panel.innerHTML = renderCollections(data);
        else if (activeTab === 'achievements') panel.innerHTML = renderAchievements(data);
        else if (activeTab === 'certificates') panel.innerHTML = renderCertificates(data);
        else if (activeTab === 'full-details') panel.innerHTML = renderFullDetails(data);
        else panel.innerHTML = renderOverview(data);
    }

    document.querySelectorAll('[data-overview-open]').forEach((btn) => {
        btn.addEventListener('click', () => open(btn));
    });

    document.getElementById('soCloseBtn').addEventListener('click', close);
    document.getElementById('soCloseFooter').addEventListener('click', close);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('show')) close();
    });

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            setTab(tab.dataset.soTab);
            render();
        });
        tab.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            e.preventDefault();
            const next = e.key === 'ArrowRight'
                ? (index + 1) % tabs.length
                : (index - 1 + tabs.length) % tabs.length;
            tabs[next].focus();
            setTab(tabs[next].dataset.soTab);
            render();
        });
    });

    panel.addEventListener('click', (e) => {
        const goto = e.target.closest('[data-so-goto]');
        if (!goto) return;
        setTab(goto.dataset.soGoto);
        render();
    });
}
</script>
@endpush
