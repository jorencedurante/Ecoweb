@extends('layouts.admin')

@section('title', 'EcoCollect - Verify Email Change')
@section('page-title', 'Verify Email Change')
@section('page-subtitle', 'Enter the verification code sent to your current email address')

@section('content')
<div style="display:flex;justify-content:center;padding:16px 0;">
    <div style="width:100%;max-width:480px;">
        <div style="background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.06);border:1px solid #e5e7eb;overflow:hidden;">
            <div style="padding:28px 28px 0;">
                <h4 style="font-size:17px;font-weight:700;margin:0 0 4px;">Confirm Email Change</h4>
                <p style="font-size:13px;color:#6b7280;margin:0 0 20px;">Enter the 6-digit verification code sent to your current email address to confirm this change.</p>

                @if($user->pending_email)
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#166534;display:flex;align-items:center;gap:8px;">
                    <span style="font-size:16px;flex-shrink:0;">📧</span>
                    <div>
                        <div style="font-size:12px;font-weight:500;margin-bottom:2px;">Pending new email address</div>
                        <strong style="font-weight:700;">{{ $user->pending_email }}</strong>
                    </div>
                </div>
                @endif

                @error('otp')
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#991b1b;display:flex;align-items:center;gap:8px;">
                    <span style="font-size:14px;">⚠️</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror
            </div>

            <form method="POST" action="{{ route('account.email-change.confirm') }}" id="emailChangeVerifyForm">
                @csrf

                <div style="padding:0 28px;">
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:12px;">Verification Code</label>

                    <div style="display:flex;gap:8px;justify-content:center;margin-bottom:8px;" id="otpContainer">
                        @for($i = 1; $i <= 6; $i++)
                        <input type="text"
                               id="otp_{{ $i }}"
                               class="otp-input"
                               maxlength="1"
                               inputmode="numeric"
                               autocomplete="off"
                               data-index="{{ $i }}"
                               style="width:48px;height:52px;text-align:center;font-size:22px;font-weight:700;border:2px solid #d1d5db;border-radius:10px;outline:none;background:#fff;color:#111827;transition:border-color 0.2s,box-shadow 0.2s;"
                               @if($i === 1) autofocus @endif>
                        @endfor
                    </div>

                    <input type="hidden" name="otp" id="otp_hidden">

                    <div style="font-size:12px;color:#9ca3af;text-align:center;margin-bottom:20px;">The code expires in 10 minutes.</div>
                </div>

                <div style="padding:0 28px 28px;">
                    <button type="submit" id="verifyBtn" style="width:100%;padding:12px 24px;background:#22c55e;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:background 0.15s;">Verify & Change Email</button>
                </div>
            </form>

            <div style="padding:0 28px 28px;border-top:1px solid #f3f4f6;padding-top:20px;">
                <div style="display:flex;gap:10px;">
                    <form method="POST" action="{{ route('account.email-change.resend') }}" style="flex:1;" id="resendEmailChangeForm">
                        @csrf
                        <button type="submit" style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:10px;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;transition:background 0.15s;">Resend Code</button>
                    </form>
                    <form method="POST" action="{{ route('account.email-change.cancel') }}" style="flex:1;" id="cancelEmailChangeForm">
                        @csrf
                        <button type="submit" style="width:100%;padding:10px;border:1.5px solid #fecaca;border-radius:10px;background:#fff;color:#dc2626;font-size:13px;font-weight:600;cursor:pointer;transition:background 0.15s;" onclick="return confirm('Cancel email change?')">Cancel</button>
                    </form>
                </div>

                <div style="margin-top:16px;text-align:center;">
                    <a href="{{ route('settings.edit') }}" style="font-size:13px;color:#6b7280;text-decoration:none;">← Back to Account Settings</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
.otp-input:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12) !important;
    background: #fff !important;
}
.otp-input.filled {
    border-color: #6366f1 !important;
    background: #f8f8ff !important;
}
.otp-input.has-error {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.12) !important;
}
#verifyBtn:hover {
    background: #16a34a !important;
}
#verifyBtn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
<script>
(function () {
    const inputs = document.querySelectorAll('.otp-input');
    const hidden = document.getElementById('otp_hidden');
    const verifyForm = document.getElementById('emailChangeVerifyForm');
    const verifyBtn = document.getElementById('verifyBtn');

    function updateHidden() {
        let code = '';
        inputs.forEach(function (inp) {
            code += inp.value || '';
        });
        hidden.value = code;
        verifyBtn.disabled = code.length !== 6;
    }

    function focusNext(idx) {
        if (idx < 6) {
            document.getElementById('otp_' + (idx + 1)).focus();
        }
    }

    function focusPrev(idx) {
        if (idx > 1) {
            document.getElementById('otp_' + (idx - 1)).focus();
        }
    }

    verifyForm.addEventListener('submit', function () {
        updateHidden();
    });

    inputs.forEach(function (inp) {
        inp.addEventListener('input', function () {
            var val = this.value.replace(/\D/g, '');
            this.value = val.slice(0, 1);
            if (val.length >= 1) {
                this.classList.add('filled');
                this.classList.remove('has-error');
                focusNext(parseInt(this.dataset.index));
            } else {
                this.classList.remove('filled');
            }
            updateHidden();
        });

        inp.addEventListener('keydown', function (e) {
            var idx = parseInt(this.dataset.index);
            if (e.key === 'Backspace') {
                if (this.value === '') {
                    focusPrev(idx);
                } else {
                    this.value = '';
                    this.classList.remove('filled');
                    updateHidden();
                }
                e.preventDefault();
            }
            if (e.key === 'ArrowLeft') { focusPrev(idx); e.preventDefault(); }
            if (e.key === 'ArrowRight') { focusNext(idx); e.preventDefault(); }
        });

        inp.addEventListener('paste', function (e) {
            e.preventDefault();
            var paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (!paste) return;
            inputs.forEach(function (input, i) {
                input.value = paste[i] || '';
                if (paste[i]) { input.classList.add('filled'); input.classList.remove('has-error'); }
                else { input.classList.remove('filled'); }
            });
            var lastIdx = Math.min(paste.length, 6);
            if (lastIdx < 6) {
                document.getElementById('otp_' + (lastIdx + 1)).focus();
            } else {
                document.getElementById('otp_6').focus();
            }
            updateHidden();
        });
    });

    updateHidden();
})();
</script>
@endpush
