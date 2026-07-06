@extends('layouts.admin')

@section('title', 'EcoCollect - Bottle Collection')
@section('page-title', 'Bottle Collection')
@section('page-subtitle', 'Manage bottle collection records')

@section('content')
    <div class="filter-card">
        <div class="filter-header" onclick="this.classList.toggle('collapsed');this.nextElementSibling.classList.toggle('collapsed')">
            <i class="fas fa-filter"></i> Filters
        </div>
        <div class="filter-body">
            <form method="GET" action="{{ route('admin.bottle-collection') }}" class="filter-form">
                <div class="filter-search">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search by LRN, student, bottle count, date..." value="{{ request('search') }}">
                </div>
                <div class="filter-search">
                    <label>Day</label>
                    <select name="day">
                        <option value="">Day</option>
                        @for($i = 1; $i <= 31; $i++)
                            <option value="{{ $i }}" {{ request('day') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="filter-search">
                    <label>Month</label>
                    <select name="month">
                        <option value="">Month</option>
                        @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Year</label>
                    <select name="year">
                        <option value="">Year</option>
                        @foreach(['2023','2024','2025','2026'] as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-search">
                    <label>Quarter</label>
                    <select name="quarter">
                        <option value="">Quarter</option>
                        <option value="q1" {{ request('quarter') == 'q1' ? 'selected' : '' }}>Q1 (Jan–Mar)</option>
                        <option value="q2" {{ request('quarter') == 'q2' ? 'selected' : '' }}>Q2 (Apr–Jun)</option>
                        <option value="q3" {{ request('quarter') == 'q3' ? 'selected' : '' }}>Q3 (Jul–Sep)</option>
                        <option value="q4" {{ request('quarter') == 'q4' ? 'selected' : '' }}>Q4 (Oct–Dec)</option>
                        <option value="current" {{ request('quarter') == 'current' ? 'selected' : '' }}>Current</option>
                        <option value="previous" {{ request('quarter') == 'previous' ? 'selected' : '' }}>Previous</option>
                    </select>
                </div>
                <div class="filter-controls">
                    <button class="btn btn-filter" type="submit">Filter</button>
                    <a href="{{ route('admin.bottle-collection') }}" class="btn btn-reset">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <div class="table-header-left">
            </div>
            <button class="btn btn-primary" data-modal-target="addCollectionModal">+ Add Collection</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Bottle Count</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                @forelse($collections as $c)
                <tr>
                    <td>{{ $c->lrn }}</td>
                    <td><strong>{{ $c->student->full_name ?? 'Unknown' }}</strong></td>
                    <td>{{ $c->collection_date }}</td>
                    <td>{{ \Carbon\Carbon::parse($c->collection_time)->format('h:i A') }}</td>
                    <td>{{ $c->bottle_count }}</td>
                    <td>{{ $c->points_earned }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No collection records found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            <span class="page-info">Showing {{ $collections->firstItem() ?? 0 }} to {{ $collections->lastItem() ?? 0 }} of {{ $collections->total() }} entries</span>
            <div class="page-btns">
                @for ($i = 1; $i <= $collections->lastPage(); $i++)
                    <a href="{{ $collections->url($i) }}" class="page-btn {{ $collections->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
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
                        >
                        <div id="bottleStudentSearchResults" class="student-search-results"></div>
                        @error('student_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="collection_date" value="{{ date('Y-m-d') }}" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="collection_time" value="{{ date('H:i') }}" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Bottle Count</label>
                        <input type="number" name="bottle_count" min="1" placeholder="Enter number of bottles" required style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
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
});
</script>
@endpush
@endsection
