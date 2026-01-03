@extends('layouts.app')

@push('styles')
<style>
    .leads-inbox-wrapper {
        background-color: #0b0e14;
        min-height: 100vh;
    }
    .leads-page-header .page-title {
        font-size: 2.2rem;
        letter-spacing: -0.02em;
        font-weight: 800;
        color: white;
    }
    .max-w-400 { max-width: 400px; }
    .btn-white {
        background: white;
        border: none;
        color: black;
        transition: all 0.2s;
        font-size: 0.85rem;
    }
    .btn-white:hover {
        background: #f1f5f9;
        transform: translateY(-1px);
    }
    .btn-cyan {
        background: #00d2ff;
        border: none;
        color: white;
        transition: all 0.2s;
        font-size: 0.85rem;
    }
    .btn-cyan:hover {
        background: #00b4db;
        transform: translateY(-1px);
    }
    .filter-box {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 16px;
    }
    .indicator-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    .scope-item {
        transition: all 0.2s;
        border-radius: 8px;
    }
    .scope-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    .cursor-pointer { cursor: pointer; }
    .form-select {
        border: 1px solid rgba(255,255,255,0.1) !important;
        background-color: rgba(255,255,255,0.02) !important;
    }
    .date-input-wrapper input {
        min-width: 140px;
        border: 1px solid rgba(255,255,255,0.1) !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 leads-inbox-wrapper">
    {{-- Custom Backdrop --}}
    <div id="sidePanelBackdrop" class="side-panel-backdrop"></div>

    {{-- Refined Leads Header --}}
    <div class="leads-page-header px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center flex-grow-1">
                <h1 class="page-title me-4 mb-0">Leads</h1>
                <div class="search-container flex-grow-1 max-w-400">
                    <form action="{{ route('leads.index') }}" method="GET" class="position-relative">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                        <input type="text" name="search" 
                            class="form-control text-white rounded-3 ps-5 py-2" 
                            style="background-color: #1a1d24; border: 1px solid #2d3342;" 
                            placeholder="Search by name, phone, or prev..." 
                            value="{{ request('search') }}">
                    </form>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                @if(Auth::user()->canAccess('leads_manage'))
                <form action="{{ route('leads.clear') }}" method="POST" onsubmit="return confirm('ARE YOU SURE?');" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-white text-dark fw-bold px-3 py-2 rounded-3 shadow-sm">
                        <i class="fas fa-trash-alt me-2 small"></i> Clear Repository
                    </button>
                </form>
                <button class="btn btn-white text-dark fw-bold px-3 py-2 rounded-3 shadow-sm" type="button" id="toggleDistribute">
                    <i class="fas fa-random me-2 small"></i> Distribute
                </button>
                <a href="{{ route('leads.export') }}?{{ http_build_query(request()->all()) }}" class="text-info text-decoration-none small fw-bold mx-2">
                    <i class="fas fa-download me-1"></i> Export
                </a>
                <a href="{{ route('leads.exportJNT') }}?{{ http_build_query(request()->all()) }}" class="text-primary text-opacity-75 text-decoration-none small fw-bold mx-2">
                    <i class="fas fa-file-excel me-1"></i> J&T Export
                </a>
                <a href="{{ route('leads.monitoring') }}" class="btn btn-info text-dark fw-bold px-3 py-2 rounded-3 shadow-sm d-none d-xl-block">
                    <i class="fas fa-chart-line me-2 small"></i> Monitoring
                </a>
                @endif
                
                @if(Auth::user()->canAccess('leads_create'))
                <a href="{{ route('leads.importForm') }}" class="btn btn-cyan text-white fw-bold px-3 py-2 rounded-3 shadow-sm">
                    <i class="fas fa-plus me-2 small"></i> New Leads
                </a>
                @endif
            </div>
        </div>

        {{-- Distribute Collapse Section --}}
        @if(Auth::user()->canAccess('leads_manage'))
        <div id="distributeCollapse" class="d-none mb-4">
            <div class="filter-box bg-opacity-5 border border-info border-opacity-25 rounded-4 p-4">
                <form action="{{ route('leads.distribute') }}" method="POST" id="distributeForm">
                    @csrf
                    <div class="row align-items-end g-3">
                        <div class="col-md-3">
                            <label class="text-white-50 small mb-2">
                                <i class="fas fa-user me-1"></i> Assign to Agent <span class="text-danger">*</span>
                            </label>
                            <select name="agent_id" class="form-select bg-dark border-info border-opacity-25 text-white rounded-3" required>
                                <option value="">-- Select Agent --</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="text-white-50 small mb-2">
                                <i class="fas fa-hashtag me-1"></i> Count <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="count" class="form-control bg-dark border-info border-opacity-25 text-white rounded-3" min="1" value="50" required placeholder="# of leads">
                        </div>
                        <div class="col-md-2">
                            <label class="text-white-50 small mb-2">
                                <i class="fas fa-filter me-1"></i> Lead Type
                            </label>
                            <select name="status" class="form-select bg-dark border-info border-opacity-25 text-white rounded-3">
                                <option value="NEW">Fresh (NEW)</option>
                                <option value="NO_ANSWER">No Answer</option>
                                <option value="REORDER">Reorder</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="text-white-50 small mb-2">
                                <i class="fas fa-box me-1"></i> Product
                            </label>
                            <select name="previous_item" class="form-select bg-dark border-info border-opacity-25 text-white rounded-3">
                                <option value="">All Products</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product }}">{{ $product }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="text-white-50 small mb-2 d-block">
                                <i class="fas fa-recycle me-1"></i> Recycle
                            </label>
                            <div class="form-check form-switch">
                                <input type="checkbox" name="recycle" value="1" class="form-check-input bg-dark border-info" id="recycleSwitch">
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="submit" class="btn btn-info text-dark fw-bold px-4 py-2 rounded-3 shadow-sm w-100">
                                <i class="fas fa-paper-plane me-2"></i> Distribute Now
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 text-white-50 small">
                        <i class="fas fa-info-circle me-1 text-info"></i>
                        Distribution will assign the specified number of unassigned leads (matching criteria) to the selected agent.
                        <span class="text-warning"><strong>Recycle:</strong> Re-distribute leads that were assigned 12+ hours ago.</span>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Filter Box --}}
        <div class="filter-box bg-opacity-5 border border-white border-opacity-10 rounded-4 p-4">
            <form action="{{ route('leads.index') }}" method="GET" id="filterForm">
                {{-- Keep Search --}}
                <input type="hidden" name="search" value="{{ request('search') }}">
                
                <div class="row align-items-center g-3">
                    {{-- Scope Filters --}}
                    <div class="col-auto">
                        <div class="scope-group d-flex bg-dark bg-opacity-50 p-1 border border-white border-opacity-10 rounded-3">
                            <label class="scope-item px-3 py-1 cursor-pointer">
                                <input type="radio" name="scope" value="all" {{ request('scope', 'all') == 'all' ? 'checked' : '' }} onchange="this.form.submit()" class="d-none">
                                <span class="d-flex align-items-center gap-2 small {{ request('scope', 'all') == 'all' ? 'text-white fw-bold' : 'text-white-50' }}">
                                    <span class="indicator-dot bg-primary"></span> All Leads
                                </span>
                            </label>
                            <label class="scope-item px-3 py-1 cursor-pointer">
                                <input type="radio" name="scope" value="fresh" {{ request('scope') == 'fresh' ? 'checked' : '' }} onchange="this.form.submit()" class="d-none">
                                <span class="d-flex align-items-center gap-2 small {{ request('scope') == 'fresh' ? 'text-white fw-bold' : 'text-white-50' }}">
                                    <span class="indicator-dot bg-white bg-opacity-50 shadow-sm"></span> Fresh (Unassigned)
                                </span>
                            </label>
                            <label class="scope-item px-3 py-1 cursor-pointer">
                                <input type="radio" name="scope" value="assigned" {{ request('scope') == 'assigned' ? 'checked' : '' }} onchange="this.form.submit()" class="d-none">
                                <span class="d-flex align-items-center gap-2 small {{ request('scope') == 'assigned' ? 'text-white fw-bold' : 'text-white-50' }}">
                                    <span class="indicator-dot bg-success"></span> Assigned
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- Dropdowns --}}
                    <div class="col-auto">
                        <select name="status" class="form-select border-white border-opacity-10 bg-dark text-white rounded-3 small" onchange="this.form.submit()">
                            <option value="">Status: All Statuses</option>
                            @foreach(['NEW', 'CALLING', 'NO_ANSWER', 'REJECT', 'CALLBACK', 'SALE', 'REORDER'] as $st)
                                <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-auto">
                        <select name="source" class="form-select border-white border-opacity-10 bg-dark text-white rounded-3 small" onchange="this.form.submit()">
                            <option value="">Source: All Sources</option>
                            <option value="fresh" {{ request('source') == 'fresh' ? 'selected' : '' }}>Fresh/Imported</option>
                            <option value="reorder" {{ request('source') == 'reorder' ? 'selected' : '' }}>Reorder Pool</option>
                        </select>
                    </div>

                    @if(Auth::user()->role !== 'agent')
                    <div class="col-auto">
                        <select name="agent_id" class="form-select border-white border-opacity-10 bg-dark text-white rounded-3 small" onchange="this.form.submit()">
                            <option value="">Agent: All Agents</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-auto">
                        <select name="previous_item" class="form-select border-white border-opacity-10 bg-dark text-white rounded-3 small" onchange="this.form.submit()">
                            <option value="">Product: All Products</option>
                            @foreach($productOptions as $product)
                                <option value="{{ $product }}" {{ request('previous_item') == $product ? 'selected' : '' }}>{{ $product }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Bottom Row: Dates --}}
                    <div class="col-12 mt-3 pt-3 border-top border-white border-opacity-10">
                        <div class="d-flex align-items-center gap-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-white-50 small fw-bold">Fresh:</span>
                                <div class="date-input-wrapper position-relative">
                                    <i class="fas fa-calendar-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50 small"></i>
                                    <input type="date" name="created_from" value="{{ request('created_from') }}" class="form-control bg-dark border-white border-opacity-10 text-white rounded-3 ps-5 py-2 small" onchange="this.form.submit()">
                                </div>
                                <span class="text-white-50">-</span>
                                <div class="date-input-wrapper position-relative">
                                    <i class="fas fa-calendar-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50 small"></i>
                                    <input type="date" name="created_to" value="{{ request('created_to') }}" class="form-control bg-dark border-white border-opacity-10 text-white rounded-3 ps-5 py-2 small" onchange="this.form.submit()">
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="text-white-50 small fw-bold">Assigned:</span>
                                <div class="date-input-wrapper position-relative">
                                    <i class="fas fa-calendar-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50 small"></i>
                                    <input type="date" name="assigned_from" value="{{ request('assigned_from') }}" class="form-control bg-dark border-white border-opacity-10 text-white rounded-3 ps-5 py-2 small" onchange="this.form.submit()">
                                </div>
                                <span class="text-white-50">-</span>
                                <div class="date-input-wrapper position-relative">
                                    <i class="fas fa-calendar-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-white-50 small"></i>
                                    <input type="date" name="assigned_to_date" value="{{ request('assigned_to_date') }}" class="form-control bg-dark border-white border-opacity-10 text-white rounded-3 ps-5 py-2 small" onchange="this.form.submit()">
                                </div>
                            </div>

                            @if(request()->anyFilled(['search', 'status', 'source', 'agent_id', 'scope', 'previous_item', 'created_from', 'created_to', 'assigned_from', 'assigned_to_date']))
                                <a href="{{ route('leads.index') }}" class="btn btn-link text-white-50 text-decoration-none small ms-auto">
                                    <i class="fas fa-redo me-1"></i> Reset Filters
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
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
                                    @if($lead->previous_item)
                                        <div class="text-white-50 small text-xs mt-1">
                                             <span class="opacity-75">Prev Product:</span> <span class="fw-bold text-white">{{ $lead->previous_item }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="text-white fw-bold small mb-1 text-uppercase">{{ $lead->city }}</div>
                                    <div class="text-white-50 x-small d-flex align-items-start gap-1">
                                        <i class="fas fa-map-marker-alt mt-1 opacity-50 text-white-50"></i>
                                        <span class="text-truncate" style="max-width: 250px;">{{ $lead->address }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-opacity-10 text-white border border-white border-opacity-10 px-2 py-1 fw-bold">{{ $lead->status }}</span>
                            </td>
                            <td>
                                <div class="text-white-50 small d-flex align-items-center gap-2">
                                    <i class="fas fa-phone opacity-50"></i>
                                    <span>{{ $lead->last_called_at ? $lead->last_called_at->diffForHumans() : 'Never' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-white-50 small text-truncate" style="max-width: 200px;">{{ $lead->notes ?? 'No notes added yet.' }}</div>
                            </td>
                            <td class="text-end pe-4">
                                @if(!$lead->isLocked())
                                <button type="button" class="btn btn-white btn-sm px-3 fw-bold text-dark border-0 shadow-sm update-lead-btn" data-lead="{{ json_encode($lead) }}">
                                    <i class="fas fa-sync-alt me-1 small"></i> Update
                                </button>
                                @else
                                <span class="badge bg-danger bg-opacity-10 text-danger small"><i class="fas fa-lock me-1"></i> Locked</span>
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
        
        <div class="p-4 border-top border-white border-opacity-10 d-flex justify-content-center pagination-wrapper">
            {{ $leads->withQueryString()->links() }}
        </div>
    </div>
</div>

{{-- Update Side Panel (Offcanvas Replacement) --}}
<div class="side-panel-custom" id="updateSidePanel">
    <div class="side-panel-header d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="icon-circle bg-info bg-opacity-10 text-info me-3">
                <i class="fas fa-phone-alt"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">Call Update</h5>
                <small class="text-secondary">Update details for <span id="panelCustomerName">---</span></small>
            </div>
        </div>
        <button type="button" class="btn-close-custom" id="closeSidePanel"><i class="fas fa-times"></i></button>
    </div>

    <div class="side-panel-body custom-scrollbar">
        <form action="" method="POST" id="panelUpdateForm">
            @csrf
            
            <!-- Top Section: Phone & Address -->
            <div class="row mb-5">
                <!-- Left Column: Phone -->
                <div class="col-md-3 border-end border-white border-opacity-10 pe-4">
                    <div class="mb-1 text-secondary small"><i class="fas fa-phone me-2"></i>Phone Number</div>
                    <div id="panelCustomerPhone" class="h4 text-info fw-bold mb-3 font-monospace">---</div>
                    
                    <div class="mb-1 text-secondary small mt-3 uppercase tracking-wider text-xs fw-bold"><i class="fas fa-history me-2"></i>Imported Waybill Item</div>
                    <div id="panelPreviousItem" class="small text-white fw-bold mb-4 bg-opacity-10 p-2 rounded border border-white border-opacity-5">---</div>

                    <a href="#" id="btnCallNow" class="btn btn-light w-100 fw-bold rounded-pill text-dark shadow-sm">Call Now</a>
                </div>

                <!-- Right Column: Address -->
                <div class="col-md-9 ps-4">
                    <div class="mb-2 text-info small"><i class="fas fa-map-marker-alt me-2"></i>Address / Location</div>
                    
                    <input type="hidden" name="address" id="panelAddress">
                    <input type="hidden" name="province" id="panelProvince">
                    <input type="hidden" name="city" id="panelCity">
                    <input type="hidden" name="barangay" id="panelBarangay">


                    <!-- Dropdowns Row -->
                    <div class="d-flex gap-3 mb-3">
                        <div class="w-100">
                            <label class="x-small text-secondary mb-1">Province</label>
                            <div class="custom-select-container" id="containerProvince">
                                <div class="custom-select-trigger" id="triggerProvince">
                                    <span>-- Select --</span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="custom-select-dropdown" id="dropdownProvince">
                                    <div class="p-2 border-bottom border-light border-opacity-10">
                                        <input type="text" class="form-control form-control-sm bg-dark border-secondary text-white select-search-input" placeholder="Search..." id="searchProvince">
                                    </div>
                                    <div class="custom-options-list" id="listProvince"></div>
                                </div>
                            </div>
                        </div>

                        <div class="w-100">
                            <label class="x-small text-secondary mb-1">City/Municipality</label>
                            <div class="custom-select-container disabled" id="containerCity">
                                <div class="custom-select-trigger" id="triggerCity">
                                    <span>-- Select --</span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="custom-select-dropdown" id="dropdownCity">
                                    <div class="p-2 border-bottom border-light border-opacity-10">
                                        <input type="text" class="form-control form-control-sm bg-dark border-secondary text-white select-search-input" placeholder="Search..." id="searchCity">
                                    </div>
                                    <div class="custom-options-list" id="listCity"></div>
                                </div>
                            </div>
                        </div>

                        <div class="w-100">
                            <label class="x-small text-secondary mb-1">Barangay</label>
                            <div class="custom-select-container disabled" id="containerBarangay">
                                <div class="custom-select-trigger" id="triggerBarangay">
                                    <span>-- Select --</span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="custom-select-dropdown" id="dropdownBarangay">
                                    <div class="p-2 border-bottom border-light border-opacity-10">
                                        <input type="text" class="form-control form-control-sm bg-dark border-secondary text-white select-search-input" placeholder="Search..." id="searchBarangay">
                                    </div>
                                    <div class="custom-options-list" id="listBarangay"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Street Address & Product Brand Row -->
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="x-small text-secondary mb-1"><i class="fas fa-home me-1"></i>House # / Street / Detailed Address</label>
                            <input type="text" name="street" id="panelStreet" class="form-control form-control-sm bg-dark border-light border-opacity-10 text-white py-2" placeholder="Complete address detail from waybill...">
                        </div>
                        <div class="col-md-5">
                            <label class="x-small text-secondary mb-1"><i class="fas fa-tag me-1"></i>Product Brand <span class="text-danger">*</span></label>
                            <select name="product_brand" id="panelProductBrand" class="form-select form-select-sm bg-dark border-light border-opacity-10 text-white py-2" required>
                                <option value="">-- Select Brand --</option>
                                <option value="STEM COFFEE">STEM COFFEE</option>
                                <option value="BG-COFFE">BG-COFFE</option>
                                <option value="INSULIN INHALER">INSULIN INHALER</option>
                                <option value="PANSITAN TEA">PANSITAN TEA</option>
                                <option value="AVOCADO OIL">AVOCADO OIL</option>
                                <option value="AVOCADO COFFEE">AVOCADO COFFEE</option>
                                <option value="UTOG">UTOG</option>
                                <option value="SVELTO">SVELTO</option>
                                <option value="LOVE CHOCO">LOVE CHOCO</option>
                                <option value="CBOOST">CBOOST</option>
                                <option value="A-OIL">A-OIL</option>
                                <option value="A-TEA">A-TEA</option>
                                <option value="MULLEIN TEA">MULLEIN TEA</option>
                                <option value="KOPI">KOPI</option>
                                <option value="TUBAPATCH">TUBAPATCH</option>
                                <option value="AKARUI COFFEE">AKARUI COFFEE</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Geographic Summary -->
                    <div class="row g-3 mt-1">
                        <div class="col-md-12">
                            <label class="x-small text-secondary mb-1">Geographic Summary</label>
                            <div id="displayCurrentAddress" class="form-control form-control-sm bg-black bg-opacity-50 border-light border-opacity-10 text-white-50 py-2 text-truncate">
                                ---
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <h6 class="section-title mb-3 text-white">Order Details <span class="text-secondary fw-normal">(Sale)</span></h6>
            <div class="row mb-5">
                <div class="col-md-8">
                    <select name="product_name" id="panelProduct" class="form-select bg-dark border-light border-opacity-10 text-white py-2">
                        <option value="" data-price="">-- Select Product --</option>
                        <!-- Options populated by data -->
                        <optgroup label="BLACK GARLIC">
                            <option value="R-BLACK GARLIC 3 SET B1T2 + 1 ROLL ON" data-price="550">R-BLACK GARLIC 3 SET B1T2 + 1 ROLL ON</option>
                            <option value="R-BLACK GARLIC 3 SET B1T2 + 10 SOFTGEL" data-price="550">R-BLACK GARLIC 3 SET B1T2 + 10 SOFTGEL</option>
                            <option value="R-BLACK GARLIC 3 SET B1T2 + 1 SOAP" data-price="550">R-BLACK GARLIC 3 SET B1T2 + 1 SOAP</option>
                            <option value="R-BLACK GARLIC 3 SET B1T2 + 10 PATCHES" data-price="550">R-BLACK GARLIC 3 SET B1T2 + 10 PATCHES</option>
                            <option value="R-BLACK GARLIC 2 SET B1T2 + 1 ROLL ON" data-price="350">R-BLACK GARLIC 2 SET B1T2 + 1 ROLL ON</option>
                            <option value="R-BLACK GARLIC 2 SET B1T2 + 1 SOAP" data-price="350">R-BLACK GARLIC 2 SET B1T2 + 1 SOAP</option>
                            <option value="R-BLACK GARLIC 2 SET B1T2 + 10 SOFTGEL" data-price="350">R-BLACK GARLIC 2 SET B1T2 + 10 SOFTGEL</option>
                            <option value="R-BLACK GARLIC 2 SET B1T2 + 5 PATCHES" data-price="350">R-BLACK GARLIC 2 SET B1T2 + 5 PATCHES</option>
                            <option value="R-BLACK GARLIC 1 SET B1T2" data-price="200">R-BLACK GARLIC 1 SET B1T2</option>
                        </optgroup>
                        <optgroup label="STEM COFFEE">
                            <option value="R-STEM COFFEE 3 SET B1T2 + 1 ROLL ON" data-price="550">R-STEM COFFEE 3 SET B1T2 + 1 ROLL ON</option>
                            <option value="R-STEM COFFEE 3 SET B1T2 + 10 SOFTGEL" data-price="550">R-STEM COFFEE 3 SET B1T2 + 10 SOFTGEL</option>
                            <option value="R-STEM COFFEE 3 SET B1T2 + 1 SOAP" data-price="550">R-STEM COFFEE 3 SET B1T2 + 1 SOAP</option>
                            <option value="R-STEM COFFEE 3 SET B1T2 + 10 PATCHES" data-price="550">R-STEM COFFEE 3 SET B1T2 + 10 PATCHES</option>
                            <option value="R-STEM COFFEE 2 SET B1T2 + 1 ROLL ON" data-price="350">R-STEM COFFEE 2 SET B1T2 + 1 ROLL ON</option>
                            <option value="R-STEM COFFEE 2 SET B1T2 + 1 SOAP" data-price="350">R-STEM COFFEE 2 SET B1T2 + 1 SOAP</option>
                            <option value="R-STEM COFFEE 2 SET B1T2 + 10 SOFTGEL" data-price="350">R-STEM COFFEE 2 SET B1T2 + 10 SOFTGEL</option>
                            <option value="R-STEM COFFEE 2 SET B1T2 + 5 PATCHES" data-price="350">R-STEM COFFEE 2 SET B1T2 + 5 PATCHES</option>
                            <option value="R-STEM COFFEE 1 SET B1T2" data-price="200">R-STEM COFFEE 1 SET B1T2</option>
                        </optgroup>
                        <optgroup label="ALINGATONG TEA">
                            <option value="R-ALITEA 1 PACK (15 TEABAGS)" data-price="350">R-ALITEA 1 PACK (15 TEABAGS)</option>
                            <option value="R-ALITEA 2 PACKS + 5 PATCHES" data-price="650">R-ALITEA 2 PACKS + 5 PATCHES</option>
                            <option value="R-ALITEA 3 PACKS + 10 SOFTGEL" data-price="800">R-ALITEA 3 PACKS + 10 SOFTGEL</option>
                            <option value="R-ALITEA 4 PACKS + 10 SOFTGEL" data-price="1000">R-ALITEA 4 PACKS + 10 SOFTGEL</option>
                        </optgroup>
                        <optgroup label="ALINGATONG OIL">
                            <option value="R-ALIOIL 2 PCS 60ML" data-price="199">R-ALIOIL 2 PCS 60ML</option>
                            <option value="R-ALIOIL 4 PCS 60ML" data-price="350">R-ALIOIL 4 PCS 60ML</option>
                            <option value="R-ALIOIL 5 PCS 60ML" data-price="500">R-ALIOIL 5 PCS 60ML</option>
                        </optgroup>
                        <optgroup label="TUBA PATCH">
                            <option value="R-TUBA 1 SET B1T3" data-price="199">R-TUBA 1 SET B1T3</option>
                            <option value="R-TUBA 2 SET B1T3" data-price="350">R-TUBA 2 SET B1T3</option>
                        </optgroup>
                        <optgroup label="MULLEIN TEA">
                            <option value="R- MULL TEA 1 SET B1T2" data-price="199">R- MULL TEA 1 SET B1T2</option>
                            <option value="R- MULL TEA 2 SET B1T2 + 1 ROLL ON" data-price="350">R- MULL TEA 2 SET B1T2 + 1 ROLL ON</option>
                            <option value="R- MULL TEA 2 SET B1T2 + 1 INHALER" data-price="330">R- MULL TEA 2 SET B1T2 + 1 INHALER</option>
                            <option value="R- MULL TEA 2 SET B1T2 + 5 PATCHES" data-price="350">R- MULL TEA 2 SET B1T2 + 5 PATCHES</option>
                        </optgroup>
                        <optgroup label="FEMWASH">
                            <option value="R-KOPIKIFF W/2 FEMWASH 1 SET B1T2 W/ 1 FEMWASH" data-price="299">R-KOPIKIFF W/2 FEMWASH 1 SET B1T2 W/ 1 FEMWASH</option>
                            <option value="R-KOPIKIFF W/2 FEMWASH 2 SET B1T2 + 1 ROLL ON" data-price="520">R-KOPIKIFF W/2 FEMWASH 2 SET B1T2 + 1 ROLL ON</option>
                            <option value="R-KOPIKIFF W/2 FEMWASH 2 SET B1T2 + 1 INHALER" data-price="520">R-KOPIKIFF W/2 FEMWASH 2 SET B1T2 + 1 INHALER</option>
                            <option value="R-KOPIKIFF W/2 FEMWASH 2 SET B1T2 + 5 PATCHES" data-price="520">R-KOPIKIFF W/2 FEMWASH 2 SET B1T2 + 5 PATCHES</option>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" name="amount" id="panelAmount" class="form-control bg-dark border-light border-opacity-10 text-white py-2" placeholder="Amount (Optional)" step="0.01">
                </div>
            </div>
            
            <h6 class="section-title mb-3 text-white">Call Outcome</h6>
            <div class="outcome-list mb-4">
                <div class="outcome-item">
                    <input type="radio" name="status" id="panel_st_noanswer" value="NO_ANSWER" class="outcome-radio" required>
                    <label for="panel_st_noanswer" class="outcome-label outcome-warning">
                        <div class="d-flex align-items-center">
                            <span class="outcome-icon"><i class="fas fa-phone-slash"></i></span>
                            <span class="outcome-text">No Answer</span>
                        </div>
                        <div class="outcome-check"></div>
                    </label>
                </div>

                <div class="outcome-item">
                    <input type="radio" name="status" id="panel_st_callback" value="CALLBACK" class="outcome-radio">
                    <label for="panel_st_callback" class="outcome-label outcome-info">
                        <div class="d-flex align-items-center">
                            <span class="outcome-icon"><i class="fas fa-phone-volume"></i></span>
                            <span class="outcome-text">Callback</span>
                        </div>
                        <div class="outcome-check"></div>
                    </label>
                </div>

                <div class="outcome-item">
                    <input type="radio" name="status" id="panel_st_reject" value="REJECT" class="outcome-radio">
                    <label for="panel_st_reject" class="outcome-label outcome-danger">
                        <div class="d-flex align-items-center">
                            <span class="outcome-icon"><i class="fas fa-user-times"></i></span>
                            <span class="outcome-text">Reject</span>
                        </div>
                        <div class="outcome-check"></div>
                    </label>
                </div>

                <div class="outcome-item">
                    <input type="radio" name="status" id="panel_st_sale" value="SALE" class="outcome-radio">
                    <label for="panel_st_sale" class="outcome-label outcome-success">
                        <div class="d-flex align-items-center">
                            <span class="outcome-icon"><i class="fas fa-check-circle"></i></span>
                            <span class="outcome-text">Sale</span>
                        </div>
                        <div class="outcome-check"></div>
                    </label>
                </div>
            </div>

            <h6 class="section-title mb-2 text-white">Call Summary / Notes</h6>
            <div class="mb-4">
                <textarea name="note" class="notes-textarea form-control bg-dark border-light border-opacity-10 text-white" rows="3" placeholder="Describe the conversation result..."></textarea>
            </div>

            <div class="side-panel-footer d-flex justify-content-end mb-4">
                <button type="button" class="btn btn-light px-4 me-2 rounded-pill" id="cancelSidePanel">Cancel</button>
                <button type="submit" class="btn btn-info text-dark fw-bold px-4 rounded-pill shadow-sm">Save Update & Log Call</button>
            </div>
        </form>

        {{-- Separated System History Section --}}
        <div class="mt-5 pt-4 border-top border-white border-opacity-10">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-circle-sm bg-success bg-opacity-10 text-success me-2">
                    <i class="fas fa-shopping-cart text-xs"></i>
                </div>
                <h6 class="mb-0 fw-bold text-white uppercase tracking-wider small">System Order History</h6>
            </div>
            <div id="panelOrderHistory" class="custom-scrollbar" style="max-height: 300px; overflow-y: auto;">
                <div class="text-white-50 text-center py-4 small bg-dark bg-opacity-25 rounded-3 border border-white border-opacity-5">
                    <i class="fas fa-box-open d-block mb-2 opacity-25" style="font-size: 1.5rem;"></i>
                    No prior system orders found
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Searchable Select Styles */
.custom-select-container {
    position: relative;
    width: 100%;
}
.custom-select-container.disabled {
    opacity: 0.5;
    pointer-events: none;
}
.custom-select-trigger {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    color: #f8fafc;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
    transition: all 0.2s;
}
.custom-select-trigger:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.2);
}
.custom-select-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    margin-top: 4px;
    z-index: 100;
    display: none;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
}
.custom-select-dropdown.show {
    display: block;
    animation: fadeInDropdown 0.2s ease;
}
@keyframes fadeInDropdown {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
.custom-options-list {
    max-height: 200px;
    overflow-y: auto;
}
.custom-option {
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    color: #cbd5e1;
    font-size: 0.875rem;
    transition: background 0.1s;
}
.custom-option:hover {
    background: rgba(56, 189, 248, 0.1); /* Sky blue tint */
    color: #38bdf8;
}
.custom-option.selected {
    background: rgba(56, 189, 248, 0.2);
    color: #38bdf8;
    font-weight: 600;
}

/* Call Outcome Styles */
.outcome-item {
    margin-bottom: 0.75rem;
}
.outcome-radio {
    display: none;
}
.outcome-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}
.outcome-label:hover {
    background: rgba(255, 255, 255, 0.06);
}
.outcome-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.1rem;
    background: rgba(255, 255, 255, 0.1);
    color: #94a3b8;
}
.outcome-text {
    font-weight: 500;
    color: #f1f5f9;
}
.outcome-check {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #475569;
    position: relative;
    transition: all 0.2s;
}

