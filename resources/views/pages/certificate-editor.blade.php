@extends('layouts.admin')

@section('title', 'EcoCollect - Design Certificate')
@section('page-title', 'Design Certificate')


@section('content')
<a href="{{ route('admin.certificate') }}" class="back-link" style="margin-bottom:16px;display:inline-block;">← Back to Certificate Award</a>

<form id="certificateForm" method="POST"
      action="{{ isset($award) ? route('admin.certificate.update-canvas', $award->id) : route('admin.certificate.save-canvas') }}"
      enctype="multipart/form-data">
    @csrf
    @if(isset($award)) @method('PUT') @endif

    <div class="card" style="margin-bottom:20px;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:15px;font-weight:600;">{{ isset($award) ? 'Edit Certificate' : 'Design Certificate' }}</span>
            <span style="font-size:12px;color:var(--text-light);">Upload a template, select a student, add text, and customize</span>
        </div>
        <div class="card-body" style="padding:20px;">
            <div class="form-group">
                <label>Upload Certificate Template <span style="color:var(--red);">*</span></label>
                <input type="file" name="template_file" id="template_file"
                       accept=".jpg,.jpeg,.png" {{ isset($award) ? '' : 'required' }}
                       style="width:100%;padding:7px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;"
                       aria-label="Upload template file">
                @if(isset($award) && $award->template_file)
                <div style="font-size:11px;color:var(--text-light);margin-top:4px;">Leave empty to keep existing template: <strong>{{ basename($award->template_file) }}</strong></div>
                @else
                <div style="font-size:11px;color:var(--text-light);margin-top:4px;">Accepted: JPG, JPEG, PNG (max 10MB)</div>
                @endif
            </div>

            <div class="form-group" style="margin-top:12px;">
                <label for="studentSearch">Select Student</label>
                <input type="hidden" name="student_id" id="student_id"
                       value="{{ old('student_id', isset($award) ? ($award->student_id ?? '') : '') }}">
                <div class="student-autocomplete">
                    <input type="text" id="studentSearch" name="student_search_display"
                           placeholder="Search student by name or LRN..."
                           autocomplete="off" role="combobox" aria-expanded="false" aria-controls="studentResults"
                           value="{{ old('student_search_display', isset($award) && $award->student ? $award->student->full_name : '') }}"
                           style="width:100%;padding:9px 34px 9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                    <button type="button" id="clearStudentBtn" class="student-clear-btn" title="Clear selected student" hidden aria-label="Clear selected student">×</button>
                    <div id="studentResults" class="student-results" role="listbox" hidden></div>
                </div>
                <div id="studentSelectedInfo" class="student-selected-info" style="{{ isset($award) && $award->student ? '' : 'display:none;' }}">
                    @if(isset($award) && $award->student)
                    <span>Selected: <strong>{{ $award->student->full_name }}</strong> — {{ $award->student->lrn }} · {{ $award->student->grade_level ?: 'N/A' }}</span>
                    @else
                    <span></span>
                    @endif
                </div>
            </div>

            <div class="form-group" style="margin-top:12px;">
                <label>Certificate Title (optional)</label>
                <input type="text" name="award_title" placeholder="e.g. Certificate of Recognition"
                       value="{{ old('award_title', isset($award) ? $award->award_title : '') }}"
                       style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;"
                       aria-label="Certificate title">
            </div>

            <div class="form-group" style="margin-top:12px;">
                <label>Description (optional)</label>
                <textarea name="award_description" placeholder="Brief description of this certificate..."
                          style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;resize:vertical;min-height:60px;"
                          aria-label="Description">{{ old('award_description', isset($award) ? $award->award_description : '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span style="font-size:15px;font-weight:600;">Certificate Design Area</span>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <button type="button" id="addTextBtn" class="btn btn-primary btn-sm">+ Add Text</button>
                <div style="border-left:1px solid var(--border);height:20px;"></div>
                <label style="font-size:12px;font-weight:500;">Size:</label>
                <input type="number" id="fontSizeControl" value="36" min="8" max="200" aria-label="Font size"
                       style="width:60px;padding:4px 6px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:12px;outline:none;">
                <label style="font-size:12px;font-weight:500;">Color:</label>
                <input type="color" id="fontColorControl" value="#000000" aria-label="Font color"
                       style="width:32px;height:28px;padding:1px;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;background:none;">
                <button type="button" id="boldToggle" class="btn btn-sm"
                        style="font-weight:700;border:1px solid var(--border);padding:4px 10px;font-size:12px;"
                        aria-label="Toggle bold"><strong>B</strong></button>
                <button type="button" id="deleteTextBtn" class="btn btn-sm"
                        style="background:var(--red);color:#fff;padding:4px 10px;font-size:12px;">Delete</button>
            </div>
        </div>
        <div class="card-body" style="padding:20px;">
            <div id="certificateCanvas" class="certificate-canvas {{ isset($award) && $award->template_file ? 'has-template' : '' }}">
                <img id="certificateBg" class="certificate-bg" src="{{ isset($award) && $award->template_file ? asset('storage/' . $award->template_file) : '' }}" alt="Certificate Template" style="{{ isset($award) && $award->template_file ? 'display:block;' : 'display:none;' }}">
                <div id="emptyTemplateMessage" class="empty-template-message" style="{{ isset($award) && $award->template_file ? 'display:none;' : '' }}">
                    Upload a certificate template to start designing.
                </div>
            </div>
            <input type="hidden" name="canvas_data" id="canvas_data" value="">

            <div class="student-name-panel" id="studentNamePanel" style="{{ isset($award) && $award->student ? '' : 'opacity:0.55;' }}">
                <div class="student-name-panel-title">Student Name Box <span class="student-name-badge">Auto</span></div>
                <div class="student-name-panel-grid">
                    <label class="snp-field">
                        <span>Font Family</span>
                        <select id="nameFontFamily" class="snp-input" disabled>
                            <option value="Georgia">Georgia</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Arial">Arial</option>
                            <option value="Verdana">Verdana</option>
                            <option value="Trebuchet MS">Trebuchet MS</option>
                            <option value="Courier New">Courier New</option>
                        </select>
                    </label>
                    <label class="snp-field">
                        <span>Preferred Font Size</span>
                        <input type="number" id="nameFontSizePref" class="snp-input" min="12" max="120" value="40" disabled>
                    </label>
                    <label class="snp-field">
                        <span>Minimum Font Size</span>
                        <input type="number" id="nameFontSizeMin" class="snp-input" min="8" max="80" value="16" disabled>
                    </label>
                    <label class="snp-field">
                        <span>Font Color</span>
                        <input type="color" id="nameFontColor" class="snp-input snp-color" value="#1F2937" disabled>
                    </label>
                    <label class="snp-field">
                        <span>Bold</span>
                        <button type="button" id="nameFontBoldBtn" class="snp-input snp-bold" disabled><strong>B</strong></button>
                    </label>
                    <label class="snp-field snp-wide">
                        <span>Text Box Width: <strong id="nameBoxWidthVal">70%</strong></span>
                        <input type="range" id="nameBoxWidth" min="20" max="95" step="1" value="70" disabled>
                    </label>
                </div>
                <div class="student-name-panel-hint">The selected student's name is inserted automatically. Drag the name box on the certificate to reposition it — long names shrink to fit.</div>
                <div id="studentNameWarning" class="student-name-warning" hidden></div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;margin-bottom:24px;">
        <button type="submit" class="btn btn-success" style="padding:10px 32px;">Save Certificate</button>
        <button type="button" id="previewPrintBtn" class="btn btn-sm" style="background:#6B7280;color:#fff;padding:10px 24px;">Print Preview</button>
    </div>
</form>
@endsection

@push('scripts')
<style>
.certificate-canvas {
    position: relative;
    width: 100%;
    max-width: 1123px;
    aspect-ratio: 297 / 210;
    margin: 20px auto;
    background: #ffffff;
    border: 2px dashed #d1d5db;
    overflow: hidden;
}
.certificate-canvas.has-template {
    border: 1px solid #d1d5db;
}
.certificate-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    z-index: 1;
    pointer-events: none;
}
.empty-template-message {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-weight: 600;
    z-index: 0;
}
.certificate-canvas.has-template .empty-template-message {
    display: none;
}
.certificate-text-box {
    position: absolute;
    z-index: 5;
    transform: translate(-50%, -50%);
    cursor: move;
    padding: 4px 8px;
    min-width: 80px;
    text-align: center;
    white-space: nowrap;
    outline: none;
    border: 1px solid transparent;
    line-height: 1.2;
    word-break: break-word;
    user-select: none;
}
.certificate-text-box:hover {
    border-color: rgba(0,174,239,0.3);
}
.certificate-text-box.selected {
    outline: 2px dashed #22c55e;
    background: rgba(255,255,255,0.25);
}
.btn-sm {
    padding: 6px 14px !important;
    font-size: 12px !important;
}
.student-autocomplete {
    position: relative;
}
.student-results {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    max-height: 260px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    box-shadow: 0 10px 26px rgba(0,0,0,0.12);
    z-index: 1200;
}
.student-result-item {
    padding: 9px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.student-result-item:last-child {
    border-bottom: none;
}
.student-result-item:hover,
.student-result-item.active {
    background: #f0f9ff;
}
.student-result-item .sr-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
}
.student-result-item .sr-meta {
    font-size: 11px;
    color: var(--text-light);
    white-space: nowrap;
}
.student-results-empty {
    padding: 10px 12px;
    font-size: 12px;
    color: var(--text-light);
}
.student-clear-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: #e5e7eb;
    color: #374151;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    line-height: 1;
    font-size: 14px;
    cursor: pointer;
    z-index: 2;
    padding: 0;
}
.student-clear-btn:hover {
    background: #d1d5db;
}
.student-selected-info {
    margin-top: 6px;
    font-size: 12px;
    color: var(--text-light);
}
.certificate-student-name-box {
    position: absolute;
    z-index: 6;
    transform: translate(-50%, -50%);
    left: 50%;
    top: 45%;
    width: 70%;
    text-align: center;
    white-space: nowrap;
    line-height: 1.2;
    padding: 4px 8px;
    cursor: move;
    user-select: none;
    border: 2px dashed rgba(37, 99, 235, 0.75);
    background: rgba(255, 255, 255, 0.15);
    overflow: visible;
    touch-action: none;
}
.certificate-student-name-box.selected {
    border-color: #2563eb;
    background: rgba(219, 234, 254, 0.35);
}
.certificate-student-name-box.name-wrap {
    white-space: normal;
    word-break: break-word;
}
.student-name-panel {
    margin-top: 18px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    background: #FAFAFA;
}
.student-name-panel-title {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.student-name-badge {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 999px;
    padding: 2px 8px;
}
.student-name-panel-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px 14px;
    align-items: end;
}
.snp-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 11px;
    color: var(--text-light);
    font-weight: 500;
}
.snp-field.snp-wide {
    grid-column: 1 / -1;
}
.snp-input {
    padding: 6px 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 12px;
    outline: none;
    background: #fff;
    color: var(--text);
}
.snp-input:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.snp-color {
    height: 32px;
    padding: 2px;
    cursor: pointer;
}
.snp-bold {
    cursor: pointer;
    width: 40px;
    text-align: center;
}
.snp-bold.active {
    background: #1f2937;
    color: #fff;
    border-color: #1f2937;
}
input[type="range"].snp-input {
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
}
.student-name-warning {
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: var(--radius-sm);
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 12px;
    font-weight: 500;
}
@media (max-width: 768px) {
    .student-name-panel-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
<script>
(function () {
    'use strict';

    // Reference canvas width in px (A4 landscape @ 96dpi). All student-name
    // font sizes are measured/stored at this width so the editor preview,
    // print preview (297mm ≈ 1123px) and saved print page always agree.
    var REF_W = 1123;
    var NAME_DEFAULTS = { x: 50, y: 45, width: 70, fontSizePref: 40, fontSizeMin: 16, fontFamily: 'Georgia', color: '#1F2937', fontWeight: '700' };

    var selectedTextBox = null;
    var isDragging = false;
    var dragElement = null;

    var selectedStudentId = null;
    var selectedStudentName = '';
    var studentNameBox = null;
    var nameDragging = false;
    var fittedRefSize = NAME_DEFAULTS.fontSizePref;
    var nameConfig = {
        x: NAME_DEFAULTS.x,
        y: NAME_DEFAULTS.y,
        width: NAME_DEFAULTS.width,
        fontSizePref: NAME_DEFAULTS.fontSizePref,
        fontSizeMin: NAME_DEFAULTS.fontSizeMin,
        fontFamily: NAME_DEFAULTS.fontFamily,
        color: NAME_DEFAULTS.color,
        fontWeight: NAME_DEFAULTS.fontWeight
    };

    var measureCanvas = document.createElement('canvas');
    var measureCtx = measureCanvas.getContext('2d');

    var existingAwardStudentId = {{ isset($award) && $award->student_id ? (int) $award->student_id : 'null' }};
    var existingAwardStudentName = @json(isset($award) && $award->student ? $award->student->full_name : '');
    var existingStudentNameEntry = null;

    function $(id) { return document.getElementById(id); }

    function nameScale() {
        var canvas = $('certificateCanvas');
        var w = canvas ? canvas.clientWidth : 0;
        return w > 0 ? w / REF_W : 1;
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if(isset($award) && $award->template_file)
        @php
            $rawData = $award->canvas_data;
            $canvasArray = is_array($rawData) ? $rawData : (is_string($rawData) ? json_decode($rawData, true) : []);
        @endphp
        var existingCanvasData = @json($canvasArray ?: []);
        if (Array.isArray(existingCanvasData) && existingCanvasData.length > 0) {
            existingCanvasData.forEach(function (box) {
                if (box && box.type === 'student_name') {
                    existingStudentNameEntry = box;
                    return;
                }
                addTextBox(box.text || 'Text', {
                    x: box.x,
                    y: box.y,
                    fontSize: box.fontSize,
                    color: box.color,
                    fontWeight: box.fontWeight,
                    textAlign: box.textAlign || 'center'
                });
            });
        }
        @endif

        if (existingAwardStudentId) {
            selectedStudentId = existingAwardStudentId;
            selectedStudentName = existingAwardStudentName || '';
            $('student_id').value = selectedStudentId;
            $('clearStudentBtn').hidden = false;
            if (selectedStudentName) {
                $('studentSearch').value = selectedStudentName;
            }
        }

        if (selectedStudentId) {
            if (existingStudentNameEntry) {
                nameConfig.x = parseFloat(existingStudentNameEntry.x) || NAME_DEFAULTS.x;
                nameConfig.y = parseFloat(existingStudentNameEntry.y) || NAME_DEFAULTS.y;
                nameConfig.width = parseFloat(existingStudentNameEntry.width) || NAME_DEFAULTS.width;
                nameConfig.fontSizePref = parseInt(existingStudentNameEntry.fontSizePref, 10) || NAME_DEFAULTS.fontSizePref;
                nameConfig.fontSizeMin = parseInt(existingStudentNameEntry.fontSizeMin, 10) || NAME_DEFAULTS.fontSizeMin;
                nameConfig.fontFamily = existingStudentNameEntry.fontFamily || NAME_DEFAULTS.fontFamily;
                nameConfig.color = existingStudentNameEntry.color || NAME_DEFAULTS.color;
                nameConfig.fontWeight = existingStudentNameEntry.fontWeight || NAME_DEFAULTS.fontWeight;
                selectedStudentName = existingStudentNameEntry.text || selectedStudentName;
            }
            syncNamePanelControls();
            ensureStudentNameBox(selectedStudentName || existingAwardStudentName || 'Student Name');
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () { fitStudentName(); });
        }
        fitStudentName();
    });

    // --- TEMPLATE FILE UPLOAD & PREVIEW ---
    document.getElementById('template_file').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;

        var img = document.getElementById('certificateBg');
        var canvas = document.getElementById('certificateCanvas');
        var emptyMessage = document.getElementById('emptyTemplateMessage');

        var reader = new FileReader();
        reader.onload = function (e) {
            img.src = e.target.result;
            img.style.display = 'block';
            canvas.classList.add('has-template');
            if (emptyMessage) {
                emptyMessage.style.display = 'none';
            }
            fitStudentName();
        };
        reader.readAsDataURL(file);
    });

    // --- STUDENT SEARCH AUTOCOMPLETE ---
    var searchTimer = null;
    var activeResultIndex = -1;
    var currentResults = [];

    function closeResults() {
        var box = $('studentResults');
        box.hidden = true;
        box.innerHTML = '';
        activeResultIndex = -1;
        currentResults = [];
        $('studentSearch').setAttribute('aria-expanded', 'false');
    }

    function openResults(html) {
        var box = $('studentResults');
        box.innerHTML = html;
        box.hidden = false;
        $('studentSearch').setAttribute('aria-expanded', 'true');
    }

    function renderResults(students) {
        if (!students.length) {
            openResults('<div class="student-results-empty">No matching students found.</div>');
            return;
        }
        var html = students.map(function (s, i) {
            var grade = s.grade_level ? s.grade_level : 'N/A';
            return '<div class="student-result-item" role="option" data-index="' + i + '">' +
                '<span class="sr-name">' + escapeHtml(s.name) + '</span>' +
                '<span class="sr-meta">' + escapeHtml(s.lrn || '') + ' · ' + escapeHtml(grade) + '</span>' +
                '</div>';
        }).join('');
        openResults(html);
        activeResultIndex = -1;
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function selectStudent(student) {
        selectedStudentId = student.id;
        selectedStudentName = student.name;
        $('student_id').value = student.id;
        $('studentSearch').value = student.name;
        $('clearStudentBtn').hidden = false;
        var info = $('studentSelectedInfo');
        info.style.display = 'block';
        info.innerHTML = '<span>Selected: <strong>' + escapeHtml(student.name) + '</strong> — ' +
            escapeHtml(student.lrn || '') + ' · ' + escapeHtml(student.grade_level || 'N/A') + '</span>';
        closeResults();
        $('studentSearch').blur();
        ensureStudentNameBox(student.name);
    }

    $('studentSearch').addEventListener('input', function () {
        var q = this.value.trim();
        clearTimeout(searchTimer);

        if (!q) {
            closeResults();
            return;
        }
        searchTimer = setTimeout(function () {
            fetch('{{ route("admin.students.search") }}?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (students) {
                    currentResults = Array.isArray(students) ? students : [];
                    renderResults(currentResults);
                })
                .catch(function () {
                    openResults('<div class="student-results-empty">Search failed. Try again.</div>');
                });
        }, 250);
    });

    $('studentSearch').addEventListener('keydown', function (e) {
        var items = $('studentResults').querySelectorAll('.student-result-item');
        if (e.key === 'Escape') {
            closeResults();
            return;
        }
        if (!items.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            activeResultIndex += (e.key === 'ArrowDown' ? 1 : -1);
            if (activeResultIndex < 0) activeResultIndex = items.length - 1;
            if (activeResultIndex >= items.length) activeResultIndex = 0;
            items.forEach(function (el) { el.classList.remove('active'); });
            items[activeResultIndex].classList.add('active');
            items[activeResultIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && activeResultIndex >= 0) {
            e.preventDefault();
            var idx = parseInt(items[activeResultIndex].getAttribute('data-index'), 10);
            if (currentResults[idx]) selectStudent(currentResults[idx]);
        }
    });

    $('studentResults').addEventListener('pointerdown', function (e) {
        var item = e.target.closest('.student-result-item');
        if (!item) return;
        e.preventDefault();
        var idx = parseInt(item.getAttribute('data-index'), 10);
        if (currentResults[idx]) selectStudent(currentResults[idx]);
    });

    $('studentSearch').addEventListener('blur', function () {
        setTimeout(closeResults, 180);
    });

    $('clearStudentBtn').addEventListener('click', function () {
        selectedStudentId = null;
        selectedStudentName = '';
        $('student_id').value = '';
        $('studentSearch').value = '';
        $('clearStudentBtn').hidden = true;
        var info = $('studentSelectedInfo');
        info.style.display = 'none';
        info.innerHTML = '<span></span>';
        removeStudentNameBox();
        closeResults();
    });

    // --- ADD TEXT (manual boxes) ---
    document.getElementById('addTextBtn').addEventListener('click', function () {
        addTextBox('Enter text', { x: 50, y: 50, fontSize: 36, color: '#000000', fontWeight: 'normal' });
    });

    function addTextBox(text, options) {
        options = options || {};
        var canvas = document.getElementById('certificateCanvas');
        var box = document.createElement('div');
        box.className = 'certificate-text-box';
        box.contentEditable = true;
        box.innerText = text;
        box.style.left = (options.x || 50) + '%';
        box.style.top = (options.y || 50) + '%';
        box.style.fontSize = (options.fontSize || 36) + 'px';
        box.style.color = options.color || '#000000';
        box.style.fontWeight = options.fontWeight || 'normal';
        box.style.textAlign = options.textAlign || 'center';
        canvas.appendChild(box);
        selectTextBox(box);
        makeDraggable(box);
        box.addEventListener('click', function (e) {
            e.stopPropagation();
            selectTextBox(box);
        });
        box.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    }

    // --- SELECT TEXT BOX ---
    function selectTextBox(box) {
        document.querySelectorAll('.certificate-text-box').forEach(function (el) {
            el.classList.remove('selected');
        });
        if (studentNameBox) studentNameBox.classList.remove('selected');
        box.classList.add('selected');
        selectedTextBox = box;

        var size = parseInt(box.style.fontSize, 10) || 36;
        document.getElementById('fontSizeControl').value = size;
        document.getElementById('fontColorControl').value = rgbToHex(box.style.color) || '#000000';
    }

    function rgbToHex(rgb) {
        if (!rgb || rgb.startsWith('#')) return rgb;
        var match = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (!match) return '#000000';
        return '#' + [match[1], match[2], match[3]].map(function (c) {
            return ('0' + parseInt(c, 10).toString(16)).slice(-2);
        }).join('');
    }

    // --- DRAGGING (manual boxes) ---
    function makeDraggable(element) {
        element.addEventListener('mousedown', function (e) {
            if (e.target.closest('.certificate-text-box') !== element) return;
            e.preventDefault();
            selectTextBox(element);
            isDragging = true;
            dragElement = element;
        });
    }

    document.addEventListener('mousemove', function (e) {
        if (!isDragging || !dragElement) return;

        var canvas = document.getElementById('certificateCanvas');
        var rect = canvas.getBoundingClientRect();

        var x = ((e.clientX - rect.left) / rect.width) * 100;
        var y = ((e.clientY - rect.top) / rect.height) * 100;

        x = Math.max(0, Math.min(100, x));
        y = Math.max(0, Math.min(100, y));

        dragElement.style.left = x + '%';
        dragElement.style.top = y + '%';
    });

    document.addEventListener('mouseup', function () {
        isDragging = false;
        dragElement = null;
    });

    // Click on canvas background deselects
    document.getElementById('certificateCanvas').addEventListener('click', function (e) {
        if (!e.target.closest('.certificate-text-box') && !e.target.closest('.certificate-student-name-box')) {
            document.querySelectorAll('.certificate-text-box.selected').forEach(function (el) {
                el.classList.remove('selected');
            });
            if (studentNameBox) studentNameBox.classList.remove('selected');
            selectedTextBox = null;
        }
    });

    // --- FONT CONTROLS (manual boxes) ---
    document.getElementById('fontSizeControl').addEventListener('input', function () {
        if (selectedTextBox) {
            selectedTextBox.style.fontSize = this.value + 'px';
        }
    });

    document.getElementById('fontColorControl').addEventListener('input', function () {
        if (selectedTextBox) {
            selectedTextBox.style.color = this.value;
        }
    });

    document.getElementById('boldToggle').addEventListener('click', function () {
        if (selectedTextBox) {
            selectedTextBox.style.fontWeight =
                selectedTextBox.style.fontWeight === 'bold' || selectedTextBox.style.fontWeight === '700' ? 'normal' : 'bold';
        }
    });

    document.getElementById('deleteTextBtn').addEventListener('click', function () {
        if (studentNameBox && studentNameBox.classList.contains('selected')) {
            alert('The Student Name box is automatic and cannot be deleted. Clear the selected student instead.');
            return;
        }
        if (selectedTextBox) {
            selectedTextBox.remove();
            selectedTextBox = null;
        } else {
            alert('Click on a text box to select it first.');
        }
    });

    // ================= STUDENT NAME BOX =================

    function setNameControlsEnabled(enabled) {
        ['nameFontFamily', 'nameFontSizePref', 'nameFontSizeMin', 'nameFontColor', 'nameFontBoldBtn', 'nameBoxWidth'].forEach(function (id) {
            var el = $(id);
            if (el) el.disabled = !enabled;
        });
        $('studentNamePanel').style.opacity = enabled ? '1' : '0.55';
    }

    function syncNamePanelControls() {
        $('nameFontFamily').value = nameConfig.fontFamily;
        $('nameFontSizePref').value = nameConfig.fontSizePref;
        $('nameFontSizeMin').value = nameConfig.fontSizeMin;
        $('nameFontColor').value = nameConfig.color;
        $('nameBoxWidth').value = nameConfig.width;
        $('nameBoxWidthVal').textContent = nameConfig.width + '%';
        $('nameFontBoldBtn').classList.toggle('active', nameConfig.fontWeight === '700' || nameConfig.fontWeight === 'bold');
    }

    function showNameWarning(message) {
        var el = $('studentNameWarning');
        if (message) {
            el.textContent = message;
            el.hidden = false;
        } else {
            el.textContent = '';
            el.hidden = true;
        }
    }

    function removeStudentNameBox() {
        if (studentNameBox) {
            studentNameBox.remove();
            studentNameBox = null;
        }
        fittedRefSize = nameConfig.fontSizePref;
        setNameControlsEnabled(false);
        showNameWarning(null);
    }

    function ensureStudentNameBox(text) {
        if (!selectedStudentId) return;
        var canvas = $('certificateCanvas');
        if (!studentNameBox) {
            var box = document.createElement('div');
            box.className = 'certificate-student-name-box';
            box.id = 'studentNameBox';
            box.setAttribute('data-role', 'student_name');
            canvas.appendChild(box);
            studentNameBox = box;
            wireNameBoxDrag(box);
        }
        studentNameBox.textContent = text || selectedStudentName || '';
        setNameControlsEnabled(true);
        fitStudentName();
    }

    function fitStudentName() {
        if (!studentNameBox) return;
        var text = (studentNameBox.textContent || '').trim();
        var avail = (nameConfig.width / 100) * REF_W - 24;
        var pref = Math.round(nameConfig.fontSizePref);
        var min = Math.round(nameConfig.fontSizeMin);
        var weight = nameConfig.fontWeight === 'normal' ? '400' : '700';
        var fitted = min;
        var s;
        for (s = pref; s >= min; s--) {
            measureCtx.font = weight + ' ' + s + 'px "' + nameConfig.fontFamily + '"';
            if (measureCtx.measureText(text).width <= avail) {
                fitted = s;
                break;
            }
        }
        fittedRefSize = fitted;
        measureCtx.font = weight + ' ' + min + 'px "' + nameConfig.fontFamily + '"';
        var fitsAtMin = measureCtx.measureText(text).width <= avail;
        studentNameBox.classList.toggle('name-wrap', !fitsAtMin);
        if (!fitsAtMin && text) {
            showNameWarning('The name "' + text + '" is too long for the name box at the minimum font size. Increase the Text Box Width so the full name fits.');
        } else {
            showNameWarning(null);
        }
        applyNameBoxStyle();
    }

    function applyNameBoxStyle() {
        if (!studentNameBox) return;
        var half = nameConfig.width / 2;
        nameConfig.x = Math.max(half, Math.min(100 - half, nameConfig.x));
        nameConfig.y = Math.max(2, Math.min(98, nameConfig.y));

        studentNameBox.style.left = nameConfig.x + '%';
        studentNameBox.style.top = nameConfig.y + '%';
        studentNameBox.style.width = nameConfig.width + '%';
        studentNameBox.style.fontFamily = '"' + nameConfig.fontFamily + '", serif';
        studentNameBox.style.color = nameConfig.color;
        studentNameBox.style.fontWeight = nameConfig.fontWeight;
        studentNameBox.style.fontSize = (fittedRefSize * nameScale()) + 'px';
        studentNameBox.style.textAlign = 'center';
    }

    function wireNameBoxDrag(box) {
        box.addEventListener('pointerdown', function (e) {
            e.preventDefault();
            e.stopPropagation();
            document.querySelectorAll('.certificate-text-box.selected').forEach(function (el) {
                el.classList.remove('selected');
            });
            selectedTextBox = null;
            box.classList.add('selected');
            nameDragging = true;
            try { box.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
        });
        box.addEventListener('pointermove', function (e) {
            if (!nameDragging) return;
            var canvas = $('certificateCanvas');
            var rect = canvas.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width) * 100;
            var y = ((e.clientY - rect.top) / rect.height) * 100;
            nameConfig.x = x;
            nameConfig.y = y;
            applyNameBoxStyle();
        });
        box.addEventListener('pointerup', function () { nameDragging = false; });
        box.addEventListener('pointercancel', function () { nameDragging = false; });
    }

    // Keep the name font scaled correctly when the editor canvas resizes
    if ('ResizeObserver' in window) {
        var resizeObserver = new ResizeObserver(function () {
            if (studentNameBox) applyNameBoxStyle();
        });
        document.addEventListener('DOMContentLoaded', function () {
            var canvas = $('certificateCanvas');
            if (canvas) resizeObserver.observe(canvas);
        });
    }

    // --- STUDENT NAME CONFIG CONTROLS ---
    $('nameFontFamily').addEventListener('change', function () {
        nameConfig.fontFamily = this.value;
        fitStudentName();
    });
    $('nameFontSizePref').addEventListener('input', function () {
        nameConfig.fontSizePref = parseInt(this.value, 10) || NAME_DEFAULTS.fontSizePref;
        if (nameConfig.fontSizeMin > nameConfig.fontSizePref) {
            nameConfig.fontSizeMin = nameConfig.fontSizePref;
            $('nameFontSizeMin').value = nameConfig.fontSizeMin;
        }
        fitStudentName();
    });
    $('nameFontSizeMin').addEventListener('input', function () {
        nameConfig.fontSizeMin = parseInt(this.value, 10) || NAME_DEFAULTS.fontSizeMin;
        if (nameConfig.fontSizeMin > nameConfig.fontSizePref) {
            nameConfig.fontSizeMin = nameConfig.fontSizePref;
            $('nameFontSizeMin').value = nameConfig.fontSizeMin;
        }
        fitStudentName();
    });
    $('nameFontColor').addEventListener('input', function () {
        nameConfig.color = this.value;
        applyNameBoxStyle();
    });
    $('nameFontBoldBtn').addEventListener('click', function () {
        nameConfig.fontWeight = (nameConfig.fontWeight === '700' || nameConfig.fontWeight === 'bold') ? '400' : '700';
        this.classList.toggle('active', nameConfig.fontWeight === '700');
        fitStudentName();
    });
    $('nameBoxWidth').addEventListener('input', function () {
        nameConfig.width = parseInt(this.value, 10) || NAME_DEFAULTS.width;
        $('nameBoxWidthVal').textContent = nameConfig.width + '%';
        fitStudentName();
    });

    // --- COLLECT CANVAS DATA (shared by save + print preview) ---
    function collectCanvasData() {
        var canvas = document.getElementById('certificateCanvas');
        var boxes = canvas.querySelectorAll('.certificate-text-box');

        var data = Array.from(boxes).map(function (box) {
            return {
                text: box.innerText.trim(),
                x: parseFloat(box.style.left) || 50,
                y: parseFloat(box.style.top) || 50,
                fontSize: parseInt(box.style.fontSize, 10) || 36,
                color: box.style.color || '#000000',
                fontWeight: box.style.fontWeight || 'normal',
                textAlign: box.style.textAlign || 'center'
            };
        });

        if (studentNameBox && selectedStudentId) {
            data.push({
                type: 'student_name',
                student_id: selectedStudentId,
                text: (studentNameBox.textContent || '').trim(),
                x: nameConfig.x,
                y: nameConfig.y,
                width: nameConfig.width,
                fontSize: fittedRefSize,
                fontSizePref: nameConfig.fontSizePref,
                fontSizeMin: nameConfig.fontSizeMin,
                fontFamily: nameConfig.fontFamily,
                color: nameConfig.color,
                fontWeight: nameConfig.fontWeight,
                textAlign: 'center'
            });
        }

        return data;
    }

    // --- FORM SUBMIT ---
    document.getElementById('certificateForm').addEventListener('submit', function (event) {
        var data = collectCanvasData();

        $('student_id').value = selectedStudentId || '';
        $('canvas_data').value = JSON.stringify(data);

        var hasManualText = data.some(function (d) { return d.type !== 'student_name'; });
        if (data.length === 0 || (!hasManualText && !selectedStudentId)) {
            if (!confirm('No text added. Save certificate without text?')) {
                event.preventDefault();
                return;
            }
        }
    });

    // --- PRINT PREVIEW ---
    document.getElementById('previewPrintBtn').addEventListener('click', function () {
        if (!document.getElementById('certificateBg').src && !document.getElementById('template_file').files[0]) {
            alert('Please upload a template first.');
            return;
        }

        var data = collectCanvasData();
        var templateUrl = document.getElementById('certificateBg').src;
        var printWindow = window.open('', '_blank');

        var textHtml = '';
        var nameHtml = '';
        data.forEach(function (box) {
            if (box.type === 'student_name') {
                nameHtml = '<div class="print-student-name" data-width="' + box.width + '" data-pref="' + box.fontSizePref +
                    '" data-min="' + box.fontSizeMin + '" data-family="' + escapeHtml(box.fontFamily) +
                    '" data-bold="' + (box.fontWeight === '400' || box.fontWeight === 'normal' ? '0' : '1') +
                    '" style="left:' + box.x + '%;top:' + box.y + '%;width:' + box.width + '%;font-family:\'' + box.fontFamily + '\',serif;color:' + box.color +
                    ';font-weight:' + box.fontWeight + ';font-size:' + box.fontSize + 'px;">' + escapeHtml(box.text) + '</div>';
            } else {
                textHtml += '<div class="print-text-box" style="left:' + box.x + '%;top:' + box.y + '%;font-size:' + box.fontSize + 'px;color:' + box.color + ';font-weight:' + box.fontWeight + ';text-align:' + (box.textAlign || 'center') + ';">' + escapeHtml(box.text) + '</div>';
            }
        });

        var fitScript = 'window.addEventListener("load",function(){' +
            'function fit(){' +
            'var els=document.querySelectorAll(".print-student-name");' +
            'if(!els.length)return;' +
            'var mc=document.createElement("canvas").getContext("2d");' +
            'for(var i=0;i<els.length;i++){' +
            'var el=els[i];' +
            'var w=parseFloat(el.getAttribute("data-width"))||70;' +
            'var pref=parseFloat(el.getAttribute("data-pref"))||40;' +
            'var min=parseFloat(el.getAttribute("data-min"))||16;' +
            'var fam=el.getAttribute("data-family")||"Georgia";' +
            'var weight=el.getAttribute("data-bold")==="1"?"700":"400";' +
            'var avail=(w/100)*1123-24;' +
            'var text=el.textContent.trim();' +
            'var fitted=min;' +
            'for(var s=Math.round(pref);s>=min;s--){' +
            'mc.font=weight+" "+s+"px \\""+fam+"\\"";' +
            'if(mc.measureText(text).width<=avail){fitted=s;break;}' +
            '}' +
            'mc.font=weight+" "+min+"px \\""+fam+"\\"";' +
            'var fits=mc.measureText(text).width<=avail;' +
            'el.style.fontSize=fitted+"px";' +
            'el.style.whiteSpace=fits?"nowrap":"normal";' +
            'el.style.wordBreak=fits?"normal":"break-word";' +
            '}' +
            '}' +
            'if(document.fonts&&document.fonts.ready){document.fonts.ready.then(fit);}else{fit();}' +
            'setTimeout(fit,300);' +
            'setTimeout(function(){window.print();},600);' +
            '});';

        printWindow.document.write('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Print Preview</title><style>*{margin:0;padding:0;box-sizing:border-box;}@page{size:A4 landscape;margin:0;}body{margin:0;background:#fff;display:flex;justify-content:center;align-items:center;min-height:100vh;}.certificate-canvas{position:relative;width:297mm;height:210mm;overflow:hidden;}.certificate-canvas img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;}.print-text-box{position:absolute;transform:translate(-50%,-50%);white-space:nowrap;line-height:1.2;word-break:break-word;text-align:center;}.print-student-name{position:absolute;transform:translate(-50%,-50%);line-height:1.2;text-align:center;}@media print{body{margin:0;}.certificate-canvas{box-shadow:none;}}</style></head><body><div class="certificate-canvas"><img src="' + templateUrl + '" alt="Certificate template">' + nameHtml + textHtml + '</div><script>' + fitScript + '<\/script></body></html>');
        printWindow.document.close();
    });
})();
</script>
@endpush
