@extends('layouts.admin')

@section('title', 'EcoCollect - Certificate Award')
@section('page-title', 'Certificate Award')
@section('page-subtitle', 'Auto-generate certificates and manage achievement quests')

@section('content')
@php
    $maxQuests = \App\Http\Controllers\AchievementController::MAX_QUESTS;
    $selectedAdviser = old('adviser_id');
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
        <h1 style="font-size:22px;font-weight:700;margin:0;">Certificate Award</h1>
        <p style="margin:4px 0 0;color:var(--text-light);font-size:13px;">Auto-generate certificates and manage achievement quests</p>
    </div>
</div>

<form id="certificateForm" method="POST" action="{{ route('admin.certificate.save-canvas') }}" enctype="multipart/form-data">
    @csrf
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Auto Generated Certificate</span>
                <span class="student-name-badge">Auto</span>
            </div>
            <span style="font-size:12px;color:var(--text-light);">Upload template → Select student → Select teacher → Print Preview</span>
        </div>
        <div class="card-body" style="padding:20px;">
            <div class="auto-cert-grid">

                <div class="auto-cert-controls">
                    <div class="form-group">
                        <label>Upload Certificate Template <span style="color:var(--red);">*</span></label>
                        <input type="file" name="template_file" id="template_file"
                               accept=".jpg,.jpeg,.png" required
                               style="width:100%;padding:7px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;"
                               aria-label="Upload template file">
                        <div style="font-size:11px;color:var(--text-light);margin-top:4px;">Accepted: JPG, JPEG, PNG (max 10MB). The image becomes the certificate background.</div>
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                        <label for="studentSearch">Search and Select Student</label>
                        <input type="hidden" name="student_id" id="student_id" value="{{ old('student_id') }}">
                        <div class="student-autocomplete">
                            <input type="text" id="studentSearch" name="student_search_display"
                                   placeholder="Search student by name or LRN..."
                                   autocomplete="off" role="combobox" aria-expanded="false" aria-controls="studentResults"
                                   value="{{ old('student_search_display') }}"
                                   style="width:100%;padding:9px 34px 9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;">
                            <button type="button" id="clearStudentBtn" class="student-clear-btn" title="Clear selected student" hidden aria-label="Clear selected student">×</button>
                            <div id="studentResults" class="student-results" role="listbox" hidden></div>
                        </div>
                        <div id="studentSelectedInfo" class="student-selected-info" style="display:none;">
                            <span></span>
                        </div>
                        <div style="font-size:11px;color:var(--text-light);margin-top:4px;">Type a first name, last name, full name, or LRN — the student's name is placed on the certificate automatically.</div>
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                        <label for="adviserSelect">Select Teacher / Adviser</label>
                        <select id="adviserSelect" name="adviser_id"
                                style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;"
                                aria-label="Select teacher or adviser">
                            <option value="" disabled {{ $selectedAdviser ? '' : 'selected' }}>Select Teacher / Adviser</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" data-name="{{ $t->name }}" {{ (string) $selectedAdviser === (string) $t->id ? 'selected' : '' }}>
                                    {{ $t->name }} · {{ ucfirst(str_replace('_', ' ', $t->role)) }}{{ $t->position ? ' — ' . $t->position : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size:11px;color:var(--text-light);margin-top:4px;">The adviser name appears on the adviser line of the certificate automatically.</div>
                    </div>

                    <div class="font-settings" id="fontSettings" style="margin-top:16px;">
                        <div class="font-settings-title">Font Settings</div>
                        <label class="fs-field">
                            <span>Font Style</span>
                            <select id="certFontFamily" class="fs-input" aria-label="Certificate name font style">
                                <option value="Arial">Arial</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Georgia" selected>Georgia</option>
                                <option value="Garamond">Garamond</option>
                                <option value="Verdana">Verdana</option>
                            </select>
                        </label>
                        <label class="fs-field">
                            <span>Student Name Size</span>
                            <input type="number" id="studentFontSize" class="fs-input" min="16" max="120" step="1" value="24" aria-label="Student name font size" style="max-width:120px;">
                        </label>
                        <label class="fs-field">
                            <span>Adviser Name Size</span>
                            <input type="number" id="adviserFontSize" class="fs-input" min="10" max="120" step="1" value="18" aria-label="Adviser name font size" style="max-width:120px;">
                        </label>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
                        <button type="button" id="previewPrintBtn" class="btn btn-sm" style="background:#6B7280;color:#fff;padding:10px 24px;">Print Preview</button>
                        <button type="button" id="awardToStudentBtn" class="btn btn-sm" style="background:#16A34A;color:#fff;padding:10px 24px;">Award to Student</button>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
                        <button type="button" id="resetNamePositionsBtn" class="btn btn-sm" style="background:#fff;color:#6B7280;border:1px solid var(--border);padding:10px 18px;" title="Restore the automatic name positions">Reset Name Positions</button>
                    </div>
                    <div id="awardStatus" role="status" aria-live="polite" style="display:none;margin-top:10px;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;"></div>
                </div>

                <div class="auto-cert-preview">
                    <div class="certificate-preview-wrapper">
                        <div id="certificateCanvas" class="certificate-canvas">
                            <img id="certificateBg" class="certificate-bg" src="" alt="Certificate Template" style="display:none;">
                            <div id="certificateStudentName" class="certificate-name-overlay" data-role="student_name"></div>
                            <div id="certificateAdviserName" class="certificate-adviser-overlay" data-role="adviser_name"></div>
                            <div id="emptyTemplateMessage" class="empty-template-message">
                                Upload a certificate template to start.
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="canvas_data" id="canvas_data" value="">

                    <div id="certNameWarning" class="student-name-warning" hidden></div>
                </div>

            </div>
        </div>
    </div>
</form>

<div style="display:flex;align-items:center;justify-content:space-between;margin-top:36px;margin-bottom:12px;">
    <div>
        <h4 style="font-size:15px;font-weight:600;">🎯 Achievement Quests</h4>
        @if(Auth::user()->isTeacher())
            <span style="font-size:12px;color:var(--text-light);">({{ $quests->count() }} of {{ $maxQuests }} quests)</span>
        @else
            <span style="font-size:12px;color:var(--text-light);">({{ $quests->count() }} quests — Monitoring)</span>
        @endif
    </div>
    @if(Auth::user()->isTeacher())
        @if($quests->count() >= $maxQuests)
            <span class="btn btn-sm" style="background:#E5E7EB;color:#6B7280;cursor:not-allowed;" role="button" aria-disabled="true" title="You can only have up to {{ $maxQuests }} achievement quests.">Maximum of {{ $maxQuests }} achievement quests reached</span>
        @else
            <button type="button" class="btn btn-primary btn-sm" onclick="openAddQuestModal()">+ Add Achievement Quest</button>
        @endif
    @endif
</div>
<div class="table-container">
    <div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Quest Title</th>
                <th scope="col">Requirement Type</th>
                <th scope="col">Required Value</th>
                <th scope="col">Description</th>
                <th scope="col">Status</th>
                <th scope="col">Created By</th>
                @if(Auth::user()->isTeacher())
                <th scope="col">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($quests as $i => $q)
            @php
                $isBottleQuest = $q->required_bottles > 0;
                $reqType = $isBottleQuest ? 'Bottles' : 'Points';
                $reqValue = $isBottleQuest ? $q->required_bottles : $q->points_required;
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="font-weight:600;">{{ $q->title }}</td>
                <td>{{ $reqType }}</td>
                <td>{{ number_format($reqValue) }}</td>
                <td style="font-size:12px;color:var(--text-light);">{{ $q->description ?: '—' }}</td>
                <td>
                    @if($q->status === 'Active')
                        <span style="color:#22C55E;font-weight:600;font-size:12px;">Active</span>
                    @else
                        <span style="color:var(--text-light);font-size:12px;">{{ $q->status }}</span>
                    @endif
                </td>
                <td>{{ $q->creator->name ?? 'System' }}</td>
                @if(Auth::user()->isTeacher())
                <td>
                    <div class="action-btns">
                        <button type="button" class="action-icon-btn edit" title="Edit" aria-label="Edit achievement quest" onclick="openQuestEditModal({{ $q->id }}, '{{ addslashes($q->title) }}', '{{ addslashes($q->description ?? '') }}', '{{ $reqType === 'Bottles' ? 'bottles' : 'points' }}', {{ $reqValue }}, '{{ addslashes($q->badge_name ?? '') }}', '{{ $q->status }}')"><x-icon name="pencil" :size="15" /></button>
                        <form method="POST" action="{{ route('achievement-quests.destroy', $q->id) }}" class="inline-action-form" onsubmit="return confirm('Delete this achievement quest?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="action-icon-btn delete" title="Delete" aria-label="Delete achievement quest"><x-icon name="trash-2" :size="15" /></button>
                        </form>
                    </div>
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ Auth::user()->isTeacher() ? 8 : 7 }}" style="text-align:center;color:var(--text-light);padding:30px;">No achievement quests found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if(Auth::user()->isTeacher() && $quests->count() < $maxQuests)
{{-- Add Achievement Quest Modal --}}
<div id="questAddModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:560px;max-width:95%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h4 style="font-size:16px;font-weight:700;">Add Achievement Quest</h4>
            <button type="button" onclick="closeAddQuestModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;" aria-label="Close">✕</button>
        </div>
        <form method="POST" action="{{ route('achievement-quests.store') }}">
            @csrf
            <div class="grid-2col" style="gap:16px;">
                <div class="form-group" style="grid-column:span 2;">
                    <label>Quest Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Quest title">
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label>Description</label>
                    <textarea name="description" rows="3" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Description">{{ old('description') }}</textarea>
                </div>
                <div class="form-group">
                    <label>Requirement Type</label>
                    <select name="requirement_type" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Requirement type">
                        <option value="points" {{ old('requirement_type') === 'points' ? 'selected' : '' }}>Points</option>
                        <option value="bottles" {{ old('requirement_type') === 'bottles' ? 'selected' : '' }}>Bottles</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Required Value</label>
                    <input type="number" name="required_value" value="{{ old('required_value') }}" min="1" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Required value">
                </div>
                <div class="form-group">
                    <label>Badge / Reward Name <span style="color:var(--text-light);">(optional)</span></label>
                    <input type="text" name="badge_name" value="{{ old('badge_name') }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Badge name">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Status">
                        <option value="Active" {{ old('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeAddQuestModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Achievement Quest</button>
            </div>
        </form>
    </div>
</div>
@endif

@if(Auth::user()->isTeacher())
{{-- Edit Achievement Quest Modal --}}
<div id="questEditModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:560px;max-width:95%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h4 style="font-size:16px;font-weight:700;">Edit Achievement Quest</h4>
            <button type="button" onclick="closeQuestEditModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;" aria-label="Close">✕</button>
        </div>
        <form method="POST" action="" id="questEditForm">
            @csrf
            @method('PUT')
            <div class="grid-2col" style="gap:16px;">
                <div class="form-group" style="grid-column:span 2;">
                    <label>Quest Title</label>
                    <input type="text" name="title" id="edit_quest_title" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Quest title">
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label>Description</label>
                    <textarea name="description" id="edit_quest_description" rows="3" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Description"></textarea>
                </div>
                <div class="form-group">
                    <label>Requirement Type</label>
                    <select name="requirement_type" id="edit_requirement_type" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Requirement type">
                        <option value="points">Points</option>
                        <option value="bottles">Bottles</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Required Value</label>
                    <input type="number" name="required_value" id="edit_required_value" min="1" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Required value">
                </div>
                <div class="form-group">
                    <label>Badge / Reward Name <span style="color:var(--text-light);">(optional)</span></label>
                    <input type="text" name="badge_name" id="edit_quest_badge" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Badge name">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_quest_status" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" aria-label="Status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeQuestEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<style>
.auto-cert-grid {
    display: grid;
    grid-template-columns: 340px minmax(0, 1fr);
    gap: 24px;
    align-items: start;
}
.auto-cert-preview {
    min-width: 0;
}
.certificate-preview-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}
.certificate-canvas {
    position: relative;
    width: 100%;
    max-width: 1123px;
    height: auto;
    background: #ffffff;
    border: 2px dashed #d1d5db;
    overflow: hidden;
}
.certificate-canvas:not(.has-template) {
    aspect-ratio: 297 / 210;
}
.certificate-canvas.has-template {
    border: 1px solid #d1d5db;
}
.certificate-bg {
    display: block;
    width: 100%;
    height: auto;
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
    text-align: center;
    padding: 0 20px;
}
.certificate-name-overlay,
.certificate-adviser-overlay {
    position: absolute;
    display: none;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    box-sizing: border-box;
    line-height: 1;
    /* horizontal padding is % of the canvas so editor and print fits match */
    padding: 2px 0.6%;
    background: rgba(255, 255, 255, 0.12);
    user-select: none;
    pointer-events: none;
    /* pointer drag support (mouse + touch) without scrolling the page */
    touch-action: none;
    z-index: 5;
}
.certificate-name-overlay {
    width: 30%;
    left: 51.61%;
    transform: translateX(-50%);
    top: 57.3%;
    height: 7%;
}
.certificate-adviser-overlay {
    width: 13.25%;
    left: 26.13%;
    transform: translateX(-50%);
    top: 87%;
    height: 5.3%;
}
.certificate-name-overlay.is-visible,
.certificate-adviser-overlay.is-visible {
    display: block;
    pointer-events: auto;
    cursor: move;
}
/* subtle dashed feedback only on hover / while dragging — preview stays clean */
.certificate-name-overlay.is-visible:hover,
.certificate-adviser-overlay.is-visible:hover,
.certificate-name-overlay.is-visible.is-dragging,
.certificate-adviser-overlay.is-visible.is-dragging {
    outline: 2px dashed rgba(37, 99, 235, 0.6);
    outline-offset: -2px;
}
.certificate-name-overlay.is-dragging,
.certificate-adviser-overlay.is-dragging {
    outline-color: rgba(37, 99, 235, 0.95);
    background: rgba(255, 255, 255, 0.25);
}
.font-settings {
    margin-top: 12px;
    max-width: 1123px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: #FAFAFA;
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.font-settings-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--text-light);
}
.fs-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 11px;
    color: var(--text-light);
    font-weight: 500;
}
.fs-input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 12px;
    outline: none;
    background: #fff;
    color: var(--text);
}
.cert-bl-probe {
    display: inline-block;
    width: 0;
    height: 0;
    overflow: hidden;
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
.student-name-warning {
    margin-top: 12px;
    max-width: 1123px;
    padding: 8px 12px;
    border-radius: var(--radius-sm);
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 12px;
    font-weight: 500;
}
@media (max-width: 980px) {
    .auto-cert-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<script>
(function () {
    'use strict';

    var REF_W = 1123;
    var measureCanvas = document.createElement('canvas');
    var measureCtx = measureCanvas.getContext('2d');

    // Line anchors, measured from the actual certificate image (sample
    // template is 1366x768). All values are % OF THE IMAGE; the canvas is
    // sized to the image, so % coordinates on the overlays map 1:1 to the image.
    var studentLineCenterX = 51.61; // horizontal center of the recipient underline
    var studentLineY = 64.32;       // top edge of the recipient underline
    var adviserLineCenterX = 26.13; // center of the LEFT Class Adviser line (never School OIC)
    var adviserLineY = 92.32;       // top edge of the Class Adviser line
    var adviserBoxWidth = 13.25;    // usable length of the Class Adviser line

    // Shared font style; separate preferred size per name.
    var sharedFontFamily = 'Georgia';
    var studentPreferredFontSize = 24;
    var adviserPreferredFontSize = 18;

    var studentCfg = { centerX: studentLineCenterX, top: 57.3, height: 7, baseline: studentLineY, width: 30, min: 16, color: '#1F2937', weight: '700', centered: true };
    var adviserCfg = { centerX: adviserLineCenterX, top: 87, height: 5.3, baseline: adviserLineY, width: adviserBoxWidth, min: 10, color: '#1F2937', weight: '600', centered: true };

    // Optional manual position overrides set by dragging (% of the image).
    // null = use the existing automatic alignment (the default).
    var studentPosOverride = null; // { centerX, baseline }
    var adviserPosOverride = null;
    var activeDrag = null;         // overlay currently being dragged

    var selectedStudentId = null;
    var selectedStudentName = '';
    var selectedAdviserId = null;
    var selectedAdviserName = '';

    var studentBox = document.getElementById('certificateStudentName');
    var adviserBox = document.getElementById('certificateAdviserName');

    function $(id) { return document.getElementById(id); }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function nameScale() {
        var canvas = $('certificateCanvas');
        var w = canvas ? canvas.clientWidth : 0;
        return w > 0 ? w / REF_W : 1;
    }

    function boxLeft(cfg) {
        if (cfg.centered) return cfg.centerX - cfg.width / 2;
        return cfg.left;
    }

    // Effective position: manual override (from dragging) when present,
    // otherwise the existing automatic alignment.
    function effectiveCfg(cfg, override) {
        if (!override) return cfg;
        var e = {};
        for (var k in cfg) {
            if (Object.prototype.hasOwnProperty.call(cfg, k)) e[k] = cfg[k];
        }
        e.centerX = override.centerX;
        e.baseline = override.baseline;
        return e;
    }

    function applyBoxStyle(box, cfg) {
        var top = cfg.top;
        var left = cfg.centered ? cfg.centerX : boxLeft(cfg);

        if (!cfg.centered && left < 0) left = 0;
        if (!cfg.centered && left + cfg.width > 100) left = 100 - cfg.width;
        if (top < 0) top = 0;
        if (top + cfg.height > 100) top = 100 - cfg.height;

        box.style.width = cfg.width + '%';
        box.style.height = cfg.height + '%';
        box.style.top = top + '%';
        box.style.left = left + '%';
        box.style.transform = cfg.centered ? 'translateX(-50%)' : 'none';
        box.style.fontFamily = '"' + sharedFontFamily + '", serif';
        box.style.color = cfg.color;
        box.style.fontWeight = cfg.weight;
        box.style.textAlign = 'center';
        box.style.lineHeight = '1';
    }

    function fitTextToBox(element, options) {
        if (!element) return { size: 0, refSize: 0, fits: true };
        options = options || {};

        var text = (element.textContent || '').trim();
        if (!text) {
            element.style.fontSize = '';
            return { size: 0, refSize: 0, fits: true };
        }

        var scale = nameScale();
        var minSize = Math.max(1, (options.minSize || 12) * scale);
        var maxSize = Math.max(minSize, (options.maxSize || 40) * scale);
        var weight = options.weight || '700';
        var family = options.family || 'Georgia';

        var cs = window.getComputedStyle(element);
        var availW = element.clientWidth - (parseFloat(cs.paddingLeft) || 0) - (parseFloat(cs.paddingRight) || 0);

        if (availW <= 0) {
            return { size: minSize, refSize: options.minSize || 12, fits: false };
        }

        var size = maxSize;
        var fits = false;
        while (size >= minSize) {
            measureCtx.font = weight + ' ' + size + 'px "' + family + '"';
            if (measureCtx.measureText(text).width <= availW) {
                fits = true;
                break;
            }
            size -= 0.25;
        }
        if (!fits) size = minSize;

        element.style.fontSize = size + 'px';

        return { size: size, refSize: scale > 0 ? size / scale : size, fits: fits };
    }

    // The zero-height probe's bottom margin edge aligns exactly with the text
    // baseline, so we can read the browser's own baseline placement instead of
    // approximating it from font metrics.
    function ensureBaselineProbe(box) {
        var probe = box.querySelector('.cert-bl-probe');
        if (!probe) {
            probe = document.createElement('span');
            probe.className = 'cert-bl-probe';
            box.appendChild(probe);
        }
        return probe;
    }

    // Baseline positioning: place the text so its visible bottom (including
    // measured descenders) sits a small gap above the printed line stored in
    // cfg.baseline. Uses the browser-measured baseline with a metrics-based
    // fallback, so it holds for any font style or size.
    function positionBaselineBox(box, cfg, size) {
        var canvas = $('certificateCanvas');
        if (!canvas || !box || !size) return;

        var H = canvas.clientHeight;
        if (H <= 0) return;

        var text = (box.textContent || '').trim();
        if (!text) return;

        measureCtx.font = cfg.weight + ' ' + size + 'px "' + sharedFontFamily + '"';
        var m = measureCtx.measureText(text);
        var descent = typeof m.actualBoundingBoxDescent === 'number' ? m.actualBoundingBoxDescent : 0;
        var ascent = m.actualBoundingBoxAscent || size * 0.8;
        var fAsc = m.fontBoundingBoxAscent || ascent;
        var fDesc = m.fontBoundingBoxDescent || size * 0.21;

        var cs = window.getComputedStyle(box);
        var padT = parseFloat(cs.paddingTop) || 0;

        var gap = Math.max(2, H * 0.005);
        var lineY = (cfg.baseline / 100) * H;
        var baselineY = lineY - gap - descent;

        // Analytic fallback: baseline offset of a line-height:1 line box.
        var offset = padT + (size + fAsc - fDesc) / 2;

        var probe = ensureBaselineProbe(box);
        var boxRect = box.getBoundingClientRect();
        var probeRect = probe.getBoundingClientRect();
        if (probeRect.bottom > boxRect.top && probeRect.bottom <= boxRect.bottom + 1) {
            offset = probeRect.bottom - boxRect.top;
        }

        box.style.top = Math.round((baselineY - offset) * 100) / 100 + 'px';
        box.style.height = Math.ceil(offset + descent + 2) + 'px';
    }

    function showWarning(message) {
        var el = $('certNameWarning');
        if (!el) return;
        if (message) {
            el.textContent = message;
            el.hidden = false;
        } else {
            el.textContent = '';
            el.hidden = true;
        }
    }

    function refreshAll() {
        if (activeDrag) return; // keep the dragged box stable until release
        var warning = '';

        if (studentBox && studentBox.classList.contains('is-visible')) {
            var sCfg = effectiveCfg(studentCfg, studentPosOverride);
            applyBoxStyle(studentBox, sCfg);
            var sFit = fitTextToBox(studentBox, { maxSize: studentPreferredFontSize, minSize: sCfg.min, weight: sCfg.weight, family: sharedFontFamily });
            positionBaselineBox(studentBox, sCfg, sFit.size);
            if (!sFit.fits) {
                warning = 'The student name "' + (studentBox.textContent || '').trim() + '" cannot fit in the certificate name area even at the smallest readable font size. Please check the certificate template.';
            }
        }

        if (adviserBox && adviserBox.classList.contains('is-visible')) {
            var aCfg = effectiveCfg(adviserCfg, adviserPosOverride);
            applyBoxStyle(adviserBox, aCfg);
            var aFit = fitTextToBox(adviserBox, { maxSize: adviserPreferredFontSize, minSize: aCfg.min, weight: aCfg.weight, family: sharedFontFamily });
            positionBaselineBox(adviserBox, aCfg, aFit.size);
            if (!warning && !aFit.fits) {
                warning = 'The adviser name "' + (adviserBox.textContent || '').trim() + '" cannot fit above the Class Adviser line even at the smallest readable font size. Please check the certificate template.';
            }
        }

        showWarning(warning);
    }

    function showStudentName() {
        if (!studentBox || !selectedStudentId || !selectedStudentName) return;
        studentBox.textContent = selectedStudentName;
        studentBox.classList.add('is-visible');
        refreshAll();
    }

    function hideStudentName() {
        if (!studentBox) return;
        studentBox.textContent = '';
        studentBox.classList.remove('is-visible');
        studentBox.style.fontSize = '';
        refreshAll();
    }

    function showAdviserName() {
        if (!adviserBox || !selectedAdviserId || !selectedAdviserName) return;
        adviserBox.textContent = selectedAdviserName;
        adviserBox.classList.add('is-visible');
        refreshAll();
    }

    function hideAdviserName() {
        if (!adviserBox) return;
        adviserBox.textContent = '';
        adviserBox.classList.remove('is-visible');
        adviserBox.style.fontSize = '';
        refreshAll();
    }

    // --- TEMPLATE UPLOAD & PREVIEW ---
    document.getElementById('template_file').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;

        var img = $('certificateBg');
        var canvas = $('certificateCanvas');
        var emptyMessage = $('emptyTemplateMessage');

        var reader = new FileReader();
        reader.onload = function (e) {
            img.onload = function () {
                img.style.display = 'block';
                canvas.classList.add('has-template');
                if (emptyMessage) emptyMessage.style.display = 'none';
                // a new template gets fresh automatic positions
                studentPosOverride = null;
                adviserPosOverride = null;
                refreshAll();
            };
            img.src = e.target.result;
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
        showStudentName();
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
        hideStudentName();
        closeResults();
    });

    // --- TEACHER / ADVISER SELECTION ---
    function syncAdviserFromSelect() {
        var select = $('adviserSelect');
        var option = select.options[select.selectedIndex];
        if (!select.value || !option) {
            selectedAdviserId = null;
            selectedAdviserName = '';
            hideAdviserName();
            return;
        }
        selectedAdviserId = select.value;
        selectedAdviserName = option.getAttribute('data-name') || option.textContent.trim();
        showAdviserName();
    }

    $('adviserSelect').addEventListener('change', syncAdviserFromSelect);

    // --- FONT SETTINGS (shared style, independent sizes) ---
    $('certFontFamily').addEventListener('change', function () {
        sharedFontFamily = this.value;
        refreshAll();
    });
    $('studentFontSize').addEventListener('input', function () {
        var v = parseInt(this.value, 10);
        if (v > 0) { studentPreferredFontSize = v; refreshAll(); }
    });
    $('adviserFontSize').addEventListener('input', function () {
        var v = parseInt(this.value, 10);
        if (v > 0) { adviserPreferredFontSize = v; refreshAll(); }
    });

    // --- OPTIONAL DRAG POSITIONING (manual override on top of automatic alignment) ---
    function attachDrag(box, getOverride, setOverride, cfg) {
        var drag = null;

        box.addEventListener('pointerdown', function (e) {
            if (activeDrag || !box.classList.contains('is-visible')) return;
            var text = (box.textContent || '').trim();
            if (!text) return; // disabled for empty name fields
            if (e.pointerType === 'mouse' && e.button !== 0) return;

            var canvas = $('certificateCanvas');
            if (!canvas || !canvas.classList.contains('has-template') || canvas.clientWidth <= 0) return;

            var topStyle = box.style.top || '';
            if (!topStyle || topStyle.indexOf('%') !== -1) return; // not positioned yet
            var startTop = parseFloat(topStyle);
            if (isNaN(startTop)) return;

            var canvasRect = canvas.getBoundingClientRect();
            var ccs = window.getComputedStyle(canvas);
            var borderL = parseFloat(ccs.borderLeftWidth) || 0;
            var borderT = parseFloat(ccs.borderTopWidth) || 0;
            var boxRect = box.getBoundingClientRect();

            drag = {
                id: e.pointerId,
                grabDX: e.clientX - boxRect.left, // pointer offset inside the box
                grabDY: e.clientY - boxRect.top,
                canvas: canvas,
                borderL: borderL,
                borderT: borderT,
                canvasW: canvas.clientWidth,
                canvasH: canvas.clientHeight,
                boxW: box.offsetWidth,
                boxH: box.offsetHeight,
                startTop: startTop,
                startBaseline: getOverride() ? getOverride().baseline : cfg.baseline,
                centerX: null,
                top: null
            };

            try { box.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
            box.classList.add('is-dragging');
            activeDrag = box;
            e.preventDefault(); // block text selection / touch scrolling
        });

        box.addEventListener('pointermove', function (e) {
            if (!drag || e.pointerId !== drag.id) return;

            // coordinates relative to the certificate image (canvas padding box)
            var canvasRect = drag.canvas.getBoundingClientRect();
            var px = e.clientX - canvasRect.left - drag.borderL;
            var py = e.clientY - canvasRect.top - drag.borderT;

            // clamp: whole box stays inside the image, never the outer card
            var left = Math.min(Math.max(px - drag.grabDX, 0), Math.max(0, drag.canvasW - drag.boxW));
            var top = Math.min(Math.max(py - drag.grabDY, 0), Math.max(0, drag.canvasH - drag.boxH));

            drag.centerX = left + drag.boxW / 2; // translateX(-50%): layout left = center
            drag.top = top;

            box.style.left = drag.centerX + 'px';
            box.style.top = top + 'px';
            e.preventDefault();
        });

        function endDrag(e) {
            if (!drag || (e && e.pointerId !== drag.id)) return;
            var d = drag;
            drag = null;
            activeDrag = null;
            box.classList.remove('is-dragging');
            try { box.releasePointerCapture(d.id); } catch (err) { /* ignore */ }

            if (d.centerX === null || d.top === null) return; // click without movement

            // convert pixels back to % of the certificate image (responsive coords)
            var baseline = d.startBaseline + ((d.top - d.startTop) / d.canvasH) * 100;
            setOverride({
                centerX: (d.centerX / d.canvasW) * 100,
                baseline: baseline
            });
            refreshAll();
        }

        box.addEventListener('pointerup', endDrag);
        box.addEventListener('pointercancel', endDrag);
    }

    if (studentBox) {
        attachDrag(studentBox, function () { return studentPosOverride; }, function (v) { studentPosOverride = v; }, studentCfg);
    }
    if (adviserBox) {
        attachDrag(adviserBox, function () { return adviserPosOverride; }, function (v) { adviserPosOverride = v; }, adviserCfg);
    }

    // --- RESET NAME POSITIONS (clears only the manual overrides) ---
    $('resetNamePositionsBtn').addEventListener('click', function () {
        studentPosOverride = null;
        adviserPosOverride = null;
        refreshAll();
    });

    // --- COLLECT CANVAS DATA ---
    function collectCanvasData() {
        var data = [];
        var canvas = $('certificateCanvas');
        var canvasH = canvas ? canvas.clientHeight : 0;

        if (studentBox && studentBox.classList.contains('is-visible') && selectedStudentId) {
            var sCfg = effectiveCfg(studentCfg, studentPosOverride);
            var sText = (studentBox.textContent || '').trim();
            var sFit = fitTextToBox(studentBox, { maxSize: studentPreferredFontSize, minSize: sCfg.min, weight: sCfg.weight, family: sharedFontFamily });
            var sLeft = boxLeft(sCfg);
            var sTop = canvasH > 0 ? (studentBox.offsetTop / canvasH) * 100 : sCfg.top;
            var sHeight = canvasH > 0 ? (studentBox.offsetHeight / canvasH) * 100 : sCfg.height;
            data.push({
                type: 'student_name',
                student_id: selectedStudentId,
                text: sText,
                left: sLeft,
                top: sTop,
                width: sCfg.width,
                height: sHeight,
                baseline: sCfg.baseline,
                x: sLeft + sCfg.width / 2,
                y: sTop + sHeight / 2,
                fontSize: sFit.refSize,
                fontSizePref: studentPreferredFontSize,
                fontSizeMin: sCfg.min,
                fontFamily: sharedFontFamily,
                color: sCfg.color,
                fontWeight: sCfg.weight,
                textAlign: 'center'
            });
        }

        if (adviserBox && adviserBox.classList.contains('is-visible') && selectedAdviserId) {
            var aCfg = effectiveCfg(adviserCfg, adviserPosOverride);
            var aText = (adviserBox.textContent || '').trim();
            var aFit = fitTextToBox(adviserBox, { maxSize: adviserPreferredFontSize, minSize: aCfg.min, weight: aCfg.weight, family: sharedFontFamily });
            var aLeft = boxLeft(aCfg);
            var aTop = canvasH > 0 ? (adviserBox.offsetTop / canvasH) * 100 : aCfg.top;
            var aHeight = canvasH > 0 ? (adviserBox.offsetHeight / canvasH) * 100 : aCfg.height;
            data.push({
                type: 'adviser_name',
                teacher_id: selectedAdviserId,
                text: aText,
                left: aLeft,
                top: aTop,
                width: aCfg.width,
                height: aHeight,
                baseline: aCfg.baseline,
                x: aLeft + aCfg.width / 2,
                y: aTop + aHeight / 2,
                fontSize: aFit.refSize,
                fontSizePref: adviserPreferredFontSize,
                fontSizeMin: aCfg.min,
                fontFamily: sharedFontFamily,
                color: aCfg.color,
                fontWeight: aCfg.weight,
                textAlign: 'center'
            });
        }

        return data;
    }

    // Certificates are generated client-side for Print Preview; the Award to
    // Student button is the only path that persists a certificate.
    document.getElementById('certificateForm').addEventListener('submit', function (e) {
        e.preventDefault();
    });

    // --- PRINT PREVIEW ---
    document.getElementById('previewPrintBtn').addEventListener('click', function () {
        if (!$('certificateBg').src && !$('template_file').files[0]) {
            alert('Please upload a template first.');
            return;
        }

        var data = collectCanvasData();
        var templateUrl = $('certificateBg').src;
        var printWindow = window.open('', '_blank');

        var nameHtml = '';
        data.forEach(function (box) {
            if (box.type !== 'student_name' && box.type !== 'adviser_name') return;
            var width = box.width;
            var height = box.height || 6;
            var left = typeof box.left === 'number' ? box.left : ((box.x || 50) - width / 2);
            var top = typeof box.top === 'number' ? box.top : ((box.y || 45) - height / 2);
            nameHtml += '<div class="print-fit-box" data-pref="' + box.fontSizePref +
                '" data-min="' + box.fontSizeMin + '" data-family="' + escapeHtml(box.fontFamily) +
                '" data-bold="' + (box.fontWeight === '400' || box.fontWeight === 'normal' ? '0' : '1') +
                '" data-baseline="' + (typeof box.baseline === 'number' ? box.baseline : '') +
                '" style="left:' + left + '%;top:' + top + '%;width:' + width + '%;height:' + height +
                '%;font-family:\'' + box.fontFamily + '\',serif;color:' + box.color +
                ';font-weight:' + box.fontWeight + ';font-size:' + box.fontSize + 'px;">' + escapeHtml(box.text) + '<span class="bl-probe"></span></div>';
        });

        // Fit by measured text width, then place via the same baseline math as
        // the on-screen preview so the printed certificate matches exactly.
        var fitScript = 'window.addEventListener("load",function(){' +
            'function fit(){' +
            'var wrap=document.querySelector(".certificate-canvas");' +
            'if(!wrap)return;' +
            'var W=wrap.clientWidth,H=wrap.clientHeight;' +
            'var scale=W/1123;' +
            'var els=document.querySelectorAll(".print-fit-box");' +
            'var mc=document.createElement("canvas").getContext("2d");' +
            'for(var i=0;i<els.length;i++){' +
            'var el=els[i];' +
            'var text=el.textContent.trim();' +
            'if(!text)continue;' +
            'var pref=(parseFloat(el.getAttribute("data-pref"))||24)*scale;' +
            'var min=(parseFloat(el.getAttribute("data-min"))||12)*scale;' +
            'if(pref<min)pref=min;' +
            'var fam=el.getAttribute("data-family")||"Georgia";' +
            'var weight=el.getAttribute("data-bold")==="1"?"700":"400";' +
            'var cs=window.getComputedStyle(el);' +
            'var padL=parseFloat(cs.paddingLeft)||0;' +
            'var padR=parseFloat(cs.paddingRight)||0;' +
            'var padT=parseFloat(cs.paddingTop)||0;' +
            'var availW=el.clientWidth-padL-padR;' +
            'if(availW<=0)continue;' +
            'var size=pref;' +
            'var ok=false;' +
            'while(size>=min){' +
            'mc.font=weight+" "+size+"px \\""+fam+"\\"";' +
            'if(mc.measureText(text).width<=availW){ok=true;break;}' +
            'size-=0.25;' +
            '}' +
            'if(!ok)size=min;' +
            'el.style.fontSize=size+"px";' +
            'var baseline=parseFloat(el.getAttribute("data-baseline"));' +
            'if(!isNaN(baseline)&&H>0){' +
            'mc.font=weight+" "+size+"px \\""+fam+"\\"";' +
            'var m=mc.measureText(text);' +
            'var asc=m.actualBoundingBoxAscent||size*0.8;' +
            'var desc=typeof m.actualBoundingBoxDescent==="number"?m.actualBoundingBoxDescent:0;' +
            'var fAsc=m.fontBoundingBoxAscent||asc;' +
            'var fDesc=m.fontBoundingBoxDescent||size*0.21;' +
            'var gap=Math.max(2,H*0.005);' +
            'var lineY=baseline/100*H;' +
            'var baselineY=lineY-gap-desc;' +
            'var offset=padT+(size+fAsc-fDesc)/2;' +
            'var pr=el.querySelector(".bl-probe");' +
            'if(pr){var er=el.getBoundingClientRect();var prr=pr.getBoundingClientRect();' +
            'if(prr.bottom>er.top){offset=prr.bottom-er.top;}}' +
            'el.style.top=((baselineY-offset)/H*100)+"%";' +
            'el.style.height=(offset+desc+2)+"px";' +
            '}' +
            '}' +
            '}' +
            'if(document.fonts&&document.fonts.ready){document.fonts.ready.then(fit);}else{fit();}' +
            'setTimeout(fit,300);' +
            'setTimeout(function(){window.print();},600);' +
            '});';

        printWindow.document.write('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Print Preview</title><style>*{margin:0;padding:0;box-sizing:border-box;}@page{size:A4 landscape;margin:0;}body{margin:0;background:#fff;display:flex;justify-content:center;align-items:center;min-height:100vh;}.certificate-canvas{position:relative;width:297mm;max-width:100%;overflow:hidden;}.certificate-canvas img{display:block;width:100%;height:auto;}.print-fit-box{position:absolute;text-align:center;white-space:nowrap;overflow:hidden;box-sizing:border-box;line-height:1;padding:2px 0.6%;z-index:5;}.bl-probe{display:inline-block;width:0;height:0;overflow:hidden;}@media print{body{margin:0;}.certificate-canvas{box-shadow:none;}}</style></head><body><div class="certificate-canvas"><img src="' + templateUrl + '" alt="Certificate template">' + nameHtml + '</div><script>' + fitScript + '<\/script></body></html>');
        printWindow.document.close();
    });

    // --- AWARD TO STUDENT ---
    var awardBusy = false;
    var lastAwardedKey = null;

    function showAwardStatus(message, kind) {
        var el = $('awardStatus');
        if (!el) return;
        el.textContent = message;
        el.style.display = 'block';
        if (kind === 'success') {
            el.style.background = 'rgba(0, 200, 83, 0.08)';
            el.style.border = '1px solid #16A34A';
            el.style.color = '#166534';
        } else if (kind === 'info') {
            el.style.background = 'rgba(107, 114, 128, 0.08)';
            el.style.border = '1px solid #9CA3AF';
            el.style.color = '#374151';
        } else {
            el.style.background = 'rgba(220, 38, 38, 0.08)';
            el.style.border = '1px solid #EF4444';
            el.style.color = '#991B1B';
        }
    }

    function awardValidationIssue() {
        var img = $('certificateBg');
        var canvas = $('certificateCanvas');
        if (!img || !img.src || !img.naturalWidth || !canvas || !canvas.classList.contains('has-template')) {
            return 'Upload a certificate template first.';
        }
        if (!selectedStudentId) return 'Search and select a student first.';
        if (!selectedAdviserId) return 'Select a teacher / adviser first.';
        if (!studentBox || !studentBox.classList.contains('is-visible') || !(studentBox.textContent || '').trim()) {
            return 'The student name is not shown on the certificate.';
        }
        if (!adviserBox || !adviserBox.classList.contains('is-visible') || !(adviserBox.textContent || '').trim()) {
            return 'The adviser name is not shown on the certificate.';
        }
        var warning = $('certNameWarning');
        if (warning && !warning.hidden) {
            return warning.textContent.trim() || 'One of the names does not fit the certificate.';
        }
        return null;
    }

    // Mirrors the server-side fingerprint: student, adviser, template file and
    // the full rendered layout. Any change means a different certificate.
    function buildAwardKey() {
        var file = $('template_file').files[0];
        var templateSignature = file ? (file.name + '|' + file.size + '|' + file.lastModified) : '';
        return [
            String(selectedStudentId || ''),
            String(selectedAdviserId || ''),
            templateSignature,
            JSON.stringify(collectCanvasData())
        ].join('|');
    }

    // Bake the template and both rendered names into one final image at the
    // template's natural resolution, mirroring the on-screen placement.
    function compositeCertificate() {
        return new Promise(function (resolve) {
            try {
                var img = $('certificateBg');
                var canvas = $('certificateCanvas');
                if (!img || !img.naturalWidth || !canvas) { resolve(null); return; }

                var out = document.createElement('canvas');
                out.width = img.naturalWidth;
                out.height = img.naturalHeight;
                var ctx = out.getContext('2d');
                if (!ctx) { resolve(null); return; }
                ctx.drawImage(img, 0, 0, out.width, out.height);

                var canvasRect = canvas.getBoundingClientRect();
                var canvasStyle = window.getComputedStyle(canvas);
                var borderLeft = parseFloat(canvasStyle.borderLeftWidth) || 0;
                var borderTop = parseFloat(canvasStyle.borderTopWidth) || 0;
                var originX = canvasRect.left + borderLeft;
                var originY = canvasRect.top + borderTop;
                var scaleX = out.width / Math.max(1, canvas.clientWidth);
                var scaleY = out.height / Math.max(1, canvas.clientHeight);

                var boxes = [studentBox, adviserBox];
                for (var i = 0; i < boxes.length; i++) {
                    var box = boxes[i];
                    if (!box || !box.classList.contains('is-visible')) continue;
                    var text = (box.textContent || '').trim();
                    if (!text) continue;

                    var probe = box.querySelector('.cert-bl-probe');
                    if (!probe) { resolve(null); return; }

                    var cs = window.getComputedStyle(box);
                    var fontSize = parseFloat(cs.fontSize);
                    if (!fontSize) continue;

                    var boxRect = box.getBoundingClientRect();
                    var centerX = boxRect.left + boxRect.width / 2 - originX;
                    var baselineY = probe.getBoundingClientRect().bottom - originY;

                    ctx.font = (cs.fontWeight || '400') + ' ' + (fontSize * scaleY) + 'px ' + (cs.fontFamily || 'Georgia');
                    ctx.fillStyle = cs.color || '#1F2937';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'alphabetic';
                    ctx.fillText(text, centerX * scaleX, baselineY * scaleY);
                }

                if (!out.toBlob) { resolve(null); return; }
                out.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', 0.98);
            } catch (e) {
                resolve(null);
            }
        });
    }

    document.getElementById('awardToStudentBtn').addEventListener('click', function () {
        if (awardBusy) return;

        refreshAll();

        var issue = awardValidationIssue();
        if (issue) { showAwardStatus(issue, 'error'); return; }

        var awardKey = buildAwardKey();
        if (awardKey === lastAwardedKey) {
            showAwardStatus('This certificate has already been awarded to the student.', 'info');
            return;
        }

        var button = this;
        awardBusy = true;
        button.disabled = true;
        button.textContent = 'Awarding...';
        showAwardStatus('Awarding certificate to the student...', 'info');

        compositeCertificate().then(function (blob) {
            if (!blob) {
                showAwardStatus('The certificate preview could not be generated. Please try again.', 'error');
                return null;
            }

            var form = new FormData();
            form.append('student_id', selectedStudentId);
            form.append('adviser_id', selectedAdviserId);
            form.append('template_file', $('template_file').files[0]);
            form.append('generated_file', blob, 'certificate.jpg');
            form.append('canvas_data', JSON.stringify(collectCanvasData()));

            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            return fetch('{{ route('admin.certificate.award') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: form
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (payload) {
                    return { ok: response.ok, payload: payload };
                });
            });
        }).then(function (result) {
            if (!result) return;

            if (result.ok && result.payload.success) {
                lastAwardedKey = awardKey;
                showAwardStatus(
                    result.payload.message || 'Certificate successfully awarded to the student.',
                    result.payload.duplicate ? 'info' : 'success'
                );
                return;
            }

            var message = result.payload.message;
            if (!message && result.payload.errors) {
                var keys = Object.keys(result.payload.errors);
                message = keys.length ? result.payload.errors[keys[0]][0] : null;
            }
            showAwardStatus(message || 'Could not award the certificate. Please try again.', 'error');
        }).catch(function () {
            showAwardStatus('Network error while awarding the certificate. Please try again.', 'error');
        }).finally(function () {
            awardBusy = false;
            button.disabled = false;
            button.textContent = 'Award to Student';
        });
    });

    // --- INIT ---
    document.addEventListener('DOMContentLoaded', function () {
        if ($('student_id').value && $('studentSearch').value) {
            selectedStudentId = $('student_id').value;
            selectedStudentName = $('studentSearch').value.trim();
            showStudentName();
        }

        syncAdviserFromSelect();

        refreshAll();

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () { refreshAll(); });
        }
    });

    if ('ResizeObserver' in window) {
        var resizeObserver = new ResizeObserver(function () { refreshAll(); });
        document.addEventListener('DOMContentLoaded', function () {
            var canvas = $('certificateCanvas');
            if (canvas) resizeObserver.observe(canvas);
        });
    }
})();
</script>

