<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your Teacher Account - EcoCollect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <style>
        .setup-info {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #166534;
            line-height: 1.6;
        }
        .setup-info strong { color: #14532d; }
        .setup-card label span.required { color: #dc2626; }
        .setup-submit {
            background: #16a34a;
            color: #ffffff;
            border: none;
        }
        .setup-submit:hover { background: #15803d; }
        .setup-submit:disabled { opacity: 0.7; cursor: not-allowed; }
        .setup-error { color: #b91c1c; font-size: 13px; margin-bottom: 14px; }
        .setup-error ul { margin: 0; padding-left: 18px; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="auth-logo-img">
            <h1>ECOCOLLECT</h1>
            <h2>Welcome to EcoCollect!</h2>
            <p>Set up your teacher account and start using EcoCollect.</p>
            <div class="login-footer">Smart Waste Management for a Cleaner Tomorrow</div>
        </div>

        <div class="login-right">
            <div class="login-card setup-card">
                <h3>Set Up Your Teacher Account</h3>
                <p class="subtitle">Verify your email and create your password to activate your account.</p>

                <div class="setup-info">
                    <div>Teacher: <strong>{{ $user->name }}</strong></div>
                    <div>Email: <strong>{{ $user->email }}</strong></div>
                </div>

                @if($errors->any())
                    <div class="setup-error" role="alert">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="teacherSetupForm" method="POST" action="{{ url()->full() }}">
                    @csrf
                    <div class="form-group">
                        <label for="password">New Password <span class="required" aria-hidden="true">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your new password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="toggle-pw" aria-label="Show password">👁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password <span class="required" aria-hidden="true">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Re-enter your new password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="toggle-pw" aria-label="Show password">👁</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-block setup-submit" id="setupSubmit">Activate Account</button>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        document.getElementById('teacherSetupForm').addEventListener('submit', function () {
            var button = document.getElementById('setupSubmit');
            if (!button || button.disabled) return;
            button.disabled = true;
            button.textContent = 'Activating Account...';
        });
    </script>
</body>
</html>
