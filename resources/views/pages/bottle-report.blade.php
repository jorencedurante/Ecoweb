@extends('layouts.admin')

@section('title', 'EcoCollect - Bottle Collection Report')
@section('page-title', 'Bottle Collection Report')
@section('page-subtitle', 'Generate and manage reports')

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    <!-- Summary Cards -->
    <div class="summary-cards br-summary-section">
        <div class="stat-card">
            <div class="stat-icon blue">📅</div>
            <div class="stat-info">
                <div class="stat-value">{{ $dailyTotal }}</div>
                <div class="stat-label">Today</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📆</div>
            <div class="stat-info">
                <div class="stat-value">{{ $weeklyTotal }}</div>
                <div class="stat-label">This Week</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">📊</div>
            <div class="stat-info">
                <div class="stat-value">{{ $monthlyTotal }}</div>
                <div class="stat-label">This Month</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📦</div>
            <div class="stat-info">
                <div class="stat-value">{{ $totalBottles ?? 0 }}</div>
                <div class="stat-label">Total Collected</div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="chart-card br-chart-section" style="margin-bottom:24px;">
        <h4>Bottles Collected — Last 7 Days</h4>
        <div class="br-chart-container" style="display:flex;align-items:flex-end;justify-content:space-between;height:200px;padding:0 10px;gap:4px;">
            @php
                $chartDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                $dayLabels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                $maxVal = max(1, max($chartData));
            @endphp
            @foreach($chartDays as $index => $day)
                @php
                    $val = $chartData[$day] ?? 0;
                    $h = max(4, ($val / $maxVal) * 150);
                    $label = $dayLabels[$index] ?? $day;
                @endphp
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end;" class="br-chart-bar" data-day="{{ $day }}" data-value="{{ $val }}">
                    <span style="font-size:11px;font-weight:700;color:var(--text-dark);margin-bottom:4px;">{{ $val }}</span>
                    <div style="width:100%;background:var(--blue);height:{{ $h }}px;border-radius:4px 4px 0 0;min-height:4px;"></div>
                    <span style="font-size:11px;color:var(--text-medium);margin-top:4px;">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
            <i class="fas fa-filter"></i> Filters
        </button>
        <div class="filter-body">
            <form id="brFilterForm" class="br-filter-form" method="GET" action="{{ route('admin.bottle-report') }}" onsubmit="return false;">
                <div class="br-filter-field br-search-field">
                    <label for="brSearch">Search Student</label>
                    <input type="text" id="brSearch" name="search" value="{{ request('search') }}" placeholder="Search student name or LRN..." aria-label="Search students">
                </div>
                <div class="br-filter-field br-date-field">
                    <label for="brDateRange">Date Range</label>
                    <div class="br-date-wrapper" style="position:relative;display:flex;align-items:center;width:100%;">
                        <input type="text" id="brDateRange" name="date_range" value="{{ request('date_range') }}" placeholder="MM/DD/YYYY - MM/DD/YYYY" aria-label="Filter by date range" style="width:100%;padding:10px 35px 10px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;background:#fff;">
                        <button type="button" id="brDateCalendarBtn" aria-label="Open calendar" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:14px;line-height:1;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </button>
                    </div>
                    <input type="hidden" name="date_from" id="brDateFrom" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" id="brDateTo" value="{{ request('date_to') }}">
                </div>
                <div class="br-filter-field br-quarter-field">
                    <label for="brQuarter">Quarter</label>
                    <select id="brQuarter" name="quarter" aria-label="Filter by quarter">
                        <option value="">Quarter</option>
                        <option value="q1" {{ request('quarter') == 'q1' ? 'selected' : '' }}>Q1 (Jan–Mar)</option>
                        <option value="q2" {{ request('quarter') == 'q2' ? 'selected' : '' }}>Q2 (Apr–Jun)</option>
                        <option value="q3" {{ request('quarter') == 'q3' ? 'selected' : '' }}>Q3 (Jul–Sep)</option>
                        <option value="q4" {{ request('quarter') == 'q4' ? 'selected' : '' }}>Q4 (Oct–Dec)</option>
                        <option value="current" {{ request('quarter') == 'current' ? 'selected' : '' }}>Current</option>
                        <option value="previous" {{ request('quarter') == 'previous' ? 'selected' : '' }}>Previous</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Calendar Date Range Dropdown -->
    <div id="brDateCalendar" class="br-date-calendar" style="display:none;position:absolute;z-index:1000;">
        <div class="br-calendar-header">
            <button type="button" id="brCalPrev" aria-label="Previous month" style="background:none;border:none;cursor:pointer;font-size:14px;padding:4px 8px;color:#059669;">‹</button>
            <span id="brCalMonthYear" style="font-weight:600;font-size:13px;color:#111827;">Month Year</span>
            <button type="button" id="brCalNext" aria-label="Next month" style="background:none;border:none;cursor:pointer;font-size:14px;padding:4px 8px;color:#059669;">›</button>
        </div>
        <div class="br-calendar-grid" id="brCalGrid"></div>
        <div class="br-calendar-footer" style="display:flex;gap:8px;padding:8px;border-top:1px solid #e2e8f0;">
            <button type="button" id="brCalClear" style="flex:1;padding:6px 8px;border:1px solid #cbd5e1;border-radius:4px;font-size:12px;background:#fff;color:#374151;cursor:pointer;">Clear</button>
            <button type="button" id="brCalApply" style="flex:1;padding:6px 8px;border:none;border-radius:4px;font-size:12px;background:#059669;color:#fff;cursor:pointer;">Apply</button>
        </div>
    </div>

    <!-- Collection Records Grouped by Student -->
    <div class="table-container" style="margin-top:24px;">
        <div class="table-header">
            <h4 style="font-size:14px;font-weight:600;">Collection Records</h4>
            <a href="{{ route('reports.bottle-collection.print') }}?{{ http_build_query(request()->query()) }}" target="_blank" class="btn btn-filter" style="font-size:12px;padding:6px 14px;text-decoration:none;">🖨️ Print Report</a>
        </div>
        <div class="table-responsive">
        <table class="br-collections-table">
            <thead>
                <tr>
                    <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Student</th>
                    <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">LRN</th>
                    <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Total Bottles</th>
                    <th scope="col" style="padding:10px 14px;font-size:11px;text-transform:uppercase;color:#6b7280;font-weight:600;">Collections</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studentGroups as $group)
                <tr class="br-student-group" data-student-id="{{ $group['student']->id }}">
                    <td style="padding:12px 14px;font-weight:600;color:#111827;cursor:pointer;">
                            <a href="#" class="br-student-name" data-student-id="{{ $group['student']->id }}" style="color:#111827;text-decoration:none;font-weight:600;">
                                {{ $group['student']->full_name }}
                            </a>
                        </td>
                        <td style="padding:12px 14px;color:#6b7280;">{{ $group['student']->lrn }}</td>
                        <td style="padding:12px 14px;color:#059669;font-weight:600;">{{ number_format($group['total_bottles']) }}</td>
                        <td style="padding:12px 14px;color:#6b7280;">{{ count($group['collections']) }}</td>
                    </tr>
                <tr class="br-student-detail" data-student-id="{{ $group['student']->id }}" style="display:none;">
                    <td colspan="4" style="padding:0;">
                        <div style="padding:14px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                                <thead style="background:#f1f5f9;">
                                    <tr>
                                        <th scope="col" style="padding:8px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;">#</th>
                                        <th scope="col" style="padding:8px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;">Collection Date</th>
                                        <th scope="col" style="padding:8px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;">Time</th>
                                        <th scope="col" style="padding:8px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;">Bottles Collected</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $idx = 1; @endphp
                                    @foreach($group['collections'] as $collection)
                                    <tr>
                                        <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">{{ $idx++ }}</td>
                                        <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">{{ $collection->collection_date ? \Carbon\Carbon::parse($collection->collection_date)->format('M d, Y') : '—' }}</td>
                                        <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">{{ $collection->collection_time ?: '—' }}</td>
                                        <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;color:#059669;font-weight:600;">{{ $collection->bottle_count }}</td>
                                    </tr>
                                    @endforeach
                                    @if(count($group['collections']) === 0)
                                    <tr>
                                        <td colspan="4" style="padding:24px;text-align:center;color:#6b7280;">No collection transactions.</td>
                                    </tr>
                                    @else
                                    <tr style="background:#f0fdfa;">
                                        <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;" colspan="3" style="font-weight:600;">Total</td>
                                        <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;color:#059669;font-weight:600;">{{ number_format($group['total_bottles']) }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="brNoResults" style="display:none;"><td colspan="4" style="text-align:center;padding:30px;color:#6b7280;">No collection records found.</td></tr>
                <tr id="brSearchError" style="display:none;"><td colspan="4" style="text-align:center;padding:30px;color:#dc2626;">Unable to load collection records. Please try again.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="pagination br-pagination">
            <span class="page-info">Showing {{ $studentGroups->firstItem() ?? 0 }} to {{ $studentGroups->lastItem() ?? 0 }} of {{ $studentGroups->total() }} students</span>
            <div class="page-btns">
                @for ($p = 1; $p <= $studentGroups->lastPage(); $p++)
                    <a href="{{ $studentGroups->url($p) }}" class="page-btn {{ $studentGroups->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
                @endfor
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// --- Live Student Search & Quarter Filter (shared, debounced, race-condition-safe) ---
const brSearchInput = document.getElementById('brSearch');
const brQuarterSelect = document.getElementById('brQuarter');
let brSearchTimer = null;
let brAbortController = null;
let brCurrentPage = 1;

