<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Unavailable - EcoCollect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="auth-logo-img">
            <h1>ECOCOLLECT</h1>
            <h2>Welcome to EcoCollect!</h2>
            <p>Teacher account setup and activation.</p>
            <div class="login-footer">Smart Waste Management for a Cleaner Tomorrow</div>
        </div>

        <div class="login-right">
            <div class="login-card">
                @php
                    $expired = str_contains(strtolower($error), 'expired');
                    $title = $expired ? 'Invitation Link Expired' : 'Invitation Link Invalid';
                    $message = $expired
                        ? 'This account setup link has expired. Please contact your administrator to request a new invitation.'
                        : $error;
                @endphp
                <h3>{{ $title }}</h3>
                <p class="subtitle">{{ $message }}</p>

                <div style="margin-top:24px;padding:12px 14px;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;font-size:13px;color:#991b1b;" role="alert">
                    No account changes were made. Your invitation status remains unchanged until a new link is issued.
                </div>

                <a href="{{ route('login') }}" class="btn btn-primary btn-block" style="display:flex;align-items:center;justify-content:center;text-decoration:none;margin-top:24px;">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
