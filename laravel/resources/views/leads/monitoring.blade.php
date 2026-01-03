@extends('layouts.app')

@section('content')
<div class="leads-inbox-wrapper p-4">
    {{-- Monitoring Header --}}
    <div class="leads-page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Operations Monitoring</h1>
                <p class="text-white-50 small mt-1">Real-time oversight of all assigned and called leads</p>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('leads.exportJNT') }}?{{ http_build_query(request()->all()) }}" class="text-primary text-opacity-75 text-decoration-none small fw-bold mx-2">
                    <i class="fas fa-file-excel me-1"></i> J&T Export
                </a>
                <a href="{{ route('leads.export') }}?{{ http_build_query(request()->all()) }}" class="text-info text-decoration-none small fw-bold mx-2">
                    <i class="fas fa-file-csv me-1"></i> CSV Export
                </a>
                <a href="{{ route('leads.index') }}" class="btn btn-white text-dark fw-bold px-3 py-2 rounded-3 shadow-sm">
                    <i class="fas fa-inbox me-2 small"></i> Inbox View
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="filter-box p-3 border border-white border-opacity-10 rounded-3 text-center bg-opacity-5">
                <div class="text-white-50 x-small text-uppercase tracking-widest mb-1">Active Operations</div>
                <div class="text-info h4 fw-bold mb-0">{{ $leads->total() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="filter-box p-3 border border-white border-opacity-10 rounded-3 text-center bg-opacity-5">
                <div class="text-white-50 x-small text-uppercase tracking-widest mb-1">Agents Active</div>
                <div class="text-success h4 fw-bold mb-0">{{ $agents->count() }}</div>
            </div>
        </div>
    </div>

    {{-- Filter Box --}}
    <div class="filter-box bg-opacity-10 border border-white border-opacity-10 rounded-4 p-4 mb-4">
        <form action="{{ route('leads.monitoring') }}" method="GET" id="filterForm">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <div class="search-container position-relative">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50"></i>
                        <input type="text" name="search" class="form-control bg-dark border-white border-opacity-10 text-white rounded-3 ps-5 py-2" placeholder="Search by name or phone..." value="{{ request('search') }}">
                    </div>
                </div>
                
                <div class="col-auto">
                    <select name="status" class="form-select border-white border-opacity-10 bg-dark text-white rounded-3 small" onchange="this.form.submit()">
                        <option value="">Status: All Statuses</option>
                        @foreach(['NEW', 'CALLING', 'NO_ANSWER', 'REJECT', 'CALLBACK', 'SALE', 'REORDER', 'DELIVERED', 'CANCELLED'] as $st)
                            <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <select name="agent_id" class="form-select border-white border-opacity-10 bg-dark text-white rounded-3 small" onchange="this.form.submit()">
                        <option value="">Agent: All Agents</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->username }} ({{ $agent->name }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <select name="previous_item" class="form-select border-white border-opacity-10 bg-dark text-white rounded-3 small" onchange="this.form.submit()">
                        <option value="">Product: All Products</option>
                        @foreach($productOptions as $product)
                            <option value="{{ $product }}" {{ request('previous_item') == $product ? 'selected' : '' }}>{{ $product }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto ms-auto d-flex align-items-center gap-2">
                    <div class="date-input-wrapper position-relative">
                        <i class="fas fa-calendar-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50 small"></i>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control bg-dark border-white border-opacity-10 text-white rounded-3 ps-5 py-2 small" onchange="this.form.submit()">
                    </div>
                    <span class="text-white-50">-</span>
                    <div class="date-input-wrapper position-relative">
                        <i class="fas fa-calendar-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50 small"></i>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control bg-dark border-white border-opacity-10 text-white rounded-3 ps-5 py-2 small" onchange="this.form.submit()">
                    </div>

                    @if(request()->anyFilled(['search', 'status', 'agent_id', 'date_from', 'date_to', 'previous_item']))
                        <a href="{{ route('leads.monitoring') }}" class="text-white-50 text-decoration-none small ms-2">
                            <i class="fas fa-redo me-1"></i> Reset
                        </a>
                    @endif

                    <button type="submit" class="btn btn-cyan text-white fw-bold px-3 py-2 rounded-3 shadow-sm ms-2">
                        REFINE
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card bg-sidebar border-soft overflow-hidden">
        <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 align-middle border-0">
            <thead>
                <tr class="text-white-50 small text-uppercase fw-bold border-bottom border-white border-opacity-10">
                    <th class="ps-4 py-3">Agent</th>
                    <th class="py-3">Customer</th>
                    <th class="py-3">Status</th>
                    <th class="py-3">Last Activity</th>
                    <th class="py-3">Latest Notes</th>
                    <th class="pe-4 py-3 text-end">Actions</th>
                </tr>
            </thead>
                <tbody class="border-top-0">
                @forelse($leads as $lead)
                <tr class="border-bottom border-white border-opacity-5 align-middle">
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar bg-info text-dark fw-bold rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; border: 1px solid rgba(255,255,255,0.1); font-size: 0.75rem;">
                                {{ strtoupper(substr($lead->assignedAgent->username ?? 'UA', 0, 2)) }}
                            </div>
                            <div>
                                <div class="text-white fw-bold small mb-0">{{ $lead->assignedAgent->username ?? 'Unassigned' }}</div>
                                <div class="text-white-50 x-small">{{ $lead->assignedAgent->name ?? '---' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-white fw-bold small mb-1">{{ $lead->name }}</div>
                        <div class="text-info x-small fw-bold">{{ $lead->phone }}</div>
                    </td>
                    <td>
                        <span class="badge bg-opacity-10 text-white border border-white border-opacity-10 px-2 py-1 fw-bold x-small">{{ $lead->status }}</span>
                    </td>
                    <td>
                        @if($lead->last_called_at)
                            <div class="text-white small d-flex align-items-center gap-2">
                                <i class="fas fa-phone opacity-50"></i>
                                <span>{{ $lead->last_called_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-white-50 x-small mt-1 ps-4">{{ $lead->last_called_at->format('M d, H:i') }}</div>
                        @else
                            <span class="text-white-50 small italic">No activity yet</span>
                        @endif
                    </td>
                    <td>
                        <div class="text-white-50 small text-truncate" style="max-width: 200px;">
                            {{ $lead->notes ?? '---' }}
                        </div>
                    </td>
                    <td class="pe-4 text-end">
                        <button type="button" class="btn btn-white btn-sm px-3 fw-bold text-dark border-0 shadow-sm update-lead-btn" data-lead="{{ json_encode($lead) }}">
                            <i class="fas fa-eye me-1 small"></i> View
                        </button>
                    </td>
                </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <i class="fas fa-search fa-3x mb-3 opacity-20"></i>
                            <p>No active operations found for the current filters.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($leads->hasPages())
        <div class="card-footer bg-black-20 border-top-0 d-flex justify-content-between align-items-center p-3">
            <div class="small text-secondary font-monospace">
                Showing {{ $leads->firstItem() }} to {{ $leads->lastItem() }} of {{ $leads->total() }} entries
            </div>
            {{ $leads->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</div>

<!-- Details Panel -->
<div id="sidePanelBackdrop" class="side-panel-backdrop"></div>
<div id="updateSidePanel" class="side-panel-custom side-panel-monitoring shadow-lg">
    <div class="side-panel-header px-4 py-4 border-bottom border-white border-opacity-10">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div>
                <h4 class="panel-title mb-0">Lead Oversight</h4>
                <p class="panel-subtitle mb-0">Operational real-time view</p>
            </div>
            <button id="closeSidePanel" class="btn btn-link text-white-50 p-0 shadow-none border-0"><i class="fas fa-times fa-lg"></i></button>
        </div>
    </div>

    <div class="side-panel-body p-4">
        <div class="info-card mb-4">
            <div id="panelCustomerName" class="h4 text-white mb-1 fw-bold">---</div>
            <div id="panelCustomerPhone" class="text-cyan h5 fw-bold mb-3">---</div>
            
            <div class="d-flex flex-column gap-3 border-top border-white border-opacity-10 pt-3">
                <div>
                    <div class="text-white-50 x-small text-uppercase fw-bold mb-1">Locality</div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-map-marker-alt text-cyan mt-1" style="width: 16px;"></i>
                        <span id="panelCityText" class="text-white small">---</span>
                    </div>
                    <div class="d-flex align-items-start gap-2 mt-2">
                        <i class="fas fa-home text-cyan mt-1" style="width: 16px;"></i>
                        <div>
                            <span id="panelStreetText" class="text-white small d-block">---</span>
                            <span id="panelBarangayText" class="text-white-50 x-small d-block">---</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mt-2">
                        <i class="fas fa-landmark text-cyan mt-1" style="width: 16px;"></i>
                        <span id="panelLandmarkText" class="text-white small">---</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="text-white-50 small fw-bold text-uppercase tracking-wider mb-3">Order Details</h6>
            <div class="filter-box p-4 border border-white border-opacity-10 rounded-3 bg-white bg-opacity-5">
                <div class="mb-3">
                    <div class="text-white-50 x-small text-uppercase mb-1">Product Name</div>
                    <div id="panelProductText" class="text-white small fw-bold" style="line-height: 1.4;">---</div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-white-50 x-small text-uppercase mb-1">Brand</div>
                        <div id="panelBrandText" class="text-white small">---</div>
                    </div>
                    <div class="col-6">
                        <div class="text-white-50 x-small text-uppercase mb-1">Total Amount</div>
                        <div id="panelAmountText" class="text-success h5 fw-bold mb-0">---</div>
                    </div>
                </div>
                <div id="saleTimestampWrapper" class="mt-3 pt-3 border-top border-white border-opacity-10 d-none">
                    <div class="text-white-50 x-small text-uppercase mb-1">Submitted On</div>
                    <div id="panelSubmittedAtText" class="text-white small">---</div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="text-white-50 small fw-bold text-uppercase tracking-wider mb-3">Current Status</h6>
            <div id="panelStatusBadge" class="filter-box p-3 border border-white border-opacity-10 rounded-3 text-white fw-bold h6 mb-0 text-center tracking-widest text-uppercase">---</div>
        </div>

        <div class="mb-4">
            <h6 class="text-white-50 small fw-bold text-uppercase tracking-wider mb-3">Operation Notes</h6>
            <div class="filter-box p-3 border border-white border-opacity-10 rounded-3 text-white-50 small min-vh-10" style="min-height: 120px;">
                <div id="panelNotes">---</div>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="text-white-50 small fw-bold text-uppercase tracking-wider mb-3">Metadata</h6>
            <div class="row g-2">
                <div class="col-6">
                    <div class="filter-box p-3 border border-white border-opacity-10 rounded-3 text-center">
                        <div class="text-white-50 x-small text-uppercase">Attempts</div>
                        <div id="panelAttempts" class="text-white fw-bold h5 mb-0">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="side-panel-footer p-4 border-top border-white border-opacity-10">
        <button id="cancelSidePanel" class="btn btn-white text-dark fw-bold w-100 py-3 rounded-3 shadow-sm">CLOSE OVERVIEW</button>
    </div>
</div>

<style>
.bg-sidebar { background-color: #0b0e14; }
.card { background-color: transparent; border: none; }
.text-cyan { color: #00d2ff !important; }

/* Side Panel System */
.side-panel-custom {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100vh;
    background: #0b0e14;
    color: #f8fafc;
    z-index: 2000;
    transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: -15px 0 50px rgba(0,0,0,0.6);
    display: flex;
    flex-direction: column;
}
.side-panel-custom.open { right: 0; }
.side-panel-monitoring { width: 500px; right: -500px; }
.side-panel-monitoring.open { right: 0; }

.side-panel-header { padding: 1.5rem; }
.side-panel-body { flex: 1; overflow-y: auto; }
.side-panel-footer { padding: 1.5rem; }

.info-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 1.25rem;
}

.x-small { font-size: 0.75rem; opacity: 0.6; }

/* Status Badges */
.status-badge-sale { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
.status-badge-rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

.side-panel-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    z-index: 1040;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.side-panel-backdrop.show { display: block; opacity: 1; }

/* Page Transition */
.leads-inbox-wrapper { animation: fadeIn 0.4s ease; min-height: 100vh; background-color: #0b0e14; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* Pagination Override */
.pagination .page-link {
    background: rgba(255, 255, 255, 0.03) !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
    color: #fff !important;
    padding: 0.5rem 1rem;
    border-radius: 8px !important;
    margin: 0 2px;
}
.pagination .page-item.active .page-link {
    background: #00d2ff !important;
    border-color: #00d2ff !important;
    color: #000 !important;
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidePanel = document.getElementById('updateSidePanel');
    const backdrop = document.getElementById('sidePanelBackdrop');
    const closeBtn = document.getElementById('closeSidePanel');
    const cancelBtn = document.getElementById('cancelSidePanel');
    const viewButtons = document.querySelectorAll('.update-lead-btn');
    
    function openPanel() {
        sidePanel.classList.add('open');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closePanel() {
        sidePanel.classList.remove('open');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const lead = JSON.parse(this.dataset.lead);
            
            document.getElementById('panelCustomerName').textContent = lead.name;
            document.getElementById('panelCustomerPhone').textContent = lead.phone;
            document.getElementById('panelCityText').textContent = lead.city || 'Location Unknown';
            
            // Street & Barangay
            document.getElementById('panelStreetText').textContent = lead.street || 'No street set';
            document.getElementById('panelBarangayText').textContent = lead.barangay || 'No barangay set';
            document.getElementById('panelLandmarkText').textContent = lead.landmark || 'No landmark set';

            // New Order fields
            document.getElementById('panelProductText').textContent = lead.product_name || 'No product selected';
            document.getElementById('panelBrandText').textContent = lead.product_brand || '---';
            document.getElementById('panelAmountText').textContent = lead.amount ? '₱' + parseFloat(lead.amount).toFixed(2) : '---';

            // Sale specific timestamp
            const tsWrapper = document.getElementById('saleTimestampWrapper');
            if (lead.submitted_at) {
                tsWrapper.classList.remove('d-none');
                document.getElementById('panelSubmittedAtText').textContent = new Date(lead.submitted_at).toLocaleString();
            } else {
                tsWrapper.classList.add('d-none');
            }

            const statusBadge = document.getElementById('panelStatusBadge');
            statusBadge.textContent = lead.status;
            
            // Highlight Sale Status
            if (lead.status === 'SALE') {
                statusBadge.style.backgroundColor = 'rgba(34, 197, 94, 0.2)';
                statusBadge.style.borderColor = 'rgba(34, 197, 94, 0.5)';
                statusBadge.style.color = '#22c55e';
            } else if (lead.status === 'REJECT') {
                statusBadge.style.backgroundColor = 'rgba(239, 68, 68, 0.2)';
                statusBadge.style.borderColor = 'rgba(239, 68, 68, 0.5)';
                statusBadge.style.color = '#ef4444';
            } else {
                statusBadge.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
                statusBadge.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                statusBadge.style.color = '#ffffff';
            }

            document.getElementById('panelNotes').textContent = lead.notes || 'No notes available.';
            document.getElementById('panelAttempts').textContent = lead.call_attempts || 0;
            
            openPanel();
        });
    });

    if(closeBtn) closeBtn.addEventListener('click', closePanel);
    if(cancelBtn) cancelBtn.addEventListener('click', closePanel);
    if(backdrop) backdrop.addEventListener('click', closePanel);
});
</script>
@endsection
