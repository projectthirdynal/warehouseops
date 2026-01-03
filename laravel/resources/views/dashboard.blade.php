@extends('layouts.app')

@section('title', 'Dashboard - Waybill System')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Page Header -->
    <div class="section-header">
        <h2>
            <svg class="section-header-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Overview
        </h2>
        <p>Real-time waybill statistics and recent activity</p>
    </div>

    <!-- Date Range Filter -->
    <div class="filter-section">
        <form method="GET" action="{{ route('dashboard') }}">
            <div class="form-group">
                <label for="start_date">From</label>
                <input type="date" name="start_date" id="start_date" value="{{ $stats['start_date'] }}">
            </div>
            <div class="form-group">
                <label for="end_date">To</label>
                <input type="date" name="end_date" id="end_date" value="{{ $stats['end_date'] }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-filter" style="font-size: 11px;"></i>
                Apply Filter
            </button>
        </form>
    </div>

    <!-- Status Monitoring Grid -->
    <div class="stats-grid stats-grid-4">
        <article class="stat-card">
            <div class="stat-content">
                <h3>{{ number_format($stats['total_waybills']) }}</h3>
                <p>Total Waybills</p>
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

        <article class="stat-card stat-dispatched">
            <div class="stat-content">
                <h3>{{ number_format($stats['dispatched']) }}</h3>
                <p>Dispatched</p>
            </div>
        </article>
    </div>

    <!-- Period Stats -->
    <div class="stats-grid-secondary">
        <article class="stat-card stat-success">
            <div class="stat-content">
                <h3>{{ number_format($stats['delivery_rate'], 1) }}<small style="font-size: 16px; opacity: 0.7;">%</small></h3>
                <p>Delivery Rate</p>
            </div>
        </article>

        <article class="stat-card stat-warning">
            <div class="stat-content">
                <h3>{{ number_format($stats['return_rate'], 1) }}<small style="font-size: 16px; opacity: 0.7;">%</small></h3>
                <p>Return Rate</p>
            </div>
        </article>
    </div>

    <!-- Recent Scans Section -->
    <section class="recent-scans">
        <div class="section-header">
            <h2>
                <svg class="section-header-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                Recent Dispatch Activity
            </h2>
            <p>Latest scanned and dispatched waybills</p>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Waybill Number</th>
                            <th>Product</th>
                            <th>Receiver</th>
                            <th>Destination</th>
                            <th>Scanned By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentScans as $scan)
                            <tr>
                                <td>
                                    <span class="waybill-badge">{{ $scan->waybill_number }}</span>
                                </td>
                                <td>{{ $scan->sender_name ?? '—' }}</td>
                                <td>{{ $scan->receiver_name ?? '—' }}</td>
                                <td>{{ $scan->destination ?? '—' }}</td>
                                <td>
                                    <span style="color: var(--text-secondary);">{{ $scan->scanned_by }}</span>
                                </td>
                                <td>
                                    <span style="color: var(--text-tertiary); font-size: var(--text-xs);">
                                        {{ \Carbon\Carbon::parse($scan->scan_date)->format('M d, Y • H:i') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div style="padding: var(--space-6);">
                                        <i class="fas fa-inbox" style="font-size: 28px; color: var(--text-muted); margin-bottom: var(--space-3); display: block;"></i>
                                        <p style="margin-bottom: var(--space-1);">No scans yet</p>
                                        <small style="color: var(--text-muted);">Start scanning waybills to see activity here</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
