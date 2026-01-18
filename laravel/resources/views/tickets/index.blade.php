@extends('layouts.app')

@section('title', 'IT Support Tickets')
@section('page-title', 'IT Support')

@section('content')
    <!-- Page Header -->
    <x-page-header
        title="Support Tickets"
        description="View and manage IT support requests"
        icon="fas fa-life-ring"
    >
        <x-button href="{{ route('tickets.create') }}" variant="gold" icon="fas fa-plus">
            New Ticket
        </x-button>
    </x-page-header>

    <!-- Filters -->
    <x-filter-bar action="{{ route('tickets.index') }}">
        <x-form.select name="status" :value="request('status')" placeholder="All Statuses" class="w-40">
            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
        </x-form.select>

        @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
            <x-form.select name="priority" :value="request('priority')" placeholder="All Priorities" class="w-40">
                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
            </x-form.select>
        @endif

        <x-button type="submit" variant="secondary" size="sm" icon="fas fa-filter">
            Filter
        </x-button>

        @if(request()->hasAny(['status', 'priority']))
            <x-button href="{{ route('tickets.index') }}" variant="ghost" size="sm">
                Clear Filters
            </x-button>
        @endif
    </x-filter-bar>

    <!-- Tickets List -->
    <x-table>
        <x-slot:head>
            <x-table-th>Reference</x-table-th>
            <x-table-th>Submitted By</x-table-th>
            <x-table-th>Subject</x-table-th>
            <x-table-th>Status</x-table-th>
            <x-table-th>Priority</x-table-th>
            <x-table-th>Category</x-table-th>
            <x-table-th>Created</x-table-th>
            <x-table-th>Action</x-table-th>
        </x-slot:head>

        @forelse($tickets as $ticket)
            <tr class="hover:bg-dark-600 transition-colors">
                <x-table-td>
                    <span class="font-mono text-gold-400 font-semibold">{{ $ticket->ref_no }}</span>
                </x-table-td>
                <x-table-td>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-dark-600 border border-dark-500 flex items-center justify-center text-slate-300 text-xs font-bold">
                            {{ strtoupper(substr($ticket->user->name, 0, 1)) }}
                        </div>
                        <span class="text-slate-200">{{ $ticket->user->name }}</span>
                    </div>
                </x-table-td>
                <x-table-td>
                    <div class="font-medium text-white mb-0.5">{{ $ticket->subject }}</div>
                    <div class="text-xs text-dark-100 truncate max-w-xs">{{ Str::limit($ticket->description, 50) }}</div>
                </x-table-td>
                <x-table-td>
                    @php
                        $statusVariant = match($ticket->status) {
                            'open' => 'info',
                            'in_progress' => 'warning',
                            'resolved' => 'success',
                            'closed' => 'default',
                            default => 'default',
                        };
                    @endphp
                    <x-badge :variant="$statusVariant">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                    </x-badge>
                </x-table-td>
                <x-table-td>
                    @php
                        $priorityClasses = match($ticket->priority) {
                            'critical' => 'text-error-500 font-bold',
                            'high' => 'text-orange-500',
                            'low' => 'text-success-500',
                            default => 'text-dark-100',
                        };
                        $priorityIcon = match($ticket->priority) {
                            'critical' => 'fa-radiation',
                            'high' => 'fa-arrow-up',
                            'low' => 'fa-arrow-down',
                            default => 'fa-minus',
                        };
                    @endphp
                    <span class="flex items-center gap-2 {{ $priorityClasses }} text-xs uppercase font-semibold tracking-wider">
                        <i class="fas {{ $priorityIcon }}"></i>
                        {{ $ticket->priority }}
                    </span>
                </x-table-td>
                <x-table-td>
                    <span class="bg-dark-800 border border-dark-500 text-slate-300 text-xs px-2.5 py-1 rounded">
                        {{ $ticket->category->name }}
                    </span>
                </x-table-td>
                <x-table-td class="text-xs text-dark-100">
                    {{ $ticket->created_at->diffForHumans() }}
                </x-table-td>
                <x-table-td>
                    <a href="{{ route('tickets.show', $ticket->id) }}" class="text-gold-400 hover:text-white transition-colors font-medium text-sm hover:underline">
                        View Details
                    </a>
                </x-table-td>
            </tr>
        @empty
            <tr>
                <td colspan="8">
                    <x-empty-state
                        icon="fas fa-ticket-alt"
                        title="No tickets found"
                        description="Try adjusting your filters or create a new ticket"
                    >
                        <x-button href="{{ route('tickets.create') }}" variant="primary" size="sm" icon="fas fa-plus">
                            Create Ticket
                        </x-button>
                    </x-empty-state>
                </td>
            </tr>
        @endforelse

        @if($tickets->hasPages())
            <x-slot:footer>
                <div class="pagination-wrapper">
                    {{ $tickets->withQueryString()->links() }}
                </div>
            </x-slot:footer>
        @endif
    </x-table>
@endsection
