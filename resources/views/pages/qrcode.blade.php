@extends('layouts.admin')

@section('title', 'EcoCollect - QR Code Generation')
@section('page-title', 'QR Code Generation')
@section('page-subtitle', 'Generate QR codes for student information.')

@section('content')
    <div class="qr-page-grid">
        <div class="qr-card">
            <h3>Generate QR Code</h3>
            <p class="qr-card-subtitle">Select a student to generate their QR code.</p>
            <form method="POST" action="{{ route(portal_prefix() . '.qrcode.generate') }}" style="overflow: visible;">
                @csrf
                <input type="hidden" name="qr_type" value="lrn" aria-label="QR code type">
                <div class="qr-form-group">
                    <label>QR Type</label>
                    <div class="readonly-field">LRN</div>
                </div>
                <div class="qr-form-group">
                    <label for="qrStudentSearchInput">Select Student</label>
                    <div class="student-search-wrapper qr-student-search-wrapper">
                        <input
                            type="text"
                            id="qrStudentSearchInput"
                            name="student_display"
                            placeholder="Search student by name, LRN, or Student ID..."
                            autocomplete="off"
                            aria-label="Search student"
                            style="width:100%;padding:14px 16px;border:1px solid #d1d5db;border-radius:10px;font-size:15px;background:#ffffff;"
                        >
                        <input
                            type="hidden"
                            id="qrSelectedStudentId"
                            name="student_id"
                            required
                        >
                        <div id="qrStudentSearchResults" class="student-search-results"></div>
                    </div>
                    <div id="qrStudentError" style="color:#ef4444;font-size:12px;margin-top:4px;display:none;">Please select a student from the search results.</div>
                    @error('student_id')
                        <div class="field-error" role="alert">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn-generate-qr">Generate QR Code</button>
            </form>
        </div>

        <div class="qr-card qr-preview-card">
            <h3>QR Code Preview</h3>
            @if(isset($qrCode) && $qrCode)
                <div class="qr-preview-box">
                    <img src="{{ asset('storage/' . $qrCode->qr_image_path) }}" alt="Generated QR Code">
                </div>
                <p class="qr-student-name">Student: {{ $qrCode->student->full_name ?? $qrCode->student_name ?? 'Student not found' }}</p>
                <p class="qr-value-text">QR Value: {!! nl2br(e($qrCode->qr_value)) !!}</p>
                <div class="qr-actions">
                    <a href="{{ route(portal_prefix() . '.qrcode.print', $qrCode->id) }}" class="btn-print-qr" target="_blank" aria-label="Print QR code">🖨 Print QR Code</a>
                </div>
            @else
                <div class="empty-qr-preview">
                    <div class="empty-icon">▦</div>
                    <h3>No QR code generated yet</h3>
                    <p>Select a student and click Generate QR Code to preview it here.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="filter-card">
        <div class="filter-card-header">
            <i class="fas fa-filter"></i> Search QR Codes
        </div>
        <div class="filter-card-body">
            <div class="qr-filter-form">
                <div class="qr-filter-field">
                    <label for="qrSearch">Search</label>
                    <input type="text" id="qrSearch" placeholder="Search student, LRN or QR value..." value="{{ request('search') }}" aria-label="Search student name, LRN or QR value" autocomplete="off">
                </div>
                <div class="qr-filter-field">
                    <label for="qrDate">Date</label>
                    <input type="date" id="qrDate" value="{{ request('date') }}" aria-label="Filter by generated date">
                </div>
                <div class="qr-loading" id="qrLoading" aria-live="polite" hidden>Updating results…</div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h4>Generated QR Codes</h4>
        </div>
        <div id="qrResultsContainer">
            @include('partials.qrcode-results', ['qrCodes' => $qrCodes])
        </div>
    </div>
@endsection

