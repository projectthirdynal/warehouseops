<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Waybill System')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts & Styles -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-dark-900 text-slate-300 font-sans antialiased selection:bg-gold-400 selection:text-white">
    <!-- Skip Link for Accessibility -->
    <a href="#main-content" class="skip-link">
        Skip to main content
    </a>

    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex flex-col md:flex-row">

        <!-- Mobile Sidebar Overlay -->
        <div
            x-show="sidebarOpen"
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-dark-950/80 z-40 md:hidden backdrop-blur-sm"
            aria-hidden="true"
        ></div>

        <!-- Sidebar Component -->
        <x-sidebar />

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-h-screen bg-dark-900 transition-all duration-300">

            <!-- Header Component -->
            <x-header />

            <!-- Page Content -->
            <main id="main-content" class="flex-1 p-6 md:p-8 overflow-x-hidden" tabindex="-1">
                @if(session('success'))
                    <x-alert type="success" class="mb-6" dismissible>
                        {{ session('success') }}
                    </x-alert>
                @endif

                @if(session('error'))
                    <x-alert type="error" class="mb-6" dismissible>
                        {{ session('error') }}
                    </x-alert>
                @endif

                @if(session('warning'))
                    <x-alert type="warning" class="mb-6" dismissible>
                        {{ session('warning') }}
                    </x-alert>
                @endif

                @if(session('info'))
                    <x-alert type="info" class="mb-6" dismissible>
                        {{ session('info') }}
                    </x-alert>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Notification Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const countBadge = document.getElementById('notification-count');
            const itemsContainer = document.getElementById('notification-items');

            function checkNotifications() {
                fetch('{{ route("notifications.index") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.unread_count > 0) {
                            countBadge.style.display = 'block';
                        } else {
                            countBadge.style.display = 'none';
                        }

                        itemsContainer.innerHTML = '';
                        if (data.notifications.length === 0) {
                            itemsContainer.innerHTML = '<div class="px-4 py-6 text-center text-dark-100 text-sm">No new notifications</div>';
                        } else {
                            data.notifications.forEach(notif => {
                                const item = document.createElement('div');
                                item.className = `px-4 py-3 border-b border-dark-500 cursor-pointer hover:bg-dark-600 transition-colors ${notif.read_at ? 'opacity-50' : ''}`;
                                item.setAttribute('role', 'listitem');
                                item.innerHTML = `
                                    <div class="text-sm text-slate-200 mb-1">${notif.data.message}</div>
                                    <div class="text-xs text-dark-100">${new Date(notif.created_at).toLocaleString()}</div>
                                `;
                                item.addEventListener('click', () => {
                                    fetch(`/notifications/${notif.id}/read`, {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Content-Type': 'application/json'
                                        }
                                    }).then(() => {
                                        if (notif.data.link) window.location.href = notif.data.link;
                                        else checkNotifications();
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

    @auth
    @php
        $sipAccount = auth()->user()->sipAccount;
        $sipConfig = $sipAccount ? $sipAccount->toSipConfig() : null;
    @endphp
    <script>
        window.laravelUserId = {{ auth()->id() }};
        window.sipConfig = @json($sipConfig);
    </script>
    <script src="{{ asset('js/softphone.js') }}?v={{ time() }}"></script>
    @endauth

    @stack('scripts')
</body>
</html>
