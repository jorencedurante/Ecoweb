@extends('layouts.admin')

@section('title', 'EcoCollect - Settings')
@section('page-title', 'Settings')
@section('page-subtitle', $canEdit ? 'Manage system settings' : 'View system settings')

@section('content')
    <div class="settings-tabs">
        <button type="button" class="settings-tab active" data-section="generalSettings">General Settings</button>
    </div>

    <!-- General Settings -->
    <div class="settings-section active" id="generalSettings">
        <div class="card">
            <div class="card-body" style="max-width:600px;margin:0 auto;">
                @unless($canEdit)
                    <div class="settings-view-note" role="status">
                        <span aria-hidden="true">🔒</span>
                        <span>View only. Only Principal can edit these settings.</span>
                    </div>
                @endunless
                <form id="settingsGeneralForm" method="POST" action="{{ route('admin.settings.general') }}">
                    @csrf
                    <div class="form-group">
                        <label>Principal Name</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name', $settings->admin_name ?? '') }}" aria-label="Admin name" {{ $canEdit ? '' : 'readonly aria-readonly="true"' }} style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        @error('admin_name')<span class="field-error" role="alert">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label>School</label>
                        <input type="text" name="school_organization" value="{{ old('school_organization', $settings->school_organization ?? '') }}" aria-label="School or organization" {{ $canEdit ? '' : 'readonly aria-readonly="true"' }} style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        @error('school_organization')<span class="field-error" role="alert">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="{{ old('address', $settings->address ?? '') }}" aria-label="Address" {{ $canEdit ? '' : 'readonly aria-readonly="true"' }} style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        @error('address')<span class="field-error" role="alert">{{ $message }}</span>@enderror
                    </div>
                    @if($canEdit)
                        <button type="submit" class="btn btn-success" id="settingsSaveBtn">Save Changes</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Narrow the white Settings card itself, not only its fields. */
        .settings-section .card{max-width:800px;margin-left:auto;margin-right:auto;}
        .settings-section .card-body{max-width:600px;margin-left:auto;margin-right:auto;}
        @media (max-width:820px){.settings-section .card{max-width:100%;}}
        .settings-section .field-error{display:block;margin-top:5px;font-size:12px;color:#b91c1c;}
        .settings-section input[readonly]{background:#f3f4f6;color:#374151;cursor:default;border-color:#e5e7eb;}
        .settings-view-note{display:flex;align-items:center;gap:8px;margin-bottom:16px;padding:10px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;color:#1e40af;font-size:13px;font-weight:500;}
    </style>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('settingsGeneralForm');
    var button = document.getElementById('settingsSaveBtn');
    if (!form || !button) return;
    // Prevent duplicate submissions while the request is in flight.
    form.addEventListener('submit', function () {
        button.disabled = true;
        button.textContent = 'Saving…';
    });
})();
</script>
@endpush
