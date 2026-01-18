@props([
    'user' => null,
])

@php
    $user = $user ?? auth()->user();
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-dark-900 border-r border-dark-600 transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:inset-0 flex flex-col"
    role="navigation"
    aria-label="Main navigation"
>
    {{-- Sidebar Header --}}
    <div class="h-16 flex items-center justify-between px-6 border-b border-dark-600 bg-dark-900/50 backdrop-blur-sm">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group px-2">
            <img src="{{ asset('images/org_logo.jpg') }}" alt="Logo" class="h-8 w-auto rounded-lg">
            <span class="text-lg font-bold text-white tracking-tight">Organization</span>
        </a>
        {{-- Mobile Close Button --}}
        <button
            @click="sidebarOpen = false"
            class="md:hidden text-slate-400 hover:text-white transition-colors p-2 -mr-2 rounded-lg hover:bg-dark-700"
            aria-label="Close navigation menu"
        >
            <i class="fas fa-times"></i>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1" aria-label="Primary navigation">
        {{-- Section: Main --}}
        <div class="px-3 mb-2 text-xs font-semibold text-dark-100 uppercase tracking-wider">Main</div>

        @if($user->canAccess('dashboard'))
            <x-sidebar-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" icon="fas fa-chart-pie">
                Dashboard
            </x-sidebar-link>
        @endif

        @if($user->canAccess('scanner'))
            <x-sidebar-link href="{{ route('scanner') }}" :active="request()->routeIs('scanner')" icon="fas fa-barcode">
                Scanner
            </x-sidebar-link>
        @endif

        @if($user->canAccess('pending'))
            <x-sidebar-link href="{{ route('pending') }}" :active="request()->routeIs('pending')" icon="fas fa-hourglass-half">
                Pending
            </x-sidebar-link>
        @endif

        @if($user->canAccess('upload'))
            <x-sidebar-link href="{{ route('upload') }}" :active="request()->routeIs('upload*')" icon="fas fa-cloud-arrow-up">
                Upload
            </x-sidebar-link>
        @endif

        @if($user->canAccess('accounts'))
            <x-sidebar-link href="{{ route('waybills') }}" :active="request()->routeIs('waybills')" icon="fas fa-file-invoice">
                Waybills
            </x-sidebar-link>
        @endif

        <div class="h-px bg-dark-600 my-4 mx-3" role="separator"></div>

        {{-- Section: Sales --}}
        <div class="px-3 mb-2 text-xs font-semibold text-dark-100 uppercase tracking-wider">Sales</div>

        @if($user->canAccess('leads_view'))
            <x-sidebar-link href="{{ route('leads.index') }}" :active="request()->routeIs('leads.index') || request()->routeIs('leads.importForm')" icon="fas fa-headset">
                Leads
            </x-sidebar-link>
        @endif

        @if($user->canAccess('leads_manage'))
            <x-sidebar-link href="{{ route('leads.monitoring') }}" :active="request()->routeIs('leads.monitoring')" icon="fas fa-chart-line">
                Monitoring
            </x-sidebar-link>
        @endif

        @if($user->canAccess('qc'))
            <x-sidebar-link href="{{ route('monitoring.index') }}" :active="request()->routeIs('monitoring.*')" icon="fas fa-clipboard-check">
                QC Dashboard
            </x-sidebar-link>
        @endif

        <div class="h-px bg-dark-600 my-4 mx-3" role="separator"></div>

        {{-- Section: Support --}}
        <div class="px-3 mb-2 text-xs font-semibold text-dark-100 uppercase tracking-wider">Support</div>

        <x-sidebar-link href="{{ route('tickets.index') }}" :active="request()->routeIs('tickets*')" icon="fas fa-life-ring">
            IT Support
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('articles.index') }}" :active="request()->routeIs('articles*')" icon="fas fa-book">
            Knowledge Base
        </x-sidebar-link>

        @if(in_array($user->role, ['superadmin', 'admin', 'it_staff']))
            <x-sidebar-link href="{{ route('reports.tickets.index') }}" :active="request()->routeIs('reports*')" icon="fas fa-chart-column">
                Reports
            </x-sidebar-link>
        @endif

        <div class="h-px bg-dark-600 my-4 mx-3" role="separator"></div>

        {{-- Section: System --}}
        <div class="px-3 mb-2 text-xs font-semibold text-dark-100 uppercase tracking-wider">System</div>

        @if($user->canAccess('settings'))
            <x-sidebar-link href="{{ route('settings') }}" :active="request()->routeIs('settings*')" icon="fas fa-gear">
                Settings
            </x-sidebar-link>
        @endif

        <form method="POST" action="{{ route('logout') }}" id="logoutForm">
            @csrf
            <button
                type="submit"
                class="w-full group flex items-center px-3 py-2 text-sm font-medium rounded-lg text-slate-400 hover:bg-error-500/10 hover:text-error-400 transition-all duration-200"
            >
                <i class="fas fa-right-from-bracket w-5 group-hover:text-error-400 transition-colors"></i>
                Sign Out
            </button>
        </form>
    </nav>

    {{-- User Profile (Bottom) --}}
    <div class="p-4 border-t border-dark-600">
        <div class="flex items-center gap-3">
            <div class="relative">
                <div class="w-10 h-10 rounded-full bg-dark-600 flex items-center justify-center text-white border border-dark-500 font-medium">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-success-500 border-2 border-dark-900 rounded-full" aria-label="Online status"></div>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-medium text-white truncate">{{ $user->name }}</div>
                <div class="text-xs text-dark-100 capitalize truncate">{{ $user->role }}</div>
            </div>
        </div>
    </div>
</aside>