@push('scripts')
@php
$qrStudentsData = $studentsForQr->map(function ($student) {
    $name = $student->full_name
        ?? trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
    return [
        'id' => $student->id,
        'name' => $name ?: 'Unnamed Student',
        'lrn' => $student->lrn ?? $student->student_id ?? '',
        'student_id' => $student->student_id ?? '',
        'grade_level' => $student->grade_level ?? $student->grade ?? '',
    ];
})->values()->toArray();
@endphp
<script>
const qrStudents = @json($qrStudentsData);
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('qrStudentSearchInput');
    var resultsBox = document.getElementById('qrStudentSearchResults');
    var hiddenInput = document.getElementById('qrSelectedStudentId');
    var studentError = document.getElementById('qrStudentError');

    if (!input || !resultsBox || !hiddenInput) return;

    function closeResults() {
        resultsBox.innerHTML = '';
        resultsBox.classList.remove('show');
    }

    function renderResults(students) {
        resultsBox.innerHTML = '';

        if (!students.length) {
            resultsBox.innerHTML = '<div class="student-search-empty">No students found.</div>';
            resultsBox.classList.add('show');
            return;
        }

        students.slice(0, 12).forEach(function (student) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'student-search-result-item';

            button.innerHTML =
                '<strong>' + student.name + '</strong>' +
                '<small>LRN: ' + (student.lrn || student.student_id || 'N/A') + (student.grade_level ? ' &middot; ' + student.grade_level : '') + '</small>';

            button.addEventListener('click', function () {
                input.value = student.name;
                hiddenInput.value = student.id;
                if (studentError) studentError.style.display = 'none';
                closeResults();
            });

            resultsBox.appendChild(button);
        });

        resultsBox.classList.add('show');
    }

    input.addEventListener('input', function () {
        var search = input.value.trim().toLowerCase();
        hiddenInput.value = '';
        if (studentError) studentError.style.display = 'none';

        if (search.length < 1) {
            closeResults();
            return;
        }

        var filtered = qrStudents.filter(function (student) {
            return (
                String(student.name || '').toLowerCase().includes(search) ||
                String(student.lrn || '').toLowerCase().includes(search) ||
                String(student.student_id || '').toLowerCase().includes(search) ||
                String(student.grade_level || '').toLowerCase().includes(search)
            );
        });

        renderResults(filtered);
    });

    input.addEventListener('focus', function () {
        if (input.value.trim().length > 0) {
            input.dispatchEvent(new Event('input'));
        }
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.qr-student-search-wrapper')) {
            closeResults();
        }
    });

    var qrForm = input.closest('form');
    if (qrForm) {
        qrForm.addEventListener('submit', function (e) {
            if (!hiddenInput.value) {
                e.preventDefault();
                if (studentError) studentError.style.display = 'block';
                input.focus();
                return false;
            }
        });
    }
});

/* Live search: debounced, abortable, AJAX-only result updates. */
(function () {
    var DATA_URL = @json(route(portal_prefix() . '.qrcode.data'));
    var searchInput = document.getElementById('qrSearch');
    var dateInput = document.getElementById('qrDate');
    var container = document.getElementById('qrResultsContainer');
    var loadingEl = document.getElementById('qrLoading');
    if (!searchInput || !dateInput || !container) return;

    var debounceTimer = null;
    var controller = null;
    var requestId = 0;
    var currentPage = 1;

    function load(page) {
        var id = ++requestId;
        if (controller) controller.abort();
        controller = new AbortController();
        loadingEl.hidden = false;
        container.style.opacity = '0.65';

        var params = new URLSearchParams({
            search: searchInput.value,
            date: dateInput.value,
            page: page || 1
        });

        fetch(DATA_URL + '?' + params.toString(), {
            signal: controller.signal,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (id !== requestId || !data.success) return;
            container.innerHTML = data.html;
            currentPage = data.pagination.current_page;
        })
        .catch(function (err) {
            if (err && err.name === 'AbortError') return;
            console.error('QR live search failed:', err);
        })
        .finally(function () {
            if (id === requestId) {
                loadingEl.hidden = true;
                container.style.opacity = '1';
            }
        });
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            load(1);
            searchInput.focus();
        }, 350);
    });

    dateInput.addEventListener('change', function () { load(1); });

    container.addEventListener('click', function (e) {
        var pageBtn = e.target.closest('.qr-page');
        if (!pageBtn || pageBtn.disabled) return;
        load(parseInt(pageBtn.dataset.page, 10));
    });
})();
</script>
@endpush

<style>
    .qr-loading { font-size: 12px; color: #6b7280; align-self: center; white-space: nowrap; }
    .qr-loading[hidden] { display: none !important; }
    .qr-pagination .page-ellipsis { color: #6b7280; padding: 0 4px; }
    .readonly-field {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #f9fafb;
        color: #111827;
        font-weight: 600;
        font-size: 14px;
    }
    .empty-qr-preview {
        min-height: 320px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #6b7280;
        border: 1px dashed #d1d5db;
        border-radius: 14px;
        padding: 30px;
        background: #ffffff;
    }
    .empty-qr-preview h3 {
        color: #111827;
        margin-top: 12px;
        margin-bottom: 8px;
    }
    .empty-icon {
        font-size: 42px;
        color: #9ca3af;
    }
</style>
