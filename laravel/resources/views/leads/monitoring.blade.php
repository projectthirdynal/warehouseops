@extends('layouts.app')

@section('title', 'Monitoring - Waybill System')
@section('page-title', 'Lead Monitoring')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ============================================
   LEADS MONITORING - FRESH REDESIGN
   ============================================ */

:root {
    --monitor-bg: #0a0c10;
    --monitor-surface: #12151c;
    --monitor-surface-2: #1a1e28;
    --monitor-border: rgba(255, 255, 255, 0.06);
    --monitor-border-strong: rgba(255, 255, 255, 0.12);
    --monitor-text: #f1f3f5;
    --monitor-text-muted: #8b919e;
    --monitor-text-dim: #5a5f6d;
    --monitor-accent: #6366f1;
    --monitor-accent-soft: rgba(99, 102, 241, 0.12);
    --monitor-cyan: #22d3ee;
    --monitor-emerald: #34d399;
    --monitor-amber: #fbbf24;
    --monitor-rose: #fb7185;
    --monitor-radius: 12px;
    --monitor-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.monitor-module {
    font-family: 'Plus Jakarta Sans', sans-serif;
    min-height: 100vh;
    background: var(--monitor-bg);
}

/* --- Header Section --- */
.monitor-header {
    padding: 28px 32px;
    background: var(--monitor-surface);
    border-bottom: 1px solid var(--monitor-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.header-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, var(--monitor-cyan), #67e8f9);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #0a0c10;
}

.header-text h1 {
    font-size: 22px;
    font-weight: 700;
    color: var(--monitor-text);
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
}

.header-text p {
    font-size: 13px;
    color: var(--monitor-text-muted);
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.header-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all var(--monitor-transition);
    border: 1px solid var(--monitor-border);
    background: var(--monitor-surface-2);
    color: var(--monitor-text-muted);
}

.header-btn:hover {
    background: var(--monitor-surface);
    color: var(--monitor-text);
    border-color: var(--monitor-border-strong);
}

.header-btn i {
    font-size: 12px;
}

/* --- Stats Grid --- */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    padding: 24px 32px;
    background: var(--monitor-surface);
    border-bottom: 1px solid var(--monitor-border);
}

@media (max-width: 1000px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

.stat-card {
    background: var(--monitor-surface-2);
    border: 1px solid var(--monitor-border);
    border-radius: var(--monitor-radius);
    padding: 20px;
    text-align: center;
}

.stat-label {
    font-size: 10px;
    font-weight: 700;
    color: var(--monitor-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--monitor-cyan);
    letter-spacing: -0.02em;
}

.stat-value.success { color: var(--monitor-emerald); }
.stat-value.info { color: var(--monitor-accent); }
.stat-value.warning { color: var(--monitor-amber); }

.stat-actions {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
}

.stat-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    background: var(--monitor-surface);
    border: 1px solid var(--monitor-border);
    color: var(--monitor-text-muted);
    transition: all var(--monitor-transition);
}

.stat-btn:hover {
    color: var(--monitor-text);
    border-color: var(--monitor-border-strong);
}

.stat-btn i {
    font-size: 11px;
}

/* --- Filters Section --- */
.monitor-filters {
    padding: 16px 32px;
    background: var(--monitor-surface);
    border-bottom: 1px solid var(--monitor-border);
}

.filter-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.filter-input {
    flex: 1;
    max-width: 280px;
    background: var(--monitor-surface-2);
    border: 1px solid var(--monitor-border);
    border-radius: 10px;
    padding: 10px 14px;
    color: var(--monitor-text);
    font-size: 13px;
    font-family: inherit;
}

.filter-input::placeholder {
    color: var(--monitor-text-dim);
}

.filter-input:focus {
    outline: none;
    border-color: var(--monitor-accent);
}

.filter-select {
    background: var(--monitor-surface-2);
    border: 1px solid var(--monitor-border);
    border-radius: 10px;
    padding: 10px 36px 10px 14px;
    color: var(--monitor-text);
    font-size: 12px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238b919e' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    min-width: 140px;
}

.filter-select:focus {
    outline: none;
    border-color: var(--monitor-accent);
}

.filter-reset {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    color: var(--monitor-text-muted);
    border: 1px solid transparent;
    transition: all var(--monitor-transition);
}

.filter-reset:hover {
    background: var(--monitor-surface-2);
    color: var(--monitor-text);
}

/* --- Data Table --- */
.monitor-table-wrapper {
    padding: 0 32px 32px;
}

.monitor-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 24px;
}