function abortPendingRequest() {
    if (brAbortController) {
        brAbortController.abort();
        brAbortController = null;
    }
}

function fetchBottleData() {
    abortPendingRequest();
    const controller = new AbortController();
    brAbortController = controller;

    const searchVal = (brSearchInput ? brSearchInput.value : '').trim();
    const quarterVal = (brQuarterSelect ? brQuarterSelect.value : '');
    const dateFrom = document.getElementById('brDateFrom') ? document.getElementById('brDateFrom').value : '';
    const dateTo = document.getElementById('brDateTo') ? document.getElementById('brDateTo').value : '';

    const url = new URL("{{ route('admin.bottle-report.search') }}");
    url.searchParams.set('search', searchVal);
    url.searchParams.set('date_from', dateFrom);
    url.searchParams.set('date_to', dateTo);
    url.searchParams.set('quarter', quarterVal);
    url.searchParams.set('page', brCurrentPage);

    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        signal: controller.signal
    })
    .then(response => {
        if (!response.ok) throw new Error('Filter failed');
        return response.json();
    })
    .then(data => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', data.pagination.current_page);
        url.searchParams.set('search', searchVal);
        url.searchParams.set('date_from', dateFrom);
        url.searchParams.set('date_to', dateTo);
        url.searchParams.set('quarter', quarterVal);
        history.pushState({ page: data.pagination.current_page, search: searchVal, date_from: dateFrom, date_to: dateTo, quarter: quarterVal }, '', url.toString());

        renderStudentGroups(data.student_groups, data.pagination);
        updateChartBars(data.chart_data);
    })
    .catch(err => {
        if (err.name === 'AbortError') return;
        const errorRow = document.getElementById('brSearchError');
        const noResultsRow = document.getElementById('brNoResults');
        if (errorRow) errorRow.style.display = '';
        if (noResultsRow) noResultsRow.style.display = 'none';
        console.error('Bottle report error:', err);
    })
    .finally(() => {
        if (brAbortController === controller) {
            brAbortController = null;
        }
    });
}

