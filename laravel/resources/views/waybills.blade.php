@extends('layouts.app')

@section('title', 'Waybills - Waybill System')

@section('content')
    <!-- Page Header with Icon -->
    <div class="section-header">
        <h2>
            <svg class="section-header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            All Uploaded Waybills
        </h2>
        <p>Complete record of all waybills in the system</p>
    </div>

    <!-- Stats Grid - 6 cards in ONE ROW -->
    <div class="stats-grid stats-grid-6" role="region" aria-label="Waybill statistics">
        <article class="stat-card">
            <div class="stat-content">
                <h3>{{ number_format($stats['total'] ?? 0) }}</h3>
                <p>Total</p>
            </div>
        </article>

        <article class="stat-card stat-dispatched">
            <div class="stat-content">
                <h3>{{ number_format($stats['dispatched'] ?? 0) }}</h3>
                <p>Dispatched</p>
            </div>
        </article>

        <article class="stat-card stat-info">
            <div class="stat-content">
                <h3>{{ number_format($stats['in_transit'] ?? 0) }}</h3>
                <p>In Transit</p>
            </div>
        </article>

        <article class="stat-card stat-success">
            <div class="stat-content">
                <h3>{{ number_format($stats['delivered'] ?? 0) }}</h3>
                <p>Delivered</p>
            </div>
        </article>

        <article class="stat-card stat-returned">
            <div class="stat-content">
                <h3>{{ number_format($stats['returned'] ?? 0) }}</h3>
                <p>Returned</p>
            </div>
        </article>

        <article class="stat-card stat-pending">
            <div class="stat-content">
                <h3>{{ number_format($stats['pending'] ?? 0) }}</h3>
                <p>Pending</p>
            </div>
        </article>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter">
        <form method="GET" action="{{ route('waybills') }}" class="filter-form">
            <input
                type="text"
                name="search"
                placeholder="Search waybill, sender, receiver, or destination..."
                value="{{ request('search') }}"
                aria-label="Search waybills"
            >

            <select name="item_name" aria-label="Filter by product">
                <option value="">All Products</option>
                @foreach($productOptions as $product)
                    <option value="{{ $product }}" {{ request('item_name') == $product ? 'selected' : '' }}>{{ $product }}</option>
                @endforeach
            </select>

            <select name="status" aria-label="Filter by status">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="dispatched" {{ request('status') === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                <option value="delivering" {{ request('status') === 'delivering' ? 'selected' : '' }}>Delivering</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned</option>
                <option value="hq_scheduling" {{ request('status') === 'hq_scheduling' ? 'selected' : '' }}>HQ Scheduling</option>
            </select>

            <select name="limit" aria-label="Rows per page">
                <option value="10" {{ request('limit') == 10 ? 'selected' : '' }}>10 rows</option>
                <option value="25" {{ request('limit', 25) == 25 ? 'selected' : '' }}>25 rows</option>
                <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50 rows</option>
                <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100 rows</option>
            </select>

            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Search
            </button>
            @if(request()->anyFilled(['search', 'status', 'item_name']))
                <a href="{{ route('waybills') }}" class="btn btn-secondary">Clear</a>
            @endif
        </form>
    </div>

    <!-- Results Info -->
    <div class="waybills-info" role="status" aria-live="polite">
        Showing {{ number_format($waybills->count()) }} of {{ number_format($waybills->total()) }} waybills
        @if($waybills->lastPage() > 1)
            | Page {{ $waybills->currentPage() }} of {{ $waybills->lastPage() }}
        @endif
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table role="table" aria-label="Waybills list">
                <thead>
                    <tr>
                        <th scope="col">Waybill #</th>
                        <th scope="col">Product</th>
                        <th scope="col">Receiver</th>
                        <th scope="col">Destination</th>
                        <th scope="col">Weight</th>
                        <th scope="col">Service</th>
                        <th scope="col">Order Tracking</th>
                        <th scope="col">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($waybills as $waybill)
                        <tr>
                            <td>
                                <span class="waybill-badge">{{ $waybill->waybill_number }}</span>
                            </td>
                            <td>
                                <strong>{{ $waybill->item_name ?: $waybill->sender_name }}</strong>
                            </td>
                            <td>
                                <strong>{{ $waybill->receiver_name }}</strong><br>
                                <small>{{ $waybill->receiver_phone }}</small>
                            </td>
                            <td>{{ $waybill->destination }}</td>
                            <td>{{ number_format($waybill->weight, 2) }} kg</td>
                            <td>{{ $waybill->service_type }}</td>
                            <td>
                                @php
                                    // Map database status to tracking steps
                                    $statusMap = [
                                        'pending' => 0,
                                        'headquarters scheduling to outlets' => 1,
                                        'in transit' => 2,
                                        'delivering' => 3,
                                        'delivered' => 4,
                                        'for return' => 4,
                                        'returned' => 4
                                    ];
                                    $currentStep = $statusMap[$waybill->status] ?? 0;
                                    $steps = [
                                        ['label' => 'Pending', 'icon' => 'fa-box'],
                                        ['label' => 'HQ Scheduling', 'icon' => 'fa-calendar'],
                                        ['label' => 'In Transit', 'icon' => 'fa-truck'],
                                        ['label' => 'Out for Delivery', 'icon' => 'fa-shipping-fast'],
                                        ['label' => (in_array($waybill->status, ['returned', 'for return']) ? 'Returned' : 'Delivered'), 'icon' => 'fa-check-circle']
                                    ];
                                @endphp
                                <div class="order-tracking">
                                    @foreach($steps as $index => $step)
                                        <div class="tracking-step {{ $index <= $currentStep ? 'completed' : '' }} {{ $index == $currentStep ? 'current' : '' }}">
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
                            </td>
                            <td>{{ $waybill->signing_time ? $waybill->signing_time->format('M d, Y') : $waybill->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">
                                <p>No waybills found</p>
                                <small>Try adjusting your search filters</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-controls">
        {{ $waybills->links('vendor.pagination.custom') }}
    </div>
@endsection

@push('styles')
<style>
    /* Order Tracking Timeline Styles */
    .order-tracking {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        padding: 12px 0;
        min-width: 450px;
    }

    .tracking-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        position: relative;
    }

    .step-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--bg-tertiary);
        border: 2px solid var(--border-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-tertiary);
        transition: all var(--transition-fast);
        flex-shrink: 0;
    }

    .tracking-step.completed .step-icon {
        background: var(--status-success);
        border-color: var(--status-success);
        color: white;
    }

    .tracking-step.current .step-icon {
        background: var(--accent-primary);
        border-color: var(--accent-primary);
        color: white;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }
        50% {
            box-shadow: 0 0 0 8px rgba(59, 130, 246, 0.1);
        }
    }

    .step-label {
        font-size: 10px;
        color: var(--text-tertiary);
        white-space: nowrap;
        font-weight: var(--font-medium);
        text-align: center;
    }

    .tracking-step.completed .step-label,
    .tracking-step.current .step-label {
        color: var(--text-primary);
        font-weight: var(--font-semibold);
    }

    .tracking-line {
        flex: 1;
        height: 2px;
        background: var(--border-primary);
        min-width: 24px;
        margin: 0 4px;
        margin-bottom: 20px; /* Offset for label */
    }

    .tracking-line.completed {
        background: var(--status-success);
    }

    .step-icon i {
        font-size: 14px;
    }

    /* Make table responsive for tracking column */
    @media (max-width: 1400px) {
        .order-tracking {
            min-width: auto;
            gap: 4px;
        }

        .step-label {
            font-size: 8px;
        }

        .step-icon {
            width: 28px;
            height: 28px;
        }

        .step-icon i {
            font-size: 12px;
        }

        .tracking-line {
            min-width: 16px;
        }
    }
</style>
@endpush
