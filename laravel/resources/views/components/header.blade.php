@props([
    'title' => null,
])

<header class="h-16 sticky top-0 z-30 flex items-center justify-between px-6 bg-dark-900/80 backdrop-blur-md border-b border-dark-600">
    <div class="flex items-center gap-4">
        {{-- Mobile Toggle --}}
        <button
            @click="sidebarOpen = !sidebarOpen"
            class="md:hidden text-slate-400 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-400 rounded-lg p-1"
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            x-bind:aria-expanded="sidebarOpen"
        >
            <i class="fas fa-bars text-xl"></i>
        </button>

        {{-- Breadcrumbs / Page Title --}}
        <nav class="flex items-center gap-2 text-sm md:text-base" aria-label="Breadcrumb">
            <span class="text-dark-100"><i class="fas fa-home" aria-hidden="true"></i></span>
            <span class="text-dark-300" aria-hidden="true">/</span>
            <span class="font-semibold text-white tracking-wide">
                {{ $title ?? View::getSection('page-title', 'Dashboard') }}
            </span>
        </nav>
    </div>

    <div class="flex items-center gap-4">
        {{-- Notifications --}}
        <div class="relative" x-data="{ open: false }" @click.away="open = false">
            <button
                @click="open = !open"
                class="relative p-2 text-slate-400 hover:text-white transition-colors rounded-lg hover:bg-dark-700 focus-visible:ring-2 focus-visible:ring-gold-400"
                aria-label="Notifications"
                aria-haspopup="true"
                x-bind:aria-expanded="open"
            >
                <i class="fas fa-bell" aria-hidden="true"></i>
                <span
                    id="notification-count"
                    class="absolute top-1 right-1 w-2 h-2 bg-error-500 rounded-full border-2 border-dark-900 hidden"
                    aria-label="Unread notifications"
                ></span>
            </button>

            {{-- Notification Dropdown --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-80 bg-dark-700 border border-dark-500 rounded-xl shadow-2xl py-2 z-50"
                role="menu"
                aria-orientation="vertical"
            >
                <div class="px-4 py-2 border-b border-dark-500 font-semibold text-white text-sm">Notifications</div>
                <div id="notification-items" class="max-h-64 overflow-y-auto" role="list">
                    {{-- JS Populated --}}
                </div>
            </div>
        </div>

        {{ $slot }}
    </div>
</header>
