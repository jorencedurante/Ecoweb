<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCollect - Forgot Password</title>
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
            <h2>Forgot Password?</h2>
            <p>No worries. Enter your email and we'll send you a reset link.</p>
            <div class="login-footer">Smart Waste Management for a Cleaner Tomorrow</div>
        </div>
        <div class="login-right">
            <div class="login-card">
                <h3>Reset Password</h3>
                <p class="subtitle">Enter your registered email address</p>
                @if(session('success'))
                    <div class="auto-dismiss-alert" role="alert" style="background:rgba(0,200,83,0.08);border:1px solid #22c55e;color:#166534;padding:12px 16px;border-radius:8px;font-size:14px;font-weight:500;margin-bottom:16px;">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="auto-dismiss-alert" role="alert" style="background:rgba(239,83,80,0.08);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="form-group">
                        <label for="forgot-email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="forgot-email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required autofocus>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Send Password Reset Link</button>
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
