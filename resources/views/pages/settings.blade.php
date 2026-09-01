@extends('layouts.admin')

@section('title', 'EcoCollect - Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage system settings')

@section('content')
    <div class="settings-tabs">
        <button type="button" class="settings-tab active" data-section="generalSettings">General Settings</button>
    </div>

    <!-- General Settings -->
    <div class="settings-section active" id="generalSettings">
        <div class="card">
            <div class="card-body" style="max-width:500px;">
                <form method="POST" action="{{ route('admin.settings.general') }}">
                    @csrf
                    <div class="form-group">
                        <label>Admin's Name</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name', $settings->admin_name ?? '') }}" aria-label="Admin name" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>School / Organization</label>
                        <input type="text" name="school_organization" value="{{ old('school_organization', $settings->school_organization ?? '') }}" aria-label="School or organization" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="{{ old('address', $settings->address ?? '') }}" aria-label="Address" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
@endsection
