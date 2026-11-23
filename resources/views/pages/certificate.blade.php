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

@if(!auth()->user()->canPrintCertificates())
    <div class="certificate-print-notice" role="status" style="display:flex;align-items:flex-start;gap:10px;margin-bottom:16px;padding:12px 16px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;color:#9a3412;font-size:13px;line-height:1.5;">
        <span aria-hidden="true" style="font-size:16px;line-height:1.2;">🔒</span>
        <span><strong>Certificate printing access has not been granted. Please contact your administrator.</strong><br>You can still prepare certificates, but Print Preview and printing are disabled.</span>
    </div>
@endif

<form id="certificateForm" method="POST" action="{{ route(portal_prefix() . '.certificate.save-canvas') }}" enctype="multipart/form-data">
    @csrf
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Auto Generated Certificate</span>
                <span class="student-name-badge">Auto</span>
            </div>
            <span style="font-size:12px;color:var(--text-light);">Upload template → Select student → Select adviser → Print Preview</span>
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
                        <label for="adviserSelect">Select Adviser</label>
                        <select id="adviserSelect" name="adviser_id"
                                style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;"
                                aria-label="Select adviser">
                            <option value="" disabled selected>Select Adviser</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" data-name="{{ $t->name }}" {{ (string) old('adviser_id') === (string) $t->id ? 'selected' : '' }}>
                                    {{ $t->name }}{{ $t->position ? ' — ' . $t->position : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div id="assignedTeacherHint" style="font-size:11px;color:var(--text-light);margin-top:4px;display:{{ $studentAssignedTeacherName ? 'block' : 'none' }};">
                            Assigned teacher for selected student: <strong id="assignedTeacherHintName">{{ $studentAssignedTeacherName }}</strong>
                        </div>
                        <div style="font-size:11px;color:var(--text-light);margin-top:4px;">Select the adviser whose name will appear on the certificate.</div>
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                        <label for="principalSelect">School OIC / Principal</label>
                        <select id="principalSelect" name="principal_id"
                                style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;outline:none;background:#FAFAFA;"
                                aria-label="Select School OIC or Principal" {{ $signatories->isEmpty() ? 'disabled' : '' }}>
                            @if($signatories->isEmpty())
                                <option value="" disabled selected>No School OIC / Principal available</option>
                            @else
                                <option value="" disabled selected>Select School OIC / Principal</option>
                                @foreach($signatories as $s)
                                    <option value="{{ $s->id }}" data-name="{{ $s->name }}" data-role="{{ $s->role }}" {{ (string) old('principal_id') === (string) $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}{{ $s->position ? ' — ' . $s->position : '' }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div style="font-size:11px;color:var(--text-light);margin-top:4px;">Select the School OIC or Principal whose name appears in the right signature area.</div>
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
                        <label class="fs-field">
                            <span>OIC/Principal Size</span>
                            <input type="number" id="principalFontSize" class="fs-input" min="10" max="120" step="1" value="18" aria-label="Principal name font size" style="max-width:120px;">
                        </label>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
                        <button type="button" id="previewPrintBtn" class="btn btn-sm" style="background:#6B7280;color:#fff;padding:10px 24px;" disabled {{ auth()->user()->canPrintCertificates() ? '' : 'title="Certificate printing access has not been granted. Please contact your administrator."' }}>Print Preview</button>
                        <button type="button" id="awardToStudentBtn" class="btn btn-sm" style="background:#16A34A;color:#fff;padding:10px 24px;" disabled>Award to Student</button>
                    </div>
                    <div id="awardStatus" role="status" aria-live="polite" style="display:none;margin-top:10px;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;"></div>
                </div>

                <div class="auto-cert-preview">
                    <div class="certificate-preview-wrapper">
                        <div id="certificateCanvas" class="certificate-canvas">
                            <img id="certificateBg" class="certificate-bg" src="" alt="Certificate Template" style="display:none;">
                            <div id="certificateStudentName" class="certificate-name-overlay" data-role="student_name" aria-hidden="true"></div>
                            <div id="certificateAdviserName" class="certificate-adviser-overlay" data-role="adviser_name" aria-hidden="true"></div>
                            <div id="certificatePrincipalName" class="certificate-principal-overlay" data-role="principal_name" aria-hidden="true"></div>
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
                        <form method="POST" action="{{ route(portal_prefix() . '.achievement-quests.destroy', $q->id) }}" class="inline-action-form" onsubmit="return confirm('Delete this achievement quest?')">
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
        <form method="POST" action="{{ route(portal_prefix() . '.achievement-quests.store') }}">
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
<script src="{{ asset('js/ocrad.js') }}?v=1"></script>
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
.certificate-adviser-overlay,
.certificate-principal-overlay {
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
    /* name overlays are display-only: positioning is fully automatic */
    z-index: 5;
}
.certificate-name-overlay {
    width: 29.65%;
    left: 51.65%;
    transform: translateX(-50%);
    top: 57.32%;
    height: 7%;
}
.certificate-adviser-overlay {
    width: 13.10%;
    left: 26.13%;
    transform: translateX(-50%);
    top: 87.02%;
    height: 5.3%;
}
.certificate-principal-overlay {
    width: 15.80%;
    left: 73.21%;
    transform: translateX(-50%);
    top: 87.02%;
    height: 5.3%;
}
.certificate-name-overlay.is-visible,
.certificate-adviser-overlay.is-visible,
.certificate-principal-overlay.is-visible {
    display: block;
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
    background: transparent;
    color: #374151;
    width: 20px;
    height: 20px;
    border-radius: 0;
    line-height: 1;
    font-size: 14px;
    cursor: pointer;
    z-index: 2;
    padding: 0;
}
.student-clear-btn:hover {
    background: transparent;
    color: #111827;
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

    // Automatic text anchors, measured directly from the CURRENT certificate
    // template (1366x768). Values are % OF THE IMAGE, so they stay aligned at
    // every responsive size and on any template with the same layout.
    // Measured blank lines:
    //   recipient   y=494 (64.32%)  x=503..908  center 51.65%  width 29.65%
    //   Class Adviser y=709 (92.32%) x=268..447  center 26.13%  width 13.10%
    //   School OIC    y=709 (92.32%) x=892..1108 center 73.21%  width 15.80%
    var studentLineCenterX = 51.65; // horizontal center of the recipient underline
    var studentLineY = 64.32;       // baseline of the recipient underline
    var studentBoxWidth = 29.65;    // usable length of the recipient underline
    var adviserLineCenterX = 26.13; // center of the LEFT Class Adviser line (never School OIC)
    var adviserLineY = 92.32;       // baseline of the Class Adviser line
    var adviserBoxWidth = 13.10;    // usable length of the Class Adviser line
    var principalLineCenterX = 73.21; // center of the RIGHT School OIC/Principal line
    var principalLineY = 92.32;       // baseline of the School OIC/Principal line
    var principalBoxWidth = 15.80;    // usable length of the School OIC/Principal line

    // Shared font style; separate preferred size per name.
    var sharedFontFamily = 'Georgia';
    var studentPreferredFontSize = 24;
    var adviserPreferredFontSize = 18;
    var principalPreferredFontSize = 18;

    // `top` + `height` place the box so its bottom sits on the measured line;
    // positionBaselineBox() then fine-tunes the top from the live font metrics.
    var studentCfg = { centerX: studentLineCenterX, top: studentLineY - 7, height: 7, baseline: studentLineY, width: studentBoxWidth, min: 16, color: '#1F2937', weight: '700', centered: true };
    var adviserCfg = { centerX: adviserLineCenterX, top: adviserLineY - 5.3, height: 5.3, baseline: adviserLineY, width: adviserBoxWidth, min: 10, color: '#1F2937', weight: '600', centered: true };
    var principalCfg = { centerX: principalLineCenterX, top: principalLineY - 5.3, height: 5.3, baseline: principalLineY, width: principalBoxWidth, min: 10, color: '#1F2937', weight: '600', centered: true };

    // Optional manual position overrides set by dragging (% of the image).
    // null = use the existing automatic alignment (the default).
    var studentPosOverride = null; // { centerX, baseline }
    var adviserPosOverride = null;
    var principalPosOverride = null;
    var activeDrag = null;
    var templateHash = null;          // stable content hash of the uploaded template
    var pendingHash = null;           // hash resolved before the image finished loading
    var imageReady = false;           // true once the new template's img.onload ran
    var detectedLines = [];           // candidate blank lines from the last scan
    var alignmentVerified = false;    // all three fields positioned for this template
    var PRINT_ALLOWED = {{ auth()->user()->canPrintCertificates() ? 'true' : 'false' }};         // overlay currently being dragged

    var selectedStudentId = null;
    var selectedStudentName = '';
    var selectedAdviserId = null;
    var selectedAdviserName = '';
    var selectedPrincipalId = null;
    var selectedPrincipalName = '';
    var selectedPrincipalRole = '';

    var studentBox = document.getElementById('certificateStudentName');
    var adviserBox = document.getElementById('certificateAdviserName');
    var principalBox = document.getElementById('certificatePrincipalName');

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

        // Normalized percentages always stay inside the image, whatever the
        // screen size or where the user dragged the box.
        if (cfg.centered) left = Math.min(100 - cfg.width / 2, Math.max(cfg.width / 2, left));
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
        // The user's minimum is preferred, but a long name must still fit the
        // line. Allow a modest safety margin below it before declaring failure.
        var floorSize = Math.max(1, (options.minSize || 12) * 0.55 * scale);
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
        while (size >= floorSize) {
            measureCtx.font = weight + ' ' + size + 'px "' + family + '"';
            if (measureCtx.measureText(text).width <= availW) {
                fits = true;
                break;
            }
            size -= 0.25;
        }
        if (!fits) size = floorSize;

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

        if (principalBox && principalBox.classList.contains('is-visible')) {
            var pCfg = effectiveCfg(principalCfg, principalPosOverride);
            applyBoxStyle(principalBox, pCfg);
            var pFit = fitTextToBox(principalBox, { maxSize: principalPreferredFontSize, minSize: pCfg.min, weight: pCfg.weight, family: sharedFontFamily });
            positionBaselineBox(principalBox, pCfg, pFit.size);
            if (!warning && !pFit.fits) {
                warning = 'The School OIC/Principal name "' + (principalBox.textContent || '').trim() + '" cannot fit above the signature line even at the smallest readable font size. Please check the certificate template.';
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

    function showPrincipalName() {
        if (!principalBox || !selectedPrincipalName) return;
        principalBox.textContent = selectedPrincipalName;
        principalBox.classList.add('is-visible');
        refreshAll();
    }

    function hidePrincipalName() {
        if (!principalBox) return;
        principalBox.textContent = '';
        principalBox.classList.remove('is-visible');
        principalBox.style.fontSize = '';
        refreshAll();
    }

    // --- TEMPLATE UPLOAD & PREVIEW ---
    document.getElementById('template_file').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;

        // Clear any previous feedback so a new image can never appear to
        // reuse an old "alignment restored" message.
        var previousStatus = $('awardStatus');
        if (previousStatus) { previousStatus.textContent = ''; previousStatus.style.display = 'none'; }
        imageReady = false;
        pendingHash = null;
        detectedLines = [];
        alignmentVerified = false;
        setOutputControlsEnabled(false);

        var img = $('certificateBg');
        var canvas = $('certificateCanvas');
        var emptyMessage = $('emptyTemplateMessage');

        var reader = new FileReader();
        reader.onload = function (e) {
            img.onload = function () {
                img.style.display = 'block';
                canvas.classList.add('has-template');
                if (emptyMessage) emptyMessage.style.display = 'none';
                // A new template always gets fresh automatic positions.
                // Resetting every override is essential: a previously dragged
                // Principal label would otherwise stay at the OLD coordinates
                // and look misaligned on the new template.
                studentPosOverride = null;
                adviserPosOverride = null;
                principalPosOverride = null;
                activeDrag = null;
                imageReady = true;
                refreshAll();
                // Restore only AFTER the image load reset above, otherwise
                // onload would wipe the saved coordinates again.
                if (pendingHash) {
                    templateHash = pendingHash;
                    pendingHash = null;
                    restoreSavedAlignment();
                }
                runAutomaticAlignment();
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Stable identity for this template: a hash of its actual bytes.
        // The same image uploaded again reuses its saved alignment.
        if (file.arrayBuffer) {
            file.arrayBuffer().then(function (buffer) {
                if (window.crypto && window.crypto.subtle && window.crypto.subtle.digest) {
                    return window.crypto.subtle.digest('SHA-256', buffer).then(function (digest) {
                        var bytes = new Uint8Array(digest);
                        var hex = '';
                        for (var i = 0; i < 12; i++) hex += ('0' + bytes[i].toString(16)).slice(-2);
                        return hex;
                    });
                }
                return simpleHash(new Uint8Array(buffer));
            }).then(function (hash) {
                templateHash = hash;
                if (imageReady) {
                    restoreSavedAlignment();
                    runAutomaticAlignment();
                } else {
                    pendingHash = hash;
                }
            }).catch(function () {
                templateHash = simpleHash(file.name + '|' + file.size + '|' + file.lastModified);
                if (imageReady) {
                    restoreSavedAlignment();
                    runAutomaticAlignment();
                } else {
                    pendingHash = templateHash;
                }
            });
        } else {
            templateHash = simpleHash(file.name + '|' + file.size + '|' + file.lastModified);
            if (imageReady) {
                restoreSavedAlignment();
                runAutomaticAlignment();
            } else {
                pendingHash = templateHash;
            }
        }
    });

    /* ---------- Smart alignment: detection, dragging, save ---------- */
    function simpleHash(value) {
        var str = typeof value === 'string' ? value : '';
        if (value && value.length !== undefined && typeof value !== 'string') {
            for (var i = 0; i < Math.min(value.length, 4096); i++) str += String.fromCharCode(value[i]);
        }
        var h = 2166136261;
        for (var j = 0; j < str.length; j++) {
            h ^= str.charCodeAt(j);
            h = Math.imul(h, 16777619);
        }
        return ('00000000' + (h >>> 0).toString(16)).slice(-8);
    }

    function alignmentKey() {
        return templateHash ? 'ecocollect.certificate.alignment.' + templateHash : null;
    }

    function setOutputControlsEnabled(enabled) {
        var printBtn = $('previewPrintBtn');
        var awardBtn = $('awardToStudentBtn');
        if (printBtn) printBtn.disabled = !enabled || !PRINT_ALLOWED;
        if (awardBtn) awardBtn.disabled = !enabled;
    }

    function applyAlignment(alignment) {
        if (!alignment) return;
        var clamp = function (cfg, override) {
            if (!override) return null;
            return {
                centerX: Math.min(100 - cfg.width / 2, Math.max(cfg.width / 2, override.centerX)),
                baseline: Math.min(99, Math.max(1, override.baseline))
            };
        };
        studentPosOverride = clamp(studentCfg, alignment.student);
        adviserPosOverride = clamp(adviserCfg, alignment.adviser);
        principalPosOverride = clamp(principalCfg, alignment.principal);
        refreshAll();
    }

    function restoreSavedAlignment() {
        var key = alignmentKey();
        if (!key) return false;
        try {
            var raw = window.localStorage.getItem(key);
            if (!raw) return false;
            var saved = JSON.parse(raw);
            if (!saved || !saved.alignment) return false;
            applyAlignment(saved.alignment);
            alignmentVerified = true;
            setOutputControlsEnabled(true);
            showAlignmentMessage('Name positions restored for this certificate template.', 'info');
            return true;
        } catch (e) {
            return false;
        }
    }

    // Remember a successful detection so the same template never needs to be
    // analysed twice. No button and no user action required.
    function rememberAlignment() {
        if (!alignmentKey()) return;
        try {
            window.localStorage.setItem(alignmentKey(), JSON.stringify({
                template: templateHash,
                saved_at: new Date().toISOString(),
                alignment: {
                    student: { centerX: effectiveCfg(studentCfg, studentPosOverride).centerX, baseline: effectiveCfg(studentCfg, studentPosOverride).baseline },
                    adviser: { centerX: effectiveCfg(adviserCfg, adviserPosOverride).centerX, baseline: effectiveCfg(adviserCfg, adviserPosOverride).baseline },
                    principal: { centerX: effectiveCfg(principalCfg, principalPosOverride).centerX, baseline: effectiveCfg(principalCfg, principalPosOverride).baseline }
                }
            }));
        } catch (e) { /* storage unavailable - alignment still applies this session */ }
    }

    function showAlignmentMessage(message, kind) {
        var el = $('awardStatus');
        if (!el) return;
        el.textContent = message;
        el.style.display = 'block';
        var colors = {
            success: ['rgba(0,200,83,.08)', '#16A34A', '#166534'],
            warning: ['rgba(245,158,11,.08)', '#f59e0b', '#92400e'],
            error: ['rgba(220,38,38,.08)', '#EF4444', '#991B1B'],
            info: ['rgba(107,114,128,.08)', '#9CA3AF', '#374151']
        };
        var c = colors[kind] || colors.info;
        el.style.background = c[0];
        el.style.border = '1px solid ' + c[1];
        el.style.color = c[2];
        // Errors are shown once inline; no toast noise for automatic steps.
        if (kind === 'error' && window.EcoToast) {
            window.EcoToast.show('error', message, { key: 'certificate-alignment' });
        }
    }

    /* Best-effort detection: find long dark horizontal runs and group them. */
    function detectBlankLines(img) {
        var W = img.naturalWidth || img.width;
        var H = img.naturalHeight || img.height;
        if (!W || !H) return [];
        var scale = Math.min(1, 1200 / W);
        var w = Math.max(1, Math.round(W * scale));
        var h = Math.max(1, Math.round(H * scale));
        var c = document.createElement('canvas');
        c.width = w; c.height = h;
        var ctx = c.getContext('2d', { willReadFrequently: true });
        if (!ctx) return [];
        ctx.drawImage(img, 0, 0, w, h);
        var data;
        try { data = ctx.getImageData(0, 0, w, h).data; } catch (e) { return []; }

        var minRun = Math.max(24, Math.round(w * 0.08));
        var rows = [];
        for (var y = 0; y < h; y++) {
            var start = -1;
            // Collect EVERY qualifying run on the row. Keeping only the
            // longest one would lose a second signature line that happens to
            // sit on the same row (e.g. Adviser and School OIC lines).
            for (var x = 0; x <= w; x++) {
                var dark = false;
                if (x < w) {
                    var i = (y * w + x) * 4;
                    dark = data[i] < 120 && data[i + 1] < 120 && data[i + 2] < 120;
                }
                if (dark) { if (start < 0) start = x; }
                else if (start >= 0) {
                    var len = x - start;
                    if (len >= minRun) {
                        rows.push({ y: y, start: start, len: len, centerX: (start + len / 2) / w * 100 });
                    }
                    start = -1;
                }
            }
        }

        var groups = [];
        rows.forEach(function (r) {
            // A run belongs to an existing line when it is on a nearby row and
            // horizontally aligned with that line.
            var match = null;
            for (var i = groups.length - 1; i >= 0 && i >= groups.length - 6; i--) {
                var g = groups[i];
                if (r.y - g.endY <= 3 && Math.abs(r.centerX - g.centerX) <= 5) { match = g; break; }
            }
            if (match) {
                match.endY = r.y;
                match.sumY += r.y;
                match.sumX += r.centerX * r.len;
                match.sumW += r.len;
                match.count++;
                if (r.len > match.len) match.len = r.len;
                match.centerX = match.sumX / match.sumW;
            } else {
                groups.push({
                    startY: r.y, endY: r.y, len: r.len, centerX: r.centerX,
                    sumY: r.y, sumX: r.centerX * r.len, sumW: r.len, count: 1
                });
            }
        });

        return groups.map(function (g) {
            // `centerX` is accumulated while grouping runs; there is no
            // `start` field any more. Reading `g.start` here produced NaN
            // centre points, which silently disabled every auto placement.
            return {
                centerX: g.centerX,
                baseline: (g.sumY / g.count) / h * 100,
                width: g.len / w * 100,
                strength: g.count
            };
        }).filter(function (line) {
            return isFinite(line.centerX) && isFinite(line.baseline) && isFinite(line.width);
        }).sort(function (a, b) { return a.baseline - b.baseline; });
    }

    /* ---------- Fully automatic, OCR-assisted name alignment ----------
       No buttons and no manual interaction: uploading a template starts the
       analysis, identifies the three name lines, places the names and saves
       the result for this exact image. */
    var FAILURE_MESSAGE = "We couldn't identify all name positions on this certificate. Please use a clearer template with visible name lines and labels.";

    function editDistance(a, b) {
        var prev = [], curr = [], i, j;
        for (j = 0; j <= b.length; j++) prev[j] = j;
        for (i = 1; i <= a.length; i++) {
            curr[0] = i;
            for (j = 1; j <= b.length; j++) {
                curr[j] = Math.min(curr[j - 1] + 1, prev[j] + 1, prev[j - 1] + (a.charAt(i - 1) === b.charAt(j - 1) ? 0 : 1));
            }
            prev = curr.slice();
        }
        return prev[b.length];
    }

    function normalizeOcr(text) {
        return (text || '').toLowerCase().replace(/[^a-z\s]/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function hasAnchor(text, words) {
        var tokens = normalizeOcr(text).split(' ').filter(Boolean);
        for (var i = 0; i < tokens.length; i++) {
            for (var w = 0; w < words.length; w++) {
                var word = words[w];
                if (tokens[i] === word) return true;
                if (tokens[i].length >= 3 && Math.abs(tokens[i].length - word.length) <= 2
                    && editDistance(tokens[i], word) <= 1) return true;
            }
        }
        return false;
    }

    function ocrTextBands(img) {
        if (typeof OCRAD !== 'function') return [];
        var W = img.naturalWidth || img.width;
        var H = img.naturalHeight || img.height;
        var count = 8;
        var bandHeight = Math.floor(H / count);
        var texts = [];
        for (var i = 0; i < count; i++) {
            var strip = document.createElement('canvas');
            // Upscale before OCR: small italic labels on the certificate are
            // not recognised at native resolution.
            strip.width = Math.min(2800, W * 2);
            strip.height = Math.max(1, Math.floor(bandHeight * strip.width / W));
            var g = strip.getContext('2d');
            if (!g) { texts.push(''); continue; }
            g.drawImage(img, 0, i * bandHeight, W, bandHeight, 0, 0, strip.width, strip.height);
            var data;
            try { data = g.getImageData(0, 0, strip.width, strip.height); } catch (e) { texts.push(''); continue; }
            for (var k = 0; k < data.data.length; k += 4) {
                var grey = data.data[k] * 0.299 + data.data[k + 1] * 0.587 + data.data[k + 2] * 0.114;
                var value = grey < 170 ? 0 : 255;
                data.data[k] = data.data[k + 1] = data.data[k + 2] = value;
            }
            g.putImageData(data, 0, 0);
            var text = '';
            try { text = OCRAD(g.getImageData(0, 0, strip.width, strip.height), { inversion: true }); } catch (e) { text = ''; }
            texts.push(text || '');
        }
        return texts;
    }

    function anchorBand(texts, words) {
        for (var i = 0; i < texts.length; i++) {
            if (hasAnchor(texts[i], words)) return i;
        }
        return -1;
    }

    function pickLine(lines, filter, preferredBand, bandCount) {
        var matches = lines.filter(filter);
        if (!matches.length) return null;
        if (preferredBand >= 0) {
            var anchorY = (preferredBand + 0.5) / bandCount * 100;
            matches.sort(function (a, b) {
                return Math.abs(a.baseline - anchorY) - Math.abs(b.baseline - anchorY) || b.width - a.width;
            });
        } else {
            matches.sort(function (a, b) { return b.width - a.width; });
        }
        return matches[0];
    }

    function runAutomaticAlignment() {
        var img = $('certificateBg');
        if (!img || !img.naturalWidth || !templateHash) return;
        if (alignmentVerified) return; // saved alignment already applied

        detectedLines = detectBlankLines(img);
        var texts = ocrTextBands(img);
        var bandCount = 8;

        // Recognise the printed labels. Geometry is used only to pair an
        // anchor with its nearest underline - never to guess by "leftmost"
        // or "rightmost" line alone.
        var studentAnchor = anchorBand(texts, ['given', 'awarded', 'presented', 'recipient']);
        var adviserAnchor = anchorBand(texts, ['adviser', 'advisor', 'teacher']);
        var principalAnchor = anchorBand(texts, ['principal', 'oic', 'officer', 'director']);

        var studentLine = pickLine(detectedLines, function (line) {
            return line.baseline >= 45 && line.baseline <= 82 && line.width >= 15;
        }, studentAnchor, bandCount);

        var adviserLine = pickLine(detectedLines, function (line) {
            return line.baseline >= 70 && line.baseline <= 99 && line.centerX < 50 && line.width >= 6;
        }, adviserAnchor, bandCount);

        var principalLine = pickLine(detectedLines, function (line) {
            return line.baseline >= 70 && line.baseline <= 99 && line.centerX > 50 && line.width >= 6;
        }, principalAnchor, bandCount);

        var anchorCount = (studentAnchor >= 0 ? 1 : 0)
            + (adviserAnchor >= 0 ? 1 : 0)
            + (principalAnchor >= 0 ? 1 : 0);
        var anchors = [studentAnchor, adviserAnchor, principalAnchor].filter(function (b) { return b >= 0; });
        var nearAnchor = function (line) {
            var lineBand = Math.floor(line.baseline / 100 * bandCount);
            return anchors.some(function (b) { return Math.abs(b - lineBand) <= 2; });
        };
        var confident = anchorCount >= 2
            && studentLine && adviserLine && principalLine
            && (studentAnchor >= 0 || nearAnchor(studentLine))
            && (adviserAnchor >= 0 || nearAnchor(adviserLine))
            && (principalAnchor >= 0 || nearAnchor(principalLine));

        if (!confident) {
            alignmentVerified = false;
            setOutputControlsEnabled(false);
            showAlignmentMessage(FAILURE_MESSAGE, 'error');
            return;
        }

        var place = function (cfg, line) {
            return {
                centerX: Math.min(100 - cfg.width / 2, Math.max(cfg.width / 2, line.centerX)),
                baseline: Math.min(99, Math.max(1, line.baseline))
            };
        };
        studentPosOverride = place(studentCfg, studentLine);
        adviserPosOverride = place(adviserCfg, adviserLine);
        principalPosOverride = place(principalCfg, principalLine);

        refreshAll();
        rememberAlignment();
        alignmentVerified = true;
        setOutputControlsEnabled(true);
        showAlignmentMessage('Name positions detected automatically. Review the preview before printing.', 'info');
    }

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
        // Indicate assigned teacher (do not auto-select adviser dropdown)
        var hint = $('assignedTeacherHint');
        var hintName = $('assignedTeacherHintName');
        var assignedName = student.assigned_teacher_name || '';
        if (hint && hintName) {
            if (assignedName) {
                hintName.textContent = assignedName;
                hint.style.display = 'block';
            } else {
                hintName.textContent = '';
                hint.style.display = 'none';
            }
        }
    }

    $('studentSearch').addEventListener('input', function () {
        var q = this.value.trim();
        clearTimeout(searchTimer);

        if (!q) {
            closeResults();
            return;
        }
        searchTimer = setTimeout(function () {
            fetch('{{ route(portal_prefix() . ".students.search") }}?q=' + encodeURIComponent(q), {
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
        var hint = $('assignedTeacherHint');
        if (hint) { hint.style.display = 'none'; }
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

    // --- PRINCIPAL / SCHOOL OIC SELECTION ---
    function syncPrincipalFromSelect() {
        var select = $('principalSelect');
        if (!select) return;
        var option = select.options[select.selectedIndex];
        if (!select.value || !option) {
            selectedPrincipalId = null;
            selectedPrincipalName = '';
            selectedPrincipalRole = '';
            hidePrincipalName();
            return;
        }
        selectedPrincipalId = select.value;
        selectedPrincipalName = option.getAttribute('data-name') || option.textContent.trim();
        selectedPrincipalRole = option.getAttribute('data-role') || '';
        showPrincipalName();
    }

    var principalSelectEl = document.getElementById('principalSelect');
    if (principalSelectEl) {
        principalSelectEl.addEventListener('change', syncPrincipalFromSelect);
    }

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
    $('principalFontSize').addEventListener('input', function () {
        var v = parseInt(this.value, 10);
        if (v > 0) { principalPreferredFontSize = v; refreshAll(); }
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
                height: aCfg.height,
                baseline: aCfg.baseline,
                x: aLeft + aCfg.width / 2,
                y: aTop + aCfg.height / 2,
                fontSize: aFit.refSize,
                fontSizePref: adviserPreferredFontSize,
                fontSizeMin: aCfg.min,
                fontFamily: sharedFontFamily,
                color: aCfg.color,
                fontWeight: aCfg.weight,
                textAlign: 'center'
            });
        }

        if (principalBox && principalBox.classList.contains('is-visible') && selectedPrincipalName) {
            var pCfg = effectiveCfg(principalCfg, principalPosOverride);
            var pText = (principalBox.textContent || '').trim();
            var pFit = fitTextToBox(principalBox, { maxSize: principalPreferredFontSize, minSize: pCfg.min, weight: pCfg.weight, family: sharedFontFamily });
            var pLeft = boxLeft(pCfg);
            var pTop = canvasH > 0 ? (principalBox.offsetTop / canvasH) * 100 : pCfg.top;
            var pHeight = canvasH > 0 ? (principalBox.offsetHeight / canvasH) * 100 : pCfg.height;
            data.push({
                type: 'principal_name',
                text: pText,
                left: pLeft,
                top: pTop,
                width: pCfg.width,
                height: pCfg.height,
                baseline: pCfg.baseline,
                x: pLeft + pCfg.width / 2,
                y: pTop + pCfg.height / 2,
                fontSize: pFit.refSize,
                fontSizePref: principalPreferredFontSize,
                fontSizeMin: pCfg.min,
                fontFamily: sharedFontFamily,
                color: pCfg.color,
                fontWeight: pCfg.weight,
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
        @if(!auth()->user()->canPrintCertificates())
            showAwardStatus('Certificate printing access has not been granted. Please contact your administrator.', 'error');
            return;
        @endif
        if (!$('certificateBg').src && !$('template_file').files[0]) {
            alert('Please upload a template first.');
            return;
        }

        var data = collectCanvasData();
        var templateUrl = $('certificateBg').src;
        var printWindow = window.open('', '_blank');

        var nameHtml = '';
        data.forEach(function (box) {
            if (box.type !== 'student_name' && box.type !== 'adviser_name' && box.type !== 'principal_name') return;
            var width = box.width;
            var height = box.height || 6;
            var left = typeof box.left === 'number' ? box.left : ((box.x || 50) - width / 2);
            var top = typeof box.top === 'number' ? box.top : ((box.y || 45) - height / 2);
            nameHtml += '<div class="print-fit-box" data-pref="' + box.fontSizePref +
                '" data-fitted="' + box.fontSize + '" data-min="' + box.fontSizeMin + '" data-family="' + escapeHtml(box.fontFamily) +
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
            'var fitted=parseFloat(el.getAttribute("data-fitted"));' +
            'var pref=(isNaN(fitted)?(parseFloat(el.getAttribute("data-pref"))||24):fitted)*scale;' +
            'var min=Math.max(1,(parseFloat(el.getAttribute("data-min"))||12)*0.55*scale);' +
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
        if (!selectedPrincipalId) return 'Select a School OIC / Principal first.';
        if (!studentBox || !studentBox.classList.contains('is-visible') || !(studentBox.textContent || '').trim()) {
            return 'The student name is not shown on the certificate.';
        }
        if (!adviserBox || !adviserBox.classList.contains('is-visible') || !(adviserBox.textContent || '').trim()) {
            return 'The adviser name is not shown on the certificate.';
        }
        if (!principalBox || !principalBox.classList.contains('is-visible') || !(principalBox.textContent || '').trim()) {
            return 'The School OIC / Principal name is not shown on the certificate.';
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
            String(selectedPrincipalId || ''),
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

                var boxes = [studentBox, adviserBox, principalBox];
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
            form.append('principal_id', selectedPrincipalId || '');
            form.append('template_file', $('template_file').files[0]);
            form.append('generated_file', blob, 'certificate.jpg');
            form.append('canvas_data', JSON.stringify(collectCanvasData()));

            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            return fetch('{{ route(portal_prefix() . '.certificate.award') }}', {
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
        syncPrincipalFromSelect();

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
    form.action = '{{ route(portal_prefix() . ".achievement-quests.update", ["achievement" => "__ID__"]) }}'.replace('__ID__', id);
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