.monitor-table th {
    background: var(--monitor-surface-2);
    padding: 14px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: var(--monitor-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid var(--monitor-border);
}

.monitor-table th:first-child {
    border-radius: var(--monitor-radius) 0 0 0;
    padding-left: 20px;
}

.monitor-table th:last-child {
    border-radius: 0 var(--monitor-radius) 0 0;
    text-align: center;
}

.monitor-table td {
    padding: 16px;
    border-bottom: 1px solid var(--monitor-border);
    vertical-align: middle;
}

.monitor-table td:first-child {
    padding-left: 20px;
}

.monitor-table tbody tr {
    background: var(--monitor-surface);
    transition: all var(--monitor-transition);
}

.monitor-table tbody tr:hover {
    background: var(--monitor-surface-2);
}

/* Agent Cell */
.agent-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.agent-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--monitor-accent), #818cf8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.agent-info {
    display: flex;
    flex-direction: column;
}

.agent-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--monitor-text);
}

.agent-username {
    font-size: 12px;
    color: var(--monitor-text-muted);
}

/* Customer Cell */
.customer-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.customer-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--monitor-text);
}

.customer-phone {
    font-size: 12px;
    color: var(--monitor-cyan);
    font-weight: 500;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.status-badge.new { background: var(--monitor-accent-soft); color: var(--monitor-accent); }
.status-badge.calling { background: rgba(34, 211, 238, 0.12); color: var(--monitor-cyan); }
.status-badge.no_answer { background: rgba(251, 191, 36, 0.12); color: var(--monitor-amber); }
.status-badge.callback { background: rgba(59, 130, 246, 0.12); color: #60a5fa; }
.status-badge.sale { background: rgba(52, 211, 153, 0.12); color: var(--monitor-emerald); }
.status-badge.delivered { background: rgba(52, 211, 153, 0.12); color: var(--monitor-emerald); }
.status-badge.reject { background: rgba(251, 113, 133, 0.12); color: var(--monitor-rose); }
.status-badge.cancelled { background: rgba(251, 113, 133, 0.12); color: var(--monitor-rose); }
.status-badge.reorder { background: rgba(139, 92, 246, 0.12); color: #a78bfa; }

/* Activity Cell */
.activity-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.activity-time {
    font-size: 13px;
    color: var(--monitor-text);
}

.activity-date {
    font-size: 11px;
    color: var(--monitor-text-dim);
}

.activity-never {
    font-size: 13px;
    color: var(--monitor-text-dim);
    font-style: italic;
}

/* Notes Cell */
.notes-preview {
    font-size: 12px;
    color: var(--monitor-text-muted);
    max-width: 160px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notes-empty {
    font-size: 12px;
    color: var(--monitor-text-dim);
}

/* Action Button */
.view-btn {
    background: var(--monitor-surface-2);
    border: 1px solid var(--monitor-border);
    color: var(--monitor-text-muted);
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all var(--monitor-transition);
}

.view-btn:hover {
    background: var(--monitor-accent);
    border-color: var(--monitor-accent);
    color: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--monitor-text-dim);
}

.empty-state i {
    font-size: 36px;
    margin-bottom: 16px;
    opacity: 0.3;
    display: block;
}

.empty-state h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--monitor-text-muted);
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 13px;
}

/* --- Pagination --- */
.monitor-pagination {
    display: flex;
    justify-content: center;
    padding: 24px 0;
}

/* ============================================
   SIDE PANEL
   ============================================ */

.panel-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.panel-overlay.active {
    opacity: 1;
    visibility: visible;
}

.details-panel {
    position: fixed;
    top: 0;
    right: -480px;
    width: 480px;
    max-width: 95vw;
    height: 100vh;
    background: var(--monitor-bg);
    z-index: 1001;
    display: flex;
    flex-direction: column;
    transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: -15px 0 50px rgba(0, 0, 0, 0.5);
}

.details-panel.active {
    right: 0;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--monitor-border);
    background: var(--monitor-surface);
}

.panel-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--monitor-text);
    margin: 0;
}

.panel-close {
    width: 36px;
    height: 36px;
    background: var(--monitor-surface-2);
    border: 1px solid var(--monitor-border);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--monitor-text-muted);
    cursor: pointer;
    transition: all var(--monitor-transition);
}

.panel-close:hover {
    background: var(--monitor-surface);
    color: var(--monitor-text);
}

.panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
}

/* Detail Groups */
.detail-group {
    margin-bottom: 24px;
}

