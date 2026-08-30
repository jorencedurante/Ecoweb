<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EcoCollect') - Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}?v=10">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}?v=10">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}?v=10">
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle no-print" onclick="toggleSidebar()" aria-label="Toggle menu">☰</button>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="admin-sidebar-logo-img">
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
            @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
            <a href="{{ route('claims.index') }}" class="nav-item {{ request()->routeIs('claims.*') ? 'active' : '' }}">
                <span class="nav-icon">🎁</span>
                <span>Claim Items</span>
            </a>
            @endif
            <a href="{{ route('admin.reports') }}" class="nav-item {{ request()->routeIs('admin.reports') || request()->routeIs('admin.*-report') || request()->routeIs('admin.admin-activities') ? 'active' : '' }}">
                <span class="nav-icon">📈</span>
                <span>Reports</span>
            </a>
            @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
            <a href="{{ route('admin.teachers') }}" class="nav-item {{ request()->is('admin/teachers*') || request()->is('admin/accounts*') ? 'active' : '' }}">
                <span class="nav-icon">👨‍🏫</span>
                <span>Accounts</span>
            </a>
            <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <span class="nav-icon">⚙️</span>
                <span>System Settings</span>
            </a>
            @endif
            <a href="{{ route('settings.edit') }}" class="nav-item {{ request()->routeIs('settings.edit') || request()->routeIs('settings.profile.update') || request()->routeIs('settings.password.update') ? 'active' : '' }}">
                <span class="nav-icon">👤</span>
                <span>Account Settings</span>
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
            @include('partials.alerts')
            @yield('content')
        </div>
    </div>

    <style>
    .auto-dismiss-alert {
        transition: opacity 0.5s ease, transform 0.5s ease;
    }
    .auto-dismiss-alert.fade-out {
        opacity: 0;
        transform: translateY(-8px);
    }
    </style>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    <script>
    function toggleSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.querySelector('.sidebar-overlay');
        var toggle = document.querySelector('.mobile-menu-toggle');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        if (sidebar.classList.contains('open')) {
            toggle.classList.add('hidden');
        } else {
            toggle.classList.remove('hidden');
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        var alerts = document.querySelectorAll('.auto-dismiss-alert');
        alerts.forEach(function (alert) {
            setTimeout(function () {
                alert.classList.add('fade-out');
                setTimeout(function () {
                    alert.remove();
                }, 500);
            }, 4000);
        });
    });
    </script>
    @stack('scripts')
</body>
</html>
