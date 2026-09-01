<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EcoCollect') - Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}?v=10">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}?v=10">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}?v=10">
</head>
<body>
    <a href="#main-content" class="skip-to-main">Skip to main content</a>

    <div class="sidebar-overlay" onclick="closeSidebar()"></div>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="admin-sidebar-logo-img" width="46" height="46">
            <span class="brand-text">ECOCOLLECT</span>
        </div>
        <nav class="sidebar-nav" id="sidebar-nav" aria-label="Main navigation">
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.students') }}" class="nav-item {{ request()->routeIs('admin.students') ? 'active' : '' }}" @if(request()->routeIs('admin.students')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">👥</span>
                <span>Student</span>
            </a>
            <a href="{{ route('admin.bottle-collection') }}" class="nav-item {{ request()->routeIs('admin.bottle-collection') ? 'active' : '' }}" @if(request()->routeIs('admin.bottle-collection')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">🧴</span>
                <span>Bottle Collection</span>
            </a>
            <a href="{{ route('admin.certificate') }}" class="nav-item {{ request()->routeIs('admin.certificate') ? 'active' : '' }}" @if(request()->routeIs('admin.certificate')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">🏆</span>
                <span>Certificate Award</span>
            </a>
            @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
            <a href="{{ route('claims.index') }}" class="nav-item {{ request()->routeIs('claims.*') ? 'active' : '' }}" @if(request()->routeIs('claims.*')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">🎁</span>
                <span>Claim Items</span>
            </a>
            @endif
            <a href="{{ route('admin.reports') }}" class="nav-item {{ request()->routeIs('admin.reports') || request()->routeIs('admin.*-report') || request()->routeIs('admin.admin-activities') ? 'active' : '' }}" @if(request()->routeIs('admin.reports') || request()->routeIs('admin.*-report') || request()->routeIs('admin.admin-activities')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">📈</span>
                <span>Reports</span>
            </a>
            @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
            <a href="{{ route('admin.teachers') }}" class="nav-item {{ request()->is('admin/teachers*') || request()->is('admin/accounts*') ? 'active' : '' }}" @if(request()->is('admin/teachers*') || request()->is('admin/accounts*')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">👨‍🏫</span>
                <span>Accounts</span>
            </a>
            <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}" @if(request()->routeIs('admin.settings')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">⚙️</span>
                <span>System Settings</span>
            </a>
            @endif
            <a href="{{ route('settings.edit') }}" class="nav-item {{ request()->routeIs('settings.edit') || request()->routeIs('settings.profile.update') || request()->routeIs('settings.password.update') ? 'active' : '' }}" @if(request()->routeIs('settings.edit') || request()->routeIs('settings.profile.update') || request()->routeIs('settings.password.update')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">👤</span>
                <span>Account Settings</span>
            </a>
            <a href="{{ route('admin.qrcode') }}" class="nav-item {{ request()->routeIs('admin.qrcode') ? 'active' : '' }}" @if(request()->routeIs('admin.qrcode')) aria-current="page" @endif>
                <span class="nav-icon" aria-hidden="true">📱</span>
                <span>QR Code</span>
            </a>
        </nav>
        <div class="sidebar-logout">
            <a href="#" class="nav-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <span class="nav-icon" aria-hidden="true">🚪</span>
                <span>Logout</span>
            </a>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar" role="banner">
            <div class="topbar-left">
                <button type="button" class="mobile-menu-toggle no-print" onclick="toggleSidebar()" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="sidebar-nav">☰</button>
                <div class="page-title-area">
                    <h1>@yield('page-title', 'Dashboard')</h1>
                    <p>@yield('page-subtitle', 'Overview')</p>
                </div>
            </div>
            <div class="admin-profile">
                <div class="admin-info">
                    <div class="admin-name">{{ Auth::user()->name ?? 'Admin User' }}</div>
                    <div class="admin-role">{{ ucfirst(Auth::user()->role ?? 'Administrator') }}</div>
                </div>
                <div class="admin-avatar" aria-hidden="true">{{ substr(Auth::user()->name ?? 'AU', 0, 2) }}</div>
            </div>
        </header>

        <main id="main-content" class="page-content" role="main">
            @include('partials.alerts')
            @yield('content')
        </main>
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
        document.body.classList.toggle('sidebar-open');
        var toggle = document.querySelector('.mobile-menu-toggle');
        if (toggle) {
            var expanded = document.body.classList.contains('sidebar-open');
            toggle.setAttribute('aria-expanded', expanded);
        }
    }
    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
        var toggle = document.querySelector('.mobile-menu-toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
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
