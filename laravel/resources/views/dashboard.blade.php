@extends('layouts.app')

@section('title', 'Dashboard - Waybill System')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Page Header -->
    <x-page-header
        title="Overview"
        description="Real-time waybill statistics and recent activity"
        icon="fas fa-th-large"
    />

    <!-- Date Range Filter -->
    <x-filter-bar action="{{ route('dashboard') }}">
        <x-form.input
            type="date"
            name="start_date"
            label="From"
            :value="$stats['start_date']"
            class="w-auto"
        />
        <x-form.input
            type="date"
            name="end_date"
            label="To"
            :value="$stats['end_date']"
            class="w-auto"
        />
        <x-button type="submit" variant="primary" size="sm" icon="fas fa-filter">
            Apply Filter
        </x-button>
    </x-filter-bar>

    <!-- Status Monitoring Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat-card
            :value="$stats['total_waybills']"
            label="Total Waybills"
            variant="cyan"
            icon="fas fa-box"
        />

        <x-stat-card
            :value="$stats['in_transit']"
            label="In Transit"
            variant="info"
            icon="fas fa-truck"
        />

        <x-stat-card
            :value="$stats['delivering']"
            label="Delivering"
            variant="warning"
            icon="fas fa-motorcycle"
        />

        <x-stat-card
            :value="$stats['delivered']"
            label="Delivered"
            variant="success"
            icon="fas fa-check-circle"
        />

        <x-stat-card
            :value="$stats['returned']"
            label="Returned"
            variant="returned"
            icon="fas fa-undo"
        />

        <x-stat-card
            :value="$stats['hq_scheduling']"
            label="HQ Scheduling"
            variant="info"
            icon="fas fa-calendar-alt"
        />

        <x-stat-card
            :value="$stats['pending']"
            label="Pending"
            variant="pending"
            icon="fas fa-clock"
        />

        <x-stat-card
            :value="$stats['dispatched']"
            label="Dispatched"
            variant="dispatched"
            icon="fas fa-paper-plane"
        />
    </div>

    <!-- Period Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-lg mb-8">
        <x-stat-card
            :value="number_format($stats['delivery_rate'], 1)"
            suffix="%"
            label="Delivery Rate"
            variant="success"
            icon="fas fa-percentage"
        />

        <x-stat-card
            :value="number_format($stats['return_rate'], 1)"
            suffix="%"
            label="Return Rate"
            variant="warning"
            icon="fas fa-exchange-alt"
        />
    </div>

    <!-- Recent Scans Section -->
    <x-page-header
        title="Recent Dispatch Activity"
        description="Latest scanned and dispatched waybills"
        icon="fas fa-sync-alt"
    />

    <x-table>
        <x-slot:head>
            <x-table-th>Waybill Number</x-table-th>
            <x-table-th>Product</x-table-th>
            <x-table-th>Receiver</x-table-th>
            <x-table-th>Destination</x-table-th>
            <x-table-th>Scanned By</x-table-th>
            <x-table-th>Date</x-table-th>
        </x-slot:head>

        @forelse($recentScans as $scan)
            <tr class="hover:bg-dark-600 transition-colors">
                <x-table-td>
                    <x-waybill-badge :number="$scan->waybill_number" />
                </x-table-td>
                <x-table-td>{{ $scan->sender_name ?? '—' }}</x-table-td>
                <x-table-td>{{ $scan->receiver_name ?? '—' }}</x-table-td>
                <x-table-td>{{ $scan->destination ?? '—' }}</x-table-td>
                <x-table-td class="text-slate-400">{{ $scan->scanned_by }}</x-table-td>
                <x-table-td class="text-dark-100 text-xs">
                    {{ \Carbon\Carbon::parse($scan->scan_date)->format('M d, Y • H:i') }}
                </x-table-td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <x-empty-state
                        icon="fas fa-inbox"
                        title="No scans yet"
                        description="Start scanning waybills to see activity here"
                    />
                </td>
            </tr>
        @endforelse
    </x-table>
@endsection
