@extends('layouts.admin')

@section('title', 'EcoCollect - Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage system settings')

@section('content')
    <!-- Settings Tabs -->
    <div class="settings-tabs">
        <button class="settings-tab active" data-section="generalSettings">General Settings</button>
        <button class="settings-tab" data-section="notificationSettings">Notification Settings</button>
        <button class="settings-tab" data-section="securitySettings">Security</button>
    </div>

    <!-- General Settings -->
    <div class="settings-section active" id="generalSettings">
        <div class="card">
            <div class="card-body" style="max-width:500px;">
                <div class="form-group">
                    <label>Admin's Name</label>
                    <input type="text" value="Admin User" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>School / Organization</label>
                    <input type="text" value="EcoCollect Elementary School" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" value="123 Green Street, Eco City" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <button class="btn btn-success">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="settings-section" id="notificationSettings">
        <div class="card">
            <div class="card-body" style="max-width:500px;">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Email Notifications</h4>
                <!-- TODO: Connect toggle states to backend settings -->
                <div class="toggle-row">
                    <div>
                        <div class="toggle-label">Bottle Collection Reports</div>
                        <div class="toggle-desc">Receive daily email summaries of bottle collections</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="toggle-row">
                    <div>
                        <div class="toggle-label">System Alerts</div>
                        <div class="toggle-desc">Receive alerts for system updates and maintenance</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <button class="btn btn-success" style="margin-top:16px;">Save Notification Settings</button>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="settings-section" id="securitySettings">
        <div class="card">
            <div class="card-body" style="max-width:500px;">
                <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Account Security</h4>
                <div class="form-group">
                    <label>Account Name</label>
                    <input type="text" value="Admin User" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>Gmail</label>
                    <input type="email" value="admin@ecocollect.edu" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" value="password123" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        <button type="button" class="toggle-pw" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);">👁</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirmation Password</label>
                    <div class="input-wrapper">
                        <input type="password" value="password123" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                        <button type="button" class="toggle-pw" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);">👁</button>
                    </div>
                </div>
                <button class="btn btn-success">Update Security</button>
            </div>
        </div>
    </div>
@endsection
