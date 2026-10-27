@extends('layouts.admin')

@if(auth()->user()->isTeacher())
@section('title', 'EcoCollect - Student Ranking')
@section('page-title', 'Student Ranking')
@section('page-subtitle', 'Ranking of students in your assigned class based on points and bottle collections.')
@else
@section('title', 'EcoCollect - Student\'s Report')
@section('page-title', 'Student\'s Report')
@section('page-subtitle', 'View detailed student performance and collection activity.')
@endif

@section('content')
    <a href="{{ route('admin.reports') }}" class="back-link">← Back to Reports</a>

    @if(auth()->user()->isTeacher())
        {{-- ==================== TEACHER: STUDENT RANKING ==================== --}}
        <div class="filter-card">
            <button type="button" class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')" aria-expanded="true">
                <i class="fas fa-filter"></i> Filters
            </button>
            <div class="filter-body">
                <form method="GET" action="{{ route('admin.student-report') }}" class="filter-form">
                    <div class="filter-search">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Search by name or LRN..." value="{{ request('search') }}" aria-label="Search by name or LRN">
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
                <a href="{{ route('reports.student-ranking.print') }}?{{ http_build_query(request()->query()) }}" target="_blank" class="btn btn-filter" style="font-size:12px;padding:6px 14px;text-decoration:none;">🖨️ Print Report</a>
            </div>
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Rank</th>
                        <th scope="col">Student Name</th>
                        <th scope="col">LRN</th>
                        <th scope="col">Grade Level</th>
                        <th scope="col">Section</th>
                        <th scope="col">Total Bottles</th>
                        <th scope="col">Total Points</th>
                        <th scope="col">Total Item Claims</th>
                        <th scope="col">Latest Collection</th>
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
            </div>
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

        {{-- Student Summary Cards --}}
        <div class="summary-cards">
            <div class="stat-card">
                <div class="stat-icon green">👥</div>
                <div class="stat-info">
                    <div class="stat-value" id="srTotalStudents">{{ $totalStudents }}</div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">♀</div>
                <div class="stat-info">
                    <div class="stat-value" id="srFemaleCount">{{ $femaleCount }}</div>
                    <div class="stat-label">Female</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">♂</div>
                <div class="stat-info">
                    <div class="stat-value" id="srMaleCount">{{ $maleCount }}</div>
                    <div class="stat-label">Male</div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filter-card sr-filter-card">
            <button type="button" class="filter-header" id="srFilterToggle" aria-expanded="true" aria-controls="srFilterBody">
                <span class="filter-header-label"><i class="fas fa-filter"></i> Filters</span>
                <span class="filter-toggle-icon">▼</span>
            </button>
            <div class="filter-body" id="srFilterBody">
                <form id="srFilterForm" class="filter-form" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                    <div class="filter-search" style="flex:1;min-width:200px;">
                        <label for="srSearch">Search Student</label>
                        <input type="text" id="srSearch" name="search" placeholder="Search by name or LRN..." value="{{ request('search') }}" aria-label="Search students" autocomplete="off">
                    </div>
                    <div class="filter-search" style="min-width:150px;">
                        <label for="srGrade">Grade Level</label>
                        <select id="srGrade" name="grade_level" aria-label="Filter by grade level">
                            <option value="">Grade Level</option>
                            @foreach($gradeLevels ?? [] as $gl)
                                <option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>{{ $gl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-search" style="min-width:150px;">
                        <label for="srGender">Gender</label>
                        <select id="srGender" name="gender" aria-label="Filter by gender">
                            <option value="">Gender</option>
                            <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        {{-- Student Performance Table --}}
        <div class="table-container sr-table-container">
            <div class="table-header">
                <h4 style="font-size:14px;font-weight:600;">Student Performance</h4>
            </div>
            <div class="table-responsive">
            <table id="srStudentTable">
                <thead>
                    <tr>
                        <th scope="col" style="width:40px;">#</th>
                        <th scope="col">LRN</th>
                        <th scope="col">Student Name</th>
                        <th scope="col">Grade Level</th>
                        <th scope="col">Gender</th>
                    </tr>
                </thead>
                <tbody id="srStudentTableBody">
                    @forelse($students as $i => $s)
                    <tr>
                        <td>{{ $students->firstItem() + $i }}</td>
                        <td>{{ $s->lrn }}</td>
                        <td>
                            <a href="#" class="sr-student-link" data-id="{{ $s->id }}" data-lrn="{{ $s->lrn }}" data-name="{{ $s->full_name }}">
                                {{ $s->full_name }}
                            </a>
                        </td>
                        <td>{{ $s->grade_level }}</td>
                        <td>{{ $s->gender }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-light);">No students found for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="pagination sr-pagination">
                <span class="page-info">Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} entries</span>
                <div class="page-btns">
                    @for ($p = 1; $p <= $students->lastPage(); $p++)
                        <a href="{{ $students->url($p) }}" class="page-btn {{ $students->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
                    @endfor
                </div>
            </div>
        </div>

        {{-- Performance Details Modal --}}
        <div id="srModal" class="sr-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="srModalTitle">
            <div class="sr-modal-backdrop"></div>
            <div class="sr-modal-content">
                <div class="sr-modal-header">
                    <div class="sr-header-left">
                        <h3 id="srModalTitle">Performance Report</h3>
                        <div class="sr-modal-subtitle">Student activity and accomplishments</div>
                    </div>
                    <div class="sr-header-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <button type="button" class="sr-modal-close" id="srModalClose" aria-label="Close">×</button>
                </div>

                <div class="sr-modal-body">
                    {{-- Student Identity Card --}}
                    <div class="sr-identity-card">
                        <div class="sr-avatar" id="srAvatar">BC</div>
                        <div class="sr-identity-info">
                            <div class="sr-identity-header">
                                <h4 id="srStudentName">Student Full Name</h4>
                                <span class="sr-status-badge" id="srStatusBadge">Active</span>
                            </div>
                            <div class="sr-lrn">LRN: <span id="srStudentLrn">108663050026</span></div>
                            <div class="sr-badges" id="srBadges">
                                <span class="sr-badge" id="srGradeBadge">Grade Level</span>
                                <span class="sr-badge" id="srGenderBadge">Gender</span>
                                <span class="sr-badge" id="srStudentIdBadge">Student ID</span>
                            </div>
                            <div class="sr-teacher">
                                <span class="sr-teacher-label">Assigned Teacher:</span>
                                <span id="srTeacherName">Teacher Name</span>
                            </div>
                        </div>
                    </div>

                    {{-- Performance Snapshot --}}
                    <div class="sr-snapshot-section">
                        <div class="sr-section-title">
                            <span class="sr-section-title-text">Performance Snapshot</span>
                            <span class="sr-section-title-sub">Lifetime totals</span>
                        </div>
                        <div class="sr-snapshot-grid" id="srSnapshotGrid">
                            <!-- populated by JS -->
                        </div>
                    </div>

                    {{-- Report Section Navigation --}}
                    <div class="sr-nav-section">
                        <div class="sr-nav-title">Report Sections</div>
                        <div class="sr-nav-pills" id="srNavPills">
                            <button type="button" class="sr-nav-pill active" data-section="activity" aria-selected="true">
                                <i class="fas fa-clock"></i> Activity
                            </button>
                            <button type="button" class="sr-nav-pill" data-section="collections" aria-selected="false">
                                <i class="fas fa-bottle-water"></i> Collections
                            </button>
                            <button type="button" class="sr-nav-pill" data-section="rewards" aria-selected="false">
                                <i class="fas fa-gift"></i> Rewards
                            </button>
                            <button type="button" class="sr-nav-pill" data-section="achievements" aria-selected="false">
                                <i class="fas fa-trophy"></i> Achievements
                            </button>
                            <button type="button" class="sr-nav-pill" data-section="certificates" aria-selected="false">
                                <i class="fas fa-award"></i> Certificates
                            </button>
                        </div>
                    </div>

                    {{-- Section Content (scrollable area) --}}
                    <div class="sr-sections-wrap" id="srSectionsWrap">
                        {{-- Activity Section --}}
                        <div class="sr-section-content active" id="srSectionActivity">
                            <div class="sr-section-content-header">
                                <h4>Activity Summary</h4>
                                <p class="sr-section-desc">Collection activity overview</p>
                            </div>
                            <div class="sr-activity-grid" id="srActivityGrid">
                                <!-- populated by JS -->
                            </div>
                        </div>

                        {{-- Collections Section --}}
                        <div class="sr-section-content" id="srSectionCollections">
                            <div class="sr-section-content-header">
                                <h4>Bottle Collection History</h4>
                                <p class="sr-section-desc">All bottle collection records</p>
                            </div>
                            <div class="sr-table-wrap" id="srCollectionsTableWrap">
                                <!-- populated by JS -->
                            </div>
                        </div>

                        {{-- Rewards Section --}}
                        <div class="sr-section-content" id="srSectionRewards">
                            <div class="sr-section-content-header">
                                <h4>Points &amp; Claimed Rewards</h4>
                                <p class="sr-section-desc">Points summary and item claim history</p>
                            </div>
                            <div class="sr-points-summary-wrap" id="srRewardsPointsWrap">
                                <!-- populated by JS -->
                            </div>
                            <div class="sr-table-wrap" id="srClaimsTableWrap">
                                <!-- populated by JS -->
                            </div>
                        </div>

                        {{-- Achievements Section --}}
                        <div class="sr-section-content" id="srSectionAchievements">
                            <div class="sr-section-content-header">
                                <h4>Achievements Earned</h4>
                                <p class="sr-section-desc">Achievement badges earned by the student</p>
                            </div>
                            <div class="sr-achievements-grid" id="srAchievementsGrid">
                                <!-- populated by JS -->
                            </div>
                        </div>

                        {{-- Certificates Section --}}
                        <div class="sr-section-content" id="srSectionCertificates">
                            <div class="sr-section-content-header">
                                <h4>Certificates Earned</h4>
                                <p class="sr-section-desc">Certificates awarded to the student</p>
                            </div>
                            <div class="sr-certificates-grid" id="srCertificatesGrid">
                                <!-- populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sr-modal-footer">
                    <span class="sr-footer-left">Student performance record</span>
                    <div class="sr-footer-right">
                        <button type="button" class="btn btn-secondary" id="srModalCloseBtn">Close</button>
                        <a href="#" id="srPrintBtn" class="btn btn-primary">Print Report</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Error display for AJAX --}}
        <div id="srError" class="sr-error" style="display:none;"></div>
    @endif
