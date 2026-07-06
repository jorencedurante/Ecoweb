@extends('layouts.admin')

@section('title', 'EcoCollect - Account Settings')
@section('page-title', 'Account Settings')
@section('page-subtitle', 'Manage your personal account information')

@section('content')
    @if($errors->any())
        <div style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card" style="max-width:600px;">
        <div class="card-body">
            <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Profile Information</h4>
            <form method="POST" action="{{ route('settings.profile.update') }}" id="profileSettingsForm">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" data-original-email="{{ $user->email }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" required>
                    @if(is_null($user->email_verified_at))
                        <div style="background:rgba(255,193,7,0.12);border:1px solid var(--yellow);color:#856404;padding:10px 14px;border-radius:var(--radius-sm);font-size:13px;margin-top:8px;">
                            Your email address is not verified yet.
                        </div>
                        <form method="POST" action="{{ route('verification.resend') }}" style="margin-top:8px;">
                            @csrf
                            <button type="submit" class="btn btn-primary" style="font-size:13px;padding:8px 16px;">Send Verification Code</button>
                        </form>
                    @else
                        <div style="background:rgba(0,200,83,0.12);border:1px solid var(--green);color:var(--green);padding:10px 14px;border-radius:var(--radius-sm);font-size:13px;margin-top:8px;">
                            Email Verified
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="{{ ucfirst($user->role) }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#F0F0F0;" readonly disabled>
                </div>
                @if($user->position)
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" value="{{ $user->position }}" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#F0F0F0;" readonly disabled>
                </div>
                @endif
                <button type="submit" class="btn btn-success" id="saveProfileBtn">Save Profile</button>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('profileSettingsForm').addEventListener('submit', function(e) {
            const emailInput = this.querySelector('input[name="email"]');
            if (emailInput.value !== emailInput.dataset.originalEmail) {
                if (!confirm('Are you sure you want to change your email address? You will use this new email the next time you login.')) {
                    e.preventDefault();
                }
            }
        });
    </script>
    @endpush

    <div class="card" style="max-width:600px;margin-top:24px;">
        <div class="card-body">
            <h4 style="font-size:15px;font-weight:600;margin-bottom:16px;">Change Password</h4>
            <form method="POST" action="{{ route('settings.password.update') }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="current_password" placeholder="Enter current password" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" required>
                        <button type="button" class="toggle-pw" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);">👁</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" placeholder="Enter new password (min. 8 characters)" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" required>
                        <button type="button" class="toggle-pw" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);">👁</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password_confirmation" placeholder="Confirm new password" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:14px;background:#FAFAFA;" required>
                        <button type="button" class="toggle-pw" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Update Password</button>
            </form>
        </div>
    </div>
@endsection
