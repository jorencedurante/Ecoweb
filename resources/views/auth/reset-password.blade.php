<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCollect - Reset Password</title>
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
            <h2>Set New Password</h2>
            <p>Choose a strong password for your account.</p>
            <div class="login-footer">Smart Waste Management for a Cleaner Tomorrow</div>
        </div>
        <div class="login-right">
            <div class="login-card">
                <h3>Set New Password</h3>
                <p class="subtitle">Enter your new password below</p>
                @if($errors->any())
                    <div class="auto-dismiss-alert" role="alert" style="background:rgba(239,83,80,0.08);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="form-group">
                        <label for="reset-email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="reset-email" name="email" placeholder="Enter your email" value="{{ old('email', $email ?? '') }}" required autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reset-password">New Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" name="password" id="reset-password" placeholder="New password (min. 8 characters)" required>
                            <button type="button" class="password-toggle" data-target="reset-password" aria-label="Toggle password visibility">👁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reset-password-confirmation">Confirm New Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" name="password_confirmation" id="reset-password-confirmation" placeholder="Confirm new password" required>
                            <button type="button" class="password-toggle" data-target="reset-password-confirmation" aria-label="Toggle password visibility">👁</button>
                        </div>
                    </div>
                    <div class="password-confirm-alert" style="display: none;">
                        Password didn't match. Try again.
                    </div>
                    <button type="submit" class="btn btn-success btn-block">Reset Password</button>
                    <div class="signup-link">
                        <a href="{{ route('login') }}">← Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <style>
    .auto-dismiss-alert { transition: opacity 0.5s ease, transform 0.5s ease; }
    .auto-dismiss-alert.fade-out { opacity: 0; transform: translateY(-8px); }
    </style>
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.auto-dismiss-alert').forEach(function (el) {
            setTimeout(function () {
                el.classList.add('fade-out');
                setTimeout(function () { el.remove(); }, 500);
            }, 4000);
        });
    });
    </script>
</body>
</html>