.detail-label {
    font-size: 10px;
    font-weight: 700;
    color: var(--monitor-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 8px;
}

.detail-value {
    font-size: 15px;
    color: var(--monitor-text);
    font-weight: 500;
}

.detail-value.phone {
    color: var(--monitor-cyan);
    font-weight: 600;
}

.detail-card {
    background: var(--monitor-surface);
    border: 1px solid var(--monitor-border);
    border-radius: var(--monitor-radius);
    padding: 16px;
}

.detail-card-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--monitor-text);
    margin-bottom: 4px;
}

.detail-card-subtitle {
    font-size: 12px;
    color: var(--monitor-text-muted);
    margin-bottom: 12px;
}

.detail-card-amount {
    font-size: 22px;
    font-weight: 800;
    color: var(--monitor-emerald);
}

/* Status Pill */
.status-pill {
    display: inline-flex;
    padding: 8px 18px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-pill.sale {
    background: rgba(52, 211, 153, 0.12);
    color: var(--monitor-emerald);
    border: 1px solid rgba(52, 211, 153, 0.25);
}

.status-pill.reject {
    background: rgba(251, 113, 133, 0.12);
    color: var(--monitor-rose);
    border: 1px solid rgba(251, 113, 133, 0.25);
}

.status-pill.default {
    background: var(--monitor-surface-2);
    color: var(--monitor-text-muted);
    border: 1px solid var(--monitor-border);
}

.detail-notes {
    background: var(--monitor-surface);
    border: 1px solid var(--monitor-border);
    border-radius: var(--monitor-radius);
    padding: 16px;
    font-size: 13px;
    color: var(--monitor-text-muted);
    min-height: 80px;
    line-height: 1.5;
}

/* Scrollbar */
.panel-body::-webkit-scrollbar {
    width: 6px;
}

.panel-body::-webkit-scrollbar-track {
    background: transparent;
}

.panel-body::-webkit-scrollbar-thumb {
    background: var(--monitor-border-strong);
    border-radius: 3px;
}

/* Responsive */
@media (max-width: 900px) {
    .monitor-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .header-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 600px) {
    .monitor-header,
    .stats-grid,
    .monitor-filters,
    .monitor-table-wrapper {
        padding-left: 16px;
        padding-right: 16px;
    }
    
    .details-panel {
        width: 100%;
        right: -100%;
    }
}
</style>
@endpush

@section('content')
<div class="monitor-module">
    <!-- Header -->
    <div class="monitor-header">
        <div class="header-left">
            <div class="header-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="header-text">
                <h1>Operations Monitoring</h1>
                <p>Real-time oversight of all assigned and called leads</p>
            </div>
        </div>
        
        <div class="header-actions">
            <a href="{{ route('leads.index') }}" class="header-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Leads
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Operations</div>
            <div class="stat-value info">{{ $leads->total() }}</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Active Agents</div>
            <div class="stat-value success">{{ $agents->count() }}</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Current Page</div>
            <div class="stat-value">{{ $leads->count() }}</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Quick Export</div>
            <div class="stat-actions">
                <a href="{{ route('leads.export') }}?{{ http_build_query(request()->all()) }}" class="stat-btn">
                    <i class="fas fa-download"></i>
                    CSV
                </a>
                <a href="{{ route('leads.exportJNT') }}?{{ http_build_query(request()->all()) }}" class="stat-btn">
                    <i class="fas fa-file-excel"></i>
                    J&T
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="monitor-filters">
        <form action="{{ route('leads.monitoring') }}" method="GET" class="filter-row">
            <input type="text" name="search" class="filter-input" placeholder="Search by name or phone..." value="{{ request('search') }}">

            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                @foreach(['NEW', 'CALLING', 'NO_ANSWER', 'REJECT', 'CALLBACK', 'SALE', 'REORDER', 'DELIVERED', 'CANCELLED'] as $st)
                    <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>

            <select name="agent_id" class="filter-select" onchange="this.form.submit()">
                <option value="">All Agents</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->username }}</option>
                @endforeach
            </select>

            <select name="previous_item" class="filter-select" onchange="this.form.submit()">
                <option value="">All Products</option>
                @foreach($productOptions as $product)
                    <option value="{{ $product }}" {{ request('previous_item') == $product ? 'selected' : '' }}>{{ $product }}</option>
                @endforeach
            </select>

            @if(request()->anyFilled(['search', 'status', 'agent_id', 'date_from', 'date_to', 'previous_item']))
                <a href="{{ route('leads.monitoring') }}" class="filter-reset">
                    <i class="fas fa-times"></i>
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Data Table -->
    <div class="monitor-table-wrapper">
        <table class="monitor-table">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Last Activity</th>
                    <th>Notes</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                <tr>
                    <td>
                        <div class="agent-cell">
                            <div class="agent-avatar">
                                {{ strtoupper(substr($lead->assignedAgent->username ?? 'NA', 0, 2)) }}
                            </div>
                            <div class="agent-info">
                                <span class="agent-name">{{ $lead->assignedAgent->name ?? 'Unassigned' }}</span>
                                <span class="agent-username">{{ $lead->assignedAgent->username ?? '—' }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="customer-cell">
                            <span class="customer-name">{{ $lead->name }}</span>
                            <span class="customer-phone">{{ $lead->phone }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge {{ strtolower($lead->status) }}">{{ $lead->status }}</span>
                    </td>
                    <td>
                        @if($lead->last_called_at)
                            <div class="activity-cell">
                                <span class="activity-time">{{ $lead->last_called_at->diffForHumans() }}</span>
                                <span class="activity-date">{{ $lead->last_called_at->setTimezone('Asia/Manila')->format('M d, h:i A') }}</span>
                            </div>
                        @else
                            <span class="activity-never">No activity</span>
                        @endif
                    </td>
                    <td>
                        @if($lead->notes)
                            <span class="notes-preview">{{ $lead->notes }}</span>
                        @else
                            <span class="notes-empty">—</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="view-btn view-lead-btn" data-lead="{{ json_encode($lead) }}">
                            <i class="fas fa-eye"></i>
                            View
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No Operations Found</h3>
                            <p>Try adjusting your filters</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="monitor-pagination">
            {{ $leads->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>

<!-- Side Panel Overlay -->
<div class="panel-overlay" id="panelOverlay"></div>

<!-- Details Panel -->
<div class="details-panel" id="detailsPanel">
    <div class="panel-header">
        <h3 class="panel-title">Lead Details</h3>
        <button type="button" class="panel-close" id="closePanel">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="panel-body">
        <div class="detail-group">
            <div class="detail-label">Customer Name</div>
            <div class="detail-value" id="panelName">—</div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Phone Number</div>
            <div class="detail-value phone" id="panelPhone">—</div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Location</div>
            <div class="detail-card">
                <div class="detail-card-title" id="panelCity">—</div>
                <div class="detail-card-subtitle" id="panelAddress">—</div>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Order Details</div>
            <div class="detail-card">
                <div class="detail-card-title" id="panelProduct">—</div>
                <div class="detail-card-subtitle" id="panelBrand">—</div>
                <div class="detail-card-amount" id="panelAmount">—</div>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Status</div>
            <div id="panelStatus" class="status-pill default">—</div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Notes</div>
            <div class="detail-notes" id="panelNotes">—</div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Call Attempts</div>
            <div class="detail-value" id="panelAttempts">0</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const panel = document.getElementById('detailsPanel');
    const overlay = document.getElementById('panelOverlay');
    const closeBtn = document.getElementById('closePanel');
    const viewBtns = document.querySelectorAll('.view-lead-btn');

    function openPanel(lead) {
        document.getElementById('panelName').textContent = lead.name || '—';
        document.getElementById('panelPhone').textContent = lead.phone || '—';
        document.getElementById('panelCity').textContent = lead.city || 'Unknown';
        document.getElementById('panelAddress').textContent = [lead.street, lead.barangay, lead.address].filter(Boolean).join(', ') || '—';
        document.getElementById('panelProduct').textContent = lead.product_name || 'No product';
        document.getElementById('panelBrand').textContent = lead.product_brand || '—';
        document.getElementById('panelAmount').textContent = lead.amount ? '₱' + parseFloat(lead.amount).toFixed(2) : '—';
        document.getElementById('panelNotes').textContent = lead.notes || 'No notes';
        document.getElementById('panelAttempts').textContent = lead.call_attempts || 0;

        const statusEl = document.getElementById('panelStatus');
        statusEl.textContent = lead.status || '—';
        statusEl.className = 'status-pill ' + (lead.status === 'SALE' || lead.status === 'DELIVERED' ? 'sale' : lead.status === 'REJECT' || lead.status === 'CANCELLED' ? 'reject' : 'default');

        panel.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closePanel() {
        panel.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const lead = JSON.parse(this.dataset.lead);
            openPanel(lead);
        });
    });

    closeBtn.addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);
});
</script>
@endpush