if (brSearchInput) {
    brSearchInput.addEventListener('input', function () {
        brCurrentPage = 1;
        clearTimeout(brSearchTimer);
        brSearchTimer = setTimeout(fetchBottleData, 300);
    });
}

if (brQuarterSelect) {
    brQuarterSelect.addEventListener('change', function () {
        brCurrentPage = 1;
        clearTimeout(brSearchTimer);
        brSearchTimer = setTimeout(fetchBottleData, 300);
    });
}

// --- Render student groups from AJAX ---
function renderStudentGroups(groups, pagination) {
    const tbody = document.querySelector('.br-collections-table tbody');
    const paginationContainer = document.querySelector('.br-pagination');
    const noResultsRow = document.getElementById('brNoResults');
    const errorRow = document.getElementById('brSearchError');

    if (errorRow) errorRow.style.display = 'none';
    if (noResultsRow) noResultsRow.style.display = 'none';

    // Always clear previous student rows before rendering new results
    const existingRows = tbody.querySelectorAll('.br-student-group, .br-student-detail');
    existingRows.forEach(row => row.remove());

    if (!groups || groups.length === 0) {
        if (noResultsRow) noResultsRow.style.display = '';
        if (paginationContainer) paginationContainer.style.display = 'none';
        return;
    }

    let html = '';
    groups.forEach(group => {
        html += `
        <tr class="br-student-group" data-student-id="${group.student.id}">
            <td style="padding:12px 14px;font-weight:600;color:#111827;cursor:pointer;">
                <a href="#" class="br-student-name" data-student-id="${group.student.id}" style="color:#111827;text-decoration:none;font-weight:600;">
                    ${group.student.full_name}
                </a>
            </td>
            <td style="padding:12px 14px;color:#6b7280;">${group.student.lrn}</td>
            <td style="padding:12px 14px;color:#059669;font-weight:600;">${group.total_bottles}</td>
            <td style="padding:12px 14px;color:#6b7280;">${group.collections.length}</td>
        </tr>
        <tr class="br-student-detail" data-student-id="${group.student.id}" style="display:none;">
            <td colspan="4" style="padding:0;">
                <div style="padding:14px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead style="background:#f1f5f9;">
                            <tr>
                                <th scope="col" style="padding:8px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;">#</th>
                                <th scope="col" style="padding:8px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;">Collection Date</th>
                                <th scope="col" style="padding:8px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;">Time</th>
                                <th scope="col" style="padding:8px 14px;text-align:left;font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:600;">Bottles Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${group.collections.map((c, idx) => `
                            <tr>
                                <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">${idx + 1}</td>
                                <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">${c.collection_date}</td>
                                <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">${c.collection_time || '—'}</td>
                                <td style="padding:8px 14px;border-bottom:1px solid #e2e8f0;color:#059669;font-weight:600;">${c.bottle_count}</td>
                            </tr>
                            `).join('')}
                            ${group.collections.length > 0 ? `
                            <tr style="background:#f0fdfa;">
                                <td style="padding:8px 14px;" colspan="3" style="font-weight:600;">Total</td>
                                <td style="padding:8px 14px;color:#059669;font-weight:600;">${group.total_bottles}</td>
                            </tr>
                            ` : ''}
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;

    // Update pagination
    if (paginationContainer) {
        paginationContainer.style.display = 'flex';
        const info = paginationContainer.querySelector('.page-info');
        if (info && pagination) {
            info.textContent = `Showing ${pagination.from} to ${pagination.to} of ${pagination.total} students`;
        }
        const btns = paginationContainer.querySelector('.page-btns');
        if (btns && pagination) {
            brCurrentPage = pagination.current_page;
            let btnsHtml = '';
            for (let p = 1; p <= pagination.last_page; p++) {
                const active = p === pagination.current_page ? 'active' : '';
                btnsHtml += `<a href="#" class="page-btn ${active}" data-page="${p}">${p}</a>`;
            }
            btns.innerHTML = btnsHtml;
        }
    }
}

// --- Update chart bars from AJAX data ---
function updateChartBars(chartData) {
    if (!chartData) return;
    const dayMap = {
        'Monday': 0, 'Tuesday': 1, 'Wednesday': 2, 'Thursday': 3,
        'Friday': 4, 'Saturday': 5, 'Sunday': 6
    };
    const dayLabels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    const bars = document.querySelectorAll('.br-chart-bar');
    let maxVal = 1;
    bars.forEach(bar => {
        const day = bar.dataset.day;
        const val = chartData[day] ?? 0;
        if (val > maxVal) maxVal = val;
    });
    bars.forEach(bar => {
        const day = bar.dataset.day;
        const val = chartData[day] ?? 0;
        const h = maxVal > 0 ? Math.max(4, (val / maxVal) * 150) : 4;
        const idx = dayMap[day] ?? 0;
        const label = dayLabels[idx] ?? day;
        const valSpan = bar.querySelector('span:first-of-type');
        const barDiv = bar.querySelector('div');
        const labelSpan = bar.querySelector('span:last-of-type');
        if (valSpan) valSpan.textContent = val;
        if (barDiv) barDiv.style.height = h + 'px';
        if (labelSpan) labelSpan.textContent = label;
    });
}

// --- Date Range Calendar ---
const brDateRangeInput = document.getElementById('brDateRange');
const brDateCalendarBtn = document.getElementById('brDateCalendarBtn');
const brDateCalendar = document.getElementById('brDateCalendar');
const brCalGrid = document.getElementById('brCalGrid');
const brCalMonthYear = document.getElementById('brCalMonthYear');
const brCalPrev = document.getElementById('brCalPrev');
const brCalNext = document.getElementById('brCalNext');
const brCalClear = document.getElementById('brCalClear');
const brCalApply = document.getElementById('brCalApply');

let brCalCurrentDate = new Date();
let brCalSelectedStart = null;
let brCalSelectedEnd = null;

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

function updateDateRangeDisplay(start, end) {
    if (start && end) {
        brDateRangeInput.value = `${formatDate(start)} - ${formatDate(end)}`;
        document.getElementById('brDateFrom').value = formatDate(start);
        document.getElementById('brDateTo').value = formatDate(end);
    } else {
        brDateRangeInput.value = '';
        document.getElementById('brDateFrom').value = '';
        document.getElementById('brDateTo').value = '';
    }
}

function renderCalendar() {
    const year = brCalCurrentDate.getFullYear();
    const month = brCalCurrentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startOffset = firstDay.getDay();
    const totalDays = lastDay.getDate();

    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    brCalMonthYear.textContent = `${monthNames[month]} ${year}`;

    const weekdays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    let html = '';
    weekdays.forEach(w => { html += `<div class="weekday">${w}</div>`; });

    for (let i = 0; i < startOffset; i++) {
        html += `<div class="day other-month"></div>`;
    }

    const today = new Date();
    today.setHours(0,0,0,0);

    for (let d = 1; d <= totalDays; d++) {
        const cellDate = new Date(year, month, d);
        let className = 'day';
        if (cellDate.getTime() === today.getTime()) className += ' today';
        if (brCalSelectedStart && cellDate.getTime() === brCalSelectedStart.getTime()) className += ' selected';
        if (brCalSelectedEnd && cellDate.getTime() === brCalSelectedEnd.getTime()) className += ' selected';
        if (brCalSelectedStart && brCalSelectedEnd && cellDate > brCalSelectedStart && cellDate < brCalSelectedEnd) className += ' in-range';
        if (cellDate.getMonth() !== month) className += ' other-month';
        html += `<div class="${className}" data-day="${d}">${d}</div>`;
    }

    brCalGrid.innerHTML = html;

    brCalGrid.querySelectorAll('.day:not(.other-month)').forEach(el => {
        el.addEventListener('click', function(e) {
            e.stopPropagation();
            const d = parseInt(this.dataset.day);
            const clicked = new Date(year, month, d);
            if (!brCalSelectedStart || (brCalSelectedStart && brCalSelectedEnd)) {
                brCalSelectedStart = clicked;
                brCalSelectedEnd = null;
            } else {
                if (clicked < brCalSelectedStart) {
                    brCalSelectedEnd = brCalSelectedStart;
                    brCalSelectedStart = clicked;
                } else {
                    brCalSelectedEnd = clicked;
                }
            }
            renderCalendar();
        });
    });
}

function toggleCalendar() {
    if (brDateCalendar.style.display === 'block') {
        closeCalendar();
    } else {
        openCalendar();
    }
}

function openCalendar() {
    const parsed = parseDateRange(brDateRangeInput.value);
    if (parsed.start) {
        brCalSelectedStart = parsed.start;
        brCalSelectedEnd = parsed.end;
        brCalCurrentDate = new Date(parsed.start);
    } else {
        brCalSelectedStart = null;
        brCalSelectedEnd = null;
    }
    renderCalendar();
    const rect = brDateRangeInput.getBoundingClientRect();
    brDateCalendar.style.top = (rect.bottom + window.scrollY + 4) + 'px';
    brDateCalendar.style.left = (rect.left + window.scrollX) + 'px';
    brDateCalendar.style.display = 'block';
}

function closeCalendar() {
    brDateCalendar.style.display = 'none';
}

function applyCalendar() {
    if (brCalSelectedStart && brCalSelectedEnd) {
        updateDateRangeDisplay(brCalSelectedStart, brCalSelectedEnd);
    }
    closeCalendar();
}

function clearCalendar() {
    brCalSelectedStart = null;
    brCalSelectedEnd = null;
    updateDateRangeDisplay(null, null);
    renderCalendar();
}

if (brDateRangeInput) {
    brDateRangeInput.addEventListener('click', function(e) {
        e.stopPropagation();
        openCalendar();
    });
    brDateRangeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const parsed = parseDateRange(this.value);
            if (parsed.valid) {
                updateDateRangeDisplay(parsed.start, parsed.end);
            } else {
                updateDateRangeDisplay(null, null);
            }
        }
    });
}

if (brDateCalendarBtn) {
    brDateCalendarBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleCalendar();
    });
}

if (brCalPrev) {
    brCalPrev.addEventListener('click', function(e) {
        e.stopPropagation();
        brCalCurrentDate = new Date(brCalCurrentDate.getFullYear(), brCalCurrentDate.getMonth() - 1, 1);
        renderCalendar();
    });
}

if (brCalNext) {
    brCalNext.addEventListener('click', function(e) {
        e.stopPropagation();
        brCalCurrentDate = new Date(brCalCurrentDate.getFullYear(), brCalCurrentDate.getMonth() + 1, 1);
        renderCalendar();
    });
}

if (brCalClear) {
    brCalClear.addEventListener('click', function(e) {
        e.stopPropagation();
        clearCalendar();
    });
}

if (brCalApply) {
    brCalApply.addEventListener('click', function(e) {
        e.stopPropagation();
        applyCalendar();
    });
}

document.addEventListener('click', function(e) {
    if (brDateCalendar && brDateCalendar.style.display === 'block') {
        if (!brDateCalendar.contains(e.target) && e.target !== brDateRangeInput && e.target !== brDateCalendarBtn) {
            closeCalendar();
        }
    }
});

// --- Toggle student collection rows via student name click ---
document.addEventListener('click', function(e) {
    const link = e.target.closest('.br-student-name');
    if (!link) return;
    e.preventDefault();
    const studentId = link.dataset.studentId;
    const detailRow = document.querySelector(`.br-student-detail[data-student-id="${studentId}"]`);
    const isHidden = detailRow.style.display === 'none';
    detailRow.style.display = isHidden ? 'table-row' : 'none';
    link.style.textDecoration = isHidden ? 'underline' : 'none';
});

// --- Quarter change triggers full-page submit (no AJAX needed) ---
const brQuarter = document.getElementById('brQuarter');
if (brQuarter) {
    brQuarter.addEventListener('change', function() {
        document.getElementById('brFilterForm').requestSubmit();
    });
}

// --- Date Range manual Enter triggers submit ---
if (brDateRangeInput) {
    brDateRangeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            brCurrentPage = 1;
            const parsed = parseDateRange(this.value);
            if (parsed.valid) {
                updateDateRangeDisplay(parsed.start, parsed.end);
            } else {
                updateDateRangeDisplay(null, null);
            }
            fetchBottleData();
        }
    });
}

// --- Pagination click delegation ---
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.page-btn');
    if (!btn) return;
    e.preventDefault();
    const page = parseInt(btn.dataset.page, 10);
    if (isNaN(page) || page === brCurrentPage) return;
    brCurrentPage = page;
    fetchBottleData();
});

// --- Browser Back/Forward ---
window.addEventListener('popstate', function(e) {
    if (!e.state) return;
    brCurrentPage = e.state.page || 1;
    const searchVal = e.state.search || '';
    const dateFrom = e.state.date_from || '';
    const dateTo = e.state.date_to || '';
    const quarterVal = e.state.quarter || '';

    if (brSearchInput) brSearchInput.value = searchVal;
    if (brQuarterSelect) brQuarterSelect.value = quarterVal;
    if (document.getElementById('brDateFrom')) document.getElementById('brDateFrom').value = dateFrom;
    if (document.getElementById('brDateTo')) document.getElementById('brDateTo').value = dateTo;
    const dateRangeInput = document.getElementById('brDateRange');
    if (dateRangeInput) {
        dateRangeInput.value = (dateFrom && dateTo) ? `${dateFrom} - ${dateTo}` : '';
    }

    fetchBottleData();
});
</script>
@endpush