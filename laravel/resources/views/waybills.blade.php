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
        background: var(--bg-tertiary);
        border: 2px solid var(--border-default);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        transition: all var(--transition-fast);
        flex-shrink: 0;
        font-size: 10px;
    }

    .tracking-step.completed .step-icon {
        background: rgba(34, 197, 94, 0.15);
        border-color: var(--accent-green);
        color: var(--accent-green);
    }

    .tracking-step.current .step-icon {
        background: rgba(59, 130, 246, 0.15);
        border-color: var(--accent-blue);
        color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        animation: pulse-soft 2s ease-in-out infinite;
    }

    @keyframes pulse-soft {
        0%, 100% { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
        50% { box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.1); }
    }

    .step-label {
        font-size: 9px;
        color: var(--text-muted);
        white-space: nowrap;
        font-weight: var(--font-medium);
        text-align: center;
        max-width: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tracking-step.completed .step-label,
    .tracking-step.current .step-label {
        color: var(--text-secondary);
        font-weight: var(--font-semibold);
    }

    .tracking-line {
        flex: 1;
        height: 2px;
        background: var(--border-default);
        min-width: 16px;
        margin: 0 2px;
        margin-bottom: 18px;
        border-radius: 1px;
    }

    .tracking-line.completed {
        background: var(--accent-green);
    }

    /* Filter form adjustments */
    .filter-form {
        display: flex;
        gap: var(--space-3);
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-form input[type="text"] {
        flex: 2;
        min-width: 240px;
    }

    .filter-form select {
        min-width: 140px;
    }

    @media (max-width: 1200px) {
        .order-tracking {
            min-width: auto;
            flex-wrap: nowrap;
        }

        .step-label {
            font-size: 8px;
            max-width: 50px;
        }

        .step-icon {
            width: 24px;
            height: 24px;
            font-size: 9px;
        }

        .tracking-line {
            min-width: 12px;
            margin-bottom: 16px;
        }
    }

    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
        }

        .filter-form input,
        .filter-form select,
        .filter-form .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="section-header">
        <h2>
            <svg class="section-header-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            All Waybills
        </h2>
        <p>Complete record of all waybills in the system</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid stats-grid-6">
        <article class="stat-card">
            <div class="stat-content">
                <h3>{{ number_format($stats['total'] ?? 0) }}</h3>
                <p>Total</p>
            </div>
        </article>

        <article class="stat-card stat-info">
            <div class="stat-content">
                <h3>{{ number_format($stats['in_transit'] ?? 0) }}</h3>
                <p>In Transit</p>
            </div>
        </article>

        <article class="stat-card stat-warning">
            <div class="stat-content">
                <h3>{{ number_format($stats['delivering'] ?? 0) }}</h3>
                <p>Delivering</p>
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

    <!-- Search and Filter -->
    <div class="search-filter">
        <form method="GET" action="{{ route('waybills') }}" class="filter-form">
            <input
                type="text"
                name="search"
                placeholder="Search waybill, sender, receiver, or phone..."
                value="{{ request('search') }}"
            >

            <select name="item_name">
                <option value="">All Products</option>
                @foreach($productOptions as $product)
                    <option value="{{ $product }}" {{ request('item_name') == $product ? 'selected' : '' }}>{{ $product }}</option>
                @endforeach
            </select>

            <select name="status">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="dispatched" {{ request('status') === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                <option value="in transit" {{ request('status') === 'in transit' ? 'selected' : '' }}>In Transit</option>
                <option value="delivering" {{ request('status') === 'delivering' ? 'selected' : '' }}>Delivering</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned</option>
                <option value="headquarters scheduling to outlets" {{ request('status') === 'headquarters scheduling to outlets' ? 'selected' : '' }}>HQ Scheduling</option>
            </select>

            <select name="limit">
                <option value="10" {{ request('limit') == 10 ? 'selected' : '' }}>10 rows</option>
                <option value="25" {{ request('limit', 25) == 25 ? 'selected' : '' }}>25 rows</option>
                <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50 rows</option>
                <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100 rows</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search" style="font-size: 11px;"></i>
                Search
            </button>
            
            @if(request()->anyFilled(['search', 'status', 'item_name']))
                <a href="{{ route('waybills') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times" style="font-size: 11px;"></i>
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Results Info -->
    <div class="waybills-info">
        Showing <strong>{{ number_format($waybills->count()) }}</strong> of <strong>{{ number_format($waybills->total()) }}</strong> waybills
        @if($waybills->lastPage() > 1)
            &nbsp;•&nbsp; Page {{ $waybills->currentPage() }} of {{ $waybills->lastPage() }}
        @endif
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Waybill #</th>
                        <th>Product</th>
                        <th>Receiver</th>
                        <th>Destination</th>
                        <th>Weight</th>
                        <th>Order Tracking</th>
                        <th>Date</th>
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
                                <div>
                                    <strong>{{ $waybill->receiver_name }}</strong>
                                    <br>
                                    <small>{{ $waybill->receiver_phone }}</small>
                                </div>
                            </td>
                            <td>{{ $waybill->destination }}</td>
                            <td>{{ number_format($waybill->weight, 2) }} kg</td>
                            <td>
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
                            </td>
                            <td>
                                <span style="color: var(--text-tertiary); font-size: var(--text-xs);">
                                    {{ $waybill->signing_time ? $waybill->signing_time->format('M d, Y') : $waybill->created_at->format('M d, Y') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div style="padding: var(--space-6);">
                                    <i class="fas fa-file-invoice" style="font-size: 28px; color: var(--text-muted); margin-bottom: var(--space-3); display: block;"></i>
                                    <p style="margin-bottom: var(--space-1);">No waybills found</p>
                                    <small style="color: var(--text-muted);">Try adjusting your search filters</small>
                                </div>
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
