@extends('layouts.admin')

@section('title', 'EcoCollect - Archived Students')
@section('page-title', 'Archived Students')
@section('page-subtitle', 'View archived student records')

@section('content')
    <style>
        .centered-btn,
        .btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            line-height: 1.4 !important;
        }
        .action-btns {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
        }
        .action-btns .btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 4px !important;
            padding: 6px 10px !important;
            line-height: 1.4 !important;
            border-radius: 6px !important;
        }
        .stu-archive-tab {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            line-height: 1.4 !important;
        }
        @media (max-width: 768px) {
            .sy-group {
                margin-bottom: 12px !important;
                border-radius: 6px !important;
            }
            .sy-group-header {
                padding: 10px 14px !important;
            }
            .sy-pagination {
                flex-direction: column !important;
                gap: 8px !important;
                align-items: flex-start !important;
            }
            .sy-page-btn {
                width: 100% !important;
                text-align: left !important;
            }
            .table-responsive {
                overflow-x: auto !important;
            }
            table {
                min-width: 600px !important;
            }
        }

        /* School-Year Archives table width and column distribution */
        .sy-table {
            width: 100% !important;
            table-layout: fixed !important;
        }
        .sy-table th.sy-col-actions,
        .sy-table td.sy-col-actions {
            width: 140px !important;
        }
        .sy-table th.sy-col-lrn,
        .sy-table td.sy-col-lrn {
            width: 120px !important;
        }
        .sy-table th.sy-col-grade,
        .sy-table td.sy-col-grade {
            width: 100px !important;
        }
        .sy-table th.sy-col-points,
        .sy-table td.sy-col-points {
            width: 110px !important;
        }
        .sy-table th.sy-col-teacher,
        .sy-table td.sy-col-teacher {
            width: 150px !important;
        }
        .sy-table th.sy-col-name,
        .sy-table td.sy-col-name {
            width: auto !important;
        }

        /* Pagination alignment */
        .sy-pagination {
            justify-content: space-between !important;
        }

        /* View Info button sizing */
        .view-info-btn {
            min-width: 38px !important;
            min-height: 38px !important;
            padding: 0 !important;
            gap: 0 !important;
            background: var(--blue) !important;
            color: #fff !important;
            border: none !important;
            border-radius: 6px !important;
        }
        .view-info-btn:hover {
            background: var(--blue-dark) !important;
        }

        /* Unarchive button */
        .unarchive-btn {
            min-width: 38px !important;
            min-height: 38px !important;
            padding: 0 !important;
            gap: 0 !important;
            color: #dc2626 !important;
            border-color: #fca5a5 !important;
            background: #fef2f2 !important;
        }
        .unarchive-btn:hover {
            background: #fee2e2 !important;
            border-color: #f87171 !important;
        }

        /* Action icon SVG sizing */
        .view-info-btn svg,
        .unarchive-btn svg {
            width: 16px !important;
            height: 16px !important;
            stroke-width: 2 !important;
        }
        .view-info-btn svg {
            stroke: #fff !important;
            fill: none !important;
        }
        .unarchive-btn svg {
            stroke: #dc2626 !important;
            fill: none !important;
        }
        .unarchive-btn:hover {
            background: #fee2e2 !important;
            border-color: #f87171 !important;
        }

        /* Unarchive confirmation modal */
        .unarchive-modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        .unarchive-modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        .unarchive-modal-content {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 480px;
            margin: 20px;
        }
        .unarchive-modal-header {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px 8px 0 0;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .unarchive-modal-header .modal-title {
            font-size: 15px;
            font-weight: 700;
            color: #dc2626;
            margin: 0;
        }
        .unarchive-modal-body {
            padding: 20px;
        }
        .unarchive-modal-body p {
            margin: 0 0 16px 0;
            color: #374151;
            font-size: 14px;
            line-height: 1.5;
        }
        .unarchive-modal-detail {
            margin-bottom: 12px;
            font-size: 13px;
        }
        .unarchive-modal-detail strong {
            color: #111827;
        }
        .unarchive-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 20px;
            border-top: 1px solid #e5e7eb;
            border-radius: 0 0 8px 8px;
        }
        .unarchive-modal-btn-cancel {
            padding: 8px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            color: #374151;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
        }
        .unarchive-modal-btn-cancel:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }
        .unarchive-modal-btn-confirm {
            padding: 8px 16px;
            border: 1px solid #dc2626;
            border-radius: 6px;
            background: #dc2626;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .unarchive-modal-btn-confirm:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }

        @media (max-width: 768px) {
            .sy-table {
                min-width: 700px !important;
            }
        }
    </style>
    <div class="archived-students-page">
    <div class="table-container archived-table-container">
        <div class="table-header archived-table-header">
            <div class="table-header-left"></div>
            <a href="{{ route('admin.students') }}" class="btn btn-outline btn-sm btn-back centered-btn" aria-label="Back to active students">← Back to Active Students</a>
        </div>

        <!-- Archive Tabs -->
        <div class="stu-archive-tabs" role="tablist" aria-label="Archive views">
            <button type="button" class="stu-archive-tab active" role="tab" id="tab-individually" aria-selected="true" aria-controls="panel-individually" data-archive-tab="individually">
                Individually Archived
            </button>
            <button type="button" class="stu-archive-tab" role="tab" id="tab-school-years" aria-selected="false" aria-controls="panel-school-years" tabindex="-1" data-archive-tab="school-years">
                School-Year Archives
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

        <!-- Unarchive Confirmation Modal -->
        <div class="unarchive-modal" id="unarchiveModal">
            <div class="unarchive-modal-content">
                <div class="unarchive-modal-header">
                    <i class="fas fa-rotate-left" style="font-size:18px;color:#dc2626;"></i>
                    <h2 class="modal-title">Unarchive Student?</h2>
                </div>
                <div class="unarchive-modal-body">
                    <p id="unarchiveModalMessage">Are you sure you want to restore this student to the active class for the selected school year?</p>
                    <div class="unarchive-modal-detail" id="unarchiveModalStudentName"><strong>Name:</strong> <span id="unarchiveModalStudentNameValue"></span></div>
                    <div class="unarchive-modal-detail" id="unarchiveModalStudentLRN"><strong>LRN:</strong> <span id="unarchiveModalStudentLRNValue"></span></div>
                    <div class="unarchive-modal-detail" id="unarchiveModalStudentGrade"><strong>Grade Level:</strong> <span id="unarchiveModalStudentGradeValue"></span></div>
                    <div class="unarchive-modal-detail" id="unarchiveModalStudentYear"><strong>School Year:</strong> <span id="unarchiveModalStudentYearValue"></span></div>
                    <div class="unarchive-modal-detail" id="unarchiveModalStudentTeacher"><strong>Assigned Teacher:</strong> <span id="unarchiveModalStudentTeacherValue"></span></div>
                </div>
                <div class="unarchive-modal-footer">
                    <button type="button" class="unarchive-modal-btn-cancel" id="unarchiveModalCancel">Cancel</button>
                    <button type="button" class="unarchive-modal-btn-confirm" id="unarchiveModalConfirm">Confirm Unarchive</button>
                </div>
            </div>
        </div>
        <!-- Bulk Unarchive Confirmation Modal -->
        <div class="unarchive-modal" id="unarchiveAllModal">
            <div class="unarchive-modal-content">
                <div class="unarchive-modal-header">
                    <i class="fas fa-rotate-left" style="font-size:18px;color:#dc2626;"></i>
                    <h2 class="modal-title">Unarchive All Students?</h2>
                </div>
                <div class="unarchive-modal-body">
                    <p id="unarchiveAllModalMessage">This will restore eligible archived enrollments from the selected school year to your active class. Historical records will remain unchanged.</p>
                    <div class="unarchive-modal-detail" id="unarchiveAllModalSchoolYear"><strong>School Year:</strong> <span id="unarchiveAllModalSchoolYearValue"></span></div>
                    <div class="unarchive-modal-detail" id="unarchiveAllModalEligible"><strong>Eligible Students:</strong> <span id="unarchiveAllModalEligibleValue"></span></div>
                    <div class="unarchive-modal-detail" id="unarchiveAllModalConflicts"><strong>Conflicting Students:</strong> <span id="unarchiveAllModalConflictsValue"></span></div>
                    <div class="unarchive-modal-detail" id="unarchiveAllModalTeacher"><strong>Assigned Teacher:</strong> <span id="unarchiveAllModalTeacherValue"></span></div>
                </div>
                <div class="unarchive-modal-footer">
                    <button type="button" class="unarchive-modal-btn-cancel" id="unarchiveAllModalCancel">Cancel</button>
                    <button type="button" class="unarchive-modal-btn-confirm" id="unarchiveAllModalConfirm">Confirm Unarchive All</button>
                </div>
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

    // School-year archives AJAX loader
    let syLoaded = false;
    let sySearchTimer = null;
    let syGroupState = {}; // school_year -> { expanded, page, loading, search }

    function renderSchoolYearGroups(groups) {
        const container = document.getElementById('syArchiveGroups');
        const emptyState = document.getElementById('syEmptyState');
        if (!container) return;

        if (!groups || groups.length === 0) {
            container.innerHTML = '';
            if (emptyState) emptyState.style.display = '';
            return;
        }

        if (emptyState) emptyState.style.display = 'none';

        let html = '';
        groups.forEach(group => {
            const sy = group.school_year || 'Unknown';
            const studentCount = group.total || (group.students ? group.students.length : 0);
            const state = syGroupState[sy] || { expanded: false, page: 1, loaded: false, search: '' };
            const isExpanded = state.expanded;
            const chevron = isExpanded ? '▼' : '▶';

            html += `
            <div class="sy-group" data-school-year="${sy}" style="border:1px solid #e2e8f0;border-radius:8px;margin-bottom:16px;overflow:hidden;background:#fff;">
                <div class="sy-group-header" style="background:#f1f5f9;padding:12px 16px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;cursor:pointer;" data-school-year="${sy}" data-student-count="${studentCount}">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="sy-chevron" style="font-size:10px;color:#6b7280;">${chevron}</span>
                        <strong style="font-size:14px;color:#111827;">SY ${sy}</strong>
                    </div>
                    <div style="font-size:12px;color:#6b7280;">${studentCount} Student${studentCount !== 1 ? 's' : ''} Archived</div>
                </div>
                <div class="sy-group-content" id="sy-content-${sy}" style="display:none;padding:0;">
                    <div class="sy-group-search" style="padding:12px 16px;border-bottom:1px solid #e2e8f0;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;">
                        <input type="text" id="sy-group-search-${sy}" class="sy-group-search-input" data-school-year="${sy}" placeholder="Search student name or LRN..." aria-label="Search students in SY ${sy}" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:12px;box-sizing:border-box;">
                        <button type="button" class="btn btn-danger btn-sm" id="sy-unarchive-all-${sy}" data-school-year="${sy}" style="padding:6px 12px;font-size:12px;border-radius:6px;margin-left:8px;">Unarchive All</button>
                    </div>
                    <div id="sy-loading-${sy}" style="text-align:center;padding:24px;color:#6b7280;font-size:12px;display:none;">Loading students...</div>
                    <div id="sy-table-${sy}" style="display:none;">
                        <table class="sy-table" style="border-collapse:collapse;font-size:12px;">
                            <thead>
                                <tr>
                                    <th scope="col" class="sy-col-lrn" style="padding:10px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;background:#f8fafc;">LRN</th>
                                    <th scope="col" class="sy-col-name" style="padding:10px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;background:#f8fafc;">Name</th>
                                    <th scope="col" class="sy-col-grade" style="padding:10px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;background:#f8fafc;">Grade Level</th>
                                    <th scope="col" class="sy-col-points" style="padding:10px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;background:#f8fafc;">Total Points</th>
                                    <th scope="col" class="sy-col-teacher" style="padding:10px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;background:#f8fafc;">Teacher</th>
                                    <th scope="col" class="sy-col-actions" style="padding:10px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;background:#f8fafc;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sy-tbody-${sy}">
                            </tbody>
                        </table>
                    </div>
                    <div id="sy-pagination-${sy}" class="sy-pagination" style="display:none;padding:12px 16px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:13px;color:#6b7280;">
                        <span id="sy-page-info-${sy}">Showing 1 to 10 of ${studentCount} students</span>
                        <div id="sy-page-btns-${sy}" style="display:flex;gap:4px;">
                        </div>
                    </div>
                </div>
            </div>
            `;
        });
        container.innerHTML = html;
    }

    // Event delegation for school-year group expand/collapse, pagination, and view info
    const syContainer = document.getElementById('syArchiveGroups');
    if (syContainer) {
        syContainer.addEventListener('click', function(e) {
            // Group expand/collapse
            const header = e.target.closest('.sy-group-header');
            if (header) {
                const schoolYear = header.getAttribute('data-school-year');
                const studentCount = parseInt(header.getAttribute('data-student-count') || '0', 10);
                toggleSyGroup(schoolYear, studentCount);
                return;
            }

            // Pagination buttons
            const pageBtn = e.target.closest('.sy-page-btn');
            if (pageBtn) {
                const page = pageBtn.getAttribute('data-page');
                const schoolYear = pageBtn.getAttribute('data-school-year');
                loadSyGroupStudents(schoolYear, parseInt(page, 10), getGroupSearch(schoolYear));
                return;
            }

            // View Info buttons
            const viewInfoBtn = e.target.closest('.view-info-btn');
            if (viewInfoBtn) {
                const studentId = viewInfoBtn.getAttribute('data-student-id');
                window.location.href = "{{ route('students.info', 0) }}".replace('/0', '/' + studentId);
                return;
            }

            // Unarchive buttons
            const unarchiveBtn = e.target.closest('.unarchive-btn');
            if (unarchiveBtn) {
                const studentId = unarchiveBtn.getAttribute('data-student-id');
                const studentName = unarchiveBtn.getAttribute('data-student-name');
                const studentLRN = unarchiveBtn.getAttribute('data-student-lrn');
                const studentGrade = unarchiveBtn.getAttribute('data-student-grade');
                const studentYear = unarchiveBtn.getAttribute('data-student-year');
                const studentTeacher = unarchiveBtn.getAttribute('data-student-teacher');
                openUnarchiveModal(studentId, studentName, studentLRN, studentGrade, studentYear, studentTeacher);
                return;
            }

            // Unarchive All buttons
            const unarchiveAllBtn = e.target.closest('[id^="sy-unarchive-all-"]');
            if (unarchiveAllBtn) {
                const schoolYear = unarchiveAllBtn.getAttribute('data-school-year');
                openUnarchiveAllModal(schoolYear);
                return;
            }
        });

        // Group search inputs
        syContainer.addEventListener('input', function(e) {
            if (e.target.classList.contains('sy-group-search-input')) {
                const schoolYear = e.target.getAttribute('data-school-year');
                const term = e.target.value.trim();
                syGroupState[schoolYear] = syGroupState[schoolYear] || {};
                syGroupState[schoolYear].search = term;
                syGroupState[schoolYear].page = 1;
                loadSyGroupStudents(schoolYear, 1, term);
            }
        });
    }

    function getGroupSearch(schoolYear) {
        return (syGroupState[schoolYear] && syGroupState[schoolYear].search) || '';
    }

    function toggleSyGroup(schoolYear, totalCount) {
        const content = document.getElementById(`sy-content-${schoolYear}`);
        const chevron = document.querySelector(`[data-school-year="${schoolYear}"] .sy-chevron`);
        const table = document.getElementById(`sy-table-${schoolYear}`);
        const loading = document.getElementById(`sy-loading-${schoolYear}`);
        const pagination = document.getElementById(`sy-pagination-${schoolYear}`);

        const state = syGroupState[schoolYear] || { expanded: false, page: 1, loaded: false, search: '' };

        if (!state.expanded) {
            // Expand
            state.expanded = true;
            content.style.display = 'block';
            chevron.textContent = '▼';

            if (!state.loaded) {
                loading.style.display = 'block';
                table.style.display = 'none';
                pagination.style.display = 'none';
                loadSyGroupStudents(schoolYear, 1, state.search || '');
                state.loaded = true;
            } else {
                loading.style.display = 'none';
                table.style.display = 'table';
                pagination.style.display = 'flex';
            }
        } else {
            // Collapse
            state.expanded = false;
            content.style.display = 'none';
            chevron.textContent = '▶';
        }

        syGroupState[schoolYear] = state;
    }

    function loadSyGroupStudents(schoolYear, page, search = '') {
        const url = new URL("{{ route('admin.students.school-year-archives') }}");
        url.searchParams.set('school_year', schoolYear);
        url.searchParams.set('page', page);
        if (search) url.searchParams.set('search', search);

        const loading = document.getElementById(`sy-loading-${schoolYear}`);
        const table = document.getElementById(`sy-table-${schoolYear}`);
        const tbody = document.getElementById(`sy-tbody-${schoolYear}`);
        const pagination = document.getElementById(`sy-pagination-${schoolYear}`);
        const pageInfo = document.getElementById(`sy-page-info-${schoolYear}`);
        const pageBtns = document.getElementById(`sy-page-btns-${schoolYear}`);

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load students');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const groups = data.groups;
                const group = groups.find(g => g.school_year === schoolYear);
                const students = group ? group.students : [];
                const pag = data.pagination;

                loading.style.display = 'none';

                if (!students || students.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#6b7280;">No matching students found.</td></tr>';
                    if (pagination) pagination.style.display = 'none';
                    return;
                }

                let html = '';
                students.forEach(s => {
                    html += `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td class="sy-col-lrn" style="padding:10px 14px;color:#6b7280;">${s.lrn}</td>
                        <td class="sy-col-name" style="padding:10px 14px;font-weight:600;color:#111827;"><strong>${s.full_name}</strong></td>
                        <td class="sy-col-grade" style="padding:10px 14px;color:#6b7280;">${s.grade_level}</td>
                        <td class="sy-col-points" style="padding:10px 14px;color:#059669;font-weight:600;">${s.total_points}</td>
                        <td class="sy-col-teacher" style="padding:10px 14px;color:#6b7280;">${s.teacher_name}</td>
                        <td class="sy-col-actions" style="padding:10px 14px;">
                            <button type="button" class="view-info-btn btn btn-view btn-xs" data-student-id="${s.id}" data-student-name="${s.full_name}" data-student-lrn="${s.lrn}" data-student-grade="${s.grade_level}" data-student-year="${s.archive_school_year}" data-student-teacher="${s.teacher_name}" aria-label="View Student Information" title="View Student Information" style="display:inline-flex;align-items:center;justify-content:center;gap:0;padding:0;margin-right:4px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                            <button type="button" class="unarchive-btn btn btn-xs" data-student-id="${s.id}" data-student-name="${s.full_name}" data-student-lrn="${s.lrn}" data-student-grade="${s.grade_level}" data-student-year="${s.archive_school_year}" data-student-teacher="${s.teacher_name}" aria-label="Unarchive Student" title="Unarchive Student" style="display:inline-flex;align-items:center;justify-content:center;gap:0;padding:0;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                    <path d="M3 3v5h5"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    `;
                });
                tbody.innerHTML = html;

                if (table) table.style.display = 'table';

                // Render pagination
                renderSyPagination(schoolYear, pag);
                if (pagination) pagination.style.display = 'flex';
            }
        })
        .catch(err => {
            console.error('School-year group students error:', err);
            loading.style.display = 'none';
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#dc2626;">Unable to load students.</td></tr>';
        });
    }

    function renderSyPagination(schoolYear, pag) {
        const pageInfo = document.getElementById(`sy-page-info-${schoolYear}`);
        const pageBtns = document.getElementById(`sy-page-btns-${schoolYear}`);

        if (!pageInfo || !pageBtns || !pag) return;

        pageInfo.textContent = `Showing ${pag.from} to ${pag.to} of ${pag.total} students`;

        let btnsHtml = '';

        // Prev
        if (pag.current_page > 1) {
            btnsHtml += `<button type="button" class="sy-page-btn" data-page="${pag.current_page - 1}" data-school-year="${schoolYear}" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:#059669;background:#d1fae5;cursor:pointer;">Prev</button>`;
        }

        // Page numbers
        const start = Math.max(1, pag.current_page - 2);
        const end = Math.min(pag.last_page, pag.current_page + 2);
        for (let p = start; p <= end; p++) {
            const active = p === pag.current_page;
            btnsHtml += `<button type="button" class="sy-page-btn" data-page="${p}" data-school-year="${schoolYear}" style="padding:6px 10px;border:1px solid ${active ? '#059669' : '#cbd5e1'};border-radius:6px;font-size:13px;color:${active ? '#059669' : '#374151'};background:${active ? '#d1fae5' : '#fff'};cursor:${active ? 'default' : 'pointer'};">${p}</button>`;
        }

        // Next
        if (pag.current_page < pag.last_page) {
            btnsHtml += `<button type="button" class="sy-page-btn" data-page="${pag.current_page + 1}" data-school-year="${schoolYear}" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;color:#059669;background:#d1fae5;cursor:pointer;">Next</button>`;
        }

        pageBtns.innerHTML = btnsHtml;
    }

    // Unarchive confirmation modal
    let pendingUnarchiveStudentId = null;

    function openUnarchiveModal(studentId, studentName, studentLRN, studentGrade, studentYear, studentTeacher) {
        pendingUnarchiveStudentId = studentId;

        document.getElementById('unarchiveModalStudentNameValue').textContent = studentName;
        document.getElementById('unarchiveModalStudentLRNValue').textContent = studentLRN;
        document.getElementById('unarchiveModalStudentGradeValue').textContent = studentGrade;
        document.getElementById('unarchiveModalStudentYearValue').textContent = studentYear;
        document.getElementById('unarchiveModalStudentTeacherValue').textContent = studentTeacher || '—';

        const modal = document.getElementById('unarchiveModal');
        modal.classList.add('show');
    }

    function closeUnarchiveModal() {
        pendingUnarchiveStudentId = null;
        const modal = document.getElementById('unarchiveModal');
        modal.classList.remove('show');
    }

    // Modal close handlers
    const unarchiveModal = document.getElementById('unarchiveModal');
    if (unarchiveModal) {
        document.getElementById('unarchiveModalCancel').addEventListener('click', closeUnarchiveModal);

        document.getElementById('unarchiveModalConfirm').addEventListener('click', function() {
            if (!pendingUnarchiveStudentId) return;

            const studentId = pendingUnarchiveStudentId;
            closeUnarchiveModal();

            const btn = document.getElementById('unarchiveModalConfirm');
            const originalText = btn.textContent;
            btn.textContent = 'Unarchiving...';
            btn.disabled = true;

            fetch("{{ route('students.unarchive', 0) }}".replace('/0', '/' + studentId), {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Unarchive failed');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove the student row from the table
                    const row = document.querySelector(`button.view-info-btn[data-student-id="${studentId}"]`)?.closest('tr');
                    if (row) {
                        row.remove();
                    }

                    // Update group count
                    const header = document.querySelector(`.sy-group-header[data-student-count]`);
                    // The count will be updated on next load; for now just remove the row

                    // Show success briefly
                    btn.textContent = 'Unarchived!';
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.disabled = false;
                    }, 1000);
                } else {
                    alert(data.message || 'Unarchive failed.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Unarchive error:', err);
                alert(err.message || 'Unable to unarchive student.');
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });

        // Close on backdrop click
        unarchiveModal.addEventListener('click', function(e) {
            if (e.target === unarchiveModal) closeUnarchiveModal();
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && unarchiveModal.classList.contains('show')) {
                closeUnarchiveModal();
            }
        });
    }

    // Bulk Unarchive confirmation modal
    let pendingUnarchiveAllSchoolYear = null;

    function openUnarchiveAllModal(schoolYear) {
        pendingUnarchiveAllSchoolYear = schoolYear;

        document.getElementById('unarchiveAllModalSchoolYearValue').textContent = schoolYear;
        document.getElementById('unarchiveAllModalEligibleValue').textContent = '--';
        document.getElementById('unarchiveAllModalConflictsValue').textContent = '--';
        document.getElementById('unarchiveAllModalTeacherValue').textContent = '--';

        const modal = document.getElementById('unarchiveAllModal');
        modal.classList.add('show');
    }

    function closeUnarchiveAllModal() {
        pendingUnarchiveAllSchoolYear = null;
        const modal = document.getElementById('unarchiveAllModal');
        modal.classList.remove('show');
    }

    // Bulk modal close handlers
    const unarchiveAllModal = document.getElementById('unarchiveAllModal');
    if (unarchiveAllModal) {
        document.getElementById('unarchiveAllModalCancel').addEventListener('click', closeUnarchiveAllModal);

        document.getElementById('unarchiveAllModalConfirm').addEventListener('click', function() {
            if (!pendingUnarchiveAllSchoolYear) return;

            const schoolYear = pendingUnarchiveAllSchoolYear;
            closeUnarchiveAllModal();

            const btn = document.getElementById('unarchiveAllModalConfirm');
            const originalText = btn.textContent;
            btn.textContent = 'Unarchiving All...';
            btn.disabled = true;

            fetch("{{ route('students.unarchive-all') }}", {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    school_year: schoolYear,
                }),
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Unarchive All failed');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Refresh school year archives to reflect changes
                    syGroupState = {};
                    loadSchoolYearArchives();
                    btn.textContent = 'Restored!';
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.disabled = false;
                    }, 1000);
                } else {
                    alert(data.message || 'Unarchive All failed.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Unarchive All error:', err);
                alert(err.message || 'Unable to unarchive all students.');
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });

        // Close on backdrop click
        unarchiveAllModal.addEventListener('click', function(e) {
            if (e.target === unarchiveAllModal) closeUnarchiveAllModal();
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && unarchiveAllModal.classList.contains('show')) {
                closeUnarchiveAllModal();
            }
        });
    }

    function loadSchoolYearArchives(search = '') {
        const url = new URL("{{ route('admin.students.school-year-archives') }}");
        if (search) url.searchParams.set('search', search);

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load school-year archives');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderSchoolYearGroups(data.groups);
            }
        })
        .catch(err => {
            console.error('School-year archives error:', err);
            const container = document.getElementById('syArchiveGroups');
            if (container) container.innerHTML = '<div style="text-align:center;padding:40px;color:#dc2626;">Unable to load school-year archives.</div>';
        });
    }

    // Load school-year archives when tab is first activated
    const tabSchoolYears = document.getElementById('tab-school-years');
    if (tabSchoolYears) {
        tabSchoolYears.addEventListener('click', function() {
            if (!syLoaded) {
                loadSchoolYearArchives();
                syLoaded = true;
            }
        });
    }

    // School-year search with debounce
    const syInput = document.getElementById('sySearchInput');
    if (syInput) {
        syInput.addEventListener('input', function(){
            clearTimeout(sySearchTimer);
            sySearchTimer = setTimeout(() => {
                const term = this.value.trim();
                // Reset all group states
                syGroupState = {};
                loadSchoolYearArchives(term);
            }, 300);
        });
    }

    // Load on page load if school-years tab is active
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('tab-school-years').classList.contains('active') && !syLoaded) {
            loadSchoolYearArchives();
            syLoaded = true;
        }
    });
})();
</script>
@endpush
