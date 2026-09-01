<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCollect - First-Time Setup</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="auth-logo-img">
            <h1>ECOCOLLECT</h1>
            <h2>First-Time Setup</h2>
            <p>Create the first Super Admin account to manage EcoCollect.</p>
            <div class="login-footer">Smart Waste Management for a Cleaner Tomorrow</div>
        </div>
        <div class="login-right">
            <div class="login-card">
                <h3>Create Super Admin</h3>
                <p class="subtitle">Set up your system administrator account</p>
                @if(session('error'))
                    <div role="alert" style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;">
                        {{ session('error') }}
                    </div>
                @endif
                @if($errors->any())
                    <div role="alert" style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('setup.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="setup-name">Full Name</label>
                        <div class="input-wrapper">
                            <input type="text" id="setup-name" name="name" placeholder="Enter full name" value="{{ old('name') }}" required autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="setup-username">Username</label>
                        <div class="input-wrapper">
                            <input type="text" id="setup-username" name="username" placeholder="Choose a username" value="{{ old('username') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="setup-email">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="setup-email" name="email" placeholder="Enter email address" value="{{ old('email') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="setup-password">Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="setup-password" name="password" placeholder="Create a password (min. 8 characters)" required>
                            <button type="button" class="password-toggle" data-target="setup-password" aria-label="Show or hide password">👁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="setup-password-confirmation">Confirm Password</label>
                        <div class="password-field-wrapper">
                            <input type="password" id="setup-password-confirmation" name="password_confirmation" placeholder="Confirm password" required>
                            <button type="button" class="password-toggle" data-target="setup-password-confirmation" aria-label="Show or hide confirm password">👁</button>
                        </div>
                    </div>
                    <div class="password-confirm-alert" style="display: none;">
                        Password didn't match. Try again.
                    </div>
                    <button type="submit" class="btn btn-success btn-block">Create Super Admin Account</button>
                    <div class="signup-link">
                        Already have an account? <a href="{{ route('login') }}">Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
