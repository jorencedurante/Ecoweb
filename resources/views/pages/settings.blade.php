@extends('layouts.admin')

@section('title', 'EcoCollect - Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage system settings')

@section('content')
    <div class="settings-tabs">
        <button class="settings-tab active" data-section="generalSettings">General Settings</button>
        <button class="settings-tab" data-section="notificationSettings">Notification Settings</button>
    </div>

    <!-- General Settings -->
    <div class="settings-section active" id="generalSettings">
        <div class="card">
            <div class="card-body" style="max-width:500px;">
                <form method="POST" action="{{ route('admin.settings.general') }}">
                    @csrf
                    <div class="form-group">
                        <label>Admin's Name</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name', $settings->admin_name ?? '') }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>School / Organization</label>
                        <input type="text" name="school_organization" value="{{ old('school_organization', $settings->school_organization ?? '') }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="{{ old('address', $settings->address ?? '') }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="settings-section" id="notificationSettings">
        <div class="card">
            <div class="card-body" style="max-width:500px;">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Email Notifications</h4>
                <form method="POST" action="{{ route('admin.settings.notifications') }}">
                    @csrf
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">Bottle Collection Reports</div>
                            <div class="toggle-desc">Receive daily email summaries of bottle collections</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="bottle_collection_reports" value="1" {{ ($notificationSettings->bottle_collection_reports ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-label">System Alerts</div>
                            <div class="toggle-desc">Receive alerts for system updates and maintenance</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="system_alerts" value="1" {{ ($notificationSettings->system_alerts ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success" style="margin-top:16px;">Save Notification Settings</button>
                </form>
            </div>
        </div>
    </div>
@endsection
