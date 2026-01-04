@extends('layouts.app')

@push('styles')
<style>
    .recycling-pool-wrapper {
        background-color: #0b0e14;
        min-height: 100vh;
    }
    .page-header .page-title {
        font-size: 2.2rem;
        letter-spacing: -0.02em;
        font-weight: 800;
        color: white;
    }
    .stats-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 1.5rem;
        transition: all 0.3s;
    }
    .stats-card:hover {
        background: rgba(255, 255, 255, 0.04);
        transform: translateY(-2px);
    }
    .stats-value {
        font-size: 2.5rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 0.5rem;
    }
    .stats-label {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .filter-box {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 16px;
    }
    .priority-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.75rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .priority-high {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }
    .priority-medium {
        background: rgba(251, 191, 36, 0.1);
        border: 1px solid rgba(251, 191, 36, 0.3);
        color: #fbbf24;
    }
    .priority-low {
        background: rgba(148, 163, 184, 0.1);
        border: 1px solid rgba(148, 163, 184, 0.3);
        color: #94a3b8;
    }
    .customer-score-badge {
        font-weight: 700;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-size: 0.75rem;
    }
    .score-high { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); }
    .score-medium { background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
    .score-low { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
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
    .custom-chk {
        width: 18px;
        height: 18px;
        background-color: transparent;
        border: 2px solid rgba(255, 255, 255, 0.2);
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
    .form-select, .form-control {
        border: 1px solid rgba(255,255,255,0.1) !important;
        background-color: rgba(255,255,255,0.02) !important;
        color: white !important;
    }
    .form-select option {
        background-color: #1a1d24;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 recycling-pool-wrapper">
    {{-- Page Header --}}
    <div class="page-header px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title mb-2">
                    <i class="fas fa-recycle me-3 text-info"></i>Lead Recycling Pool
                </h1>
                <p class="text-white-50 mb-0">Manage and assign recycled customer leads</p>
            </div>

            <div class="d-flex align-items-center gap-3">
                <form action="{{ route('recycling.cleanup') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-white text-dark fw-bold px-3 py-2 rounded-3 shadow-sm">
                        <i class="fas fa-broom me-2 small"></i> Run Cleanup
                    </button>
                </form>
                <a href="{{ route('reports.recycling-funnel') }}" class="btn btn-info text-dark fw-bold px-3 py-2 rounded-3 shadow-sm">
                    <i class="fas fa-chart-line me-2 small"></i> View Analytics
                </a>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-white">{{ number_format($stats['total_available'] ?? 0) }}</div>
                    <div class="stats-label">Available</div>
                    <div class="mt-2">
                        <span class="badge bg-success bg-opacity-10 text-success small">
                            <i class="fas fa-circle me-1" style="font-size: 6px;"></i> Ready to Assign
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-info">{{ number_format($stats['total_assigned'] ?? 0) }}</div>
                    <div class="stats-label">Assigned</div>
                    <div class="mt-2">
                        <span class="badge bg-info bg-opacity-10 text-info small">
                            <i class="fas fa-user-clock me-1"></i> In Progress
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-success">{{ number_format($stats['total_converted'] ?? 0) }}</div>
                    <div class="stats-label">Converted</div>
                    <div class="mt-2">
                        <span class="badge bg-success bg-opacity-10 text-success small">
                            <i class="fas fa-check-circle me-1"></i> Success
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-warning">
                        {{ number_format($stats['conversion_rate'] ?? 0, 1) }}<span class="fs-4">%</span>
                    </div>
                    <div class="stats-label">Conversion Rate</div>
                    <div class="mt-2">
                        @php
                            $rate = $stats['conversion_rate'] ?? 0;
                            $badgeClass = $rate >= 15 ? 'success' : ($rate >= 10 ? 'warning' : 'danger');
                        @endphp
                        <span class="badge bg-{{ $badgeClass }} bg-opacity-10 text-{{ $badgeClass }} small">
                            <i class="fas fa-chart-line me-1"></i>
                            {{ $rate >= 15 ? 'Excellent' : ($rate >= 10 ? 'Good' : 'Needs Improvement') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Box --}}
        <div class="filter-box bg-opacity-5 border border-white border-opacity-10 rounded-4 p-4">
            <form action="{{ route('recycling.pool') }}" method="GET" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="text-white-50 small mb-2">
                            <i class="fas fa-signal me-1"></i> Min Priority
                        </label>
                        <select name="min_priority" class="form-select bg-dark text-white rounded-3" onchange="this.form.submit()">
                            <option value="">All Priorities</option>
                            <option value="70" {{ request('min_priority') == '70' ? 'selected' : '' }}>High (70+)</option>
                            <option value="40" {{ request('min_priority') == '40' ? 'selected' : '' }}>Medium (40+)</option>
                            <option value="1" {{ request('min_priority') == '1' ? 'selected' : '' }}>Low (1+)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="text-white-50 small mb-2">
                            <i class="fas fa-tag me-1"></i> Recycle Reason
                        </label>
                        <select name="recycle_reason" class="form-select bg-dark text-white rounded-3" onchange="this.form.submit()">
                            <option value="">All Reasons</option>
                            @foreach($reasons as $reason)
                                <option value="{{ $reason }}" {{ request('recycle_reason') == $reason ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', $reason) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="text-white-50 small mb-2">
                            <i class="fas fa-star me-1"></i> Min Customer Score
                        </label>
                        <select name="customer_score_min" class="form-select bg-dark text-white rounded-3" onchange="this.form.submit()">
                            <option value="">All Scores</option>
                            <option value="70" {{ request('customer_score_min') == '70' ? 'selected' : '' }}>70+ (High)</option>
                            <option value="50" {{ request('customer_score_min') == '50' ? 'selected' : '' }}>50+ (Medium)</option>
                            <option value="30" {{ request('customer_score_min') == '30' ? 'selected' : '' }}>30+ (Low)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="text-white-50 small mb-2">
                            <i class="fas fa-filter me-1"></i> Pool Status
                        </label>
                        <select name="pool_status" class="form-select bg-dark text-white rounded-3" onchange="this.form.submit()">
                            <option value="">Available & Assigned</option>
                            <option value="AVAILABLE" {{ request('pool_status') == 'AVAILABLE' ? 'selected' : '' }}>Available Only</option>
                            <option value="ASSIGNED" {{ request('pool_status') == 'ASSIGNED' ? 'selected' : '' }}>Assigned Only</option>
                            <option value="CONVERTED" {{ request('pool_status') == 'CONVERTED' ? 'selected' : '' }}>Converted</option>
                            <option value="EXHAUSTED" {{ request('pool_status') == 'EXHAUSTED' ? 'selected' : '' }}>Exhausted</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="text-white-50 small mb-2">
                            <i class="fas fa-list me-1"></i> Per Page
                        </label>
                        <select name="count" class="form-select bg-dark text-white rounded-3" onchange="this.form.submit()">
                            <option value="50" {{ request('count', 50) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('count') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        @if(request()->anyFilled(['min_priority', 'recycle_reason', 'customer_score_min', 'pool_status']))
                            <a href="{{ route('recycling.pool') }}" class="btn btn-link text-white-50 text-decoration-none small w-100">
                                <i class="fas fa-redo me-1"></i> Reset Filters
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table Area --}}
    <div class="px-0 pt-3">
        <form action="{{ route('recycling.assign') }}" method="POST" id="bulkAssignForm">
            @csrf
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 custom-table-spacing">
                    <thead>
                        <tr class="text-white-50 small text-uppercase fw-bold border-bottom border-white border-opacity-10">
                            <th class="ps-4 py-3" style="width: 50px;">
                                <input type="checkbox" id="selectAll" class="custom-chk">
                            </th>
                            <th class="py-3">Customer</th>
                            <th class="py-3">Performance</th>
                            <th class="py-3">Priority</th>
                            <th class="py-3">Reason</th>
                            <th class="py-3">Available Since</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($poolEntries as $entry)
                        <tr class="align-middle border-bottom border-white border-opacity-5">
                            <td class="ps-4">
                                @if($entry->pool_status === 'AVAILABLE')
                                    <input type="checkbox" name="pool_ids[]" value="{{ $entry->id }}" class="pool-check custom-chk">
                                @else
                                    <i class="fas fa-lock text-white text-opacity-10"></i>
                                @endif
                            </td>

                            {{-- Customer Info --}}
                            <td>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div class="fw-bold text-white h6 mb-0">{{ $entry->customer->name_display }}</div>

                                        {{-- Customer Profile Link --}}
                                        <a href="{{ route('customers.show', $entry->customer->id) }}"
                                           class="text-cyan text-decoration-none"
                                           data-bs-toggle="tooltip"
                                           title="View Full Customer Profile">
                                            <i class="fas fa-user-circle"></i>
                                        </a>
                                    </div>

                                    <div class="text-info small d-flex align-items-center gap-2">
                                        <span><i class="fas fa-phone-alt me-1 opacity-50"></i> {{ $entry->customer->phone_primary }}</span>
                                    </div>
                                    @if($entry->customer->city)
                                        <div class="text-white-50 small mt-1">
                                            <i class="fas fa-map-marker-alt me-1 opacity-50"></i> {{ $entry->customer->city }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Performance Metrics --}}
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    {{-- Customer Score --}}
                                    <div>
                                        @php
                                            $score = $entry->customer->customer_score;
                                            $scoreClass = $score >= 70 ? 'high' : ($score >= 50 ? 'medium' : 'low');
                                        @endphp
                                        <span class="customer-score-badge score-{{ $scoreClass }}">
                                            <i class="fas fa-star me-1"></i> {{ $score }}/100
                                        </span>
                                    </div>

                                    {{-- Order History --}}
                                    <div class="small text-white-50">
                                        <i class="fas fa-box me-1"></i>
                                        {{ $entry->customer->total_orders }} orders
                                        @if($entry->customer->total_orders > 0)
                                            | {{ round($entry->customer->delivery_success_rate) }}% success
                                        @endif
                                    </div>

                                    {{-- Risk Level --}}
                                    @if($entry->customer->risk_level !== 'UNKNOWN')
                                        <div>
                                            @php
                                                $riskColors = [
                                                    'LOW' => 'success',
                                                    'MEDIUM' => 'warning',
                                                    'HIGH' => 'danger',
                                                    'BLACKLIST' => 'dark'
                                                ];
                                                $riskColor = $riskColors[$entry->customer->risk_level] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $riskColor }} bg-opacity-10 text-{{ $riskColor }} border border-{{ $riskColor }} border-opacity-20 small">
                                                {{ $entry->customer->risk_level }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Priority --}}
                            <td>
                                @php
                                    $priority = $entry->priority_score;
                                    $priorityClass = $priority >= 70 ? 'high' : ($priority >= 40 ? 'medium' : 'low');
                                    $priorityIcon = $priority >= 70 ? 'fire' : ($priority >= 40 ? 'bolt' : 'circle');
                                @endphp
                                <span class="priority-badge priority-{{ $priorityClass }}">
                                    <i class="fas fa-{{ $priorityIcon }}"></i>
                                    <span>{{ $priority }}</span>
                                </span>
                            </td>

                            {{-- Reason --}}
                            <td>
                                <div class="small">
                                    <div class="text-white fw-bold mb-1">{{ $entry->reason_label }}</div>
                                    @if($entry->recycle_count > 1)
                                        <div class="text-warning small">
                                            <i class="fas fa-redo me-1"></i> Attempt #{{ $entry->recycle_count }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Available Since --}}
                            <td>
                                <div class="text-white-50 small">
                                    <i class="fas fa-clock opacity-50 me-1"></i>
                                    {{ $entry->available_from->diffForHumans() }}
                                </div>
                                @if($entry->expires_at)
                                    <div class="text-danger small mt-1">
                                        <i class="fas fa-hourglass-end me-1"></i>
                                        Expires {{ $entry->expires_at->diffForHumans() }}
                                    </div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td>
                                @php
                                    $statusColors = [
                                        'AVAILABLE' => 'success',
                                        'ASSIGNED' => 'info',
                                        'CONVERTED' => 'success',
                                        'EXHAUSTED' => 'danger',
                                        'EXPIRED' => 'secondary'
                                    ];
                                    $statusColor = $statusColors[$entry->pool_status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-20 px-2 py-1 fw-bold">
                                    {{ $entry->pool_status }}
                                </span>
                                @if($entry->pool_status === 'ASSIGNED' && $entry->assignedAgent)
                                    <div class="text-white-50 small mt-1">
                                        <i class="fas fa-user me-1"></i> {{ $entry->assignedAgent->name }}
                                    </div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="text-end pe-4">
                                @if($entry->pool_status === 'AVAILABLE')
                                    <div class="d-flex gap-2 justify-content-end">
                                        <select class="form-select form-select-sm bg-dark border-info text-white" style="width: 150px;" onchange="assignSingle(this, '{{ $entry->id }}')">
                                            <option value="">Assign to...</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif($entry->pool_status === 'ASSIGNED')
                                    <span class="badge bg-info bg-opacity-10 text-info small">
                                        <i class="fas fa-clock me-1"></i> Waiting
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary small">
                                        {{ $entry->processed_outcome ?? 'N/A' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="opacity-25 py-4">
                                    <i class="fas fa-inbox fa-4x mb-3"></i>
                                    <h5 class="fw-light">No Entries in Recycling Pool</h5>
                                    <p class="small">Pool entries will appear here when customers are eligible for recycling.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Bulk Action Footer --}}
            @if($poolEntries->count() > 0)
            <div class="d-flex justify-content-between align-items-center p-4 border-top border-white border-opacity-10 bg-dark bg-opacity-20">
                <div class="small text-white-50">
                    <span id="selectedCount" class="text-white fw-bold">0</span> entries selected
                </div>
                <div class="d-flex gap-2">
                    <select name="agent_id" class="form-select bg-dark border-white border-opacity-10 text-white w-auto" required>
                        <option value="">-- Assign to Agent --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-info px-4 fw-bold">Assign Selected</button>
                </div>
            </div>
            @endif
        </form>

        {{-- Pagination --}}
        <div class="p-4 border-top border-white border-opacity-10 d-flex justify-content-center pagination-wrapper">
            {{ $poolEntries->withQueryString()->links() }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Select All Checkbox
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.pool-check');
    const selectedCount = document.getElementById('selectedCount');

    function updateCount() {
        const count = document.querySelectorAll('.pool-check:checked').length;
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

    // Show success/error messages
    @if(session('success'))
        showToast('success', '{{ session('success') }}');
    @endif

    @if(session('error'))
        showToast('error', '{{ session('error') }}');
    @endif
});

// Assign single entry
function assignSingle(select, poolId) {
    const agentId = select.value;
    if (!agentId) return;

    if (!confirm('Assign this lead to the selected agent?')) {
        select.value = '';
        return;
    }

    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("recycling.assign") }}';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';

    const poolIdInput = document.createElement('input');
    poolIdInput.type = 'hidden';
    poolIdInput.name = 'pool_ids[]';
    poolIdInput.value = poolId;

    const agentIdInput = document.createElement('input');
    agentIdInput.type = 'hidden';
    agentIdInput.name = 'agent_id';
    agentIdInput.value = agentId;

    form.appendChild(csrf);
    form.appendChild(poolIdInput);
    form.appendChild(agentIdInput);

    document.body.appendChild(form);
    form.submit();
}

// Simple toast notification
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed top-0 start-50 translate-middle-x mt-3`;
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<style>
/* Pagination styling */
.pagination { margin-bottom: 0; gap: 4px; }
.page-link {
    background-color: #1a1d24 !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: #fff !important;
    border-radius: 6px !important;
    padding: 0.6rem 1rem;
}
.page-item.active .page-link {
    background-color: #00bfff !important;
    border-color: #00bfff !important;
}
</style>
@endsection
