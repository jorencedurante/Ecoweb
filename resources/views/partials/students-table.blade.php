<div class="table-container" id="studentsTableContainer">
    <div class="table-header">
        <div class="table-header-left">
            <span class="stu-result-count" role="status">
                Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} entries{{ $appliedFilters->isNotEmpty() ? ' matching your filters' : '' }}
            </span>
        </div>
        <div class="table-toolbar-actions">
            <a href="{{ route(portal_prefix() . '.students.archived') }}" class="btn btn-outline btn-sm archived-btn">📦 Archived</a>
            @if(Auth::user()->role !== 'admin')
            <button type="button" class="btn btn-outline btn-sm archive-all-btn" style="border-color:var(--red);color:var(--red);" data-modal-target="archiveAllStudentModal" aria-label="Archive all students for a school year">📦 Archive All</button>
            @if($nullSchoolYearCount > 0)
            <button type="button" class="btn btn-outline btn-sm fix-school-years-btn" style="border-color:var(--amber);color:var(--amber);" data-modal-target="fixSchoolYearsModal" aria-label="Assign school year to {{ $nullSchoolYearCount }} student(s) without one">⚠️ Fix School Years</button>
            @endif
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
                        data-overview-url="{{ route(portal_prefix() . '.students.overview', $student->id) }}"
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
                            data-overview-url="{{ route(portal_prefix() . '.students.overview', $student->id) }}"
                            data-student-key="{{ $student->id }}"
                            data-tab="overview"><x-icon name="eye" :size="16" /></button>
                        <button type="button" class="action-icon-btn achievements" title="View Achievements" aria-label="View achievements"
                            data-overview-open
                            data-overview-url="{{ route(portal_prefix() . '.students.overview', $student->id) }}"
                            data-student-key="{{ $student->id }}"
                            data-tab="achievements"><x-icon name="trophy" :size="16" /></button>
                        <button type="button" class="action-icon-btn awards" title="View Certificates" aria-label="View certificates"
                            data-overview-open
                            data-overview-url="{{ route(portal_prefix() . '.students.overview', $student->id) }}"
                            data-student-key="{{ $student->id }}"
                            data-tab="certificates"><x-icon name="award" :size="16" /></button>
                        @if(Auth::user()->role !== 'admin')
                        @php
                            $editEnrollment = $student->enrollments
                                ->when(Auth::user()->isTeacher(), fn ($en) => $en->where('teacher_id', Auth::id()))
                                ->sortByDesc('id')
                                ->first();
                        @endphp
                        <button type="button" class="action-icon-btn edit" title="Edit Student" aria-label="Edit student" data-modal-target="editStudentModal"
                            data-id="{{ $student->id }}"
                            data-lrn="{{ $student->lrn }}"
                            data-first="{{ $student->first_name }}"
                            data-middle="{{ $student->middle_name }}"
                            data-last="{{ $student->last_name }}"
                            data-grade="{{ $student->grade_level }}"
                            data-gender="{{ $student->gender }}"
                            data-birth="{{ $student->birth_date ? $student->birth_date->format('Y-m-d') : '' }}"
                            data-section="{{ $editEnrollment?->section ?? '' }}"
                            data-school-year="{{ $editEnrollment?->school_year ?? '' }}"
                            data-house="{{ $student->house_street }}"
                            data-barangay="{{ $student->barangay }}"
                            data-municipality="{{ $student->municipality_city }}"
                            data-province="{{ $student->province }}"
                            data-region="{{ $student->region }}"
                            data-father="{{ $student->father_name }}"
                            data-mother="{{ $student->mother_maiden_name }}"
                            data-guardian="{{ $student->guardian_name }}"
                            data-guardian-rel="{{ $student->guardian_relationship }}"
                            data-contact="{{ $student->contact_number }}"
                            data-ethnic="{{ $student->ip_ethnic_group }}"
                            data-religion="{{ $student->religion }}"
                            data-nationality="{{ $student->nationality }}"
                            data-modality="{{ $student->learning_modality }}"
                            data-remarks="{{ $student->remarks }}"><x-icon name="pencil" :size="15" /></button>
                        @if($student->status !== 'Archived')
                        <form method="POST" action="{{ route(portal_prefix() . '.students.archive', $student->id) }}" class="inline-action-form" onsubmit="return confirm('Archive this student?')">
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
    @php
        $pagerCurrent = $students->currentPage();
        $pagerLast = $students->lastPage();
        if ($pagerLast <= 5) {
            $pagerWindow = range(1, $pagerLast);
        } elseif ($pagerCurrent <= 2) {
            $pagerWindow = range(1, 3);
        } elseif ($pagerCurrent >= $pagerLast - 1) {
            $pagerWindow = range($pagerLast - 2, $pagerLast);
        } else {
            $pagerWindow = range($pagerCurrent - 1, $pagerCurrent + 1);
        }
        $pagerPages = array_values(array_unique(array_merge([1, $pagerLast], $pagerWindow)));
        sort($pagerPages);
    @endphp
    <style>
        /* Compact pager for the main Student table (admin + teacher). */
        #studentsTableContainer .students-pager .page-btns {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
        }
        #studentsTableContainer .students-pager .page-btn {
            min-width: 30px;
            text-align: center;
            white-space: nowrap;
        }
        #studentsTableContainer .students-pager .page-btn.disabled {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
            background: #f3f4f6;
            color: #9ca3af;
            border-color: #e2e8f0;
        }
        #studentsTableContainer .students-pager .page-ellipsis {
            padding: 6px 2px;
            color: #6b7280;
            font-size: 13px;
        }
        @media (max-width: 640px) {
            #studentsTableContainer .students-pager {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            #studentsTableContainer .students-pager .page-btns {
                justify-content: flex-start;
            }
        }
    </style>
    <div class="pagination students-pager">
        <span class="page-info">Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} entries</span>
        <div class="page-btns">
            @if ($pagerCurrent > 1)
                <a href="{{ $students->previousPageUrl() }}" data-page="{{ $pagerCurrent - 1 }}" class="page-btn" aria-label="Previous page">&lsaquo; Previous</a>
            @else
                <span class="page-btn disabled" aria-disabled="true">&lsaquo; Previous</span>
            @endif
            @php $pagerPrevious = null; @endphp
            @foreach ($pagerPages as $pagerPage)
                @if ($pagerPrevious !== null && $pagerPage - $pagerPrevious > 1)
                    <span class="page-ellipsis" aria-hidden="true">&hellip;</span>
                @endif
                <a href="{{ $students->url($pagerPage) }}" data-page="{{ $pagerPage }}" class="page-btn {{ $pagerCurrent == $pagerPage ? 'active' : '' }}" @if($pagerCurrent == $pagerPage) aria-current="page" @endif>{{ $pagerPage }}</a>
                @php $pagerPrevious = $pagerPage; @endphp
            @endforeach
            @if ($pagerCurrent < $pagerLast)
                <a href="{{ $students->nextPageUrl() }}" data-page="{{ $pagerCurrent + 1 }}" class="page-btn" aria-label="Next page">Next &rsaquo;</a>
            @else
                <span class="page-btn disabled" aria-disabled="true">Next &rsaquo;</span>
            @endif
        </div>
    </div>
    @endif
</div>
