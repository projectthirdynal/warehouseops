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
            background: var(--bg-secondary);
            padding: var(--space-4) var(--space-6);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .page-title {
            font-size: var(--text-xl);
            font-weight: var(--font-semibold);
            color: var(--text-primary);
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
                <a href="{{ route('leads.index') }}" class="nav-item {{ request()->routeIs('leads*') ? 'active' : '' }}">
                    <i class="fas fa-headset"></i>
                    <span>Leads</span>
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
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
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
            const toggleIcon = sidebarToggle.querySelector('i');

            // Load saved state
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
            }

            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                const nowCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', nowCollapsed);
                
                if (nowCollapsed) {
                    toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
                } else {
                    toggleIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
                }
            });
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

