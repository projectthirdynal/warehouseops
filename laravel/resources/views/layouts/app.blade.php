<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Waybill System')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('styles')
    <style>
        /* ============================================
           APP LAYOUT - Refined Sidebar & Header
           ============================================ */
        .app-container {
            display: flex;
            min-height: 100vh;
            background-color: var(--bg-primary);
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #0c1017 0%, #080b11 100%);
            border-right: 1px solid var(--border-primary);
            display: flex;
            flex-direction: column;
            transition: width var(--transition-base);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .sidebar-header {
            padding: var(--space-4) var(--space-4);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 64px;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-size: var(--text-lg);
            font-weight: var(--font-bold);
            color: var(--accent-primary);
            letter-spacing: -0.02em;
            white-space: nowrap;
            overflow: hidden;
            transition: opacity var(--transition-base);
        }

        .sidebar-brand-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--accent-cyan) 0%, var(--accent-blue) 100%);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-md);
            flex-shrink: 0;
        }

        .sidebar.collapsed .sidebar-brand span {
            opacity: 0;
            width: 0;
        }

        .sidebar-toggle {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-default);
            color: var(--text-tertiary);
            cursor: pointer;
            padding: var(--space-2);
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-toggle:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
            border-color: var(--border-active);
        }

        .sidebar-nav {
            flex: 1;
            padding: var(--space-4) var(--space-2);
            overflow-y: auto;
        }

        .nav-section-title {
            font-size: var(--text-2xs);
            font-weight: var(--font-semibold);
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: var(--space-3) var(--space-3);
            margin-bottom: var(--space-1);
        }

        .sidebar.collapsed .nav-section-title {
            display: none;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: var(--space-3) var(--space-3);
            color: var(--text-secondary);
            text-decoration: none;
            transition: all var(--transition-fast);
            gap: var(--space-3);
            margin: 2px 0;
            border-radius: var(--radius-lg);
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
            position: relative;
        }

        .nav-item:hover {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(34, 211, 238, 0.1) 100%);
            color: var(--accent-cyan);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: linear-gradient(180deg, var(--accent-cyan) 0%, var(--accent-blue) 100%);
            border-radius: 0 2px 2px 0;
        }

        .nav-item i {
            width: 18px;
            text-align: center;
            font-size: var(--text-md);
            flex-shrink: 0;
        }

        .nav-item span {
            white-space: nowrap;
            overflow: hidden;
            transition: opacity var(--transition-base);
        }

        .sidebar.collapsed .nav-item span {
            opacity: 0;
            width: 0;
        }

        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding: var(--space-3);
        }

        .sidebar.collapsed .nav-item.active::before {
            display: none;
        }

        .nav-divider {
            height: 1px;
            background: var(--border-primary);
            margin: var(--space-3) var(--space-3);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-base);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed);
        }

        /* Top Header */
        .top-header {
            background: var(--bg-secondary);
            padding: 0 var(--space-5);
            height: var(--header-height);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            padding: var(--space-2);
            font-size: var(--text-lg);
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            padding: 0;
            margin: 0;
            list-style: none;
            background: transparent;
            gap: var(--space-2);
        }

        .breadcrumb-item {
            display: flex;
            align-items: center;
            font-size: var(--text-sm);
        }

        .breadcrumb-item a {
            color: var(--text-tertiary);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .breadcrumb-item a:hover {
            color: var(--text-primary);
        }

        .breadcrumb-item.active {
            color: var(--text-primary);
            font-weight: var(--font-medium);
        }

        .breadcrumb-separator {
            color: var(--text-muted);
            font-size: var(--text-xs);
        }

        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            color: var(--text-primary);
            font-weight: var(--font-semibold);
            font-size: var(--text-sm);
            line-height: 1.2;
        }

        .user-role {
            color: var(--text-tertiary);
            font-size: var(--text-xs);
            text-transform: capitalize;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-cyan) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: var(--font-bold);
            font-size: var(--text-md);
            border: 2px solid var(--border-default);
        }

        /* Content Area */
        .content-area {
            flex: 1;
            padding: var(--space-6);
            max-width: var(--content-max-width);
            width: 100%;
        }

        /* Alert Messages */
        .alert {
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-5);
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: var(--accent-green);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: var(--accent-red);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .content-area {
                padding: var(--space-4);
            }

            .user-details {
                display: none;
            }
        }

        @media (max-width: 640px) {
            .content-area {
                padding: var(--space-3);
            }

            .top-header {
                padding: 0 var(--space-3);
            }

            .breadcrumb-item a,
            .breadcrumb-item.active {
                font-size: var(--text-xs);
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <span class="sidebar-brand-icon">
                        <i class="fas fa-boxes-stacked"></i>
                    </span>
                    <span>Thirdynal</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-title">Main</div>
                
                @if(auth()->user()->canAccess('dashboard'))
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('scanner'))
                <a href="{{ route('scanner') }}" class="nav-item {{ request()->routeIs('scanner') ? 'active' : '' }}">
                    <i class="fas fa-barcode"></i>
                    <span>Scanner</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('pending'))
                <a href="{{ route('pending') }}" class="nav-item {{ request()->routeIs('pending') ? 'active' : '' }}">
                    <i class="fas fa-hourglass-half"></i>
                    <span>Pending</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('upload'))
                <a href="{{ route('upload') }}" class="nav-item {{ request()->routeIs('upload*') ? 'active' : '' }}">
                    <i class="fas fa-cloud-arrow-up"></i>
                    <span>Upload</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('accounts'))
                <a href="{{ route('waybills') }}" class="nav-item {{ request()->routeIs('waybills') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice"></i>
                    <span>Waybills</span>
                </a>
                @endif

                <div class="nav-divider"></div>
                <div class="nav-section-title">Sales</div>

                @if(auth()->user()->canAccess('leads_view'))
                <a href="{{ route('leads.index') }}" class="nav-item {{ request()->routeIs('leads.index') || request()->routeIs('leads.importForm') ? 'active' : '' }}">
                    <i class="fas fa-headset"></i>
                    <span>Leads</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('leads_manage'))
                <a href="{{ route('leads.monitoring') }}" class="nav-item {{ request()->routeIs('leads.monitoring') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Monitoring</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('qc'))
                <a href="{{ route('monitoring.index') }}" class="nav-item {{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-check"></i>
                    <span>QC Dashboard</span>
                </a>
                @endif

                <div class="nav-divider"></div>
                <div class="nav-section-title">System</div>

                @if(auth()->user()->canAccess('settings'))
                <a href="{{ route('settings') }}" class="nav-item {{ request()->routeIs('settings*') ? 'active' : '' }}">
                    <i class="fas fa-gear"></i>
                    <span>Settings</span>
                </a>
                @endif

                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <a href="#" class="nav-item" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
                        <i class="fas fa-right-from-bracket"></i>
                        <span>Sign Out</span>
                    </a>
                </form>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard') }}">
                                    <i class="fas fa-home"></i>
                                </a>
                            </li>
                            <li class="breadcrumb-separator">
                                <i class="fas fa-chevron-right"></i>
                            </li>
                            <li class="breadcrumb-item active">@yield('page-title', 'Dashboard')</li>
                        </ol>
                    </nav>
                </div>
                
                <div class="user-info">
                    <div class="user-details">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-role">{{ auth()->user()->role }}</div>
                    </div>
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </header>

            <main class="content-area">
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Notification Bell -->
    <div id="notification-bell">
        <i class="fas fa-bell"></i>
        <span id="notification-count">0</span>
    </div>

    <!-- Notification List -->
    <div id="notification-list">
        <div class="notification-header">
            <i class="fas fa-bell" style="margin-right: 8px;"></i>
            Notifications
        </div>
        <div id="notification-items"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileToggle = document.getElementById('mobileToggle');
            const toggleIcon = sidebarToggle ? sidebarToggle.querySelector('i') : null;

            // Load saved state
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed && window.innerWidth > 1024) {
                sidebar.classList.add('collapsed');
                if (toggleIcon) toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
            }

            // Toggle sidebar (desktop)
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    const nowCollapsed = sidebar.classList.contains('collapsed');
                    localStorage.setItem('sidebarCollapsed', nowCollapsed);
                    
                    if (nowCollapsed) {
                        toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
                    } else {
                        toggleIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
                    }
                });
            }

            // Toggle sidebar (mobile)
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-open');
                });
            }

            // Close mobile sidebar on outside click
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024) {
                    if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                        sidebar.classList.remove('mobile-open');
                    }
                }
            });
        });

        // Notification functionality
        document.addEventListener('DOMContentLoaded', function() {
            const bell = document.getElementById('notification-bell');
            const list = document.getElementById('notification-list');
            const countBadge = document.getElementById('notification-count');
            const itemsContainer = document.getElementById('notification-items');

            bell.addEventListener('click', function(e) {
                e.stopPropagation();
                list.style.display = list.style.display === 'none' ? 'block' : 'none';
            });

            document.addEventListener('click', function(e) {
                if (!list.contains(e.target) && !bell.contains(e.target)) {
                    list.style.display = 'none';
                }
            });

            function checkNotifications() {
                fetch('{{ route("notifications.index") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.unread_count > 0) {
                            countBadge.style.display = 'flex';
                            countBadge.textContent = data.unread_count;
                        } else {
                            countBadge.style.display = 'none';
                        }

                        itemsContainer.innerHTML = '';
                        if (data.notifications.length === 0) {
                            itemsContainer.innerHTML = '<div style="padding: 16px; color: var(--text-tertiary); text-align: center; font-size: 13px;">No notifications</div>';
                        } else {
                            data.notifications.forEach(notif => {
                                const item = document.createElement('div');
                                item.style.cssText = 'padding: 12px 16px; border-bottom: 1px solid var(--border-subtle); font-size: 13px; cursor: pointer; transition: background 0.15s;';
                                item.style.background = notif.read_at ? 'transparent' : 'rgba(59, 130, 246, 0.05)';

                                item.innerHTML = `
                                    <div style="margin-bottom: 4px; color: var(--text-primary);">${notif.data.message}</div>
                                    <small style="color: var(--text-tertiary);">${new Date(notif.created_at).toLocaleString()}</small>
                                `;

                                item.addEventListener('mouseenter', () => item.style.background = 'var(--bg-card-hover)');
                                item.addEventListener('mouseleave', () => item.style.background = notif.read_at ? 'transparent' : 'rgba(59, 130, 246, 0.05)');

                                item.addEventListener('click', () => {
                                    fetch(`/notifications/${notif.id}/read`, {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Content-Type': 'application/json'
                                        }
                                    }).then(() => {
                                        if (notif.data.link) {
                                            window.location.href = notif.data.link;
                                        } else {
                                            checkNotifications();
                                        }
                                    });
                                });

                                itemsContainer.appendChild(item);
                            });
                        }
                    });
            }

            checkNotifications();
            setInterval(checkNotifications, 30000);
        });
    </script>
    @stack('scripts')
    
    {{-- Softphone Widget (Global) --}}
    @auth
    @php
        $sipAccount = auth()->user()->sipAccount;
        $sipConfig = $sipAccount ? $sipAccount->toSipConfig() : null;
        // Ensure WS server matches current host for hybrid safety if not set? 
        // No, the seeder set it to 192.168.120.33. That's fine.
    @endphp
    <script>
        window.laravelUserId = {{ auth()->id() }};
        window.sipConfig = @json($sipConfig);
    </script>
    <script src="{{ asset('js/softphone.js') }}?v={{ time() }}"></script>
    @endauth
</body>
</html>
