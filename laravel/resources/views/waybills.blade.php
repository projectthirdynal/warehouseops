@extends('layouts.app')

@section('title', 'Waybills - Waybill System')
@section('page-title', 'Waybills')

@push('styles')
<style>
    /* Order Tracking Timeline */
    .order-tracking {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 4px;
        padding: 8px 0;
        min-width: 380px;
    }

    .tracking-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        position: relative;
    }

    .step-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--color-dark-700);
        border: 2px solid var(--color-dark-500);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-dark-100);
        transition: all 0.15s ease;
        flex-shrink: 0;
        font-size: 10px;
    }

    .tracking-step.completed .step-icon {
        background: rgba(34, 197, 94, 0.15);
        border-color: var(--color-success-500);
        color: var(--color-success-500);
    }

    .tracking-step.current .step-icon {
        background: rgba(59, 130, 246, 0.15);
        border-color: var(--color-info-500);
        color: var(--color-info-500);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        animation: pulse-soft 2s ease-in-out infinite;
    }

    @keyframes pulse-soft {
        0%, 100% { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
        50% { box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.1); }
    }

    .step-label {
        font-size: 9px;
        color: var(--color-dark-100);
        white-space: nowrap;
        font-weight: 500;
        text-align: center;
        max-width: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tracking-step.completed .step-label,
    .tracking-step.current .step-label {
        color: var(--color-slate-300);
        font-weight: 600;
    }

    .tracking-line {
        flex: 1;
        height: 2px;
        background: var(--color-dark-500);
        min-width: 16px;
        margin: 0 2px;
        margin-bottom: 18px;
        border-radius: 1px;
    }

    .tracking-line.completed {
        background: var(--color-success-500);
    }

    @media (max-width: 1200px) {
        .order-tracking {
            min-width: auto;
            flex-wrap: nowrap;
        }
        .step-label { font-size: 8px; max-width: 50px; }
        .step-icon { width: 24px; height: 24px; font-size: 9px; }
        .tracking-line { min-width: 12px; margin-bottom: 16px; }
    }

    /* Repeat Customer Badge */
    .repeat-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        margin-left: 8px;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    .repeat-badge i { font-size: 9px; }
</style>
@endpush

@section('content')
    <!-- Page Header -->
    <x-page-header
        title="All Waybills"
        description="Complete record of all waybills in the system"
        icon="fas fa-file-invoice"
    />

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <x-stat-card
            :value="$stats['total'] ?? 0"
            label="Total"
            variant="cyan"
        />
        <x-stat-card
            :value="$stats['in_transit'] ?? 0"
            label="In Transit"
            variant="info"
        />
        <x-stat-card
            :value="$stats['delivering'] ?? 0"
            label="Delivering"
            variant="warning"
        />
        <x-stat-card
            :value="$stats['delivered'] ?? 0"
            label="Delivered"
            variant="success"
        />
        <x-stat-card
            :value="$stats['returned'] ?? 0"
            label="Returned"
            variant="returned"
        />
        <x-stat-card
            :value="$stats['pending'] ?? 0"
            label="Pending"
            variant="pending"
        />
    </div>

    <!-- Search and Filter -->
    <x-filter-bar action="{{ route('waybills') }}">
        <x-form.input
            type="text"
            name="search"
            placeholder="Search waybill, sender, receiver, or phone..."
            :value="request('search')"
            class="flex-1 min-w-[240px]"
        />
        <x-form.select name="item_name" :value="request('item_name')" placeholder="All Products" class="min-w-[140px]">
            @foreach($productOptions as $product)
                <option value="{{ $product }}" {{ request('item_name') == $product ? 'selected' : '' }}>{{ $product }}</option>
            @endforeach
        </x-form.select>
        <x-form.select name="status" :value="request('status')" placeholder="All Status" class="min-w-[140px]">
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="dispatched" {{ request('status') === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
            <option value="in transit" {{ request('status') === 'in transit' ? 'selected' : '' }}>In Transit</option>
            <option value="delivering" {{ request('status') === 'delivering' ? 'selected' : '' }}>Delivering</option>
            <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
            <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned</option>
            <option value="headquarters scheduling to outlets" {{ request('status') === 'headquarters scheduling to outlets' ? 'selected' : '' }}>HQ Scheduling</option>
        </x-form.select>
        <x-form.select name="limit" :value="request('limit', 25)" placeholder="" class="w-24">
            <option value="10" {{ request('limit') == 10 ? 'selected' : '' }}>10 rows</option>
            <option value="25" {{ request('limit', 25) == 25 ? 'selected' : '' }}>25 rows</option>
            <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50 rows</option>
            <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100 rows</option>
        </x-form.select>
        <x-button type="submit" variant="primary" size="sm" icon="fas fa-search">
            Search
        </x-button>
        @if(request()->anyFilled(['search', 'status', 'item_name']))
            <x-button href="{{ route('waybills') }}" variant="secondary" size="sm" icon="fas fa-times">
                Clear
            </x-button>
        @endif
    </x-filter-bar>

    <!-- Results Info -->
    <div class="text-sm text-dark-100 text-center py-3">
        Showing <strong class="text-white">{{ number_format($waybills->count()) }}</strong> of <strong class="text-white">{{ number_format($waybills->total()) }}</strong> waybills
        @if($waybills->lastPage() > 1)
            &nbsp;•&nbsp; Page {{ $waybills->currentPage() }} of {{ $waybills->lastPage() }}
        @endif
    </div>

    <!-- Data Table -->
    <x-table>
        <x-slot:head>
            <x-table-th>Waybill #</x-table-th>
            <x-table-th>Product</x-table-th>
            <x-table-th>Receiver</x-table-th>
            <x-table-th>Destination</x-table-th>
            <x-table-th>Weight</x-table-th>
            <x-table-th>Order Tracking</x-table-th>
            <x-table-th>Date</x-table-th>
        </x-slot:head>

        @forelse($waybills as $waybill)
            <tr class="hover:bg-dark-600 transition-colors">
                <x-table-td>
                    <x-waybill-badge :number="$waybill->waybill_number" />
                </x-table-td>
                <x-table-td highlight>
                    {{ $waybill->item_name ?: $waybill->sender_name }}
                </x-table-td>
                <x-table-td>
                    <div>
                        @if($waybill->customer)
                            <a href="{{ route('customers.show', $waybill->customer->id) }}" class="text-white hover:text-gold-400 transition-colors border-b border-dashed border-dark-100">
                                <strong>{{ $waybill->receiver_name }}</strong>
                            </a>
                        @else
                            <strong class="text-white">{{ $waybill->receiver_name }}</strong>
                        @endif
                        @if($waybill->is_repeat_customer ?? false)
                            <span class="repeat-badge" title="Repeat customer - {{ $waybill->total_customer_orders }} total orders">
                                <i class="fas fa-redo-alt"></i> {{ $waybill->total_customer_orders }}
                            </span>
                        @endif
                        <br>
                        <small class="text-dark-100">{{ $waybill->receiver_phone }}</small>
                    </div>
                </x-table-td>
                <x-table-td>{{ $waybill->destination }}</x-table-td>
                <x-table-td>{{ number_format($waybill->weight, 2) }} kg</x-table-td>
                <x-table-td>
                    @php
                        $statusMap = [
                            'pending' => 0,
                            'headquarters scheduling to outlets' => 1,
                            'in transit' => 2,
                            'delivering' => 3,
                            'delivered' => 4,
                            'for return' => 4,
                            'returned' => 4
                        ];
                        $currentStep = $statusMap[strtolower($waybill->status)] ?? 0;
                        $isReturned = in_array(strtolower($waybill->status), ['returned', 'for return']);
                        $steps = [
                            ['label' => 'Pending', 'icon' => 'fa-box'],
                            ['label' => 'HQ', 'icon' => 'fa-building'],
                            ['label' => 'Transit', 'icon' => 'fa-truck'],
                            ['label' => 'Delivering', 'icon' => 'fa-motorcycle'],
                            ['label' => ($isReturned ? 'Returned' : 'Delivered'), 'icon' => ($isReturned ? 'fa-rotate-left' : 'fa-check')]
                        ];
                    @endphp
                    <div class="order-tracking">
                        @foreach($steps as $index => $step)
                            <div class="tracking-step {{ $index <= $currentStep ? 'completed' : '' }} {{ $index == $currentStep && $index < 4 ? 'current' : '' }}">
                                <div class="step-icon">
                                    <i class="fas {{ $step['icon'] }}"></i>
                                </div>
                                <div class="step-label">{{ $step['label'] }}</div>
                            </div>
                            @if($index < count($steps) - 1)
                                <div class="tracking-line {{ $index < $currentStep ? 'completed' : '' }}"></div>
                            @endif
                        @endforeach
                    </div>
                </x-table-td>
                <x-table-td class="text-dark-100 text-xs">
                    {{ $waybill->signing_time ? $waybill->signing_time->format('M d, Y') : $waybill->created_at->format('M d, Y') }}
                </x-table-td>
            </tr>
        @empty
            <tr>
                <td colspan="7">
                    <x-empty-state
                        icon="fas fa-file-invoice"
                        title="No waybills found"
                        description="Try adjusting your search filters"
                    />
                </td>
            </tr>
        @endforelse

        <x-slot:footer>
            <div class="pagination-wrapper">
                {{ $waybills->links('vendor.pagination.custom') }}
            </div>
        </x-slot:footer>
    </x-table>
@endsection
