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
            <div class="card-body" style="max-width:600px;margin:0 auto;">
                <form method="POST" action="{{ route(portal_prefix() . '.settings.general') }}">
                    @csrf
                    <div class="form-group">
                        <label>Principal Name</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name', $settings->admin_name ?? '') }}" aria-label="Admin name" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;">
                    </div>
                    <div class="form-group">
                        <label>School</label>
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

    <style>
        /* Narrow the white Settings card itself, not only its fields. */
        .settings-section .card{max-width:800px;margin-left:auto;margin-right:auto;}
        .settings-section .card-body{max-width:600px;margin-left:auto;margin-right:auto;}
        @media (max-width:820px){.settings-section .card{max-width:100%;}}
    </style>
@endsection
