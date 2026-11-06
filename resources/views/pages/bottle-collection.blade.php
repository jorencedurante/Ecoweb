@extends('layouts.admin')

@section('title', 'EcoCollect - Bottle Collection')
@section('page-title', 'Bottle Collection')
@section('page-subtitle', 'Manage bottle collection records')

@section('content')
    <style>
        /* Bottle Collection — automatic filter bar (scoped to this page) */
        .bottle-filter-card .filter-header .icon {
            color: #4a90d9;
            flex: 0 0 auto;
        }

        .bottle-filter-chevron {
            margin-left: auto;
            color: #4a90d9;
            transition: transform 0.25s ease;
        }

        .bottle-filter-card .filter-header.collapsed .bottle-filter-chevron {
            transform: rotate(-90deg);
        }

        .bottle-filter-card .filter-body {
            padding: 14px 20px 16px;
        }

        .bottle-filter-form {
            display: grid;
            grid-template-columns: minmax(280px, 5fr) minmax(200px, 3fr) minmax(230px, 2fr);
            gap: 16px;
            align-items: end;
        }

        .bottle-filter-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .bottle-filter-field > label {
            font-size: 11.5px;
            font-weight: 700;
            color: #5a6a7a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bottle-input {
            position: relative;
            display: flex;
            align-items: center;
        }

        .bottle-input input,
        .bottle-input select {
            width: 100%;
            height: 44px;
            padding: 0 38px 0 40px;
            border: 1px solid #cdd5df;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            font-size: 13.5px;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .bottle-input select {
            -webkit-appearance: none;
            appearance: none;
            padding: 0 34px 0 12px;
            cursor: pointer;
        }

        .bottle-input input:focus,
        .bottle-input select:focus {
            outline: none;
            border-color: #4a90d9;
            box-shadow: 0 0 0 3px rgba(74, 144, 217, 0.14);
        }

        .bottle-input input::placeholder {
            color: #94a3b8;
        }

        .bottle-input-icon {
            position: absolute;
            left: 13px;
            display: flex;
            color: #94a3b8;
            pointer-events: none;
            z-index: 1;
        }

        .bottle-input-clear {
            position: absolute;
            right: 8px;
            width: 26px;
            height: 26px;
            border: none;
            border-radius: 50%;
            background: #e2e8f0;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            z-index: 2;
            transition: background 0.2s;
        }

        .bottle-input-clear:hover {
            background: #cbd5e1;
        }

        .bottle-input-clear[hidden] {
            display: none;
        }

        .bottle-input--date input[type="date"] {
            padding-right: 38px;
            cursor: pointer;
        }

        .bottle-input--date input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            cursor: pointer;
        }

        .bottle-select-chevron {
            position: absolute;
            right: 12px;
            display: flex;
            color: #64748b;
            pointer-events: none;
        }

        @media (max-width: 1100px) {
            .bottle-filter-form {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .bottle-filter-card .filter-body {
                padding: 12px 16px 14px;
            }
        }
    </style>

    <div class="filter-card bottle-filter-card">
        <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
            <x-icon name="funnel" :size="16" />
            Filters
            <x-icon name="chevron-down" :size="15" class="bottle-filter-chevron" />
        </button>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.bottle-collection') }}" class="bottle-filter-form" id="bottleFilterForm">
                <div class="bottle-filter-field">
                    <label for="bottleFilterSearch">Search Collections</label>
                    <div class="bottle-input">
                        <span class="bottle-input-icon" aria-hidden="true"><x-icon name="search" :size="16" /></span>
                        <input
                            type="text"
                            id="bottleFilterSearch"
                            name="search"
                            placeholder="Search by student name, LRN, or bottle type..."
                            value="{{ request('search') }}"
                            aria-label="Search collections by student name, LRN, or bottle type"
                            autocomplete="off"
                        >
                        <button type="button" class="bottle-input-clear" id="bottleSearchClear" aria-label="Clear search" title="Clear search" {{ request('search') ? '' : 'hidden' }}>
                            <x-icon name="x" :size="13" />
                        </button>
                    </div>
                </div>

                <div class="bottle-filter-field">
                    <label for="bottleFilterDate">Collection Date</label>
                    <div class="bottle-input bottle-input--date">
                        <span class="bottle-input-icon" aria-hidden="true"><x-icon name="calendar" :size="16" /></span>
                        <input
                            type="date"
                            id="bottleFilterDate"
                            name="date"
                            placeholder="Select collection date"
                            title="Select collection date"
                            value="{{ request('date') }}"
                            aria-label="Collection date"
                        >
                        <button type="button" class="bottle-input-clear" id="bottleDateClear" aria-label="Clear collection date" title="Clear date" {{ request('date') ? '' : 'hidden' }}>
                            <x-icon name="x" :size="13" />
                        </button>
                    </div>
                </div>

                <div class="bottle-filter-field">
                    <label for="bottleFilterQuarter">Quarter</label>
                    <div class="bottle-input">
                        <select id="bottleFilterQuarter" name="quarter" aria-label="Quarter">
                            <option value="">All Quarters</option>
                            <option value="q1" {{ request('quarter') == 'q1' ? 'selected' : '' }}>Quarter 1 (January–March)</option>
                            <option value="q2" {{ request('quarter') == 'q2' ? 'selected' : '' }}>Quarter 2 (April–June)</option>
                            <option value="q3" {{ request('quarter') == 'q3' ? 'selected' : '' }}>Quarter 3 (July–September)</option>
                            <option value="q4" {{ request('quarter') == 'q4' ? 'selected' : '' }}>Quarter 4 (October–December)</option>
                        </select>
                        <span class="bottle-select-chevron" aria-hidden="true"><x-icon name="chevron-down" :size="14" /></span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container" id="bottle-collection-table" aria-busy="false">
        <div class="table-header">
            <div class="table-header-left">
            </div>
            <button type="button" class="btn btn-primary" data-modal-target="addCollectionModal">+ Add Collection</button>
        </div>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th scope="col">LRN</th>
                    <th scope="col">Student</th>
                    <th scope="col">Date</th>
                    <th scope="col">Time</th>
                    <th scope="col">Bottle Count</th>
                    <th scope="col">Points</th>
                </tr>
            </thead>
            <tbody id="bottle-collection-tbody">
                @forelse($collections as $c)
                <tr>
                    <td>{{ $c->lrn }}</td>
                    <td><strong>{{ $c->student->full_name ?? 'Unknown' }}</strong></td>
                    <td>{{ $c->collection_date?->format('Y-m-d') ?? $c->collection_date }}</td>
                    <td>{{ \Carbon\Carbon::parse($c->collection_time)->format('h:i A') }}</td>
                    <td>{{ $c->bottle_count }}</td>
                    <td>{{ $c->points_earned }}</td>
                </tr>
                @empty
                <tr id="bottle-collection-empty"><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No bottle collection records found.@if(request('search') || request('date') || request('quarter'))<div style="margin-top:6px;font-size:12.5px;">Try another student, date, or quarter.</div>@endif</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="pagination">
            <span class="page-info" id="bottle-collection-page-info">Showing {{ $collections->firstItem() ?? 0 }} to {{ $collections->lastItem() ?? 0 }} of {{ $collections->total() }} entries</span>
            <div class="page-btns" id="bottle-collection-page-btns">
                @for ($i = 1; $i <= $collections->lastPage(); $i++)
                    <a href="{{ $collections->url($i) }}" data-page="{{ $i }}" class="page-btn {{ $collections->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                @endfor
            </div>
        </div>
    </div>

    <!-- Add Collection Modal -->
    <div class="modal-overlay" id="addCollectionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add bottle collection</h3>
                <p class="subtitle">Record a new bottle collection</p>
            </div>
            <form method="POST" action="{{ route('admin.bottle-collection.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group student-search-wrapper">
                        <label for="bottleStudentSearchInput">Student</label>
                        <input
                            type="text"
                            id="bottleStudentSearchInput"
                            name="student_display"
                            class="student-search-input"
                            placeholder="Search student by name, LRN, or Student ID..."
                            autocomplete="off"
                            value="{{ old('student_display') }}"
                        >
                        <input
                            type="hidden"
                            name="student_id"
                            id="bottleSelectedStudentId"
                            value="{{ old('student_id') }}"
                            aria-label="Select student"
                        >
                        <div id="bottleStudentSearchResults" class="student-search-results"></div>
                        @error('student_id')
                            <div class="field-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="collection_date" value="{{ date('Y-m-d') }}" required aria-label="Collection date" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="collection_time" value="{{ date('H:i') }}" required aria-label="Collection time" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Bottle Count</label>
                        <input type="number" name="bottle_count" min="1" placeholder="Enter number of bottles" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;" aria-label="Bottle count">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Save Collection</button>
                </div>
            </form>
        </div>
    </div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('bottleStudentSearchInput');
    const hiddenId = document.getElementById('bottleSelectedStudentId');
    const results = document.getElementById('bottleStudentSearchResults');
    let searchTimeout = null;

    if (searchInput && hiddenId && results) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            hiddenId.value = '';
            clearTimeout(searchTimeout);
            if (query.length < 2) {
                results.innerHTML = '';
                results.style.display = 'none';
                return;
            }
            searchTimeout = setTimeout(function () {
                fetch('{{ route("admin.students.search") }}?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(students => {
                        results.innerHTML = '';
                        if (!students.length) {
                            results.innerHTML = '<div class="student-search-empty">No student found.</div>';
                            results.style.display = 'block';
                            return;
                        }
                        students.forEach(student => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'student-search-result-item';
                            btn.innerHTML = '<strong>' + student.name + '</strong><span>LRN: ' + (student.lrn ?? 'N/A') + ' | ID: ' + (student.student_id ?? 'N/A') + ' | ' + (student.grade_level ?? 'No grade') + '</span>';
                            btn.addEventListener('click', function () {
                                searchInput.value = student.name + ' - LRN: ' + (student.lrn ?? 'N/A');
                                hiddenId.value = student.id;
                                results.innerHTML = '';
                                results.style.display = 'none';
                                const err = document.querySelector('.student-search-wrapper .field-error');
                                if (err) err.style.display = 'none';
                            });
                            results.appendChild(btn);
                        });
                        results.style.display = 'block';
                    })
                    .catch(function () {
                        results.innerHTML = '<div class="student-search-empty">Unable to search students.</div>';
                        results.style.display = 'block';
                    });
            }, 300);
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.student-search-wrapper')) {
                results.style.display = 'none';
            }
        });
    }

    // ===== Automatic filtering: search / collection date / quarter =====
    const fetchUrl = '{{ route("admin.bottle-collection.fetch") }}';
    const filterForm = document.getElementById('bottleFilterForm');
    const filterSearch = document.getElementById('bottleFilterSearch');
    const filterSearchClear = document.getElementById('bottleSearchClear');
    const filterDate = document.getElementById('bottleFilterDate');
    const filterDateClear = document.getElementById('bottleDateClear');
    const filterQuarter = document.getElementById('bottleFilterQuarter');
    const tableContainer = document.getElementById('bottle-collection-table');
    const tableBody = document.getElementById('bottle-collection-tbody');
    const pageInfo = document.getElementById('bottle-collection-page-info');
    const pageBtns = document.getElementById('bottle-collection-page-btns');

    let currentPage = parseInt(new URLSearchParams(window.location.search).get('page'), 10) || 1;
    let requestSeq = 0;
    let activeRequest = null;
    let searchTimer = null;
    let isPollingPaused = false;

    function buildFilterParams(includePage) {
        const params = new URLSearchParams();
        const search = filterSearch ? filterSearch.value.trim() : '';
        const date = filterDate ? filterDate.value : '';
        const quarter = filterQuarter ? filterQuarter.value : '';

        if (search) params.set('search', search);
        if (date) params.set('date', date);
        if (quarter) params.set('quarter', quarter);
        if (includePage && currentPage > 1) params.set('page', currentPage);

        return params;
    }

    function syncUrl() {
        const query = buildFilterParams(true).toString();
        try {
            history.replaceState(null, '', window.location.pathname + (query ? '?' + query : ''));
        } catch (e) {
            // Ignore browsers that block history updates
        }
    }

    function setLoading(isLoading) {
        if (!tableContainer) return;
        tableContainer.classList.toggle('loading', isLoading);
        tableContainer.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    function fetchBottleCollections(options) {
        options = options || {};
        if (options.resetPage) currentPage = 1;
        if (options.background && isPollingPaused) return;

        // Stale-response protection: only the newest request may update the table
        const seq = ++requestSeq;

        if (activeRequest) {
            activeRequest.abort();
            activeRequest = null;
        }

        const controller = new AbortController();
        activeRequest = controller;

        if (!options.background) setLoading(true);

        const url = fetchUrl + '?' + buildFilterParams(true).toString();

        fetch(url, {
            signal: controller.signal,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Request failed');
                return response.json();
            })
            .then(function (data) {
                if (seq !== requestSeq) return;
                activeRequest = null;

                if (data && data.success) {
                    const pagination = data.pagination || {};

                    // Requested page is beyond the filtered result set — retry on the last page
                    if ((!data.records || data.records.length === 0) && pagination.total > 0 && pagination.current_page > pagination.last_page) {
                        currentPage = pagination.last_page;
                        fetchBottleCollections(options);
                        return;
                    }

                    currentPage = pagination.current_page || currentPage;
                    updateTable(data.records, pagination);
                    if (!options.background) syncUrl();
                }

                setLoading(false);
            })
            .catch(function (error) {
                if (error && error.name === 'AbortError') return;
                if (seq !== requestSeq) return;
                activeRequest = null;
                setLoading(false);
                // Network failure: keep the last results instead of disrupting the page
            });
    }

    // Search: debounced auto-update (~300ms after typing stops)
    if (filterSearch) {
        filterSearch.addEventListener('input', function () {
            if (filterSearchClear) filterSearchClear.hidden = filterSearch.value.length === 0;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                searchTimer = null;
                fetchBottleCollections({ resetPage: true });
            }, 300);
        });
    }

    if (filterSearchClear) {
        filterSearchClear.addEventListener('click', function () {
            clearTimeout(searchTimer);
            searchTimer = null;
            filterSearch.value = '';
            filterSearchClear.hidden = true;
            fetchBottleCollections({ resetPage: true });
            filterSearch.focus();
        });
    }

    // Collection date: auto-update immediately when selected or cleared
    if (filterDate) {
        filterDate.addEventListener('change', function () {
            if (filterDateClear) filterDateClear.hidden = !filterDate.value;
            fetchBottleCollections({ resetPage: true });
        });

        filterDate.addEventListener('click', function () {
            if (typeof this.showPicker === 'function') {
                try {
                    this.showPicker();
                } catch (e) {
                    // Browser opens the calendar on its own
                }
            }
        });
    }

    if (filterDateClear) {
        filterDateClear.addEventListener('click', function () {
            filterDate.value = '';
            filterDateClear.hidden = true;
            fetchBottleCollections({ resetPage: true });
        });
    }

    // Quarter: auto-update immediately on change
    if (filterQuarter) {
        filterQuarter.addEventListener('change', function () {
            fetchBottleCollections({ resetPage: true });
        });
    }

    // Enter inside the filter bar must not trigger a full page reload
    if (filterForm) {
        filterForm.addEventListener('submit', function (event) {
            event.preventDefault();
        });
    }

    // Pagination: keep active filters and update without a page reload
    if (pageBtns) {
        pageBtns.addEventListener('click', function (event) {
            const link = event.target.closest('a.page-btn');
            if (!link) return;
            event.preventDefault();

            let page = parseInt(link.getAttribute('data-page'), 10);
            if (!page) {
                try {
                    page = parseInt(new URL(link.href).searchParams.get('page'), 10) || 1;
                } catch (e) {
                    page = 1;
                }
            }

            if (!page || page === currentPage) return;
            currentPage = page;
            fetchBottleCollections();
        });
    }

    function updateTable(records, pagination) {
        if (!tableBody) return;

        if (!records || records.length === 0) {
            const hasFilters = buildFilterParams(false).toString() !== '';
            tableBody.innerHTML = '<tr id="bottle-collection-empty"><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">' +
                'No bottle collection records found.' +
                (hasFilters ? '<div style="margin-top:6px;font-size:12.5px;">Try another student, date, or quarter.</div>' : '') +
                '</td></tr>';
            updatePagination(pagination);
            return;
        }

        let html = '';
        records.forEach(function (r) {
            html += '<tr>' +
                '<td>' + escapeHtml(r.lrn) + '</td>' +
                '<td><strong>' + escapeHtml(r.student) + '</strong></td>' +
                '<td>' + escapeHtml(r.date) + '</td>' +
                '<td>' + escapeHtml(r.time) + '</td>' +
                '<td>' + escapeHtml(r.bottle_count) + '</td>' +
                '<td>' + escapeHtml(r.points) + '</td>' +
            '</tr>';
        });
        tableBody.innerHTML = html;
        updatePagination(pagination);
    }

    function updatePagination(pagination) {
        if (!pagination) return;

        if (pageInfo) {
            pageInfo.textContent = 'Showing ' + (pagination.from || 0) + ' to ' + (pagination.to || 0) + ' of ' + (pagination.total || 0) + ' entries';
        }

        if (pageBtns) {
            let html = '';
            for (let i = 1; i <= pagination.last_page; i++) {
                const params = buildFilterParams(false);
                params.set('page', i);
                const classes = i === pagination.current_page ? 'page-btn active' : 'page-btn';
                const current = i === pagination.current_page ? ' aria-current="page"' : '';
                html += '<a href="?' + params.toString() + '" data-page="' + i + '" class="' + classes + '"' + current + '>' + i + '</a>';
            }
            pageBtns.innerHTML = html;
        }
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    // Auto-refresh: poll for new records every 3 seconds (respects active filters)
    let pollInterval = setInterval(function () {
        fetchBottleCollections({ background: true });
    }, 3000);

    // Pause polling when the Add Collection modal is open
    const modalOverlay = document.getElementById('addCollectionModal');
    if (modalOverlay) {
        const observer = new MutationObserver(function () {
            isPollingPaused = modalOverlay.style.display === 'block' || modalOverlay.classList.contains('show');
        });
        observer.observe(modalOverlay, { attributes: true, attributeFilter: ['style', 'class'] });
    }

    // Refresh when the tab becomes visible again
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            fetchBottleCollections({ background: true });
        }
    });
});
</script>
@endpush
@endsection
