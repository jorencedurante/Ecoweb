@extends('layouts.admin')

@section('title', 'EcoCollect - Account Settings')
@section('page-title', 'Account Settings')
@section('page-subtitle', 'Manage your personal account information and security settings.')

@section('content')
    @include('partials.alerts')

    {{-- Pending Email Change Banner --}}
    @if($user->pending_email)
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
        <span style="font-size:20px;flex-shrink:0;">📧</span>
        <div style="flex:1;">
            <div style="font-size:13px;color:#166534;font-weight:600;margin-bottom:4px;">Email Change Pending</div>
            <div style="font-size:14px;color:#166534;">A verification code was sent to your current email. <a href="{{ route('account.email-change.verify') }}" style="color:#00a94f;font-weight:600;text-decoration:underline;">Verify now</a> or <button type="button" onclick="document.getElementById('resendPendingForm').submit()" style="background:none;border:none;color:#00a94f;font-weight:600;cursor:pointer;padding:0;font-size:inherit;">resend code</button>.</div>
        </div>
    </div>
    <form id="resendPendingForm" method="POST" action="{{ route('account.email-change.resend') }}" style="display:none;">@csrf</form>
    @endif

    {{-- CARD 1: PROFILE INFORMATION --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-body" style="padding:28px 28px 24px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                <div style="width:40px;height:40px;background:#e7f9e9;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <x-icon name="user-round-cog" :size="20" style="color:#00C853;" />
                </div>
                <div>
                    <h3 style="font-size:18px;font-weight:700;color:#111827;margin:0 0 2px;">Profile Information</h3>
                    <p style="font-size:13px;color:#6b7280;margin:0;">Update your personal information and account details.</p>
                </div>
            </div>

            <form id="profileSettingsForm" action="{{ route('settings.profile.update') }}" method="POST" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;">
                @csrf

                {{-- Full Name --}}
                <div class="form-group">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" aria-label="Full name" style="width:100%;height:46px;padding:0 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;background:#fff;color:#111827;outline:none;" required>
                </div>

                {{-- Email Address --}}
                <div class="form-group" style="display:flex;flex-direction:column;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Email Address</label>
                    <div style="display:flex;gap:8px;align-items:flex-end;">
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" readonly aria-label="Email address" style="flex:1;height:46px;padding:0 14px;border:1px solid var(--border);border-radius:10px 0 0 10px;font-size:14px;background:#f5f5f5;color:#6b7280;outline:none;">
                        <button type="button" id="changeEmailBtn" class="btn btn-outline" style="height:46px;padding:0 20px;border:1px solid #00C853;color:#00C853;border-radius:0 10px 10px 0;background:#fff;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">Change Email</button>
                    </div>
                    <p style="font-size:12px;color:#6b7280;margin-top:6px;margin-left:0;">For security, changing your email requires verification and confirmation.</p>
                </div>

                {{-- Role --}}
                <div class="form-group">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Role</label>
                    <input type="text" value="{{ ucfirst($user->role) }}" readonly aria-label="Role" style="width:100%;height:46px;padding:0 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;background:#f5f5f5;color:#6b7280;outline:none;">
                </div>

                {{-- Position --}}
                @if($user->position)
                <div class="form-group">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Position</label>
                    <input type="text" value="{{ $user->position }}" readonly aria-label="Position" style="width:100%;height:46px;padding:0 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;background:#f5f5f5;color:#6b7280;outline:none;">
                </div>
                @endif
            </form>

            <div style="margin-top:8px;grid-column:1/-1;display:flex;justify-content:flex-end;">
                <button type="button" id="saveProfileBtn" class="btn btn-success" style="height:46px;padding:0 24px;font-size:14px;font-weight:600;">Save Changes</button>
            </div>
        </div>
    </div>

    {{-- CARD 2: SECURITY --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-body" style="padding:28px 28px 24px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
                <div style="width:40px;height:40px;background:#e7f9e9;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <x-icon name="shield-check" :size="20" style="color:#00C853;" />
                </div>
                <div>
                    <h3 style="font-size:18px;font-weight:700;color:#111827;margin:0 0 2px;">Security</h3>
                    <p style="font-size:13px;color:#6b7280;margin:0;">Manage your password and account security settings.</p>
                </div>
            </div>

            {{-- Light-green security panel --}}
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:24px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:16px;min-width:260px;">
                    <div style="width:56px;height:56px;background:#dcfce7;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <x-icon name="lock" :size="26" style="color:#00C853;" />
                    </div>
                    <div>
                        <h4 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 4px;">Account Password</h4>
                        <p style="font-size:13px;color:#6b7280;margin:0 0 2px;line-height:1.4;">Keep your account secure by using a strong password.</p>
                        <p style="font-size:12px;color:#6b7280;margin:0;line-height:1.4;">We recommend using a unique password that you don't use on other websites.</p>
                    </div>
                </div>
                <button type="button" id="changePasswordBtn" class="btn btn-outline" style="height:44px;padding:0 24px;border:1px solid #00C853;color:#00C853;border-radius:10px;background:#fff;font-size:14px;font-weight:600;cursor:pointer;flex-shrink:0;">Change Password</button>
            </div>
        </div>
    </div>

    {{-- CHANGE EMAIL MODAL --}}
    <div class="modal-overlay" id="changeEmailModalOverlay" aria-hidden="true">
        <div class="modal-content" style="max-width:480px;">
            <div class="modal-header" style="background:#f0fdf4;border-bottom-color:#bbf7d0;">
                <div style="width:48px;height:48px;background:#dcfce7;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-left:-8px;">
                    <x-icon name="mail" :size="24" style="color:#00C853;" />
                </div>
                <div style="flex:1;">
                    <h2 style="font-size:20px;font-weight:700;color:#111827;margin:0 0 4px;">Change Email Address</h2>
                    <p style="font-size:13px;color:#6b7280;margin:0;">Update your email address. A verification link will be sent to your new email.</p>
                </div>
                <button type="button" onclick="closeChangeEmailModal()" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="changeEmailForm" action="{{ route('settings.email-change.initiate') }}" method="POST" style="display:flex;flex-direction:column;gap:16px;">
                    @csrf

                    {{-- Current Email --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Current Email</label>
                        <input type="email" name="current_email" value="{{ $user->email }}" readonly style="width:100%;height:44px;padding:0 14px;border:1px solid var(--border);border-radius:8px;font-size:14px;background:#f5f5f5;color:#6b7280;outline:none;">
                    </div>

                    {{-- New Email --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">New Email Address</label>
                        <input type="email" name="new_email" id="newEmailInput" placeholder="Enter your new email address" aria-label="New email address" style="width:100%;height:44px;padding:0 14px;border:1px solid var(--border);border-radius:8px;font-size:14px;outline:none;" required>
                    </div>

                    {{-- Information panel --}}
                    <div style="background:#e8f4fd;border:1px solid #bae6fd;border-radius:8px;padding:14px 16px;margin-top:4px;">
                        <div style="display:flex;align-items:flex-start;gap:8px;">
                            <span style="font-size:16px;flex-shrink:0;">ℹ️</span>
                            <div style="flex:1;">
                                <div style="font-size:12px;font-weight:600;color:#0369a1;margin-bottom:4px;">Important Information</div>
                                <ul style="font-size:12px;color:#0369a1;margin:0;padding-left:16px;line-height:1.5;">
                                    <li style="margin-bottom:2px;">New email must be valid.</li>
                                    <li style="margin-bottom:2px;">New email must not already be in use.</li>
                                    <li style="margin-bottom:2px;">A verification email will be sent to the new address.</li>
                                    <li>Additional password confirmation may be required.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div id="emailError" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;color:#991b1b;font-size:13px;"></div>
                </form>
            </div>
            <div class="modal-footer" style="padding:18px 26px;">
                <button type="button" onclick="closeChangeEmailModal()" class="btn btn-secondary" style="height:44px;padding:0 20px;">Cancel</button>
                <button type="button" id="sendVerificationBtn" class="btn btn-primary" style="height:44px;padding:0 24px;">Send Verification</button>
            </div>
        </div>
    </div>

    {{-- CHANGE PASSWORD MODAL --}}
    <div class="modal-overlay" id="changePasswordModalOverlay" aria-hidden="true">
        <div class="modal-content" style="max-width:480px;">
            <div class="modal-header" style="background:#f0fdf4;border-bottom-color:#bbf7d0;">
                <div style="width:48px;height:48px;background:#dcfce7;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-left:-8px;">
                    <x-icon name="lock" :size="24" style="color:#00C853;" />
                </div>
                <div style="flex:1;">
                    <h2 style="font-size:20px;font-weight:700;color:#111827;margin:0 0 4px;">Change Password</h2>
                    <p style="font-size:13px;color:#6b7280;margin:0;">Update your password to keep your account secure.</p>
                </div>
                <button type="button" onclick="closeChangePasswordModal()" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm" action="{{ route('settings.password.update') }}" method="POST" style="display:flex;flex-direction:column;gap:16px;">
                    @csrf

                    {{-- Current Password --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Current Password</label>
                        <div class="password-field-wrapper" style="position:relative;">
                            <input type="password" name="current_password" id="cpCurrentPassword" placeholder="Enter your current password" aria-label="Current password" style="width:100%;height:44px;padding:0 40px 0 14px;border:1px solid var(--border);border-radius:8px;font-size:14px;outline:none;" required>
                            <button type="button" class="password-toggle" data-target="cpCurrentPassword" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;padding:4px;"><x-icon name="eye" :size="18" /></button>
                        </div>
                    </div>

                    {{-- New Password --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">New Password</label>
                        <div class="password-field-wrapper" style="position:relative;">
                            <input type="password" name="password" id="cpPassword" placeholder="At least 8 characters" aria-label="New password" style="width:100%;height:44px;padding:0 40px 0 14px;border:1px solid var(--border);border-radius:8px;font-size:14px;outline:none;" required>
                            <button type="button" class="password-toggle" data-target="cpPassword" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;padding:4px;"><x-icon name="eye" :size="18" /></button>
                        </div>
                    </div>

                    {{-- Confirm New Password --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Confirm New Password</label>
                        <div class="password-field-wrapper" style="position:relative;">
                            <input type="password" name="password_confirmation" id="cpPasswordConfirmation" placeholder="Confirm your new password" aria-label="Confirm new password" style="width:100%;height:44px;padding:0 40px 0 14px;border:1px solid var(--border);border-radius:8px;font-size:14px;outline:none;" required>
                            <button type="button" class="password-toggle" data-target="cpPasswordConfirmation" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6b7280;cursor:pointer;padding:4px;"><x-icon name="eye" :size="18" /></button>
                        </div>
                    </div>

                    {{-- Password requirements panel --}}
                    <div style="background:#e8f4fd;border:1px solid #bae6fd;border-radius:8px;padding:14px 16px;">
                        <div style="display:flex;align-items:flex-start;gap:8px;">
                            <span style="font-size:16px;flex-shrink:0;">🔒</span>
                            <div style="flex:1;">
                                <div style="font-size:12px;font-weight:600;color:#0369a1;margin-bottom:4px;">Password Requirements</div>
                                <ul id="passwordRequirements" style="font-size:12px;color:#0369a1;margin:0;padding-left:16px;line-height:1.6;">
                                    <li id="reqLength" style="margin-bottom:2px;">At least 8 characters</li>
                                    <li id="reqCase" style="margin-bottom:2px;">Uppercase and lowercase letters</li>
                                    <li id="reqNumber" style="margin-bottom:2px;">At least one number</li>
                                    <li id="reqMatch" style="margin-bottom:2px;">Confirmation must match</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div id="passwordError" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;color:#991b1b;font-size:13px;"></div>
                </form>
            </div>
            <div class="modal-footer" style="padding:18px 26px;">
                <button type="button" onclick="closeChangePasswordModal()" class="btn btn-secondary" style="height:44px;padding:0 20px;">Cancel</button>
                <button type="button" id="updatePasswordBtn" class="btn btn-success" style="height:44px;padding:0 24px;">Update Password</button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // ---------- Modal Functions ----------
        var lastOpenedEmailBtn = null;
        var lastOpenedPasswordBtn = null;

        function openChangeEmailModal() {
            document.getElementById('changeEmailModalOverlay').classList.add('show');
            document.getElementById('changeEmailModalOverlay').setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.getElementById('newEmailInput').focus();
            document.getElementById('emailError').style.display = 'none';
        }

        function closeChangeEmailModal() {
            document.getElementById('changeEmailModalOverlay').classList.remove('show');
            document.getElementById('changeEmailModalOverlay').setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            document.getElementById('changeEmailForm').reset();
            document.getElementById('emailError').style.display = 'none';
            if (lastOpenedEmailBtn) {
                lastOpenedEmailBtn.focus();
            }
        }

        function openChangePasswordModal() {
            document.getElementById('changePasswordModalOverlay').classList.add('show');
            document.getElementById('changePasswordModalOverlay').setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.getElementById('cpCurrentPassword').focus();
            document.getElementById('passwordError').style.display = 'none';
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModalOverlay').classList.remove('show');
            document.getElementById('changePasswordModalOverlay').setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            document.getElementById('changePasswordForm').reset();
            document.getElementById('passwordError').style.display = 'none';
            document.getElementById('reqLength').style.color = '#6b7280';
            document.getElementById('reqCase').style.color = '#6b7280';
            document.getElementById('reqNumber').style.color = '#6b7280';
            document.getElementById('reqMatch').style.color = '#6b7280';
            // Reset eye icons to hidden state
            document.querySelectorAll('#changePasswordModalOverlay .password-toggle').forEach(function(btn) {
                var svg = btn.querySelector('svg');
                if (svg) {
                    svg.outerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>';
                }
                var input = document.getElementById(btn.getAttribute('data-target'));
                if (input) input.type = 'password';
            });
            if (lastOpenedPasswordBtn) {
                lastOpenedPasswordBtn.focus();
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('changeEmailModalOverlay').classList.contains('show')) {
                    closeChangeEmailModal();
                }
                if (document.getElementById('changePasswordModalOverlay').classList.contains('show')) {
                    closeChangePasswordModal();
                }
            }
        });

        // Close modal when clicking outside
        document.getElementById('changeEmailModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeChangeEmailModal();
        });
        document.getElementById('changePasswordModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeChangePasswordModal();
        });

        // Open modals from card buttons
        document.getElementById('changeEmailBtn')?.addEventListener('click', function() {
            lastOpenedEmailBtn = this;
            openChangeEmailModal();
        });
        document.getElementById('changePasswordBtn')?.addEventListener('click', function() {
            lastOpenedPasswordBtn = this;
            openChangePasswordModal();
        });

        // ---------- Password Toggle ----------
        document.querySelectorAll('.password-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                if (!input) return;
                var type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                var icon = this.querySelector('svg');
                if (icon) {
                    icon.outerHTML = type === 'password'
                        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>'
                        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="true"><path d="M17.94 11.34a10 10 0 0 1-4.11 0 10.75 10.75 0 0 1-5.88-1.86M17.94 3c-.51 0-1 .45-1 1s.45 1 1 1"/><circle cx="12" cy="12" r="3"/><path d="M1 1l15 15M4.93 4.93 19.07 19.07"/></svg>';
                }
            });
        });

        // ---------- Password Requirements Real-time Validation ----------
        var newPasswordInput = document.getElementById('cpPassword');
        var confirmPasswordInput = document.getElementById('cpPasswordConfirmation');

        function checkPasswordRequirements() {
            var val = newPasswordInput.value;
            var len = val.length >= 8;
            var hasUpper = /[A-Z]/.test(val);
            var hasLower = /[a-z]/.test(val);
            var hasNumber = /[0-9]/.test(val);
            var caseOk = hasUpper && hasLower;

            var reqLength = document.getElementById('reqLength');
            var reqCase = document.getElementById('reqCase');
            var reqNumber = document.getElementById('reqNumber');
            var reqMatch = document.getElementById('reqMatch');

            setReqState(reqLength, len);
            setReqState(reqCase, caseOk);
            setReqState(reqNumber, hasNumber);

            var confirmVal = confirmPasswordInput.value;
            var matchOk = confirmVal !== '' && val === confirmVal;
            setReqState(reqMatch, matchOk);
        }

        function setReqState(el, ok) {
            if (ok) {
                el.style.color = '#00C853';
                el.style.fontWeight = '600';
            } else {
                el.style.color = '#6b7280';
                el.style.fontWeight = '400';
            }
        }

        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', checkPasswordRequirements);
        }
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', checkPasswordRequirements);
        }

        // ---------- Profile Save (AJAX) ----------
        document.getElementById('saveProfileBtn').addEventListener('click', function() {
            var btn = this;
            var form = document.getElementById('profileSettingsForm');
            var formData = new FormData(form);

            btn.disabled = true;
            btn.innerHTML = 'Saving...';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast(data.message || 'Profile updated successfully.', false);
                } else {
                    showToast(data.message || 'Update failed.', true);
                }
            })
            .catch(function() {
                showToast('An error occurred. Please try again.', true);
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = 'Save Changes';
            });
        });

        // ---------- Change Email (AJAX) ----------
        document.getElementById('sendVerificationBtn').addEventListener('click', function() {
            var btn = this;
            var form = document.getElementById('changeEmailForm');
            var formData = new FormData(form);

            btn.disabled = true;
            btn.innerHTML = 'Sending...';
            document.getElementById('emailError').style.display = 'none';

            fetch(form.action || '{{ route('settings.email-change.initiate') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast(data.message || 'Verification email sent.', false);
                    closeChangeEmailModal();
                    window.location.href = '{{ route('account.email-change.verify') }}';
                } else {
                    var errBox = document.getElementById('emailError');
                    errBox.textContent = data.message || 'Failed to send verification email.';
                    errBox.style.display = 'block';
                }
            })
            .catch(function() {
                var errBox = document.getElementById('emailError');
                errBox.textContent = 'An error occurred. Please try again.';
                errBox.style.display = 'block';
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = 'Send Verification';
            });
        });

        // ---------- Change Password (AJAX) ----------
        document.getElementById('updatePasswordBtn').addEventListener('click', function() {
            var btn = this;
            var form = document.getElementById('changePasswordForm');
            var formData = new FormData(form);

            btn.disabled = true;
            btn.innerHTML = 'Updating...';
            document.getElementById('passwordError').style.display = 'none';

            fetch(form.action || '{{ route('settings.password.update') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast(data.message || 'Password updated successfully.', false);
                    closeChangePasswordModal();
                } else {
                    var errBox = document.getElementById('passwordError');
                    errBox.textContent = data.message || 'Password update failed.';
                    errBox.style.display = 'block';
                }
            })
            .catch(function() {
                var errBox = document.getElementById('passwordError');
                errBox.textContent = 'An error occurred. Please try again.';
                errBox.style.display = 'block';
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = 'Update Password';
            });
        });

        // ---------- Toast Notification ----------
        function showToast(message, isError) {
            var toast = document.createElement('div');
            toast.className = 'auto-dismiss-alert';
            toast.style.background = isError ? 'rgba(239,83,80,0.08)' : 'rgba(0,200,83,0.08)';
            toast.style.border = '1px solid ' + (isError ? 'var(--red)' : 'var(--green)');
            toast.style.color = isError ? 'var(--red-dark)' : 'var(--green-dark)';
            toast.style.padding = '12px 16px';
            toast.style.borderRadius = '8px';
            toast.style.fontSize = '14px';
            toast.style.fontWeight = '500';
            toast.style.marginBottom = '16px';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '8px';
            toast.innerHTML = '<span aria-hidden="true">' + (isError ? '❌' : '✅') + '</span><span>' + message + '</span>';
            document.getElementById('main-content').insertBefore(toast, document.getElementById('main-content').firstChild);
            setTimeout(function() {
                toast.style.opacity = '0';
                setTimeout(function() { toast.remove(); }, 500);
            }, 4000);
        }
    </script>
    @endpush
@endsection