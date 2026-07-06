<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCollect - Verify Email</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="auth-logo-img">
            <h1>ECOCOLLECT</h1>
            <h2>Verify Your Email</h2>
            <p>Enter the 6-digit verification code sent to your email address.</p>
            <div class="login-footer">Smart Waste Management for a Cleaner Tomorrow</div>
        </div>
        <div class="login-right">
            <div class="login-card">
                <h3>Email Verification</h3>
                <p class="subtitle">We sent a verification code to <strong>{{ $email }}</strong></p>
                @if(session('success'))
                    <div class="alert-success show" style="margin-bottom:16px;">{{ session('success') }}</div>
                @endif
                @if(session('info'))
                    <div style="background:rgba(0,174,239,0.1);border:1px solid var(--blue);color:var(--blue-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;">{{ session('info') }}</div>
                @endif
                @if($errors->any())
                    <div style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('verification.verify') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="otp" id="otp">
                    <div class="form-group">
                        <label>Verification Code</label>
                        <div class="otp-boxes">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input" autofocus>
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input">
                        </div>
                        @error('otp')
                            <small style="color:var(--red);font-size:12px;display:block;margin-top:4px;">{{ $message }}</small>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-success btn-block">Verify Email</button>
                </form>
                <form method="POST" action="{{ route('verification.resend') }}" style="margin-top:16px;">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <button type="submit" style="background:none;border:none;color:var(--blue);cursor:pointer;font-size:13px;text-decoration:underline;display:block;margin:0 auto;">Resend verification code</button>
                </form>
                <div class="signup-link" style="margin-top:16px;">
                    <a href="{{ route('login') }}">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputs = document.querySelectorAll('.otp-input');
        const hiddenOtp = document.getElementById('otp');
        const form = document.querySelector('form');

        function updateHiddenOtp() {
            hiddenOtp.value = Array.from(inputs).map(input => input.value).join('');
        }

        inputs.forEach((input, index) => {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                updateHiddenOtp();
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData)
                    .getData('text')
                    .replace(/[^0-9]/g, '')
                    .slice(0, 6);
                pasted.split('').forEach((digit, i) => {
                    if (inputs[i]) inputs[i].value = digit;
                });
                updateHiddenOtp();
                if (pasted.length === 6) {
                    inputs[5].focus();
                } else if (inputs[pasted.length]) {
                    inputs[pasted.length].focus();
                }
            });
        });

        if (form) {
            form.addEventListener('submit', function () {
                updateHiddenOtp();
            });
        }
    });
    </script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
