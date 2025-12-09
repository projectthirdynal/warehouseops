<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Waybill System')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('styles')
</head>
<body>
    <div class="container">
        <header class="main-header">
            <h1>@yield('header', 'Thirdynal Warehouse Ops System')</h1>
            <p>@yield('subheader', 'Manage and track your waybill dispatches')</p>
        </header>

        <nav class="main-nav">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('scanner') }}" class="nav-link {{ request()->routeIs('scanner') ? 'active' : '' }}">Scanner</a>
            <a href="{{ route('pending') }}" class="nav-link {{ request()->routeIs('pending') ? 'active' : '' }}">Pending Section</a>
            <a href="{{ route('upload') }}" class="nav-link {{ request()->routeIs('upload') ? 'active' : '' }}">Upload</a>
            <a href="{{ route('waybills') }}" class="nav-link {{ request()->routeIs('waybills') ? 'active' : '' }}">Accounts</a>
        </nav>

        @yield('content')
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
        document.addEventListener('DOMContentLoaded', function() {
            const bell = document.getElementById('notification-bell');
            const list = document.getElementById('notification-list');
            const countBadge = document.getElementById('notification-count');
            const itemsContainer = document.getElementById('notification-items');
            
            // Toggle list
            bell.addEventListener('click', () => {
                list.style.display = list.style.display === 'none' ? 'block' : 'none';
            });

            // Poll notifications
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
                                    // Mark as read
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
                                            checkNotifications(); // refresh
                                        }
                                    });
                                });

                                itemsContainer.appendChild(item);
                            });
                        }
                    });
            }

            // Initial check and poll every 30s
            checkNotifications();
            setInterval(checkNotifications, 30000);
        });
    </script>
    @stack('scripts')
</body>
</html>
