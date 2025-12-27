@extends('layouts.app')

@section('content')
<div class="container-fluid px-0 leads-inbox-wrapper">
    {{-- Custom Backdrop --}}
    <div id="sidePanelBackdrop" class="side-panel-backdrop"></div>

    {{-- Top Navigation / Header --}}
    <div class="leads-header d-flex justify-content-between align-items-center px-4 py-3 border-bottom border-white border-opacity-10">
        <div class="d-flex align-items-center">
            <h1 class="h4 text-white mb-0 fw-bold">
                Leads
            </h1>
            <div class="ms-4 search-wrapper position-relative" style="min-width: 400px;">
                <form action="{{ route('leads.index') }}" method="GET" class="d-flex gap-2">
                    <div class="position-relative flex-grow-1">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50"></i>
                        <input type="text" name="search" class="form-control ps-5 bg-dark bg-opacity-50 border-white border-opacity-10 text-white rounded-3 shadow-none" placeholder="Search leads by name or phone..." value="{{ request('search') }}">
                    </div>
                    @if(request()->filled('search') || request()->filled('status') || request()->filled('source') || request()->filled('agent_id') || request()->filled('scope'))
                        <a href="{{ route('leads.index') }}" class="btn btn-outline-light border-white border-opacity-10 px-2"><i class="fas fa-times"></i></a>
                    @endif
                </form>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            @if(Auth::user()->canAccess('leads_manage'))
            <button class="btn btn-outline-info border-white border-opacity-10" type="button" id="toggleDistribute">
                <i class="fas fa-random me-1"></i> Distribute
            </button>
            <a href="{{ route('leads.export') }}?{{ http_build_query(request()->all()) }}" class="btn btn-outline-success border-white border-opacity-10">
                <i class="fas fa-file-csv me-1"></i> Export
            </a>
            @endif

            @if(Auth::user()->canAccess('leads_create'))
            <a href="{{ route('leads.importForm') }}" class="btn btn-primary rounded-3 px-4">
                <i class="fas fa-plus me-1"></i> New Leads
            </a>
            @endif
        </div>
    </div>

    @if(session('success') || session('error'))
    <div class="px-4 pt-3">
        @if(session('success'))
            <div class="alert alert-success bg-success bg-opacity-20 border-success border-opacity-20 text-success rounded-3 mb-0">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger bg-danger bg-opacity-20 border-danger border-opacity-20 text-danger rounded-3 mb-0">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            </div>
        @endif
    </div>
    @endif

    {{-- Auto Distribute Section (Collapsible) --}}
    @if(Auth::user()->canAccess('leads_manage'))
    <div class="px-4 pt-3 d-none" id="distributeCollapse">
        <div class="card bg-dark bg-opacity-50 border-white border-opacity-10 rounded-4">
            <div class="card-body p-4">
                <form action="{{ route('leads.distribute') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label text-white-50 small fw-bold text-uppercase tracking-wider">Target Agent</label>
                        <select name="agent_id" class="form-select bg-dark border-white border-opacity-10 text-white" required>
                            <option value="">Select Agent...</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-white-50 small fw-bold text-uppercase tracking-wider">Quantity</label>
                        <input type="number" name="count" class="form-control bg-dark border-white border-opacity-10 text-white" placeholder="20" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white-50 small fw-bold text-uppercase tracking-wider">Lead Type</label>
                        <select name="status" class="form-select bg-dark border-white border-opacity-10 text-white">
                            <option value="NEW">Fresh New Leads</option>
                            <option value="REORDER">Reorder Leads</option>
                            <option value="NO_ANSWER">Include No Answer</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-info w-100 fw-bold">Assign Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    {{-- Filtering & Scope Bar --}}
    <div class="px-4 py-2 bg-dark bg-opacity-25 border-bottom border-white border-opacity-10">
        <form action="{{ route('leads.index') }}" method="GET" id="filterForm" class="d-flex flex-wrap align-items-center gap-3">
            {{-- Keep Search --}}
            <input type="hidden" name="search" value="{{ request('search') }}">
            
            {{-- Scope Tabs --}}
            @if(Auth::user()->role !== 'agent')
            <div class="btn-group border border-white border-opacity-10 p-1 rounded-3">
                <input type="radio" class="btn-check" name="scope" id="scope_all" value="all" {{ request('scope', 'all') == 'all' ? 'checked' : '' }} onchange="this.form.submit()">
                <label class="btn btn-sm btn-outline-light border-0 px-3" for="scope_all">All Leads</label>

                <input type="radio" class="btn-check" name="scope" id="scope_unassigned" value="unassigned" {{ request('scope') == 'unassigned' ? 'checked' : '' }} onchange="this.form.submit()">
                <label class="btn btn-sm btn-outline-light border-0 px-3" for="scope_unassigned">Fresh (Unassigned)</label>

                <input type="radio" class="btn-check" name="scope" id="scope_assigned" value="assigned" {{ request('scope') == 'assigned' ? 'checked' : '' }} onchange="this.form.submit()">
                <label class="btn btn-sm btn-outline-light border-0 px-3" for="scope_assigned">Assigned</label>
            </div>
            @endif

            {{-- Status Dropdown --}}
            <div class="d-flex align-items-center">
                <span class="text-white-50 small me-2">Status:</span>
                <select name="status" class="form-select form-select-sm bg-dark border-white border-opacity-10 text-white w-auto" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['NEW', 'CALLING', 'NO_ANSWER', 'REJECT', 'CALLBACK', 'SALE', 'REORDER'] as $st)
                        <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Source Filter --}}
            <div class="d-flex align-items-center">
                <span class="text-white-50 small me-2">Source:</span>
                <select name="source" class="form-select form-select-sm bg-dark border-white border-opacity-10 text-white w-auto" onchange="this.form.submit()">
                    <option value="">All Sources</option>
                    <option value="fresh" {{ request('source') == 'fresh' ? 'selected' : '' }}>Fresh/Imported</option>
                    <option value="reorder" {{ request('source') == 'reorder' ? 'selected' : '' }}>Reorder Pool</option>
                </select>
            </div>

            {{-- Agent Filter (Admin) --}}
            @if(Auth::user()->role !== 'agent')
            <div class="d-flex align-items-center">
                <span class="text-white-50 small me-2">Agent:</span>
                <select name="agent_id" class="form-select form-select-sm bg-dark border-white border-opacity-10 text-white w-auto" onchange="this.form.submit()">
                    <option value="">All Agents</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>
    </div>

    {{-- Main Table Area --}}
    <div class="px-0 pt-3">
        <form action="{{ route('leads.assign') }}" method="POST" id="bulkActionForm">
            @csrf
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 leads-table custom-table-spacing">
                    <thead>
                        <tr class="text-white-50 small text-uppercase fw-bold border-bottom border-white border-opacity-10">
                            <th class="ps-4 py-3" style="width: 50px;">
                                <input type="checkbox" id="selectAll" class="custom-chk">
                            </th>
                            <th class="py-3 px-3">Customer</th>
                            <th class="py-3">Location</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Last Activity</th>
                            <th class="py-3">Notes</th>
                            <th class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($leads as $lead)
                        <tr class="align-middle border-bottom border-white border-opacity-5 {{ $lead->isLocked() ? 'locked-row' : '' }}">
                            <td class="ps-4">
                                @if(!$lead->isLocked())
                                <input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" class="lead-check custom-chk">
                                @else
                                <i class="fas fa-lock text-white text-opacity-10"></i>
                                @endif
                            </td>
                            <td class="px-3">
                                <div>
                                    <div class="fw-bold text-white mb-0 h6">{{ $lead->name }}</div>
                                    <div class="text-info small fs-7 mt-1">
                                        <i class="fas fa-phone-alt me-1 opacity-50"></i> {{ $lead->phone }}
                                    </div>
                                    @if($lead->source === 'reorder')
                                        <span class="badge reorder-badge">REORDER</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="text-white opacity-75 small text-uppercase fw-bold">{{ $lead->city }}</div>
                                    <div class="text-white-50 text-xs mt-1 d-flex align-items-start">
                                        <i class="fas fa-map-marker-alt me-1 mt-1 opacity-25"></i>
                                        <span class="text-truncate" style="max-width: 200px;">{{ $lead->address }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $stClass = strtolower(str_replace('_', '-', $lead->status));
                                @endphp
                                <span class="status-badge-modern status-{{ $stClass }}">
                                    {{ $lead->status }}
                                </span>
                            </td>
                            <td>
                                <div class="text-white opacity-75 small">
                                    {{ $lead->last_called_at ? $lead->last_called_at->diffForHumans() : 'Never called' }}
                                </div>
                                @if($lead->call_attempts > 0)
                                <div class="text-white-50 text-xs mt-1">Called {{ $lead->call_attempts }}x</div>
                                @endif
                            </td>
                            <td>
                                <div class="text-white-50 small text-italic text-truncate-2" title="{{ $lead->notes }}" style="max-width: 250px;">
                                    {{ $lead->notes ?: 'No notes added yet.' }}
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                @if($lead->isLocked())
                                    <span class="text-white-50 text-xs opacity-50">Locked</span>
                                @else
                                    <button type="button" class="btn btn-sm btn-info-modern px-3 update-lead-btn" 
                                            data-lead="{{ json_encode($lead) }}">
                                        <i class="fas fa-sync-alt me-1"></i> Update
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="opacity-25 py-4">
                                    <i class="fas fa-inbox fa-4x mb-3"></i>
                                    <h5 class="fw-light">Empty Leads Repository</h5>
                                    <p class="small">Try adjusting filters or importing new data.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Bulk Footer --}}
            @if(Auth::user()->canAccess('leads_manage') && $leads->count() > 0)
            <div class="d-flex justify-content-between align-items-center p-4 border-top border-white border-opacity-10 bg-dark bg-opacity-20">
                <div class="small text-white-50">
                    <span id="selectedCount" class="text-white fw-bold">0</span> leads selected
                </div>
                <div class="d-flex gap-2">
                    <select name="agent_id" class="form-select bg-dark border-white border-opacity-10 text-white w-auto" required>
                        <option value="">-- Assign to Agent --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-info px-4 fw-bold">Apply Assignment</button>
                </div>
            </div>
            @endif
        </form>
        
        <div class="p-4 border-top border-white border-opacity-10 d-flex justify-content-center">
            {{ $leads->withQueryString()->links() }}
        </div>
    </div>
