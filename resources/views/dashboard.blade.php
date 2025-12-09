@extends('layouts.app')

@section('title', 'Dashboard - Waybill System')

@section('content')
    <!-- Page Header with Icon -->
    <div class="section-header">
        <h2>
            <svg class="section-header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Dashboard
        </h2>
        <p>Overview of waybill statistics and recent activity</p>
    </div>

    <!-- Date Range Filter Section -->
    <section class="filter-section" aria-label="Date range filter">
        <form method="GET" action="{{ route('dashboard') }}">
            <div class="form-group">
                <label for="start_date">From</label>
                <input
                    type="date"
                    name="start_date"
                    id="start_date"
                    value="{{ $stats['start_date'] }}"
                >
            </div>
            <div class="form-group">
                <label for="end_date">To</label>
                <input
                    type="date"
                    name="end_date"
                    id="end_date"
                    value="{{ $stats['end_date'] }}"
                >
            </div>
            <button type="submit" class="btn btn-primary">Filter Stats</button>
        </form>
    </section>

    <!-- Stats Grid - Primary Row (4 columns) -->
    <div class="stats-grid stats-grid-4" role="region" aria-label="Waybill statistics">
        <article class="stat-card">
            <div class="stat-content">
                <h3>{{ number_format($stats['total_waybills']) }}</h3>
                <p>Total Waybills</p>
            </div>
        </article>

        <article class="stat-card stat-dispatched">
            <div class="stat-content">
                <h3>{{ number_format($stats['dispatched_waybills']) }}</h3>
                <p>Dispatched</p>
            </div>
        </article>

        <article class="stat-card stat-success">
            <div class="stat-content">
                <h3>{{ number_format($stats['delivery_rate'], 1) }}%</h3>
                <p>Delivered %</p>
            </div>
        </article>

        <article class="stat-card stat-returned">
            <div class="stat-content">
                <h3>{{ number_format($stats['return_rate'], 1) }}%</h3>
                <p>Returned %</p>
            </div>
        </article>
    </div>

    <!-- Stats Grid - Secondary Row (2 columns, half width) -->
    <div class="stats-grid-secondary">
        <article class="stat-card stat-warning">
            <div class="stat-content">
                <h3>{{ number_format($stats['return_rate'], 1) }}%</h3>
                <p>Return Rate</p>
            </div>
        </article>

        <article class="stat-card stat-pending">
            <div class="stat-content">
                <h3>{{ number_format($stats['pending_waybills']) }}</h3>
                <p>Pending</p>
            </div>
        </article>
    </div>

    <!-- All Waybills Section -->
    <section class="all-waybills" aria-label="Waybills table">
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
            <p>Complete overview of all waybills in the system</p>
        </div>

        <!-- Search and Filter Bar -->
        <div class="search-filter">
            <form method="GET" action="{{ route('dashboard') }}" class="filter-form">
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
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned</option>
                </select>

                <select name="limit" aria-label="Rows per page">
                    <option value="10" {{ request('limit') == 10 ? 'selected' : '' }}>10 rows</option>
                    <option value="25" {{ request('limit', 25) == 25 ? 'selected' : '' }}>25 rows</option>
                    <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50 rows</option>
                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>100 rows</option>
                </select>

                <button type="submit" class="btn btn-primary">Search</button>
                @if(request('search') || request('status'))
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Clear</a>
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
                                <td>
                                    {{ $waybill->signing_time ? \Carbon\Carbon::parse($waybill->signing_time)->format('M d, Y') : $waybill->created_at->format('M d, Y') }}
                                </td>
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
    </section>

    <!-- Recent Dispatch Scans Section -->
    <section class="recent-scans" aria-label="Recent scans">
        <div class="section-header">
            <h2>
                <svg class="section-header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                Recent Dispatch Scans
            </h2>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table role="table" aria-label="Recent scans list">
                    <thead>
                        <tr>
                            <th scope="col">Waybill Number</th>
                            <th scope="col">Sender</th>
                            <th scope="col">Receiver</th>
                            <th scope="col">Destination</th>
                            <th scope="col">Scanned By</th>
                            <th scope="col">Scan Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentScans as $scan)
                            <tr>
                                <td>
                                    <span class="waybill-badge">{{ $scan->waybill_number }}</span>
                                </td>
                                <td>{{ $scan->sender_name ?? 'N/A' }}</td>
                                <td>{{ $scan->receiver_name ?? 'N/A' }}</td>
                                <td>{{ $scan->destination ?? 'N/A' }}</td>
                                <td>{{ $scan->scanned_by }}</td>
                                <td>{{ \Carbon\Carbon::parse($scan->scan_date)->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <p>No scans yet</p>
                                    <small>Start scanning waybills to see them here</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
