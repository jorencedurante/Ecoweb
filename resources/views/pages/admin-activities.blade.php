@extends('layouts.admin')

@if(auth()->user()->hasTeacherScope())
@section('title', 'EcoCollect - Item Claims')
@section('page-title', 'Item Claims')
@section('page-subtitle', 'Summary of item claims made by students in your assigned class.')
@else
@section('title', 'EcoCollect - Student Activities')
@section('page-title', 'Student Activities')
@section('page-subtitle', 'Summary of student activity and ranking grouped by teacher.')
@endif

@section('content')
    <a href="{{ route(portal_prefix() . '.reports') }}" class="back-link">← Back to Reports</a>

    @if(auth()->user()->hasTeacherScope())
        {{-- ==================== TEACHER / SUB TEACHER: SCOPED ITEM CLAIMS ==================== --}}
        <div class="filter-card">
            <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
                <i class="fas fa-filter"></i> Filters
            </button>
            <div class="filter-body">
                <form method="GET" action="{{ route(portal_prefix() . '.admin-activities') }}" class="filter-form tc-filter-form" role="search">
                    <div class="filter-search">
                        <label for="teacherClaimsSearch">Search</label>
                        <input type="text" id="teacherClaimsSearch" name="search" placeholder="Search by name or LRN..." value="{{ request('search') }}" aria-label="Search by name or LRN" autocomplete="off">
                    </div>
                    <div class="filter-search">
                        <label>&nbsp;</label>
                        <small class="text-muted" style="color:var(--text-light);font-size:12px;padding-top:10px;">Item claims shown are from your assigned class only.</small>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h4 style="font-size:14px;font-weight:600;">Item Claims</h4>
                <a href="{{ route(portal_prefix() . '.reports.item-claims.print') }}?{{ http_build_query(request()->query()) }}" target="_blank" class="btn btn-filter" style="font-size:12px;padding:6px 14px;text-decoration:none;">🖨️ Print Report</a>
            </div>
            <div class="table-responsive">
            <table id="teacherClaimsTable">
                <colgroup>
                    <col style="width:40%;">
                    <col style="width:20%;">
                    <col style="width:20%;">
                    <col style="width:20%;">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Student Name</th>
                        <th scope="col">LRN</th>
                        <th scope="col">Grade Level</th>
                        <th scope="col">Total Item Claims</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $s)
                    @php
                        $claims = $s->claims->sortByDesc('claim_date')->sortByDesc('id')->values();
                        $claimsPayload = $claims->map(function ($c) {
                            return [
                                'item_name' => $c->item_name,
                                'quantity' => (int) $c->quantity,
                                'points_deducted' => (int) ($c->points_deducted ?? 0),
                                'claim_date' => $c->claim_date ? \Carbon\Carbon::parse($c->claim_date)->format('M d, Y') : 'N/A',
                                'status' => $c->status ?? 'Pending',
                            ];
                        })->all();
                    @endphp
                    <tr>
                        <td class="teacher-claims-name-cell">
                            <button type="button" class="teacher-claims-student-btn"
                                    aria-haspopup="dialog" aria-controls="teacherClaimsModal"
                                    data-student-name="{{ $s->full_name }}"
                                    data-student-lrn="{{ $s->lrn }}"
                                    data-student-grade="{{ $s->grade_level }}"
                                    data-claims="{{ json_encode($claimsPayload) }}">{{ $s->full_name }}</button>
                        </td>
                        <td>{{ $s->lrn }}</td>
                        <td>{{ $s->grade_level }}</td>
                        <td class="teacher-claims-count">{{ $s->total_claims ?? 0 }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-light);">{{ request()->filled('search') ? 'No students found.' : 'No item claims found for your assigned students.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            @php
                $tcCurrent = $students->currentPage();
                $tcLast = $students->lastPage();
                if ($tcLast <= 4) {
                    $tcWindow = range(1, $tcLast);
                } elseif ($tcCurrent <= 2) {
                    $tcWindow = range(1, 3);
                } elseif ($tcCurrent >= $tcLast - 1) {
                    $tcWindow = range($tcLast - 2, $tcLast);
                } else {
                    $tcWindow = range($tcCurrent - 1, $tcCurrent + 1);
                }
                $tcPages = array_values(array_unique(array_merge([1, $tcLast], $tcWindow)));
                sort($tcPages);
            @endphp
            <div id="teacherClaimsPagination" class="teacher-claims-pager">
                <span class="page-info" aria-live="polite">
                    Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} students
                </span>
                <div class="page-btns">
                    @if ($tcCurrent > 1)
                        <a href="#" class="page-btn" data-page="{{ $tcCurrent - 1 }}" aria-label="Previous page">&lsaquo; Previous</a>
                    @else
                        <span class="page-btn disabled" aria-disabled="true">&lsaquo; Previous</span>
                    @endif
                    @php $tcPrevious = null; @endphp
                    @foreach ($tcPages as $tcPage)
                        @if ($tcPrevious !== null && $tcPage - $tcPrevious > 1)
                            <span class="page-ellipsis" aria-hidden="true">&hellip;</span>
                        @endif
                        <a href="#" class="page-btn {{ $tcCurrent == $tcPage ? 'active' : '' }}" data-page="{{ $tcPage }}" @if($tcCurrent == $tcPage) aria-current="page" @endif>{{ $tcPage }}</a>
                        @php $tcPrevious = $tcPage; @endphp
                    @endforeach
                    @if ($tcCurrent < $tcLast)
                        <a href="#" class="page-btn" data-page="{{ $tcCurrent + 1 }}" aria-label="Next page">Next &rsaquo;</a>
                    @else
                        <span class="page-btn disabled" aria-disabled="true">Next &rsaquo;</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ==================== TEACHER: SUMMARY ITEM REPORT ==================== --}}
        <div class="table-container summary-item-card" style="margin-top:24px;">
            <div class="table-header summary-item-header">
                <div>
                    <h4 style="font-size:14px;font-weight:600;">Summary Item Report</h4>
                    <p style="font-size:12px;color:#6b7280;margin-top:4px;">Approved items claimed by students in your assigned class.</p>
                </div>
                <a href="{{ route(portal_prefix() . '.reports.summary-item.print') }}" target="_blank" class="btn btn-filter" style="font-size:12px;padding:6px 14px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <x-icon name="printer" :size="14" /> Print Report
                </a>
            </div>
            <div class="summary-item-body">
                <table class="summary-item-table">
                    <colgroup>
                        <col style="width:55%;">
                        <col style="width:45%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Item Name</th>
                            <th scope="col" class="summary-qty">Total Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summaryItems ?? [] as $summaryItem)
                            <tr>
                                <td>{{ $summaryItem->item_name }}</td>
                                <td class="summary-qty">{{ (int) $summaryItem->total_quantity }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="summary-empty">No approved items claimed yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="summary-item-total">Total Items Claimed: {{ (int) ($summaryTotal ?? 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <style>
            /* Summary Item Report uses the same table design as Item Claims. */
            .summary-item-card .summary-item-header {
                gap: 16px;
            }
            .summary-item-card .summary-item-body {
                padding: 24px;
                overflow-x: auto;
            }
            /* Full-width table; content is positioned inward via cell padding. */
            .summary-item-card .summary-item-table {
                width: 100%;
            }
            .summary-item-card .summary-item-table th:first-child,
            .summary-item-card .summary-item-table td:first-child {
                padding-left: 18%;
            }
            .summary-item-card .summary-item-table th:nth-child(2),
            .summary-item-card .summary-item-table td:nth-child(2) {
                padding-left: 3%;
                text-align: left;
                font-variant-numeric: tabular-nums;
            }
            .summary-item-card .summary-item-table .summary-empty {
                text-align: center;
                color: var(--text-light);
            }
            .summary-item-card .summary-item-table .summary-item-total {
                border-top: 1px solid var(--border);
                border-bottom: none;
                padding-right: 14%;
                text-align: right;
                white-space: nowrap;
                font-weight: 600;
            }
            @media (max-width: 640px) {
                .summary-item-card .summary-item-header {
                    align-items: flex-start;
                    flex-direction: column;
                }
                .summary-item-card .summary-item-body {
                    padding: 16px;
                }
                .summary-item-card .summary-item-table th:first-child,
                .summary-item-card .summary-item-table td:first-child {
                    padding-left: 8%;
                }
                .summary-item-card .summary-item-table th:nth-child(2),
                .summary-item-card .summary-item-table td:nth-child(2) {
                    padding-left: 4%;
                }
                .summary-item-card .summary-item-table .summary-item-total {
                    padding-right: 8%;
                }
            }

            /* Search field sizes to content; no leftover space from removed buttons. */
            .tc-filter-form {
                grid-template-columns: minmax(220px, 360px) minmax(0, 1fr);
            }
            @media (max-width: 768px) {
                .tc-filter-form {
                    grid-template-columns: 1fr;
                }
            }
            /* Shared table design for Item Claims and Summary Item Report. */
            #teacherClaimsTable,
            .summary-item-card .summary-item-table {
                table-layout: fixed;
            }
            #teacherClaimsTable th,
            #teacherClaimsTable td {
                padding: 12px 16px;
                vertical-align: middle;
                line-height: 1.3;
            }
            .summary-item-card .summary-item-table th,
            .summary-item-card .summary-item-table td {
                padding: 10px 14px;
                vertical-align: middle;
            }
            #teacherClaimsTable td:not(:first-child),
            .summary-item-card .summary-item-table td:not(:first-child) {
                white-space: nowrap;
            }
            #teacherClaimsTable th,
            .summary-item-card .summary-item-table th {
                white-space: normal;
                overflow-wrap: break-word;
            }
            #teacherClaimsTable .teacher-claims-name-cell {
                overflow-wrap: anywhere;
            }
            #teacherClaimsTable .teacher-claims-count {
                color: #7c3aed;
                font-weight: 600;
            }
            /* Compact Item Claims pager. */
            #teacherClaimsPagination {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 16px;
                margin-top: 8px;
                border-top: 1px solid #e2e8f0;
            }
            #teacherClaimsPagination .page-info {
                font-size: 13px;
                color: #6b7280;
            }
            #teacherClaimsPagination .page-btns {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }
            #teacherClaimsPagination .page-btn {
                padding: 6px 10px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                font-size: 13px;
                color: #374151;
                background: #fff;
                text-decoration: none;
            }
            #teacherClaimsPagination .page-btn.active {
                color: #059669;
                background: #d1fae5;
                border-color: #a7f3d0;
                font-weight: 600;
            }
            #teacherClaimsPagination .page-btn.disabled {
                opacity: 0.45;
                cursor: not-allowed;
                pointer-events: none;
                background: #f3f4f6;
                color: #6b7280;
            }
            #teacherClaimsPagination .page-ellipsis {
                padding: 6px 2px;
                color: #6b7280;
                font-size: 13px;
            }
            @media (max-width: 640px) {
                #teacherClaimsPagination {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                }
            }
            .teacher-claims-student-btn {
                background: none;
                border: none;
                padding: 0;
                margin: 0;
                min-height: 0;
                font: inherit;
                line-height: 1.3;
                font-weight: 600;
                color: #111827;
                cursor: pointer;
                text-align: left;
                text-decoration: none;
            }
            .teacher-claims-student-btn:hover {
                color: #111827;
            }
            .teacher-claims-student-btn:focus-visible {
                color: #111827;
                outline: 2px solid #059669;
                outline-offset: 2px;
                border-radius: 2px;
            }
            .teacher-claims-modal .modal-content {
                max-width: 720px;
                width: 100%;
            }
            /* Only the claim list scrolls; header and footer stay visible. */
            .teacher-claims-modal .modal-body {
                display: flex;
                flex-direction: column;
                min-height: 0;
                overflow: hidden;
            }
            .teacher-claims-list {
                flex: 1;
                min-height: 0;
                overflow-y: auto;
            }
            .teacher-claims-list thead th {
                position: sticky;
                top: 0;
                background: #f3f4f6;
                z-index: 1;
            }
        </style>

        <div id="teacherClaimsModal" class="modal-overlay teacher-claims-modal" role="dialog" aria-modal="true" aria-labelledby="tcModalTitle" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 id="tcModalTitle">Item Claims</h2>
                        <p id="tcModalSubtitle" style="margin:4px 0 0;font-size:13px;color:#6b7280;"></p>
                    </div>
                    <button type="button" id="tcModalClose" class="modal-close" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="teacher-claims-list">
                        <table style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead>
                                <tr style="background:#f3f4f6;">
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Item Claimed</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Quantity</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points Used</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Date</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="tcModalClaimsBody"></tbody>
                        </table>
                        <div id="tcModalNoClaims" style="text-align:center;padding:30px;color:#6b7280;display:none;">
                            No claim history for this student.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="tcModalCloseBtn" class="btn btn-secondary" style="padding:8px 16px;">Close</button>
                </div>
            </div>
        </div>
    @else
        {{-- ==================== ADMIN / SUPER ADMIN: STUDENT ACTIVITIES ==================== --}}

        <!-- All Teachers Summary Report -->
        <div class="table-container sa-summary-section">
            <div class="table-header">
                <h4 style="font-size:14px;font-weight:600;">All Teachers Summary Report</h4>
                <p style="font-size:12px;color:#6b7280;margin-top:4px;">Overview of student activities grouped by teacher</p>
            </div>
            <div class="stat-cards" style="margin-top:16px;">
                <div class="stat-card">
                    <div class="stat-icon blue">👨‍🏫</div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $teachers->count() }}</div>
                        <div class="stat-label">Total Teachers</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">👥</div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $totalStudents }}</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">🧴</div>
                    <div class="stat-info">
                        <div class="stat-value">{{ number_format(collect($teacherGroups)->sum('total_bottles')) }}</div>
                        <div class="stat-label">Bottles Collected</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">🏆</div>
                    <div class="stat-info">
                        <div class="stat-value">{{ number_format(collect($teacherGroups)->sum('total_points')) }}</div>
                        <div class="stat-label">Total Points</div>
                    </div>
                </div>
            </div>
            <div class="table-responsive" style="margin-top:20px;">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Teacher</th>
                            <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Students</th>
                            <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Student Claimed</th>
                            <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Bottles</th>
                            <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points</th>
                            <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Claims</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teacherGroups as $group)
                        <tr>
                            <td style="padding:10px 14px;font-weight:600;color:#111827;">{{ $group['teacher']?->name ?? 'Unassigned Students' }}</td>
                            <td style="padding:10px 14px;">{{ $group['student_count'] }}</td>
                            <td style="padding:10px 14px;">{{ $group['student_claimed'] }}</td>
                            <td style="padding:10px 14px;color:#059669;font-weight:600;">{{ number_format($group['total_bottles']) }}</td>
                            <td style="padding:10px 14px;color:#d97706;font-weight:600;">{{ number_format($group['total_points']) }}</td>
                            <td style="padding:10px 14px;color:#7c3aed;font-weight:600;">{{ $group['total_claims'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No teacher data found for the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="filter-card sa-filter-card">
            <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
                <i class="fas fa-filter"></i> Filters
            </button>
            <div class="filter-body">
                <form id="saFilterForm" class="sa-filter-form" method="GET" action="{{ route(portal_prefix() . '.admin-activities') }}" onsubmit="return false;">
                    <div class="filter-field sa-filter-field">
                        <label for="saTeacher">Teacher</label>
                        <select id="saTeacher" name="teacher_id" aria-label="Filter by teacher">
                            <option value="">All Teachers</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field sa-filter-field">
                        <label for="saGrade">Grade Level</label>
                        <select id="saGrade" name="grade_level" aria-label="Filter by grade level">
                            <option value="">Grade Level</option>
                            @foreach($gradeLevels as $gl)
                                <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field sa-filter-field sa-filter-date">
                        <label for="saDateRange">Date Range</label>
                        <div class="date-range-wrapper" style="position:relative;display:flex;align-items:center;width:100%;">
                            <input type="text" id="saDateRange" name="date_range" value="{{ request('date_range') }}" placeholder="MM/DD/YYYY - MM/DD/YYYY" aria-label="Filter by date range" style="width:100%;padding:10px 35px 10px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;background:#fff;">
                            <button type="button" id="saDateCalendarBtn" aria-label="Open calendar" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:14px;line-height:1;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="date_from" id="saDateFrom" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" id="saDateTo" value="{{ request('date_to') }}">
                    </div>
                </form>
            </div>
        </div>

        <!-- Calendar Date Range Dropdown -->
        <!-- Calendar Date Range Dropdown -->
        <div id="saDateCalendar" class="sa-date-calendar" style="display:none;position:absolute;z-index:1000;">
            <div class="sa-calendar-header">
                <button type="button" id="saCalPrev" aria-label="Previous month" style="background:none;border:none;cursor:pointer;font-size:14px;padding:4px 8px;color:#059669;">‹</button>
                <span id="saCalMonthYear" style="font-weight:600;font-size:13px;color:#111827;">Month Year</span>
                <button type="button" id="saCalNext" aria-label="Next month" style="background:none;border:none;cursor:pointer;font-size:14px;padding:4px 8px;color:#059669;">›</button>
            </div>
            <div class="sa-calendar-grid" id="saCalGrid"></div>
            <div class="sa-calendar-footer" style="display:flex;gap:8px;padding:8px;border-top:1px solid #e2e8f0;">
                <button type="button" id="saCalClear" style="flex:1;padding:6px 8px;border:1px solid #cbd5e1;border-radius:4px;font-size:12px;background:#fff;color:#374151;cursor:pointer;">Clear</button>
                <button type="button" id="saCalApply" style="flex:1;padding:6px 8px;border:none;border-radius:4px;font-size:12px;background:#059669;color:#fff;cursor:pointer;">Apply</button>
            </div>
        </div>

        <!-- Student Item Claims Modal -->
        <div id="studentClaimsModal" class="modal-overlay" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 id="scModalStudentName">Student Name</h2>
                        <p id="scModalStudentLRN" style="margin:4px 0 0;font-size:13px;color:#6b7280;">LRN: —</p>
                    </div>
                    <button type="button" id="scModalClose" class="modal-close" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <h3 style="font-size:16px;font-weight:700;margin:0 0 16px;color:#111827;">Item Claims History</h3>
                    <div id="scModalLoading" style="text-align:center;padding:24px;color:#6b7280;">Loading claims...</div>
                    <div id="scModalClaimsContent" style="display:none;">
                        <table style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead>
                                <tr style="background:#f3f4f6;">
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Item Name</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Quantity</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Points Used</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Date Requested</th>
                                    <th scope="col" style="padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="scModalClaimsTableBody">
                            </tbody>
                        </table>
                        <div id="scModalNoClaims" style="text-align:center;padding:30px;color:#6b7280;display:none;">
                            No item claims found for this student.
                        </div>
                        <div id="scModalError" style="text-align:center;padding:30px;color:#dc2626;display:none;">
                            Unable to load item claims. Please try again.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="scModalCloseBtn" class="btn btn-secondary" style="padding:8px 16px;">Close</button>
                </div>
            </div>
        </div>

        @forelse($teacherGroups as $group)
        @php
            $tid = $group['teacher']?->id ?? 'unassigned';
            $tname = $group['teacher']?->name ?? 'Unassigned Students';
            $isUnassigned = $group['is_unassigned'] ?? false;
        @endphp
        <div class="sa-teacher-card" id="saTeacherCard-{{ $tid }}">
            <button type="button" class="sa-teacher-header" @if(!$isUnassigned) onclick="toggleTeacherCard({{ $tid }})" @endif aria-expanded="false" aria-controls="saTeacherStudents-{{ $tid }}">
                <div class="sa-teacher-info" style="flex:1;">
                    <span class="sa-toggle-icon" style="font-size:12px;">▶</span>
                    <strong style="font-size:14px;">Teacher: {{ $tname }}</strong>
                    <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                        <span style="font-weight:600;">{{ $group['student_count'] }}</span> Students ·
                        <span style="color:#059669;font-weight:600;">{{ number_format($group['total_bottles']) }}</span> Bottles ·
                        <span style="color:#d97706;font-weight:600;">{{ number_format($group['total_points']) }}</span> Points ·
                        <span style="color:#7c3aed;font-weight:600;">{{ $group['total_claims'] }}</span> Claims
                    </div>
                </div>
            </button>

            <div id="saTeacherStudents-{{ $tid }}" class="sa-teacher-content" style="display:none;">
                <div id="saTeacherStudentsContent-{{ $tid }}">
                    @include('partials.teacher-students-table', [
                        'teacher' => $group['teacher'],
                        'students' => $group['students'] ?? null,
                        'sort' => 'alpha',
                        'gradeLevel' => $gradeLevel ?? '',
                        'dateFrom' => $dateFrom ?? '',
                        'dateTo' => $dateTo ?? '',
                        'teacherId' => $tid
                    ])
                </div>
            </div>
        </div>
        @empty
        <div class="table-container">
            <div class="table-header">
                <h4 style="font-size:14px;font-weight:600;">Student Activities</h4>
            </div>
            <div class="table-responsive">
            <table>
                <tbody>
                    <tr><td style="text-align:center;padding:30px;color:var(--text-light);">No teacher summaries found for the selected filters.</td></tr>
                </tbody>
            </table>
            </div>
        </div>
        @endforelse
    @endif
@endsection

@push('scripts')
<script>
// --- Date Range Input ---
const dateRangeInput = document.getElementById('saDateRange');
const dateCalendarBtn = document.getElementById('saDateCalendarBtn');
const dateCalendar = document.getElementById('saDateCalendar');
const calGrid = document.getElementById('saCalGrid');
const calMonthYear = document.getElementById('saCalMonthYear');
const calPrev = document.getElementById('saCalPrev');
const calNext = document.getElementById('saCalNext');
const calClear = document.getElementById('saCalClear');
const calApply = document.getElementById('saCalApply');

let calCurrentDate = new Date();
let calSelectedStart = null;
let calSelectedEnd = null;

// Parse MM/DD/YYYY - MM/DD/YYYY from input
function parseDateRange(input) {
    const val = input.trim();
    if (!val) return { start: null, end: null, valid: false };
    const parts = val.split(/[–—-]/).map(p => p.trim());
    if (parts.length !== 2) return { start: null, end: null, valid: false };
    const m1 = parts[0].match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    const m2 = parts[1].match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (!m1 || !m2) return { start: null, end: null, valid: false };
    const d1 = new Date(m1[3], m1[1] - 1, m1[2]);
    const d2 = new Date(m2[3], m2[1] - 1, m2[2]);
    if (d1.getFullYear() !== parseInt(m1[3]) || d1.getMonth() !== parseInt(m1[1]) - 1 || d1.getDate() !== parseInt(m1[2])) return { start: null, end: null, valid: false };
    if (d2.getFullYear() !== parseInt(m2[3]) || d2.getMonth() !== parseInt(m2[1]) - 1 || d2.getDate() !== parseInt(m2[2])) return { start: null, end: null, valid: false };
    if (d1 > d2) return { start: d1, end: d2, valid: false };
    return { start: d1, end: d2, valid: true };
}

function formatDate(d) {
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const y = d.getFullYear();
    return `${m}/${day}/${y}`;
}

function formatDisplayDate(d) {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${months[d.getMonth()]} ${String(d.getDate()).padStart(2, '0')}, ${d.getFullYear()}`;
}

function updateDateRangeDisplay(start, end) {
    if (start && end) {
        dateRangeInput.value = `${formatDate(start)} - ${formatDate(end)}`;
        document.getElementById('saDateFrom').value = formatDate(start);
        document.getElementById('saDateTo').value = formatDate(end);
    } else {
        dateRangeInput.value = '';
        document.getElementById('saDateFrom').value = '';
        document.getElementById('saDateTo').value = '';
    }
}

// Calendar rendering
function renderCalendar() {
    const year = calCurrentDate.getFullYear();
    const month = calCurrentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startOffset = firstDay.getDay();
    const totalDays = lastDay.getDate();

    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    calMonthYear.textContent = `${monthNames[month]} ${year}`;

    const weekdays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    let html = '';
    weekdays.forEach(w => {
        html += `<div class="weekday">${w}</div>`;
    });

    for (let i = 0; i < startOffset; i++) {
        html += `<div class="day other-month"></div>`;
    }

    const today = new Date();
    today.setHours(0,0,0,0);

    for (let d = 1; d <= totalDays; d++) {
        const cellDate = new Date(year, month, d);
        let className = 'day';
        if (cellDate.getTime() === today.getTime()) className += ' today';
        if (calSelectedStart && cellDate.getTime() === calSelectedStart.getTime()) className += ' selected';
        if (calSelectedEnd && cellDate.getTime() === calSelectedEnd.getTime()) className += ' selected';
        if (calSelectedStart && calSelectedEnd && cellDate > calSelectedStart && cellDate < calSelectedEnd) className += ' in-range';
        if (cellDate.getMonth() !== month) className += ' other-month';
        html += `<div class="${className}" data-day="${d}">${d}</div>`;
    }

    calGrid.innerHTML = html;

    calGrid.querySelectorAll('.day:not(.other-month)').forEach(el => {
        el.addEventListener('click', function(e) {
            e.stopPropagation();
            const d = parseInt(this.dataset.day);
            const clicked = new Date(year, month, d);
            if (!calSelectedStart || (calSelectedStart && calSelectedEnd)) {
                calSelectedStart = clicked;
                calSelectedEnd = null;
            } else {
                if (clicked < calSelectedStart) {
                    calSelectedEnd = calSelectedStart;
                    calSelectedStart = clicked;
                } else {
                    calSelectedEnd = clicked;
                }
            }
            renderCalendar();
        });
    });
}

// Open/close calendar
function toggleCalendar() {
    if (dateCalendar.style.display === 'block') {
        closeCalendar();
    } else {
        openCalendar();
    }
}

function openCalendar() {
    const parsed = parseDateRange(dateRangeInput.value);
    if (parsed.start) {
        calSelectedStart = parsed.start;
        calSelectedEnd = parsed.end;
        calCurrentDate = new Date(parsed.start);
    } else {
        calSelectedStart = null;
        calSelectedEnd = null;
    }
    renderCalendar();
    const rect = dateRangeInput.getBoundingClientRect();
    dateCalendar.style.top = (rect.bottom + window.scrollY + 4) + 'px';
    dateCalendar.style.left = (rect.left + window.scrollX) + 'px';
    dateCalendar.style.display = 'block';
}

function closeCalendar() {
    dateCalendar.style.display = 'none';
}

function applyCalendar() {
    if (calSelectedStart && calSelectedEnd) {
        updateDateRangeDisplay(calSelectedStart, calSelectedEnd);
        saFilterForm.submit();
    }
    closeCalendar();
}

function clearCalendar() {
    calSelectedStart = null;
    calSelectedEnd = null;
    updateDateRangeDisplay(null, null);
    renderCalendar();
    saFilterForm.submit();
}

// --- Event Listeners ---

if (dateRangeInput) {
    // Manual input: apply only on Enter key
    dateRangeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const parsed = parseDateRange(this.value);
            if (parsed.valid) {
                updateDateRangeDisplay(parsed.start, parsed.end);
                saFilterForm.submit();
            } else if (parsed.start && !parsed.end) {
                // Incomplete: show what we have but don't submit
                updateDateRangeDisplay(parsed.start, null);
            } else {
                // Invalid format: clear
                updateDateRangeDisplay(null, null);
            }
        }
    });
}

if (dateCalendarBtn) {
    dateCalendarBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleCalendar();
    });
}

if (dateRangeInput) {
    dateRangeInput.addEventListener('click', function (e) {
        e.stopPropagation();
        openCalendar();
    });
}

if (calPrev) {
    calPrev.addEventListener('click', function (e) {
        e.stopPropagation();
        calCurrentDate = new Date(calCurrentDate.getFullYear(), calCurrentDate.getMonth() - 1, 1);
        renderCalendar();
    });
}

if (calNext) {
    calNext.addEventListener('click', function (e) {
        e.stopPropagation();
        calCurrentDate = new Date(calCurrentDate.getFullYear(), calCurrentDate.getMonth() + 1, 1);
        renderCalendar();
    });
}

if (calClear) {
    calClear.addEventListener('click', function (e) {
        e.stopPropagation();
        clearCalendar();
    });
}

if (calApply) {
    calApply.addEventListener('click', function (e) {
        e.stopPropagation();
        applyCalendar();
    });
}

// Close calendar when clicking outside
document.addEventListener('click', function (e) {
    if (dateCalendar && dateCalendar.style.display === 'block') {
        if (!dateCalendar.contains(e.target) && e.target !== dateRangeInput && e.target !== dateCalendarBtn) {
            closeCalendar();
        }
    }
});

// --- Main Filter Auto-Submit (Teacher & Grade Level on change) ---
const saFilterForm = document.getElementById('saFilterForm');
const saTeacherSelect = document.getElementById('saTeacher');
const saGradeSelect = document.getElementById('saGrade');

if (saTeacherSelect) {
    saTeacherSelect.addEventListener('change', function () {
        saFilterForm.submit();
    });
}

if (saGradeSelect) {
    saGradeSelect.addEventListener('change', function () {
        saFilterForm.submit();
    });
}

// --- Teacher Card Toggle ---
function toggleTeacherCard(teacherId) {
    const header = document.getElementById('saTeacherCard-' + teacherId).querySelector('.sa-teacher-header');
    const content = document.getElementById('saTeacherStudents-' + teacherId);
    const icon = header.querySelector('.sa-toggle-icon');
    const expanded = header.getAttribute('aria-expanded') === 'true';

    if (expanded) {
        content.style.display = 'none';
        header.setAttribute('aria-expanded', 'false');
        icon.textContent = '▶';
    } else {
        content.style.display = 'block';
        header.setAttribute('aria-expanded', 'true');
        icon.textContent = '▼';
        loadTeacherStudents(teacherId);
    }
}

// --- Per-Teacher Students AJAX ---
let teacherPendingControllers = {};
let teacherCurrentSequence = {};

function loadTeacherStudents(teacherId, page = null) {
    const container = document.getElementById('saTeacherStudentsContent-' + teacherId);
    const searchInput = document.getElementById('teacherSearch-' + teacherId);
    const sortInput = document.getElementById('teacherSort-' + teacherId);

    if (!container) return;

    const search = searchInput ? searchInput.value.trim() : '';
    const sort = sortInput ? sortInput.value : 'alpha';

    const gradeLevel = document.getElementById('saGrade')?.value || '';
    const dateFrom = document.getElementById('saDateFrom')?.value || '';
    const dateTo = document.getElementById('saDateTo')?.value || '';

    if (teacherPendingControllers[teacherId]) {
        teacherPendingControllers[teacherId].abort();
    }
    teacherCurrentSequence[teacherId] = (teacherCurrentSequence[teacherId] || 0) + 1;
    const seq = teacherCurrentSequence[teacherId];
    const controller = new AbortController();
    teacherPendingControllers[teacherId] = controller;

    const params = new URLSearchParams();
    params.append('teacher_id', teacherId);
    params.append('sort', sort);
    if (search) params.append('search', search);
    if (gradeLevel) params.append('grade_level', gradeLevel);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (page) params.append('page', page);

    container.style.opacity = '0.6';
    container.style.pointerEvents = 'none';

    fetch('{{ route(portal_prefix() . '.admin-activities.teacher-students') }}' + '?' + params.toString(), {
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        signal: controller.signal
    })
    .then((response) => {
        if (!response.ok) throw new Error('Filter failed');
        return response.text();
    })
    .then((html) => {
        if (seq !== teacherCurrentSequence[teacherId]) return;
        container.innerHTML = html;
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
    })
    .catch((err) => {
        if (seq !== teacherCurrentSequence[teacherId]) return;
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
        if (err.name !== 'AbortError') console.error('Fetch error:', err);
    })
    .finally(() => {
        teacherPendingControllers[teacherId] = null;
    });
}

// --- Per-Teacher Search (300ms debounce) ---
const teacherSearchTimers = {};
document.addEventListener('input', (e) => {
    if (!e.target.id || !e.target.id.startsWith('teacherSearch-')) return;
    const teacherId = e.target.id.replace('teacherSearch-', '');
    clearTimeout(teacherSearchTimers[teacherId]);
    teacherSearchTimers[teacherId] = setTimeout(() => {
        loadTeacherStudents(teacherId);
    }, 300);
});

// Prevent Enter key on teacher search inputs (no full-page submit)
document.addEventListener('keydown', (e) => {
    if (e.target.id && e.target.id.startsWith('teacherSearch-') && e.key === 'Enter') {
        e.preventDefault();
    }
});

// --- Per-Teacher Sort Change ---
document.addEventListener('change', (e) => {
    if (!e.target.id || !e.target.id.startsWith('teacherSort-')) return;
    const teacherId = e.target.id.replace('teacherSort-', '');
    loadTeacherStudents(teacherId);
});

// --- Per-Teacher Pagination (event delegation on document) ---
document.addEventListener('click', (e) => {
    const pageLink = e.target.closest('.teacher-students-table a.page-btn[data-page]');
    if (!pageLink) return;
    e.preventDefault();
    const page = pageLink.getAttribute('data-page');
    const table = pageLink.closest('.teacher-students-table');
    const teacherId = table.id.replace('teacher-students-', '');
    loadTeacherStudents(teacherId, page);
});

// --- Student Claims Modal ---
const studentClaimsModal = document.getElementById('studentClaimsModal');
const scModalClose = document.getElementById('scModalClose');
const scModalCloseBtn = document.getElementById('scModalCloseBtn');
const scModalStudentName = document.getElementById('scModalStudentName');
const scModalStudentLRN = document.getElementById('scModalStudentLRN');
const scModalLoading = document.getElementById('scModalLoading');
const scModalClaimsContent = document.getElementById('scModalClaimsContent');
const scModalClaimsTableBody = document.getElementById('scModalClaimsTableBody');
const scModalNoClaims = document.getElementById('scModalNoClaims');
const scModalError = document.getElementById('scModalError');

let currentStudentId = null;

// Open claims modal via event delegation (supports AJAX-loaded table rows)
document.addEventListener('click', (e) => {
    const link = e.target.closest('.student-claims-link');
    if (!link) return;
    e.preventDefault();

    currentStudentId = link.getAttribute('data-student-id');
    const studentName = link.getAttribute('data-student-name') || 'Student';

    scModalStudentName.textContent = studentName;
    scModalStudentLRN.textContent = 'LRN: ' + (link.getAttribute('data-student-lrn') || '—');

    scModalLoading.style.display = 'block';
    scModalClaimsContent.style.display = 'none';
    scModalNoClaims.style.display = 'none';
    studentClaimsModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    fetch('{{ route(portal_prefix() . '.admin-activities.student-claims') }}?student_id=' + currentStudentId, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then((response) => {
        if (!response.ok) throw new Error('Failed to load claims');
        return response.json();
    })
    .then((data) => {
        scModalLoading.style.display = 'none';
        scModalStudentLRN.textContent = 'LRN: ' + (data.student.lrn || '—');

        const claims = data.claims || [];
        scModalClaimsTableBody.innerHTML = '';

        if (claims.length === 0) {
            scModalNoClaims.style.display = 'block';
        } else {
            scModalNoClaims.style.display = 'none';
            claims.forEach((claim) => {
                const statusClass = 'sc-status-' + (claim.status || 'pending').toLowerCase();
                const row = document.createElement('tr');
                row.innerHTML =
                    '<td style="padding:10px 14px;font-weight:600;color:#111827;">' + (claim.item_name || '—') + '</td>' +
                    '<td style="padding:10px 14px;">' + claim.quantity + '</td>' +
                    '<td style="padding:10px 14px;color:#d97706;font-weight:600;">' + claim.points_deducted + '</td>' +
                    '<td style="padding:10px 14px;color:#6b7280;font-size:12px;">' + claim.claim_date + '</td>' +
                    '<td style="padding:10px 14px;"><span class="' + statusClass + '" style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;">' + (claim.status || 'Pending') + '</span></td>';
                scModalClaimsTableBody.appendChild(row);
            });
        }
        scModalClaimsContent.style.display = 'block';
    })
    .catch((err) => {
        scModalLoading.style.display = 'none';
        scModalNoClaims.style.display = 'none';
        scModalError.style.display = 'block';
        scModalClaimsContent.style.display = 'block';
        console.error('Claims fetch error:', err);
    });
});

function closeClaimsModal() {
    studentClaimsModal.style.display = 'none';
    document.body.style.overflow = '';
    currentStudentId = null;
}

if (scModalClose) {
    scModalClose.addEventListener('click', closeClaimsModal);
}

if (scModalCloseBtn) {
    scModalCloseBtn.addEventListener('click', closeClaimsModal);
}

if (studentClaimsModal) {
    studentClaimsModal.addEventListener('click', (e) => {
        if (e.target === studentClaimsModal) closeClaimsModal();
    });
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && studentClaimsModal && studentClaimsModal.style.display === 'flex') {
        closeClaimsModal();
    }
});

// --- Teacher Item Claims: Popup ---
const teacherClaimsModal = document.getElementById('teacherClaimsModal');
const tcModalTitle = document.getElementById('tcModalTitle');
const tcModalSubtitle = document.getElementById('tcModalSubtitle');
const tcModalClaimsBody = document.getElementById('tcModalClaimsBody');
const tcModalNoClaims = document.getElementById('tcModalNoClaims');
const tcModalClose = document.getElementById('tcModalClose');
const tcModalCloseBtn = document.getElementById('tcModalCloseBtn');

let tcLastFocused = null;
let tcScrollY = 0;

function openTeacherClaimsModal(trigger) {
    if (!teacherClaimsModal || !trigger) return;

    tcLastFocused = trigger;
    tcScrollY = window.scrollY || window.pageYOffset || 0;

    const studentName = trigger.getAttribute('data-student-name') || 'Student';
    const studentLrn = trigger.getAttribute('data-student-lrn') || '—';
    const studentGrade = trigger.getAttribute('data-student-grade') || '—';

    tcModalTitle.textContent = 'Item Claims — ' + studentName;
    tcModalSubtitle.textContent = 'LRN: ' + studentLrn + ' · Grade Level: ' + studentGrade;

    let claims = [];
    try {
        claims = JSON.parse(trigger.getAttribute('data-claims') || '[]');
    } catch (err) {
        claims = [];
    }
    if (!Array.isArray(claims)) claims = [];

    tcModalClaimsBody.innerHTML = '';

    claims.forEach(function (claim) {
        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid #e2e8f0';

        const itemCell = document.createElement('td');
        itemCell.style.cssText = 'padding:10px 14px;font-weight:600;color:#111827;';
        itemCell.textContent = claim.item_name || '—';

        const qtyCell = document.createElement('td');
        qtyCell.style.cssText = 'padding:10px 14px;color:#374151;';
        qtyCell.textContent = (claim.quantity !== null && claim.quantity !== undefined) ? claim.quantity : '—';

        const pointsCell = document.createElement('td');
        pointsCell.style.cssText = 'padding:10px 14px;color:#d97706;font-weight:600;';
        pointsCell.textContent = Number(claim.points_deducted || 0).toLocaleString('en-US');

        const dateCell = document.createElement('td');
        dateCell.style.cssText = 'padding:10px 14px;color:#6b7280;font-size:12px;';
        dateCell.textContent = claim.claim_date || 'N/A';

        // Use each claim's saved status, not points/dates/item name.
        const statusCell = document.createElement('td');
        statusCell.style.cssText = 'padding:10px 14px;';
        const savedStatus = claim.status || 'Pending';
        const statusKey = String(savedStatus).toLowerCase();
        const statusBadge = document.createElement('span');
        statusBadge.className = 'status-badge'
            + (['pending', 'approved', 'rejected', 'claimed'].includes(statusKey) ? ' ' + statusKey : '');
        statusBadge.textContent = savedStatus;
        statusCell.appendChild(statusBadge);

        row.appendChild(itemCell);
        row.appendChild(qtyCell);
        row.appendChild(pointsCell);
        row.appendChild(dateCell);
        row.appendChild(statusCell);
        tcModalClaimsBody.appendChild(row);
    });

    tcModalNoClaims.style.display = claims.length > 0 ? 'none' : 'block';

    teacherClaimsModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    tcModalClose.focus();
}

function closeTeacherClaimsModal() {
    if (!teacherClaimsModal || teacherClaimsModal.style.display === 'none') return;

    teacherClaimsModal.style.display = 'none';
    document.body.style.overflow = '';
    window.scrollTo(0, tcScrollY);

    if (tcLastFocused && document.contains(tcLastFocused)) {
        tcLastFocused.focus();
    }
    tcLastFocused = null;
}

// Event delegation so the popup also works on rows replaced by AJAX search/pagination.
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.teacher-claims-student-btn');
    if (!trigger) return;
    e.preventDefault();
    openTeacherClaimsModal(trigger);
});

if (tcModalClose) tcModalClose.addEventListener('click', closeTeacherClaimsModal);
if (tcModalCloseBtn) tcModalCloseBtn.addEventListener('click', closeTeacherClaimsModal);

if (teacherClaimsModal) {
    teacherClaimsModal.addEventListener('click', function (e) {
        if (e.target === teacherClaimsModal) closeTeacherClaimsModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && teacherClaimsModal.style.display === 'flex') {
            e.preventDefault();
            closeTeacherClaimsModal();
        }
    });
}

// --- Teacher Item Claims: live search + pagination ---
const teacherClaimsFilterForm = document.querySelector('.tc-filter-form');
const teacherClaimsSearch = document.getElementById('teacherClaimsSearch');
const teacherClaimsTable = document.getElementById('teacherClaimsTable');
const teacherClaimsPagination = document.getElementById('teacherClaimsPagination');

let teacherClaimsDebounce = null;
let teacherClaimsSeq = 0;
let teacherClaimsAbort = null;

function buildTeacherClaimsUrl(page) {
    const params = new URLSearchParams(window.location.search);
    const term = teacherClaimsSearch ? teacherClaimsSearch.value.trim() : '';

    if (term) {
        params.set('search', term);
    } else {
        params.delete('search');
    }
    params.set('page', page);

    return window.location.pathname + '?' + params.toString();
}

function fetchTeacherClaimsPage(page) {
    if (!teacherClaimsTable || !teacherClaimsPagination) return;

    const seq = ++teacherClaimsSeq;
    if (teacherClaimsAbort) teacherClaimsAbort.abort();
    const controller = new AbortController();
    teacherClaimsAbort = controller;

    const url = buildTeacherClaimsUrl(page);

    fetch(url, {
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        signal: controller.signal
    })
    .then((response) => {
        if (!response.ok) throw new Error('Fetch failed');
        return response.text();
    })
    .then((html) => {
        if (seq !== teacherClaimsSeq) return;

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTable = doc.getElementById('teacherClaimsTable');
        const newPagination = doc.getElementById('teacherClaimsPagination');

        if (newTable) teacherClaimsTable.innerHTML = newTable.innerHTML;
        if (newPagination) teacherClaimsPagination.innerHTML = newPagination.innerHTML;

        window.history.replaceState(null, '', url);

        if (teacherClaimsSearch && document.activeElement !== teacherClaimsSearch) {
            teacherClaimsSearch.focus({ preventScroll: true });
        }
    })
    .catch((err) => {
        if (err.name === 'AbortError') return;
        console.error('Teacher claims fetch error:', err);
    })
    .finally(() => {
        if (seq === teacherClaimsSeq) teacherClaimsAbort = null;
    });
}

if (teacherClaimsSearch && teacherClaimsFilterForm) {
    teacherClaimsFilterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(teacherClaimsDebounce);
        fetchTeacherClaimsPage(1);
    });

    teacherClaimsSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') e.preventDefault();
    });

    teacherClaimsSearch.addEventListener('input', function () {
        clearTimeout(teacherClaimsDebounce);
        teacherClaimsDebounce = setTimeout(() => {
            fetchTeacherClaimsPage(1);
        }, 250);
    });
}

// --- Teacher Item Claims: AJAX Pagination ---
document.addEventListener('click', (e) => {
    const pageLink = e.target.closest('#teacherClaimsPagination a.page-btn[data-page]');
    if (!pageLink) return;
    e.preventDefault();
    const page = pageLink.getAttribute('data-page');
    fetchTeacherClaimsPage(page);
});
</script>
@endpush
