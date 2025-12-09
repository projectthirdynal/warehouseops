@extends('layouts.app')

@section('title', 'Accounts - Waybill System')

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

            <select name="status" aria-label="Filter by status">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="dispatched" {{ request('status') === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned</option>
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
            @if(request()->anyFilled(['search', 'status']))
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
                        <th scope="col">Sender</th>
                        <th scope="col">Receiver</th>
                        <th scope="col">Destination</th>
                        <th scope="col">Weight</th>
                        <th scope="col">Service</th>
                        <th scope="col">Status</th>
                        <th scope="col">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($waybills as $waybill)
                        <tr>
                            <td>
                                <span class="waybill-badge">{{ $waybill->waybill_number }}</span>
                            </td>
                            <td>
                                <strong>{{ $waybill->sender_name }}</strong><br>
                                <small>{{ $waybill->sender_phone }}</small>
                            </td>
                            <td>
                                <strong>{{ $waybill->receiver_name }}</strong><br>
                                <small>{{ $waybill->receiver_phone }}</small>
                            </td>
                            <td>{{ $waybill->destination }}</td>
                            <td>{{ number_format($waybill->weight, 2) }} kg</td>
                            <td>{{ $waybill->service_type }}</td>
                            <td>
                                <span class="status-badge status-{{ $waybill->status }}">
                                    {{ strtoupper($waybill->status) }}
                                </span>
                            </td>
                            <td>{{ $waybill->created_at->format('M d, Y') }}</td>
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
