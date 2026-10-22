<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EcoCollect') - Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}?v=10">
    <link rel="shortcut icon" type="image/jpeg" href="{{ asset('image/Page-logo.jpg') }}?v=10">
    <link rel="apple-touch-icon" href="{{ asset('image/Page-logo.jpg') }}?v=10">
    <script>
        // Apply the persisted sidebar state before first paint to avoid a flash of the wrong width.
        try {
            if (localStorage.getItem('ecocollect.sidebar.collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>
</head>
<body>
    <a href="#main-content" class="skip-to-main">Skip to main content</a>

    <div class="sidebar-overlay" onclick="closeSidebar()" aria-hidden="true"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="{{ asset('image/ecocollect-logo.jpg') }}" alt="EcoCollect Logo" class="admin-sidebar-logo-img" width="38" height="38">
            <span class="brand-text">ECOCOLLECT</span>
            <button type="button" class="sidebar-collapse-btn" id="sidebar-collapse-btn" onclick="toggleSidebarCollapse()" aria-label="Collapse sidebar" title="Collapse sidebar" aria-expanded="true" aria-controls="sidebar-nav">
                <span class="collapse-icon collapse-icon-expand" aria-hidden="true"><x-icon name="chevrons-left" :size="18" /></span>
                <span class="collapse-icon collapse-icon-collapse" aria-hidden="true"><x-icon name="chevrons-right" :size="18" /></span>
            </button>
        </div>

        <nav class="sidebar-nav" id="sidebar-nav" aria-label="Main navigation">
            {{-- OVERVIEW --}}
            <div class="nav-group" role="group" aria-labelledby="nav-group-overview">
                <p class="nav-group-label" id="nav-group-overview">Overview</p>
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" data-label="Dashboard" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="layout-dashboard" :size="19" /></span>
                    <span class="nav-label">Dashboard</span>
                </a>
            </div>

            {{-- MANAGEMENT --}}
            <div class="nav-group" role="group" aria-labelledby="nav-group-management">
                <p class="nav-group-label" id="nav-group-management">Management</p>
                <a href="{{ route('admin.students') }}" class="nav-item {{ request()->routeIs('admin.students') ? 'active' : '' }}" data-label="Student" @if(request()->routeIs('admin.students')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="users" :size="19" /></span>
                    <span class="nav-label">Student</span>
                </a>
                <a href="{{ route('admin.bottle-collection') }}" class="nav-item {{ request()->routeIs('admin.bottle-collection') ? 'active' : '' }}" data-label="Bottle Collection" @if(request()->routeIs('admin.bottle-collection')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="recycle" :size="19" /></span>
                    <span class="nav-label">Bottle Collection</span>
                </a>
                <a href="{{ route('admin.certificate') }}" class="nav-item {{ request()->routeIs('admin.certificate') ? 'active' : '' }}" data-label="Certificate Award" @if(request()->routeIs('admin.certificate')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="award" :size="19" /></span>
                    <span class="nav-label">Certificate Award</span>
                </a>
                @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
                <a href="{{ route('claims.index') }}" class="nav-item {{ request()->routeIs('claims.*') ? 'active' : '' }}" data-label="Claim Items" @if(request()->routeIs('claims.*')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="gift" :size="19" /></span>
                    <span class="nav-label">Claim Items</span>
                </a>
                @endif
            </div>

            {{-- ANALYTICS --}}
            <div class="nav-group" role="group" aria-labelledby="nav-group-analytics">
                <p class="nav-group-label" id="nav-group-analytics">Analytics</p>
                <a href="{{ route('admin.reports') }}" class="nav-item {{ request()->routeIs('admin.reports') || request()->routeIs('admin.*-report') || request()->routeIs('admin.admin-activities') ? 'active' : '' }}" data-label="Reports" @if(request()->routeIs('admin.reports') || request()->routeIs('admin.*-report') || request()->routeIs('admin.admin-activities')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="chart-no-axes-combined" :size="19" /></span>
                    <span class="nav-label">Reports</span>
                </a>
            </div>

            @if(in_array(Auth::user()->role, ['admin', 'super_admin']))
            {{-- ADMINISTRATION --}}
            <div class="nav-group" role="group" aria-labelledby="nav-group-administration">
                <p class="nav-group-label" id="nav-group-administration">Administration</p>
                <a href="{{ route('admin.teachers') }}" class="nav-item {{ request()->is('admin/teachers*') || request()->is('admin/accounts*') ? 'active' : '' }}" data-label="Accounts" @if(request()->is('admin/teachers*') || request()->is('admin/accounts*')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="user-round-cog" :size="19" /></span>
                    <span class="nav-label">Accounts</span>
                </a>
                <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}" data-label="System Settings" @if(request()->routeIs('admin.settings')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="settings" :size="19" /></span>
                    <span class="nav-label">System Settings</span>
                </a>
            </div>
            @endif

            {{-- ACCOUNT --}}
            <div class="nav-group" role="group" aria-labelledby="nav-group-account">
                <p class="nav-group-label" id="nav-group-account">Account</p>
                <a href="{{ route('settings.edit') }}" class="nav-item {{ request()->routeIs('settings.edit') || request()->routeIs('settings.profile.update') || request()->routeIs('settings.password.update') ? 'active' : '' }}" data-label="Account Settings" @if(request()->routeIs('settings.edit') || request()->routeIs('settings.profile.update') || request()->routeIs('settings.password.update')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="circle-user-round" :size="19" /></span>
                    <span class="nav-label">Account Settings</span>
                </a>
                <a href="{{ route('admin.qrcode') }}" class="nav-item {{ request()->routeIs('admin.qrcode') ? 'active' : '' }}" data-label="QR Code" @if(request()->routeIs('admin.qrcode')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><x-icon name="qr-code" :size="19" /></span>
                    <span class="nav-label">QR Code</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-logout">
            <a href="#" class="nav-item nav-item-logout" data-label="Logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <span class="nav-icon" aria-hidden="true"><x-icon name="log-out" :size="19" /></span>
                <span class="nav-label">Logout</span>
            </a>
            <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar" role="banner">
            <div class="topbar-left">
                <button type="button" class="mobile-menu-toggle no-print" onclick="toggleSidebar()" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="sidebar">
                    <x-icon name="menu" :size="22" />
                </button>
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
    var SIDEBAR_COLLAPSED_KEY = 'ecocollect.sidebar.collapsed';
    var sidebarTooltip = null;

    function isDrawerMode() {
        return window.matchMedia('(max-width: 1024px)').matches;
    }

    function setScrollLock(on) {
        if (on && isDrawerMode()) {
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        } else {
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }
    }

    function toggleSidebar() {
        var open = !document.body.classList.contains('sidebar-open');
        document.body.classList.toggle('sidebar-open', open);
        var toggle = document.querySelector('.mobile-menu-toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        setScrollLock(open);
    }

    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
        var toggle = document.querySelector('.mobile-menu-toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
        setScrollLock(false);
    }

    function toggleSidebarCollapse() {
        var collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        try {
            localStorage.setItem(SIDEBAR_COLLAPSED_KEY, collapsed ? '1' : '0');
        } catch (e) {}
        updateSidebarCollapseUI();
        hideSidebarTooltip();
    }

    function updateSidebarCollapseUI() {
        var collapsed = document.documentElement.classList.contains('sidebar-collapsed');
        var btn = document.getElementById('sidebar-collapse-btn');
        if (btn) {
            var label = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
        }
    }

    function showSidebarTooltip(event) {
        if (!sidebarTooltip) return;
        if (!document.documentElement.classList.contains('sidebar-collapsed')) return;
        if (!window.matchMedia('(min-width: 1025px)').matches) return;

        var item = event.currentTarget;
        var label = item.getAttribute('data-label');
        if (!label) return;

        var rect = item.getBoundingClientRect();
        sidebarTooltip.textContent = label;
        sidebarTooltip.style.top = Math.round(rect.top + rect.height / 2) + 'px';
        sidebarTooltip.style.left = Math.round(rect.right + 12) + 'px';
        sidebarTooltip.classList.add('show');
    }

    function hideSidebarTooltip() {
        if (sidebarTooltip) {
            sidebarTooltip.classList.remove('show');
        }
    }

    function initSidebarTooltips() {
        sidebarTooltip = document.createElement('div');
        sidebarTooltip.className = 'sidebar-tooltip';
        sidebarTooltip.setAttribute('aria-hidden', 'true');
        document.body.appendChild(sidebarTooltip);

        document.querySelectorAll('#sidebar .nav-item[data-label]').forEach(function (item) {
            item.addEventListener('mouseenter', showSidebarTooltip);
            item.addEventListener('mouseleave', hideSidebarTooltip);
            item.addEventListener('focus', showSidebarTooltip);
            item.addEventListener('blur', hideSidebarTooltip);
        });

        window.addEventListener('scroll', hideSidebarTooltip, true);
    }

    function initSidebarDrawerClose() {
        document.querySelectorAll('#sidebar-nav .nav-item, .sidebar-logout .nav-item').forEach(function (link) {
            link.addEventListener('click', function () {
                if (document.body.classList.contains('sidebar-open')) {
                    closeSidebar();
                }
            });
        });
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeSidebar();
            hideSidebarTooltip();
        }
    });

    window.addEventListener('resize', function () {
        if (!isDrawerMode() && document.body.classList.contains('sidebar-open')) {
            closeSidebar();
        }
        hideSidebarTooltip();
    });

    document.addEventListener('DOMContentLoaded', function () {
        updateSidebarCollapseUI();
        initSidebarTooltips();
        initSidebarDrawerClose();

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
