<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EcoCollect') - Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="logo-circle">EC</div>
            <span class="brand-text">ECOCOLLECT</span>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.students') }}" class="nav-item {{ request()->routeIs('admin.students') ? 'active' : '' }}">
                <span class="nav-icon">👥</span>
                <span>Student</span>
            </a>
            <a href="{{ route('admin.bottle-collection') }}" class="nav-item {{ request()->routeIs('admin.bottle-collection') ? 'active' : '' }}">
                <span class="nav-icon">🧴</span>
                <span>Bottle Collection</span>
            </a>
            <a href="{{ route('admin.certificate') }}" class="nav-item {{ request()->routeIs('admin.certificate') ? 'active' : '' }}">
                <span class="nav-icon">🏆</span>
                <span>Certificate Award</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="nav-item {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                <span class="nav-icon">📈</span>
                <span>Reports</span>
            </a>
            <a href="{{ route('admin.teachers') }}" class="nav-item {{ request()->routeIs('admin.teachers') ? 'active' : '' }}">
                <span class="nav-icon">🔐</span>
                <span>Admin</span>
            </a>
            <a href="{{ route('admin.teachers') }}" class="nav-item {{ request()->routeIs('admin.teachers') ? 'active' : '' }}">
                <span class="nav-icon">👨‍🏫</span>
                <span>Teachers</span>
            </a>
            <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <span class="nav-icon">⚙️</span>
                <span>Settings</span>
            </a>
            <a href="{{ route('admin.qrcode') }}" class="nav-item {{ request()->routeIs('admin.qrcode') ? 'active' : '' }}">
                <span class="nav-icon">📱</span>
                <span>QR Code</span>
            </a>
        </nav>
        <div class="sidebar-logout">
            <a href="{{ route('login') }}" class="nav-item">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header class="topbar">
            <div class="page-title-area">
                <h2>@yield('page-title', 'Dashboard')</h2>
                <p>@yield('page-subtitle', 'Overview')</p>
            </div>
            <div class="admin-profile">
                <div class="admin-info">
                    <div class="admin-name">Admin User</div>
                    <div class="admin-role">Administrator</div>
                </div>
                <div class="admin-avatar">AU</div>
            </div>
        </header>

        <div class="page-content">
            @yield('content')
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
