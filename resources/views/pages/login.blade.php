<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCollect - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <div class="login-logo">EC</div>
            <h1>ECOCOLLECT</h1>
            <h2>Welcome Back!</h2>
            <p>Sign in to continue to your account and access the dashboard.</p>
            <div class="login-footer">Smart Waste Management for a Cleaner Tomorrow</div>
        </div>
        <div class="login-right">
            <div class="login-card">
                <h3>Login to Your Account</h3>
                <p class="subtitle">Enter your credentials to continue</p>
                <form onsubmit="event.preventDefault(); alert('Login placeholder - no authentication yet'); window.location.href='{{ route('admin.dashboard') }}';">
                    <div class="form-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <span class="input-icon">✉</span>
                            <input type="email" placeholder="Enter your email" value="admin@ecocollect.edu">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" placeholder="Enter your password" value="password123">
                            <button type="button" class="toggle-pw">👁</button>
                        </div>
                    </div>
                    <div class="checkbox-row">
                        <label><input type="checkbox" checked> Remember me</label>
                        <a href="#">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </form>
                <p class="signup-link">Don't have an account? <a href="#">Create Account</a></p>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