/* Outcome Colors */
.outcome-warning:hover .outcome-icon { color: #fbbf24; background: rgba(251, 191, 36, 0.1); }
.outcome-radio:checked + .outcome-warning { border-color: #fbbf24; background: rgba(251, 191, 36, 0.05); }
.outcome-radio:checked + .outcome-warning .outcome-icon { color: #fbbf24; background: rgba(251, 191, 36, 0.1); }
.outcome-radio:checked + .outcome-warning .outcome-check { border-color: #fbbf24; background: #fbbf24; box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.1); }

.outcome-info:hover .outcome-icon { color: #22d3ee; background: rgba(34, 211, 238, 0.1); }
.outcome-radio:checked + .outcome-info { border-color: #22d3ee; background: rgba(34, 211, 238, 0.05); }
.outcome-radio:checked + .outcome-info .outcome-icon { color: #22d3ee; background: rgba(34, 211, 238, 0.1); }
.outcome-radio:checked + .outcome-info .outcome-check { border-color: #22d3ee; background: #22d3ee; box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.1); }

.outcome-danger:hover .outcome-icon { color: #f87171; background: rgba(248, 113, 113, 0.1); }
.outcome-radio:checked + .outcome-danger { border-color: #f87171; background: rgba(248, 113, 113, 0.05); }
.outcome-radio:checked + .outcome-danger .outcome-icon { color: #f87171; background: rgba(248, 113, 113, 0.1); }
.outcome-radio:checked + .outcome-danger .outcome-check { border-color: #f87171; background: #f87171; box-shadow: 0 0 0 4px rgba(248, 113, 113, 0.1); }

.outcome-success:hover .outcome-icon { color: #4ade80; background: rgba(74, 222, 128, 0.1); }
.outcome-radio:checked + .outcome-success { border-color: #4ade80; background: rgba(74, 222, 128, 0.05); }
.outcome-radio:checked + .outcome-success .outcome-icon { color: #4ade80; background: rgba(74, 222, 128, 0.1); }
.outcome-radio:checked + .outcome-success .outcome-check { border-color: #4ade80; background: #4ade80; box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.1); }

</style>
<style>
/* Side Panel Styles - Exact match to screenshot */
.side-panel-custom {
    position: fixed;
    top: 0;
    right: -800px;
    width: 800px;
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

    // Custom Searchable Select Logic
    let addressData = {};
    const panelAddress = document.getElementById('panelAddress');
    const panelStreetInput = document.getElementById('panelStreet');
    
    // State to track selections
    let selectedProvince = '';
    let selectedCity = '';
    let selectedBarangay = '';

    // Fetch Address Data
    fetch('/js/address-data.json')
        .then(response => response.json())
        .then(data => {
            addressData = data;
            initCustomSelects();
        })
        .catch(error => console.error('Error loading address data:', error));

    function initCustomSelects() {
        populateProvinceOptions();
        setupDropdown('Province');
        setupDropdown('City');
        setupDropdown('Barangay');
    }

    function setupDropdown(type) {
        const trigger = document.getElementById(`trigger${type}`);
        const dropdown = document.getElementById(`dropdown${type}`);
        const searchInput = document.getElementById(`search${type}`);
        const list = document.getElementById(`list${type}`);
        const container = document.getElementById(`container${type}`);

        // Toggle Dropdown
        trigger.addEventListener('click', function(e) {
            if(container.classList.contains('disabled')) return;
            e.stopPropagation();
            closeAllDropdowns();
            dropdown.classList.add('show');
            searchInput.focus();
        });

        // Search Filter
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const options = list.querySelectorAll('.custom-option');
            options.forEach(opt => {
                const text = opt.textContent.toLowerCase();
                opt.style.display = text.includes(filter) ? 'block' : 'none';
            });
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if(!container.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.custom-select-dropdown').forEach(el => el.classList.remove('show'));
    }

    function renderOptions(type, options, onSelect) {
        const list = document.getElementById(`list${type}`);
        list.innerHTML = '';
        
        if (options.length === 0) {
            list.innerHTML = '<div class="p-2 text-muted x-small">No results found</div>';
            return;
        }

        options.sort().forEach(val => {
            const div = document.createElement('div');
            div.className = 'custom-option';
            div.textContent = val;
            div.addEventListener('click', function(e) {
                e.stopPropagation();
                updateTriggerText(type, val);
                document.getElementById(`dropdown${type}`).classList.remove('show');
                onSelect(val);
            });
            list.appendChild(div);
        });
    }

    function updateTriggerText(type, text) {
        const trigger = document.getElementById(`trigger${type}`);
        trigger.querySelector('span').textContent = text;
        trigger.classList.add('text-info');
    }

    function resetTriggerText(type, placeholder) {
        const trigger = document.getElementById(`trigger${type}`);
        trigger.querySelector('span').textContent = placeholder;
        trigger.classList.remove('text-info');
        document.getElementById(`search${type}`).value = ''; // Reset search
    }

    // --- Population Logic ---

    function populateProvinceOptions() {
        const provinces = Object.keys(addressData);
        renderOptions('Province', provinces, (val) => {
            selectedProvince = val;
            selectedCity = '';
            selectedBarangay = '';
            
            // Reset dependent fields
            resetTriggerText('City', '-- Select City/Municipality --');
            resetTriggerText('Barangay', '-- Select Barangay --');
            document.getElementById('listCity').innerHTML = '';
            document.getElementById('listBarangay').innerHTML = '';
            
            // Enable City
            document.getElementById('containerCity').classList.remove('disabled');
            document.getElementById('containerBarangay').classList.add('disabled');
            
            populateCityOptions(val);
            updateHiddenAddress();
        });
    }

    function populateCityOptions(province) {
        if (!province || !addressData[province]) return;
        const cities = Object.keys(addressData[province]);
        renderOptions('City', cities, (val) => {
            selectedCity = val;
            selectedBarangay = '';
            
            // Reset dependent field
            resetTriggerText('Barangay', '-- Select Barangay --');
            
            // Enable Barangay
            document.getElementById('containerBarangay').classList.remove('disabled');
            
            populateBarangayOptions(province, val);
            updateHiddenAddress();
        });
    }

    function populateBarangayOptions(province, city) {
        if (!province || !city || !addressData[province][city]) return;
        const barangays = addressData[province][city];
        renderOptions('Barangay', barangays, (val) => {
            selectedBarangay = val;
            updateHiddenAddress();
        });
    }

    function updateHiddenAddress() {
        const parts = [];
        const streetVal = panelStreetInput ? panelStreetInput.value.trim() : '';
        
        if (streetVal) parts.push(streetVal);
        if (selectedBarangay) parts.push(selectedBarangay);
        if (selectedCity) parts.push(selectedCity);
        if (selectedProvince) parts.push(selectedProvince);
        
        panelAddress.value = parts.join(', ');
        
        // Also update separate hidden fields if they exist (backward compat or specific use)
        if(document.getElementById('panelProvince')) document.getElementById('panelProvince').value = selectedProvince;
        if(document.getElementById('panelCity')) document.getElementById('panelCity').value = selectedCity;
        if(document.getElementById('panelBarangay')) document.getElementById('panelBarangay').value = selectedBarangay;
    }

    if (panelStreetInput) {
        panelStreetInput.addEventListener('input', updateHiddenAddress);
    }

    // --- Panel Opening Logic ---
    updateButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const lead = JSON.parse(this.dataset.lead);
            
            document.getElementById('panelCustomerName').textContent = lead.name;
            document.getElementById('panelCustomerPhone').textContent = lead.phone;
            
            // Show current address
            const displayCurrentAddress = document.getElementById('displayCurrentAddress');
            if (displayCurrentAddress) {
                displayCurrentAddress.textContent = lead.address || 'No address set';
            }
            
            // Set hidden address input default
            panelAddress.value = lead.address || '';
            
            // Population of individual address parts
            document.getElementById('panelProvince').value = lead.state || '';
            document.getElementById('panelCity').value = lead.city || '';
            document.getElementById('panelBarangay').value = lead.barangay || '';
            document.getElementById('panelStreet').value = lead.street || '';

            // Handle dropdown selections if data exists
            if (lead.state && addressData[lead.state]) {
                selectedProvince = lead.state;
                updateTriggerText('Province', lead.state);
                
                document.getElementById('containerCity').classList.remove('disabled');
                populateCityOptions(lead.state);

                if (lead.city && addressData[lead.state][lead.city]) {
                    selectedCity = lead.city;
                    updateTriggerText('City', lead.city);
                    
                    document.getElementById('containerBarangay').classList.remove('disabled');
                    populateBarangayOptions(lead.state, lead.city);

                    if (lead.barangay) {
                        selectedBarangay = lead.barangay;
                        updateTriggerText('Barangay', lead.barangay);
                    } else {
                        selectedBarangay = '';
                        resetTriggerText('Barangay', '-- Select Barangay --');
                    }
                } else {
                    selectedCity = '';
                    resetTriggerText('City', '-- Select City/Municipality --');
                    selectedBarangay = '';
                    resetTriggerText('Barangay', '-- Select Barangay --');
                    document.getElementById('containerBarangay').classList.add('disabled');
                }
            } else {
                selectedProvince = '';
                selectedCity = '';
                selectedBarangay = '';
                resetTriggerText('Province', '-- Select Province --');
                resetTriggerText('City', '-- Select City/Municipality --');
                resetTriggerText('Barangay', '-- Select Barangay --');
                document.getElementById('containerCity').classList.add('disabled');
                document.getElementById('containerBarangay').classList.add('disabled');
            }

            if (panelStreetInput) panelStreetInput.value = lead.street || '';

            document.getElementById('panelPreviousItem').textContent = lead.previous_item || 'None';
            
            // Populate System Order History
            const historyContainer = document.getElementById('panelOrderHistory');
            if (historyContainer) {
                if (lead.orders && lead.orders.length > 0) {
                    historyContainer.innerHTML = lead.orders.map(order => `
                        <div class="bg-dark bg-opacity-50 p-3 rounded-3 border border-white border-opacity-5 mb-2 shadow-sm hover-border-info transition-all">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-2 py-1 text-xs">${order.status}</span>
                                <span class="text-white-50 x-small font-monospace">${new Date(order.created_at).toLocaleDateString()}</span>
                            </div>
                            <div class="text-white fw-bold small mb-1">${order.product_name}</div>
                            <div class="d-flex justify-content-between align-items-center x-small">
                                <span class="text-white-50"><i class="fas fa-tag me-1"></i>${order.product_brand || 'No Brand'}</span>
                                <span class="text-info fw-bold font-monospace">₱${order.amount || 0}</span>
                            </div>
                            ${order.notes ? `
                                <div class="mt-2 text-white-50 text-xs border-top border-white border-opacity-5 pt-2">
                                    <i class="fas fa-comment-dots me-1 opacity-50"></i> "${order.notes}"
                                </div>
                            ` : ''}
                        </div>
                    `).join('');
                } else {
                    historyContainer.innerHTML = `
                        <div class="text-white-50 text-center py-4 small bg-dark bg-opacity-25 rounded-3 border border-white border-opacity-5">
                            <i class="fas fa-box-open d-block mb-2 opacity-25" style="font-size: 1.5rem;"></i>
                            No prior system orders found
                        </div>
                    `;
                }
            }

            document.getElementById('panelProductBrand').value = lead.product_brand || '';
            document.getElementById('panelProduct').value = lead.product_name || '';
            document.getElementById('panelAmount').value = lead.amount || '';
            
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

    // Auto-fill Amount based on Product Selection
    const panelProduct = document.getElementById('panelProduct');
    const panelAmount = document.getElementById('panelAmount');
    
    if(panelProduct && panelAmount) {
        panelProduct.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price;
            
            if(price) {
                panelAmount.value = price;
            }
        });
    }
});
</script>
@endsection
