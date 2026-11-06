<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCollect - Login</title>
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
            <h2>Welcome Back!</h2>
            <p>Sign in to continue to your account and access the dashboard.</p>
            <div class="login-footer">Smart Waste Management for a Cleaner Tomorrow</div>
        </div>
        <div class="login-right">
            <div class="login-card">
                <h3>Login to Your Account</h3>
                <p class="subtitle">Enter your credentials to continue</p>
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
                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf
                    <div class="form-group">
                        <label for="login">Email or Username</label>
                        <div class="input-wrapper">
                            <input type="text" id="login" name="login" placeholder="Enter email or username" value="{{ old('login') }}" required autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password-login">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password-login" name="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-pw" aria-label="Toggle password visibility">👁</button>
                        </div>
                    </div>
                    <div class="checkbox-row">
                        <label><input type="checkbox" name="remember" checked> Remember me</label>
                        <a href="{{ route('password.request') }}">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                    <div class="signup-link">
                        Don't have an account? <a href="{{ route('register') }}">Create an account</a>
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
