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

    <!-- Real-Time Status Monitoring Grid (8 cards in 2 rows) -->
    <div class="stats-grid stats-grid-4" role="region" aria-label="Waybill status monitoring">
        <article class="stat-card">
            <div class="stat-content">
                <h3>{{ number_format($stats['total_waybills']) }}</h3>
                <p>Total Waybills</p>
            </div>
        </article>

        <article class="stat-card stat-dispatched">
            <div class="stat-content">
                <h3>{{ number_format($stats['dispatched']) }}</h3>
                <p>Dispatched</p>
            </div>
        </article>

        <article class="stat-card stat-info">
            <div class="stat-content">
                <h3>{{ number_format($stats['in_transit']) }}</h3>
                <p>In Transit</p>
            </div>
        </article>

        <article class="stat-card stat-warning">
            <div class="stat-content">
                <h3>{{ number_format($stats['delivering']) }}</h3>
                <p>Delivering</p>
            </div>
        </article>

        <article class="stat-card stat-success">
            <div class="stat-content">
                <h3>{{ number_format($stats['delivered']) }}</h3>
                <p>Delivered</p>
            </div>
        </article>

        <article class="stat-card stat-returned">
            <div class="stat-content">
                <h3>{{ number_format($stats['returned']) }}</h3>
                <p>Returned</p>
            </div>
        </article>

        <article class="stat-card stat-info">
            <div class="stat-content">
                <h3>{{ number_format($stats['hq_scheduling']) }}</h3>
                <p>HQ Scheduling</p>
            </div>
        </article>

        <article class="stat-card stat-pending">
            <div class="stat-content">
                <h3>{{ number_format($stats['pending']) }}</h3>
                <p>Pending</p>
            </div>
        </article>
    </div>

    <!-- Period Stats (Delivery/Return Rates) -->
    <div class="stats-grid-secondary">
        <article class="stat-card stat-success">
            <div class="stat-content">
                <h3>{{ number_format($stats['delivery_rate'], 1) }}%</h3>
                <p>Delivery Rate (Period)</p>
            </div>
        </article>

        <article class="stat-card stat-warning">
            <div class="stat-content">
                <h3>{{ number_format($stats['return_rate'], 1) }}%</h3>
                <p>Return Rate (Period)</p>
            </div>
        </article>
    </div>


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
                            <th scope="col">Product</th>
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
