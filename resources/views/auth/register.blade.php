<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCollect - Register</title>
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
            <h2>Create Account</h2>
            <p>Join the EcoCollect community and help make waste management smarter.</p>
            <div class="login-footer">Smart Waste Management for a Cleaner Tomorrow</div>
        </div>
        <div class="login-right">
            <div class="login-card">
                <h3>Create Your Account</h3>
                <p class="subtitle">Fill in the details to get started</p>
                @if($errors->any())
                    <div style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('register.submit') }}">
                    @csrf
                    <div class="form-group">
                        <label>Full Name</label>
                        <div class="input-wrapper">
                            <input type="text" name="name" placeholder="Enter your full name" value="{{ old('name') }}" required autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <div class="input-wrapper">
                            <input type="text" name="username" placeholder="Choose a username" value="{{ old('username') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required>
                        </div>
                        @error('email')
                            <small style="color:var(--red);font-size:12px;margin-top:4px;display:block;">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" placeholder="Create a password (min. 8 characters)" required>
                            <button type="button" class="toggle-pw">👁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password_confirmation" placeholder="Confirm your password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <div class="input-wrapper">
                            <input type="text" name="position" placeholder="Example: Teacher 1 or Teacher 2" value="{{ old('position') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">Create Account</button>
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