</div>

{{-- Update Side Panel (Offcanvas Replacement) --}}
<div class="side-panel-custom" id="updateSidePanel">
    <div class="side-panel-header">
        <div class="d-flex align-items-center">
            <div class="header-icon">
                <i class="fas fa-phone-alt"></i>
            </div>
            <div class="ms-3">
                <h5 class="panel-title mb-0">Call Update</h5>
                <p class="panel-subtitle mb-0">Update the status for <span id="panelCustomerName" class="fw-bold text-white"></span></p>
            </div>
        </div>
        <button type="button" class="btn-close-custom" id="closeSidePanel">&times;</button>
    </div>
    <div class="side-panel-body">
        {{-- Profile Info Card --}}
        <div class="info-card mb-4">
            <div class="info-row d-flex align-items-start mb-3">
                <i class="fas fa-phone-alt info-icon"></i>
                <div>
                    <div class="info-label">Phone Number</div>
                    <div class="info-value text-cyan" id="panelCustomerPhone">9105516640</div>
                </div>
            </div>
            <div class="info-row d-flex align-items-start">
                <i class="fas fa-map-marker-alt info-icon"></i>
                <div>
                    <div class="info-label">Location</div>
                    <div class="info-value text-white-50 small text-uppercase" id="panelCustomerCity">MUNTINLUPA, CUYAG, MUNTINLUPA, METRO MANILA</div>
                </div>
            </div>
        </div>

        <form action="" method="POST" id="panelUpdateForm">
            @csrf
            
            <h6 class="section-title mb-3">Call Outcome</h6>
            <div class="outcome-list mb-4">
                <div class="outcome-item">
                    <input type="radio" name="status" id="panel_st_noanswer" value="NO_ANSWER" class="outcome-radio" required>
                    <label for="panel_st_noanswer" class="outcome-label">
                        <span class="radio-circle"></span>
                        <i class="fas fa-phone-slash icon-no-answer"></i>
                        <span class="outcome-text">No Answer</span>
                    </label>
                </div>

                <div class="outcome-item">
                    <input type="radio" name="status" id="panel_st_callback" value="CALLBACK" class="outcome-radio">
                    <label for="panel_st_callback" class="outcome-label">
                        <span class="radio-circle"></span>
                        <i class="fas fa-phone-volume icon-callback"></i>
                        <span class="outcome-text">Callback</span>
                    </label>
                </div>

                <div class="outcome-item">
                    <input type="radio" name="status" id="panel_st_reject" value="REJECT" class="outcome-radio">
                    <label for="panel_st_reject" class="outcome-label">
                        <span class="radio-circle"></span>
                        <i class="fas fa-user-times icon-reject"></i>
                        <span class="outcome-text">Reject</span>
                    </label>
                </div>

                <div class="outcome-item">
                    <input type="radio" name="status" id="panel_st_sale" value="SALE" class="outcome-radio">
                    <label for="panel_st_sale" class="outcome-label">
                        <span class="radio-circle"></span>
                        <i class="fas fa-check-circle icon-sale"></i>
                        <span class="outcome-text">Sale</span>
                    </label>
                </div>
            </div>

            <h6 class="section-title mb-2">Call Summary / Notes</h6>
            <div class="mb-4">
                <textarea name="note" class="notes-textarea" placeholder="Describe the conversation result..."></textarea>
            </div>

            <div class="side-panel-footer">
                <button type="submit" class="btn-save-panel">Save Changes</button>
                <button type="button" class="btn-cancel-panel mt-2" id="cancelSidePanel">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Side Panel Styles - Exact match to screenshot */
