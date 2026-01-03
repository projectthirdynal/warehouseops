@extends('layouts.app')

@section('title', 'Monitoring - Waybill System')
@section('page-title', 'Lead Monitoring')

@push('styles')
<style>
    .monitoring-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: var(--space-4);
        margin-bottom: var(--space-5);
    }

    @media (max-width: 900px) {
        .monitoring-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .monitoring-stats {
            grid-template-columns: 1fr;
        }
    }

    .mini-stat {
        background: var(--bg-card);
        border: 1px solid var(--border-default);
        border-radius: var(--radius-xl);
        padding: var(--space-4);
        text-align: center;
    }

    .mini-stat-label {
        font-size: var(--text-2xs);
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: var(--space-1);
    }

    .mini-stat-value {
        font-size: var(--text-2xl);
        font-weight: var(--font-bold);
        color: var(--accent-cyan);
    }

    .mini-stat-value.success { color: var(--accent-green); }
    .mini-stat-value.info { color: var(--accent-blue); }

    .user-avatar-sm {
        width: 32px;
        height: 32px;
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-cyan) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: var(--font-bold);
        font-size: var(--text-xs);
        flex-shrink: 0;
    }

    /* Side Panel */
    .side-panel-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all var(--transition-base);
    }

    .side-panel-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .side-panel {
        position: fixed;
        top: 0;
        right: -480px;
        width: 480px;
        height: 100vh;
        background: var(--bg-secondary);
        border-left: 1px solid var(--border-default);
        z-index: 1001;
        transition: right var(--transition-slow);
        display: flex;
        flex-direction: column;
    }

    .side-panel.active {
        right: 0;
    }

    .side-panel-header {
        padding: var(--space-5);
        border-bottom: 1px solid var(--border-default);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .side-panel-title {
        font-size: var(--text-lg);
        font-weight: var(--font-semibold);
        color: var(--text-primary);
    }

    .side-panel-close {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: var(--space-2);
        font-size: var(--text-lg);
        transition: color var(--transition-fast);
    }

    .side-panel-close:hover {
        color: var(--text-primary);
    }

    .side-panel-body {
        flex: 1;
        overflow-y: auto;
        padding: var(--space-5);
    }

    .detail-group {
        margin-bottom: var(--space-5);
    }

    .detail-label {
        font-size: var(--text-2xs);
        font-weight: var(--font-semibold);
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: var(--space-2);
    }

    .detail-value {
        font-size: var(--text-md);
        color: var(--text-primary);
    }

    .detail-card {
        background: var(--bg-tertiary);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-lg);
        padding: var(--space-4);
    }

    .status-pill {
        display: inline-flex;
        padding: 6px 16px;
        border-radius: var(--radius-full);
        font-size: var(--text-xs);
        font-weight: var(--font-bold);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-pill.sale {
        background: rgba(34, 197, 94, 0.15);
        color: var(--accent-green);
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .status-pill.reject {
        background: rgba(239, 68, 68, 0.15);
        color: var(--accent-red);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .status-pill.default {
        background: var(--bg-input);
        color: var(--text-secondary);
        border: 1px solid var(--border-input);
    }

    @media (max-width: 600px) {
        .side-panel {
            width: 100%;
            right: -100%;
        }
    }
</style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="section-header">
        <h2>
            <svg class="section-header-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            Operations Monitoring
        </h2>
        <p>Real-time oversight of all assigned and called leads</p>
    </div>

    <!-- Stats Summary -->
    <div class="monitoring-stats">
        <div class="mini-stat">
            <div class="mini-stat-label">Active Operations</div>
            <div class="mini-stat-value info">{{ $leads->total() }}</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-label">Agents Active</div>
            <div class="mini-stat-value success">{{ $agents->count() }}</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-label">This Page</div>
            <div class="mini-stat-value">{{ $leads->count() }}</div>
        </div>
        <div class="mini-stat">
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('leads.export') }}?{{ http_build_query(request()->all()) }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-download"></i> CSV
                </a>
                <a href="{{ route('leads.exportJNT') }}?{{ http_build_query(request()->all()) }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-file-excel"></i> J&T
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="search-filter">
        <form action="{{ route('leads.monitoring') }}" method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search by name or phone..." value="{{ request('search') }}">

            <select name="status" onchange="this.form.submit()">
                <option value="">All Status</option>
                @foreach(['NEW', 'CALLING', 'NO_ANSWER', 'REJECT', 'CALLBACK', 'SALE', 'REORDER', 'DELIVERED', 'CANCELLED'] as $st)
                    <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>

            <select name="agent_id" onchange="this.form.submit()">
                <option value="">All Agents</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->username }}</option>
                @endforeach
            </select>

            <select name="previous_item" onchange="this.form.submit()">
                <option value="">All Products</option>
                @foreach($productOptions as $product)
                    <option value="{{ $product }}" {{ request('previous_item') == $product ? 'selected' : '' }}>{{ $product }}</option>
                @endforeach
            </select>

            @if(request()->anyFilled(['search', 'status', 'agent_id', 'date_from', 'date_to', 'previous_item']))
                <a href="{{ route('leads.monitoring') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times"></i> Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Last Activity</th>
                        <th>Notes</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar-sm">
                                    {{ strtoupper(substr($lead->assignedAgent->username ?? 'UA', 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight: var(--font-semibold);">{{ $lead->assignedAgent->username ?? 'Unassigned' }}</div>
                                    <small>{{ $lead->assignedAgent->name ?? '—' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <div style="font-weight: var(--font-semibold);">{{ $lead->name }}</div>
                                <small style="color: var(--accent-cyan);">{{ $lead->phone }}</small>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $lead->status === 'SALE' ? 'badge-success' : ($lead->status === 'REJECT' ? 'badge-danger' : 'badge-info') }}">
                                {{ $lead->status }}
                            </span>
                        </td>
                        <td>
                            @if($lead->last_called_at)
                                <div>{{ $lead->last_called_at->diffForHumans() }}</div>
                                <small>{{ $lead->last_called_at->setTimezone('Asia/Manila')->format('M d, h:i A') }}</small>
                            @else
                                <span style="color: var(--text-muted);">No activity</span>
                            @endif
                        </td>
                        <td>
                            <div style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-tertiary);">
                                {{ $lead->notes ?? '—' }}
                            </div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-secondary btn-sm view-lead-btn" data-lead="{{ json_encode($lead) }}">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div style="padding: var(--space-6);">
                                <i class="fas fa-search" style="font-size: 28px; color: var(--text-muted); margin-bottom: var(--space-3); display: block;"></i>
                                <p style="margin-bottom: var(--space-1);">No operations found</p>
                                <small style="color: var(--text-muted);">Try adjusting your filters</small>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-controls">
            {{ $leads->links('vendor.pagination.custom') }}
        </div>
    </div>
@endsection

<!-- Side Panel Overlay -->
<div class="side-panel-overlay" id="panelOverlay"></div>

<!-- Details Panel -->
<div class="side-panel" id="detailsPanel">
    <div class="side-panel-header">
        <h3 class="side-panel-title">Lead Details</h3>
        <button class="side-panel-close" id="closePanel">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="side-panel-body">
        <div class="detail-group">
            <div class="detail-label">Customer</div>
            <div class="detail-value" id="panelName">—</div>
            <div style="color: var(--accent-cyan); font-weight: var(--font-semibold); margin-top: 4px;" id="panelPhone">—</div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Location</div>
            <div class="detail-card">
                <div id="panelCity" style="font-weight: var(--font-semibold); margin-bottom: 4px;">—</div>
                <div id="panelAddress" style="color: var(--text-secondary); font-size: var(--text-sm);">—</div>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Order Details</div>
            <div class="detail-card">
                <div style="margin-bottom: 8px;">
                    <div style="font-weight: var(--font-semibold);" id="panelProduct">—</div>
                    <div style="color: var(--text-tertiary); font-size: var(--text-sm);" id="panelBrand">—</div>
                </div>
                <div style="font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--accent-green);" id="panelAmount">—</div>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Status</div>
            <div id="panelStatus" class="status-pill default">—</div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Notes</div>
            <div class="detail-card" style="min-height: 80px;">
                <div id="panelNotes" style="color: var(--text-secondary); font-size: var(--text-sm);">—</div>
            </div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Call Attempts</div>
            <div class="detail-value" id="panelAttempts">0</div>
        </div>
    </div>
</div>

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
        statusEl.className = 'status-pill ' + (lead.status === 'SALE' ? 'sale' : lead.status === 'REJECT' ? 'reject' : 'default');

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