@if(Auth::user()->isTeacher())
<script>
function openAddQuestModal() {
    var modal = document.getElementById('questAddModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeAddQuestModal() {
    var modal = document.getElementById('questAddModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
}
function openQuestEditModal(id, title, description, type, value, badge, status) {
    document.getElementById('edit_quest_title').value = title;
    document.getElementById('edit_quest_description').value = description;
    document.getElementById('edit_requirement_type').value = type;
    document.getElementById('edit_required_value').value = value;
    document.getElementById('edit_quest_badge').value = badge;
    document.getElementById('edit_quest_status').value = status;
    var form = document.getElementById('questEditForm');
    form.action = '{{ route("achievement-quests.update", ["achievement" => "__ID__"]) }}'.replace('__ID__', id);
    document.getElementById('questEditModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeQuestEditModal() {
    document.getElementById('questEditModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('DOMContentLoaded', function () {
    var addModal = document.getElementById('questAddModal');
    if (addModal) {
        addModal.addEventListener('click', function (e) {
            if (e.target === addModal) closeAddQuestModal();
        });
    }
    var editModal = document.getElementById('questEditModal');
    if (editModal) {
        editModal.addEventListener('click', function (e) {
            if (e.target === editModal) closeQuestEditModal();
        });
    }
});
</script>
@endif
@endpush
