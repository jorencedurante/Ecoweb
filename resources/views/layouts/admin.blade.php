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
            <a href="{{ route('claims.index') }}" class="nav-item {{ request()->routeIs('claims.*') ? 'active' : '' }}">
                <span class="nav-icon">🎁</span>
                <span>Claim Items</span>
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
                <span>Accounts</span>
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
            <a href="#" class="nav-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>
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
                    <div class="admin-name">{{ Auth::user()->name ?? 'Admin User' }}</div>
                    <div class="admin-role">{{ ucfirst(Auth::user()->role ?? 'Administrator') }}</div>
                </div>
                <div class="admin-avatar">{{ substr(Auth::user()->name ?? 'AU', 0, 2) }}</div>
            </div>
        </header>

        <div class="page-content">
            @if(session('success'))
                <div class="alert-success show" style="margin-bottom:16px;">✅ {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div style="background:rgba(239,83,80,0.1);border:1px solid var(--red);color:var(--red-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:16px;">❌ {{ session('error') }}</div>
            @endif
            @if(session('info'))
                <div style="background:rgba(0,174,239,0.1);border:1px solid var(--blue);color:var(--blue-dark);padding:12px 16px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:16px;">ℹ️ {{ session('info') }}</div>
            @endif
            @yield('content')
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
