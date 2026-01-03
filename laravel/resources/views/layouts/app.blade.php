<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Waybill System')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('styles')
    <style>
        /* Sidebar Layout Styles */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-primary);
            display: flex;
            flex-direction: column;
            transition: width var(--transition-normal);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            padding: var(--space-4);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-brand {
            font-size: var(--text-lg);
            font-weight: var(--font-bold);
            color: var(--accent-primary);
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .sidebar-brand {
            display: none;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: var(--space-2);
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
        }

        .sidebar-toggle:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .sidebar-nav {
            flex: 1;
            padding: var(--space-4) 0;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: var(--space-3) var(--space-4);
            color: var(--text-secondary);
            text-decoration: none;
            transition: all var(--transition-fast);
            gap: var(--space-3);
            margin: var(--space-1) var(--space-2);
            border-radius: var(--radius-md);
        }

        .nav-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--accent-primary);
            color: white;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
            font-size: var(--text-base);
        }

        .nav-item span {
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .nav-item span {
            display: none;
        }

        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding: var(--space-3);
        }

        .sidebar-footer {
            padding: var(--space-4);
            border-top: 1px solid var(--border-primary);
        }

        .nav-divider {
            height: 1px;
            background: var(--border-primary);
            margin: var(--space-3) var(--space-4);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 240px;
            transition: margin-left var(--transition-normal);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 70px;
        }

        .top-header {
            background: #0b0e14;
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            padding: 0;
            margin: 0;
            list-style: none !important;
            background: transparent;
        }

        .breadcrumb-item {
            display: flex;
            align-items: center;
            list-style: none !important;
        }

        .breadcrumb-item + .breadcrumb-item {
            padding-left: 0.5rem;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            display: inline-block;
            padding-right: 0.5rem;
            color: rgba(255, 255, 255, 0.3);
            content: ">";
            font-size: 0.75rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .user-name {
            color: var(--text-primary);
            font-weight: var(--font-medium);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--accent-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: var(--font-semibold);
        }

        .content-area {
            flex: 1;
            padding: var(--space-6);
        }

        /* Essential Layout Helpers */
        .d-flex { display: flex !important; }
        .flex-wrap { flex-wrap: wrap !important; }
        .align-items-center { align-items: center !important; }
        .justify-content-between { justify-content: space-between !important; }
        .gap-1 { gap: 0.25rem !important; }
        .gap-2 { gap: 0.5rem !important; }
        .gap-3 { gap: 1rem !important; }
        .gap-4 { gap: 1.5rem !important; }
        .flex-grow-1 { flex-grow: 1 !important; }
        .ms-auto { margin-left: auto !important; }
        .me-3 { margin-right: 1rem !important; }
        .mb-0 { margin-bottom: 0 !important; }
        .p-0 { padding: 0 !important; }
        .p-1 { padding: 0.25rem !important; }
        .p-2 { padding: 0.5rem !important; }
        .p-3 { padding: 1rem !important; }
        .p-4 { padding: 1.5rem !important; }
        .ps-0 { padding-left: 0 !important; }
        .pe-0 { padding-right: 0 !important; }
        .px-3 { padding-left: 1rem !important; padding-right: 1rem !important; }
        .px-4 { padding-left: 1.5rem !important; padding-right: 1.5rem !important; }
        .py-1 { padding-top: 0.25rem !important; padding-bottom: 0.25rem !important; }
        .py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
        .py-3 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
        .py-4 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
        .list-unstyled { list-style: none !important; padding-left: 0 !important; }
        .border { border: 1px solid rgba(255, 255, 255, 0.1) !important; }
        .rounded-4 { border-radius: 1rem !important; }
        .text-decoration-none { text-decoration: none !important; }
        .small { font-size: 0.875rem !important; }
        .text-white-50 { color: rgba(255, 255, 255, 0.5) !important; }
        .text-white { color: #ffffff !important; }
        .fw-bold { font-weight: 700 !important; }
        .shadow-none { box-shadow: none !important; }
        .border-0 { border: 0 !important; }
        .rounded-circle { border-radius: 50% !important; }
        .rounded-3 { border-radius: 0.5rem !important; }
        .d-none { display: none !important; }
        @media (min-width: 768px) { .d-md-block { display: block !important; } .d-md-flex { display: flex !important; } }

        /* Position Helpers */
        .position-relative { position: relative !important; }
        .position-absolute { position: absolute !important; }
        .top-50 { top: 50% !important; }
        .start-0 { left: 0 !important; }
        .translate-middle-y { transform: translateY(-50%) !important; }
        .ms-3 { margin-left: 1rem !important; }
        .ps-5 { padding-left: 3rem !important; }
        .py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }

        /* Grid & Spacing Helpers */
        .row { display: flex !important; flex-wrap: wrap !important; margin-right: -15px !important; margin-left: -15px !important; }
        .g-3 { margin: -0.5rem !important; }
        .g-3 > * { padding: 0.5rem !important; }
        .col-auto { flex: 0 0 auto !important; width: auto !important; }
        .col-12 { flex: 0 0 100% !important; width: 100% !important; }
        .mt-3 { margin-top: 1rem !important; }
        .mt-4 { margin-top: 1.5rem !important; }
        .pt-3 { padding-top: 1rem !important; }
        .pt-4 { padding-top: 1.5rem !important; }
        .border-top { border-top: 1px solid rgba(255, 255, 255, 0.1) !important; }

        /* Form Helpers */
        .form-control, .form-select {
            display: block !important;
            width: 100% !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
            font-weight: 400 !important;
            line-height: 1.5 !important;
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
            background-clip: padding-box !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 0.5rem !important;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.25) !important;
            outline: 0 !important;
        }
        .form-select option {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }
        .bg-dark { background-color: #0b0e14 !important; }
        .bg-white { background-color: #ffffff; }
        .bg-opacity-10 { background-color: rgba(255, 255, 255, 0.1) !important; }
        .bg-opacity-5 { background-color: rgba(255, 255, 255, 0.05) !important; }
        .border-white { border-color: #ffffff; }
        .border-opacity-10 { border-color: rgba(255, 255, 255, 0.1) !important; }

        /* Button Helpers */
        .btn {
            display: inline-block !important;
            font-weight: 400 !important;
            text-align: center !important;
            vertical-align: middle !important;
            cursor: pointer !important;
            user-select: none !important;
            background-color: transparent !important;
            border: 1px solid transparent !important;
            padding: 0.375rem 0.75rem !important;
            font-size: 0.875rem !important;
            line-height: 1.5 !important;
            border-radius: 0.5rem !important;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
            text-decoration: none !important;
        }
        .btn-link { color: #ffffff !important; background-color: transparent !important; border: 0 !important; }
        .btn-info { color: #000000 !important; background-color: #22d3ee !important; border-color: #22d3ee !important; }
        .btn-primary { color: #ffffff !important; background-color: #3b82f6 !important; border-color: #3b82f6 !important; }
        .btn-white { background: #ffffff !important; color: #000000 !important; border: 0 !important; }

        /* Alert Messages */
        .alert {
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-4);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid var(--status-success);
            color: var(--status-success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--status-error);
            color: var(--status-error);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <span class="sidebar-brand">Thirdynal</span>
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>

                @if(auth()->user()->canAccess('scanner'))
                <a href="{{ route('scanner') }}" class="nav-item {{ request()->routeIs('scanner') ? 'active' : '' }}">
                    <i class="fas fa-barcode"></i>
                    <span>Scanner</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('pending'))
                <a href="{{ route('pending') }}" class="nav-item {{ request()->routeIs('pending') ? 'active' : '' }}">
                    <i class="fas fa-clock"></i>
                    <span>Pending Section</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('upload'))
                <a href="{{ route('upload') }}" class="nav-item {{ request()->routeIs('upload*') ? 'active' : '' }}">
                    <i class="fas fa-upload"></i>
                    <span>Upload</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('accounts'))
                <a href="{{ route('waybills') }}" class="nav-item {{ request()->routeIs('waybills') ? 'active' : '' }}">
                    <i class="fas fa-list"></i>
                    <span>Waybills</span>
                </a>
                @endif

                @if(auth()->user()->canAccess('leads_view'))
                <a href="{{ route('leads.index') }}" class="nav-item {{ request()->routeIs('leads.index') ? 'active' : '' }}">
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

                <div class="nav-divider"></div>

                @if(auth()->user()->canAccess('settings'))
                <a href="{{ route('settings') }}" class="nav-item {{ request()->routeIs('settings*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                @endif

                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <a href="#" class="nav-item" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Sign Out</span>
                    </a>
                </form>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <header class="top-header">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link text-white-50 p-0 me-3 shadow-none border-0" id="sidebarToggleGlobal">
                        <i class="fas fa-bars-staggered"></i>
                    </button>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-white-50 text-decoration-none small">Dashboard</a></li>
                            <li class="breadcrumb-item active text-white small" aria-current="page">@yield('page-title', 'Leads')</li>
                        </ol>
                    </nav>
                </div>
                
                <div class="user-info d-flex align-items-center">
                    <div class="text-end me-3 d-none d-md-block">
                        <div class="user-name text-white fw-bold small mb-0">{{ auth()->user()->name }}</div>
                        <div class="user-email text-white-50 small" style="font-size: 0.75rem;">{{ auth()->user()->email ?? 'admin@thirdynal.com' }}</div>
                    </div>
                    <div class="user-avatar bg-info text-dark fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; border: 2px solid rgba(255,255,255,0.1);">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </header>

            <main class="content-area">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Notification Bell (Fixed Position) -->
    <div id="notification-bell">
        <i class="fas fa-bell" style="font-size: 1.5rem;"></i>
        <span id="notification-count">0</span>
    </div>

    <!-- Notification List container (Hidden by default) -->
    <div id="notification-list">
        <div class="notification-header">Notifications</div>
        <div id="notification-items">
            <!-- Items injected by JS -->
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarToggleGlobal = document.getElementById('sidebarToggleGlobal');
            const toggleIcon = sidebarToggle ? sidebarToggle.querySelector('i') : null;
            const globalToggleIcon = sidebarToggleGlobal ? sidebarToggleGlobal.querySelector('i') : null;

            // Load saved state
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                if(toggleIcon) toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
            }

            const toggleLogic = () => {
                sidebar.classList.toggle('collapsed');
                const nowCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', nowCollapsed);
                
                if (nowCollapsed) {
                   if(toggleIcon) toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
                } else {
                   if(toggleIcon) toggleIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
                }
            };

            sidebarToggle.addEventListener('click', toggleLogic);
            if(sidebarToggleGlobal) sidebarToggleGlobal.addEventListener('click', toggleLogic);
        });

        // Notification functionality
        document.addEventListener('DOMContentLoaded', function() {
            const bell = document.getElementById('notification-bell');
            const list = document.getElementById('notification-list');
            const countBadge = document.getElementById('notification-count');
            const itemsContainer = document.getElementById('notification-items');

            bell.addEventListener('click', () => {
                list.style.display = list.style.display === 'none' ? 'block' : 'none';
            });

            function checkNotifications() {
                fetch('{{ route("notifications.index") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.unread_count > 0) {
                            countBadge.style.display = 'block';
                            countBadge.textContent = data.unread_count;
                        } else {
                            countBadge.style.display = 'none';
                        }

                        itemsContainer.innerHTML = '';
                        if (data.notifications.length === 0) {
                            itemsContainer.innerHTML = '<div style="padding: 10px; color: #9ca3af; text-align: center;">No notifications</div>';
                        } else {
                            data.notifications.forEach(notif => {
                                const item = document.createElement('div');
                                item.style.padding = '10px';
                                item.style.borderBottom = '1px solid #374151';
                                item.style.fontSize = '0.9rem';
                                item.style.cursor = 'pointer';
                                item.style.background = notif.read_at ? 'transparent' : 'rgba(59, 130, 246, 0.1)';

                                item.innerHTML = `
                                    <div style="margin-bottom: 5px;">${notif.data.message}</div>
                                    <small style="color: #9ca3af;">${new Date(notif.created_at).toLocaleString()}</small>
                                `;

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
</body>
</html>