@endsection

@push('scripts')
<script>
// --- Student Report Live Filters ---
(function() {
    const searchInput = document.getElementById('srSearch');
    const gradeSelect = document.getElementById('srGrade');
    const genderSelect = document.getElementById('srGender');
    const tableBody = document.getElementById('srStudentTableBody');
    const paginationContainer = document.querySelector('.sr-pagination');
    const errorDiv = document.getElementById('srError');

    let timer = null;
    let abortController = null;

    function abortPending() {
        if (abortController) {
            abortController.abort();
            abortController = null;
        }
    }

    function showError(msg) {
        if (errorDiv) {
            errorDiv.textContent = msg;
            errorDiv.style.display = '';
        }
    }

    function hideError() {
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    function buildUrl(page = null) {
        const params = new URLSearchParams(window.location.search);
        const searchVal = searchInput ? searchInput.value.trim() : '';
        const gradeVal = gradeSelect ? gradeSelect.value : '';
        const genderVal = genderSelect ? genderSelect.value : '';

        if (searchVal) params.set('search', searchVal);
        else params.delete('search');
        if (gradeVal) params.set('grade_level', gradeVal);
        else params.delete('grade_level');
        if (genderVal) params.set('gender', genderVal);
        else params.delete('gender');

        if (page !== null) {
            params.set('page', page);
        } else {
            params.set('page', 1);
        }

        return window.location.pathname + '?' + params.toString();
    }

    function fetchStudents(page = null) {
        abortPending();
        const controller = new AbortController();
        abortController = controller;

        hideError();

        const url = buildUrl(page);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            signal: controller.signal
        })
        .then(response => {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        })
        .then(data => {
            if (!data.success) throw new Error('Request failed');

            // Update summary cards
            const totalEl = document.getElementById('srTotalStudents');
            const femaleEl = document.getElementById('srFemaleCount');
            const maleEl = document.getElementById('srMaleCount');
            if (totalEl) totalEl.textContent = data.total_students ?? 0;
            if (femaleEl) femaleEl.textContent = data.female_count ?? 0;
            if (maleEl) maleEl.textContent = data.male_count ?? 0;

            // Extract table body and pagination from returned HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(data.table_html, 'text/html');
            const newBody = doc.getElementById('srStudentTableBody');
            const newPagination = doc.querySelector('.sr-pagination');

            if (newBody) {
                tableBody.innerHTML = newBody.innerHTML;
            }
            if (newPagination && paginationContainer) {
                paginationContainer.innerHTML = newPagination.innerHTML;
            }

            // Re-bind click handlers on new student links
            bindStudentLinks();

            // Re-bind pagination event delegation
            bindPaginationLinks();

            // Update browser URL without page reload
            if (page !== null) {
                const newUrl = window.location.pathname + '?' + paramsToString(page);
                history.pushState(null, '', newUrl);
            }

            abortController = null;
        })
        .catch(err => {
            if (err.name === 'AbortError') return;
            showError('Unable to load student data. Please try again.');
            console.error('Student report error:', err);
        });
    }

    function paramsToString(page = 1) {
        const params = new URLSearchParams();
        const searchVal = searchInput ? searchInput.value.trim() : '';
        const gradeVal = gradeSelect ? gradeSelect.value : '';
        const genderVal = genderSelect ? genderSelect.value : '';

        if (searchVal) params.set('search', searchVal);
        if (gradeVal) params.set('grade_level', gradeVal);
        if (genderVal) params.set('gender', genderVal);
        params.set('page', page);

        return params.toString();
    }

    function bindPaginationLinks() {
        if (!paginationContainer) return;
        // Remove existing listeners by replacing the element
        const newContainer = paginationContainer.cloneNode(true);
        if (paginationContainer.parentNode) {
            paginationContainer.parentNode.replaceChild(newContainer, paginationContainer);
        }
        // Update the module-level reference
        // Use a fresh query since the old element is now detached
        const refreshedContainer = document.querySelector('.sr-pagination');
        if (refreshedContainer) {
            refreshedContainer.addEventListener('click', function(e) {
                const link = e.target.closest('.page-btn');
                if (!link) return;
                e.preventDefault();
                const href = link.getAttribute('href');
                if (!href) return;
                const url = new URL(href, window.location.origin);
                const page = url.searchParams.get('page');
                if (page) {
                    fetchStudents(parseInt(page));
                }
            });
        }
    }

    function bindStudentLinks() {
        document.querySelectorAll('.sr-student-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const studentId = this.dataset.id;
                const studentName = this.dataset.name;
                const studentLrn = this.dataset.lrn;
                openStudentModal(studentId, studentName, studentLrn);
            });
        });
    }

    // Initial bind
    bindStudentLinks();
    bindPaginationLinks();

    // Live search with debounce
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(() => fetchStudents(1), 300);
        });
    }

    if (gradeSelect) {
        gradeSelect.addEventListener('change', function() {
            clearTimeout(timer);
            timer = setTimeout(() => fetchStudents(1), 300);
        });
    }

    if (genderSelect) {
        genderSelect.addEventListener('change', function() {
            clearTimeout(timer);
            timer = setTimeout(() => fetchStudents(1), 300);
        });
    }

    // Collapsible filter toggle
    const filterToggle = document.getElementById('srFilterToggle');
    const filterBody = document.getElementById('srFilterBody');
    if (filterToggle && filterBody) {
        filterToggle.addEventListener('click', function() {
            const isExpanded = filterToggle.getAttribute('aria-expanded') === 'true';
            filterToggle.setAttribute('aria-expanded', !isExpanded);
            if (isExpanded) {
                filterBody.classList.add('collapsed');
            } else {
                filterBody.classList.remove('collapsed');
            }
        });
    }

    // --- Student Performance Modal ---
    const modal = document.getElementById('srModal');
    const backdrop = document.querySelector('.sr-modal-backdrop');
    const closeBtn = document.getElementById('srModalClose');
    const closeBtn2 = document.getElementById('srModalCloseBtn');
    const printBtn = document.getElementById('srPrintBtn');
    const modalTitle = document.getElementById('srModalTitle');

    // Identity card elements
    const srAvatar = document.getElementById('srAvatar');
    const srStudentName = document.getElementById('srStudentName');
    const srStatusBadge = document.getElementById('srStatusBadge');
    const srStudentLrn = document.getElementById('srStudentLrn');
    const srGradeBadge = document.getElementById('srGradeBadge');
    const srGenderBadge = document.getElementById('srGenderBadge');
    const srStudentIdBadge = document.getElementById('srStudentIdBadge');
    const srTeacherName = document.getElementById('srTeacherName');

    // Snapshot elements
    const srSnapshotGrid = document.getElementById('srSnapshotGrid');

    // Section nav
    const srNavPills = document.querySelectorAll('.sr-nav-pill');

    // Section content containers
    const srActivityGrid = document.getElementById('srActivityGrid');
    const srCollectionsTableWrap = document.getElementById('srCollectionsTableWrap');
    const srRewardsPointsWrap = document.getElementById('srRewardsPointsWrap');
    const srClaimsTableWrap = document.getElementById('srClaimsTableWrap');
    const srAchievementsGrid = document.getElementById('srAchievementsGrid');
    const srCertificatesGrid = document.getElementById('srCertificatesGrid');

    let currentStudentId = null;
    let currentSection = 'activity';
    let studentData = null;

    function openModal() {
        if (modal) {
            modal.style.display = 'flex';
            document.body.classList.add('modal-open');
        }
    }

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
        currentStudentId = null;
        studentData = null;
        currentSection = 'activity';
    }

    function closeModalDelayed() {
        timer = setTimeout(closeModal, 150);
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModalDelayed);
    if (closeBtn2) closeBtn2.addEventListener('click', closeModalDelayed);
    if (backdrop) backdrop.addEventListener('click', closeModalDelayed);

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeModalDelayed();
        }
    });

    // Section navigation switching
    srNavPills.forEach(pill => {
        pill.addEventListener('click', function() {
            const section = this.dataset.section;
            if (section === currentSection) return;
            srNavPills.forEach(p => {
                p.classList.remove('active');
                p.setAttribute('aria-selected', 'false');
                p.tabIndex = -1;
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            this.tabIndex = 0;
            currentSection = section;
            showSection(section);
        });
    });

    function showSection(section) {
        document.querySelectorAll('.sr-section-content').forEach(el => {
            el.classList.remove('active');
        });
        const target = document.getElementById('srSection' + section.charAt(0).toUpperCase() + section.slice(1));
        if (target) target.classList.add('active');
    }

    async function openStudentModal(studentId, studentName, studentLrn) {
        abortPending();
        currentStudentId = studentId;

        const controller = new AbortController();
        abortController = controller;

        const url = "{{ route('admin.student-report.details', '__PLACEHOLDER__') }}".replace('__PLACEHOLDER__', studentId);

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                signal: controller.signal
            });

            if (!response.ok) throw new Error('Failed to load student details');
            const data = await response.json();

            if (!data.success) {
                alert(data.message || 'Unable to load student details.');
                return;
            }

            const s = data.student;
            studentData = s;
            currentSection = 'activity';

            // Reset nav pills to Activity
            srNavPills.forEach(p => {
                p.classList.remove('active');
                p.setAttribute('aria-selected', 'false');
                p.tabIndex = -1;
            });
            const activityPill = document.querySelector('.sr-nav-pill[data-section="activity"]');
            if (activityPill) {
                activityPill.classList.add('active');
                activityPill.setAttribute('aria-selected', 'true');
                activityPill.tabIndex = 0;
            }

            // Modal title
            if (modalTitle) modalTitle.textContent = 'Performance Report — ' + escapeHtml(s.full_name || '');

            // Student Identity Card
            if (srAvatar) {
                const name = s.full_name || 'Student';
                const initials = name.split(' ').map(n => n[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() || 'BC';
                srAvatar.textContent = initials;
            }
            if (srStudentName) srStudentName.textContent = escapeHtml(s.full_name || 'N/A');
            if (srStatusBadge) srStatusBadge.textContent = escapeHtml(s.status || 'Active');
            if (srStudentLrn) srStudentLrn.textContent = escapeHtml(s.lrn || 'N/A');
            if (srGradeBadge) srGradeBadge.textContent = escapeHtml(s.grade_level || 'N/A');
            if (srGenderBadge) srGenderBadge.textContent = escapeHtml(s.gender || 'N/A');
            if (srStudentIdBadge) srStudentIdBadge.textContent = escapeHtml(s.student_id || 'N/A');
            if (srTeacherName) srTeacherName.textContent = escapeHtml(s.teacher_name || 'N/A');

            // Performance Snapshot (always visible)
            if (srSnapshotGrid) {
                srSnapshotGrid.innerHTML = `
                    <div class="sr-snapshot-card blue">
                        <div class="sr-snapshot-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5"/><path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12"/><path d="m14 16-3 3 3 3"/><path d="M8.293 13.596 7.196 9.5 3.1 10.598"/><path d="m9.344 5.811 1.093-1.892A1.83 1.83 0 0 1 11.985 3a1.784 1.784 0 0 1 1.546.888l3.943 6.843"/><path d="m13.378 9.633 4.096 1.098 1.097-4.096"/></svg></div>
                        <div>
                            <div class="sr-snapshot-value">${s.total_bottles || 0}</div>
                            <div class="sr-snapshot-label">Bottles Collected</div>
                        </div>
                    </div>
                    <div class="sr-snapshot-card green">
                        <div class="sr-snapshot-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg></div>
                        <div>
                            <div class="sr-snapshot-value">${s.total_points || 0}</div>
                            <div class="sr-snapshot-label">Points Earned</div>
                        </div>
                    </div>
                    <div class="sr-snapshot-card purple">
                        <div class="sr-snapshot-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14.66V17a1 1 0 0 1-1 1 2 2 0 0 0-2 2v2"/><path d="M14 14.66V17a1 1 0 0 0 1 1 2 2 0 0 1 2 2v2"/><path d="M17.916 10H19.5A2.5 2.5 0 0 0 22 7.5V5a1 1 0 0 0-1-1h-3"/><path d="M4 22h16"/><path d="M6 9a6 6 0 0 0 12 0V3a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1z"/><path d="M6.084 10H4.5A2.5 2.5 0 0 1 2 7.5V5a1 1 0 0 1 1-1h3"/></svg></div>
                        <div>
                            <div class="sr-snapshot-value">${s.achievements_count || 0}</div>
                            <div class="sr-snapshot-label">Achievements</div>
                        </div>
                    </div>
                    <div class="sr-snapshot-card orange">
                        <div class="sr-snapshot-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/></svg></div>
                        <div>
                            <div class="sr-snapshot-value">${s.certificates_count || 0}</div>
                            <div class="sr-snapshot-label">Certificates</div>
                        </div>
                    </div>
                `;
            }

            // Activity Section
            renderActivitySection(s);

            // Collections Section
            renderCollectionsSection(s);

            // Rewards Section
            renderRewardsSection(s);

            // Achievements Section
            renderAchievementsSection(s);

            // Certificates Section
            renderCertificatesSection(s);

            // Show default section
            showSection('activity');

            // Print button
            if (printBtn) {
                printBtn.href = "{{ route('reports.student.print', '__PLACEHOLDER__') }}".replace('__PLACEHOLDER__', studentId);
            }

            openModal();
            abortController = null;

        } catch (err) {
            if (err.name === 'AbortError') return;
            alert('Unable to load student details. Please try again.');
            console.error('Student modal error:', err);
        } finally {
            if (abortController === controller) abortController = null;
        }
    }

    function renderActivitySection(s) {
        const collections = s.bottle_collections || [];
        const latest = collections.length > 0 ? collections[0] : null;
        const totalBottles = collections.reduce((sum, c) => sum + (parseInt(c.bottle_count) || 0), 0);

        if (srActivityGrid) {
            srActivityGrid.innerHTML = `
                <div class="sr-activity-item">
                    <div class="sr-activity-label">Collection Sessions</div>
                    <div class="sr-activity-value">${collections.length}</div>
                </div>
                <div class="sr-activity-item">
                    <div class="sr-activity-label">Latest Collection</div>
                    <div class="sr-activity-value">${latest ? latest.collection_date : 'No record'}</div>
                </div>
                <div class="sr-activity-item">
                    <div class="sr-activity-label">Available Points</div>
                    <div class="sr-activity-value">${s.available_points || 0}</div>
                </div>
                <div class="sr-activity-item">
                    <div class="sr-activity-label">Total Bottles Collected</div>
                    <div class="sr-activity-value">${totalBottles}</div>
                </div>
            `;
        }
    }

    function renderCollectionsSection(s) {
        const collections = (s.bottle_collections || []).slice();
        if (srCollectionsTableWrap) {
            if (collections.length === 0) {
                srCollectionsTableWrap.innerHTML = `
                    <div class="sr-empty-state">
                        <div class="sr-empty-icon"><i class="fas fa-bottle-water"></i></div>
                        <div class="sr-empty-msg">No collection records found.</div>
                    </div>
                `;
                return;
            }
            const thead = `<thead><tr><th>Date</th><th>Time</th><th>Bottles Collected</th><th>Points Earned</th></tr></thead>`;
            const tbody = `<tbody>${collections.map(item => `
                <tr>
                    <td>${escapeHtml(item.collection_date || 'N/A')}</td>
                    <td>${escapeHtml(item.collection_time || '—')}</td>
                    <td>${item.bottle_count || 0}</td>
                    <td>${item.points_earned || 0}</td>
                </tr>
            `).join('')}</tbody>`;
            srCollectionsTableWrap.innerHTML = `<table class="sr-detail-table">${thead}${tbody}</table>`;
        }
    }

    function renderRewardsSection(s) {
        const claims = s.claims || [];
        if (srRewardsPointsWrap) {
            srRewardsPointsWrap.innerHTML = `
                <div class="sr-points-card">
                    <div class="sr-points-label">Total Points Earned</div>
                    <div class="sr-points-value">${s.total_points || 0}</div>
                </div>
                <div class="sr-points-card">
                    <div class="sr-points-label">Points Redeemed</div>
                    <div class="sr-points-value">${s.points_redeemed || 0}</div>
                </div>
                <div class="sr-points-card">
                    <div class="sr-points-label">Available Points</div>
                    <div class="sr-points-value">${s.available_points || 0}</div>
                </div>
            `;
        }
        if (srClaimsTableWrap) {
            if (claims.length === 0) {
                srClaimsTableWrap.innerHTML = `
                    <div class="sr-empty-state">
                        <div class="sr-empty-icon"><i class="fas fa-gift"></i></div>
                        <div class="sr-empty-msg">No reward claims recorded.</div>
                    </div>
                `;
                return;
            }
            const thead = `<thead><tr><th>Item Name</th><th>Quantity</th><th>Points Used</th><th>Date</th><th>Status</th></tr></thead>`;
            const tbody = `<tbody>${claims.map(c => `
                <tr>
                    <td>${escapeHtml(c.item_name || 'N/A')}</td>
                    <td>${c.quantity || 0}</td>
                    <td>${c.points_deducted || 0}</td>
                    <td>${escapeHtml(c.claim_date || 'N/A')}</td>
                    <td>${escapeHtml(c.status || 'N/A')}</td>
                </tr>
            `).join('')}</tbody>`;
            srClaimsTableWrap.innerHTML = `<table class="sr-detail-table">${thead}${tbody}</table>`;
        }
    }

    function renderAchievementsSection(s) {
        const achievements = s.achievements || [];
        if (srAchievementsGrid) {
            if (achievements.length === 0) {
                srAchievementsGrid.innerHTML = `
                    <div class="sr-empty-state" style="grid-column: 1 / -1;">
                        <div class="sr-empty-icon"><i class="fas fa-trophy"></i></div>
                        <div class="sr-empty-msg">No achievements earned yet.</div>
                    </div>
                `;
                return;
            }
            srAchievementsGrid.innerHTML = achievements.map(a => `
                <div class="sr-achievement-card">
                    <div class="sr-ach-badge"><i class="fas fa-trophy"></i></div>
                    <div class="sr-ach-title">${escapeHtml(a.title || 'N/A')}</div>
                    <div class="sr-ach-desc">${escapeHtml(a.description || 'N/A')}</div>
                    <div class="sr-ach-date">${escapeHtml(a.awarded_date || 'N/A')}</div>
                </div>
            `).join('');
        }
    }

    function renderCertificatesSection(s) {
        const certificates = s.certificates || [];
        if (srCertificatesGrid) {
            if (certificates.length === 0) {
                srCertificatesGrid.innerHTML = `
                    <div class="sr-empty-state" style="grid-column: 1 / -1;">
                        <div class="sr-empty-icon"><i class="fas fa-award"></i></div>
                        <div class="sr-empty-msg">No awarded certificates found.</div>
                    </div>
                `;
                return;
            }
            srCertificatesGrid.innerHTML = certificates.map(c => `
                <div class="sr-certificate-card">
                    <div class="sr-cert-icon"><i class="fas fa-award"></i></div>
                    <div class="sr-cert-title">${escapeHtml(c.award_title || 'N/A')}</div>
                    <div class="sr-cert-desc">${escapeHtml(c.award_description || 'N/A')}</div>
                    <div class="sr-cert-date">${escapeHtml(c.awarded_date || 'N/A')}</div>
                    ${c.can_render ? `<a href="#" class="sr-cert-action" onclick="event.preventDefault(); alert('Certificate print functionality opens the certificate viewer.');">View / Print</a>` : `<span class="sr-cert-action" style="color:var(--text-light);cursor:not-allowed;">N/A</span>`}
                </div>
            `).join('');
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Browser Back/Forward support
    window.addEventListener('popstate', function() {
        // Parse current URL to get page number
        const params = new URLSearchParams(window.location.search);
        const page = parseInt(params.get('page')) || 1;
        fetchStudents(page);
    });
})();
</script>
@endpush