.side-panel-custom {
    position: fixed;
    top: 0;
    right: -450px;
    width: 450px;
    height: 100vh;
    background: #0f172a; /* Deeper dark */
    color: #f8fafc;
    z-index: 2000;
    transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: -15px 0 50px rgba(0,0,0,0.6);
    display: flex;
    flex-direction: column;
}

.side-panel-custom.open {
    right: 0;
}

.side-panel-header {
    padding: 2rem 1.5rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.header-icon {
    width: 42px;
    height: 42px;
    background: rgba(34, 211, 238, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #22d3ee;
    font-size: 1.1rem;
}

.panel-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.01em;
}

.panel-subtitle {
    color: #64748b;
    font-size: 0.9rem;
    margin-top: 2px;
}

.side-panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 0 1.5rem 1.5rem;
}

.info-card {
    background: #1e293b;
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 1.25rem;
}

.info-icon {
    color: #22d3ee;
    font-size: 0.9rem;
    margin-right: 1rem;
    width: 16px;
    text-align: center;
}

.info-label {
    color: #64748b;
    font-size: 0.75rem;
    text-transform: none;
    margin-bottom: 2px;
}

.info-value {
    font-weight: 600;
}

.text-cyan { color: #22d3ee; }

.section-title {
    color: #fff;
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

/* Outcome Radio List */
.outcome-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.outcome-item {
    position: relative;
}

.outcome-radio {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.outcome-label {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #1e293b;
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
}

.outcome-label:hover {
    background: #273549;
}

.radio-circle {
    width: 18px;
    height: 18px;
    border: 2px solid #475569;
    border-radius: 50%;
    margin-right: 12px;
    position: relative;
}

.outcome-radio:checked + .outcome-label {
    border-color: #22d3ee;
    background: rgba(34, 211, 238, 0.05);
}

.outcome-radio:checked + .outcome-label .radio-circle {
    border-color: #22d3ee;
}

.outcome-radio:checked + .outcome-label .radio-circle::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: #22d3ee;
    border-radius: 50%;
}

.outcome-label i {
    font-size: 1rem;
    margin-right: 12px;
}

.icon-no-answer { color: #f59e0b; }
.icon-callback { color: #3b82f6; }
.icon-reject { color: #ef4444; }
.icon-sale { color: #22c55e; }

.outcome-text {
    font-weight: 500;
    font-size: 0.9rem;
}

/* Notes Textarea */
.notes-textarea {
    width: 100%;
    background: #1e293b;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 1rem;
    color: #f8fafc;
    font-size: 0.9rem;
    min-height: 120px;
    resize: none;
    transition: border-color 0.2s;
}

.notes-textarea:focus {
    outline: none;
    border-color: #22d3ee;
}

.side-panel-footer {
    display: flex;
    flex-direction: column;
    margin-top: 1rem;
}

.btn-save-panel {
    background: #0ea5e9;
    color: white;
    border: none;
    padding: 0.85rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-save-panel:hover {
    background: #0284c7;
}

.btn-cancel-panel {
    background: transparent;
    color: #64748b;
    border: 1px solid #334155;
    padding: 0.85rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-cancel-panel:hover {
    background: rgba(255,255,255,0.05);
    color: #fff;
}

.side-panel-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 1999;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.side-panel-backdrop.show {
    display: block;
    opacity: 1;
}

.btn-close-custom {
    background: none;
    border: none;
    color: #64748b;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 5px;
    line-height: 1;
}

.btn-close-custom:hover {
    color: #fff;
}
.icon-circle { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }

/* Custom Radio Outcomes */
.outcome-option .btn-outline-custom {
    border: 1px solid var(--border-soft);
    background: rgba(255, 255, 255, 0.02);
    transition: all 0.2s;
    position: relative;
}
.outcome-option .btn-outline-custom:hover { background: rgba(255, 255, 255, 0.05); }
.outcome-option .btn-check:checked + .btn-outline-custom {
    border-color: #00bfff;
    background: rgba(0, 191, 255, 0.1);
}

.outcome-option .btn-outline-sale {
    border: 1px dashed rgba(50, 205, 50, 0.4);
    background: rgba(50, 205, 50, 0.02);
    transition: all 0.2s;
}
.outcome-option .btn-check:checked + .btn-outline-sale {
    border: 1px solid #32cd32;
    background: rgba(50, 205, 50, 0.1);
}

/* Indicators */
.check-indicator { width: 16px; height: 16px; border: 2px solid var(--border-soft); border-radius: 50%; position: relative; }
.outcome-option .btn-check:checked + label .check-indicator {
    border-color: currentColor;
    background: currentColor;
}

/* Checkbox Style */
.custom-chk {
    width: 18px;
    height: 18px;
    background-color: transparent;
    border: 2px solid var(--border-soft);
    border-radius: 4px;
    appearance: none;
    cursor: pointer;
    position: relative;
}
.custom-chk:checked { background-color: #00bfff; border-color: #00bfff; }
.custom-chk:checked::after {
    content: "\f00c";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    color: white;
    font-size: 10px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Utilities */
.tracking-widest { letter-spacing: 0.15em; }
.tracking-wider { letter-spacing: 0.05em; }
.x-small { font-size: 0.7rem; opacity: 0.6; }

/* Page Transition */
.leads-inbox-wrapper { animation: fadeIn 0.4s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* Pagination styling adjustments for dark theme */
.pagination { margin-bottom: 0; gap: 4px; }
.page-link { 
    background-color: var(--bg-sidebar) !important; 
    border-color: var(--border-soft) !important; 
    color: #fff !important; 
    border-radius: 6px !important; 
    padding: 0.6rem 1rem;
}
.page-item.active .page-link { background-color: #00bfff !important; border-color: #00bfff !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidePanel = document.getElementById('updateSidePanel');
    const backdrop = document.getElementById('sidePanelBackdrop');
    const closeBtn = document.getElementById('closeSidePanel');
    const cancelBtn = document.getElementById('cancelSidePanel');
    const updateButtons = document.querySelectorAll('.update-lead-btn');
    
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

    updateButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const lead = JSON.parse(this.dataset.lead);
            
            document.getElementById('panelCustomerName').textContent = lead.name;
            document.getElementById('panelCustomerPhone').textContent = lead.phone;
            document.getElementById('panelCustomerCity').textContent = lead.city || 'Location Unknown';
            
            const form = document.getElementById('panelUpdateForm');
            form.action = `/leads/${lead.id}/status`;
            
            document.querySelectorAll('.btn-check').forEach(el => el.checked = false);
            const radio = document.querySelector(`input[name="status"][value="${lead.status}"]`);
            if (radio) radio.checked = true;

            const textArea = form.querySelector('textarea[name="note"]');
            if (textArea) textArea.value = '';
            
            openPanel();
        });
    });

    if(closeBtn) closeBtn.addEventListener('click', closePanel);
    if(cancelBtn) cancelBtn.addEventListener('click', closePanel);
    if(backdrop) backdrop.addEventListener('click', closePanel);

    // Custom Collapse for Distribute
    const toggleDistribute = document.getElementById('toggleDistribute');
    const distributeCollapse = document.getElementById('distributeCollapse');
    
    if(toggleDistribute && distributeCollapse) {
        toggleDistribute.addEventListener('click', function() {
            distributeCollapse.classList.toggle('d-none');
        });
    }

    // Bulk Action Selection
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.lead-check');
    const selectedCount = document.getElementById('selectedCount');

    function updateCount() {
        const count = document.querySelectorAll('.lead-check:checked').length;
        if(selectedCount) selectedCount.textContent = count;
    }

    if(selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateCount();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });
});
</script>
@endsection
