@extends('layouts.app')

@push('styles')
<style>
    .customer-profile-wrapper {
        background-color: #0b0e14;
        min-height: 100vh;
    }
    .page-title {
        font-size: 2rem;
        letter-spacing: -0.02em;
        font-weight: 800;
        color: white;
    }
    .profile-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 2rem;
    }
    .score-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        font-size: 3rem;
        font-weight: 800;
        position: relative;
    }
    .score-high {
        background: radial-gradient(circle, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.05) 100%);
        border: 3px solid #22c55e;
        color: #22c55e;
    }
    .score-medium {
        background: radial-gradient(circle, rgba(251, 191, 36, 0.15) 0%, rgba(251, 191, 36, 0.05) 100%);
        border: 3px solid #fbbf24;
        color: #fbbf24;
    }
    .score-low {
        background: radial-gradient(circle, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.05) 100%);
        border: 3px solid #ef4444;
        color: #ef4444;
    }
    .metric-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 1.25rem;
        text-align: center;
        transition: all 0.3s;
    }
    .metric-card:hover {
        background: rgba(255, 255, 255, 0.04);
        transform: translateY(-2px);
    }
    .metric-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: white;
    }
    .metric-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 0.25rem;
    }
    .timeline-item {
        position: relative;
        padding-left: 2rem;
        padding-bottom: 1.5rem;
        border-left: 2px solid rgba(255, 255, 255, 0.1);
    }
    .timeline-item:last-child {
        border-left-color: transparent;
        padding-bottom: 0;
    }
    .timeline-dot {
        position: absolute;
        left: -8px;
        top: 0;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 3px solid #1a1d24;
    }
    .timeline-dot-delivered {
        background: #22c55e;
    }
    .timeline-dot-returned {
        background: #ef4444;
    }
    .timeline-dot-pending {
        background: #fbbf24;
    }
    .btn-white {
        background: white;
        border: none;
        color: black;
        transition: all 0.2s;
    }
    .btn-white:hover {
        background: #f1f5f9;
        transform: translateY(-1px);
    }
    .btn-danger-outline {
        background: transparent;
        border: 1px solid #ef4444;
        color: #ef4444;
        transition: all 0.2s;
    }
    .btn-danger-outline:hover {
        background: rgba(239, 68, 68, 0.1);
        border-color: #dc2626;
        color: #dc2626;
    }
    .info-row {
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .info-row:last-child {
        border-bottom: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 customer-profile-wrapper">
    {{-- Page Header --}}
    <div class="px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <a href="{{ url()->previous() }}" class="text-white-50 text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i> Back
                    </a>
                </div>
                <h1 class="page-title mb-1">
                    <i class="fas fa-user-circle me-3 text-info"></i>{{ $customer->name_display }}
                    @if($metrics['repeat_customer'] ?? false)
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-30 ms-3" style="font-size: 0.6em; vertical-align: middle;">
                            <i class="fas fa-repeat me-1"></i> REPEAT CUSTOMER
                        </span>
                    @endif
                </h1>
                <p class="text-white-50 mb-0">Customer Profile & History</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if(Auth::user()->canAccess('leads_create'))
                    <button type="button" class="btn btn-white text-dark fw-bold px-3 py-2 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createLeadModal">
                        <i class="fas fa-plus me-2 small"></i> Create Lead
                    </button>
                @endif

                @if(Auth::user()->canAccess('leads_manage'))
                    @if($customer->risk_level === 'BLACKLIST')
                        <form action="{{ route('customers.unblacklist', $customer->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success text-white fw-bold px-3 py-2 rounded-3 shadow-sm">
                                <i class="fas fa-undo me-2 small"></i> Remove Blacklist
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn btn-danger-outline fw-bold px-3 py-2 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#blacklistModal">
                            <i class="fas fa-ban me-2 small"></i> Blacklist
                        </button>
                    @endif
                @endif
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="row g-4">
            {{-- Left Column: Customer Info --}}
            <div class="col-md-4">
                {{-- Customer Score Card --}}
                <div class="profile-card text-center mb-4">
                    <div class="mb-3">
                        @php
                            $score = $customer->customer_score;
                            $scoreClass = $score >= 70 ? 'high' : ($score >= 50 ? 'medium' : 'low');
                        @endphp
                        <div class="score-circle score-{{ $scoreClass }} mx-auto">
                            <div>{{ $score }}</div>
                            <div style="font-size: 0.9rem; font-weight: 600; opacity: 0.8;">/100</div>
                        </div>
                    </div>

                    <h5 class="text-white fw-bold mb-2">Customer Score</h5>
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $score >= ($i * 20) ? 'text-warning' : 'text-white-50' }}"></i>
                        @endfor
                    </div>

                    {{-- Risk Level Badge --}}
                    @php
                        $riskColors = [
                            'LOW' => 'success',
                            'MEDIUM' => 'warning',
                            'HIGH' => 'danger',
                            'BLACKLIST' => 'dark',
                            'UNKNOWN' => 'secondary'
                        ];
                        $riskColor = $riskColors[$customer->risk_level] ?? 'secondary';
                    @endphp
                    <span class="badge bg-{{ $riskColor }} bg-opacity-10 text-{{ $riskColor }} border border-{{ $riskColor }} border-opacity-30 px-3 py-2 fs-6">
                        <i class="fas fa-shield-alt me-2"></i>
                        Risk: {{ $customer->risk_level }}
                    </span>

                    @if($customer->cooldown_until && $customer->cooldown_until->isFuture())
                        <div class="mt-3 alert alert-warning bg-opacity-10 border-warning small">
                            <i class="fas fa-clock me-1"></i>
                            Cooldown until {{ $customer->cooldown_until->format('M d, Y') }}
                        </div>
                    @endif
                </div>

                {{-- Contact Information --}}
                <div class="profile-card mb-4">
                    <h6 class="text-white fw-bold mb-3">
                        <i class="fas fa-address-card me-2 text-info"></i> Contact Information
                    </h6>

                    <div class="info-row">
                        <div class="small text-white-50 mb-1">Primary Phone</div>
                        <div class="text-white fw-bold">
                            <i class="fas fa-phone me-2 text-info"></i>
                            {{ $customer->phone_primary }}
                        </div>
                    </div>

                    @if($customer->phone_secondary)
                        <div class="info-row">
                            <div class="small text-white-50 mb-1">Secondary Phone</div>
                            <div class="text-white">
                                <i class="fas fa-phone me-2 text-info"></i>
                                {{ $customer->phone_secondary }}
                            </div>
                        </div>
                    @endif

                    <div class="info-row">
                        <div class="small text-white-50 mb-1">Address</div>
                        <div class="text-white">
                            <i class="fas fa-map-marker-alt me-2 text-info"></i>
                            {{ $customer->primary_address ?: 'Not set' }}
                        </div>
                    </div>

                    @if($customer->city)
                        <div class="info-row">
                            <div class="small text-white-50 mb-1">City</div>
                            <div class="text-white">{{ $customer->city }}, {{ $customer->province }}</div>
                        </div>
                    @endif
                </div>

                {{-- Key Dates --}}
                <div class="profile-card">
                    <h6 class="text-white fw-bold mb-3">
                        <i class="fas fa-calendar me-2 text-info"></i> Key Dates
                    </h6>

                    <div class="info-row">
                        <div class="small text-white-50 mb-1">First Seen</div>
                        <div class="text-white">
                            {{ $customer->first_seen_at ? $customer->first_seen_at->format('M d, Y') : 'N/A' }}
                            @if($customer->first_seen_at)
                                <span class="text-white-50 small">({{ $metrics['days_since_first_order'] }} days ago)</span>
                            @endif
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="small text-white-50 mb-1">Last Order</div>
                        <div class="text-white">
                            {{ $customer->last_order_at ? $customer->last_order_at->format('M d, Y') : 'Never' }}
                            @if($metrics['days_since_last_order'])
                                <span class="text-white-50 small">({{ $metrics['days_since_last_order'] }} days ago)</span>
                            @endif
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="small text-white-50 mb-1">Last Contact</div>
                        <div class="text-white">
                            {{ $customer->last_contact_at ? $customer->last_contact_at->diffForHumans() : 'Never' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Metrics & History --}}
            <div class="col-md-8">
                {{-- Performance Metrics --}}
                <div class="profile-card mb-4">
                    <h6 class="text-white fw-bold mb-3">
                        <i class="fas fa-chart-line me-2 text-info"></i> Performance Metrics
                    </h6>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-value">{{ $customer->total_orders }}</div>
                                <div class="metric-label">Total Orders</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-value text-success">{{ $customer->total_delivered }}</div>
                                <div class="metric-label">Delivered</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-value text-danger">{{ $customer->total_returned }}</div>
                                <div class="metric-label">Returned</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-value text-warning">{{ round($customer->delivery_success_rate) }}%</div>
                                <div class="metric-label">Success Rate</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-value text-info">₱{{ number_format($customer->total_delivered_value, 2) }}</div>
                                <div class="metric-label">Lifetime Value</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-value">₱{{ number_format($metrics['avg_order_value'], 2) }}</div>
                                <div class="metric-label">Avg Order Value</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-value">{{ $customer->recency_score ?? 0 }}/10</div>
                                <div class="metric-label">Recency Score</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Order History Timeline --}}
                <div class="profile-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-white fw-bold mb-0">
                            <i class="fas fa-history me-2 text-info"></i> Order History Timeline
                        </h6>
                        <span class="badge bg-info bg-opacity-10 text-info">
                            {{ $customer->orderHistory->count() }} orders
                        </span>
                    </div>

                    <div class="custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                        @forelse($customer->orderHistory as $order)
                            <div class="timeline-item">
                                @php
                                    $dotClass = match(strtolower($order->current_status)) {
                                        'delivered' => 'delivered',
                                        'returned' => 'returned',
                                        default => 'pending'
                                    };
                                @endphp
                                <div class="timeline-dot timeline-dot-{{ $dotClass }}"></div>

                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="text-white fw-bold">{{ $order->created_at->format('M d, Y') }}</div>
                                        <div class="text-white-50 small">{{ $order->created_at->format('h:i A') }}</div>
                                    </div>
                                    @php
                                        $statusColors = [
                                            'DELIVERED' => 'success',
                                            'RETURNED' => 'danger',
                                            'PENDING' => 'warning',
                                            'IN_TRANSIT' => 'info'
                                        ];
                                        $statusColor = $statusColors[$order->current_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-20">
                                        {{ $order->current_status }}
                                    </span>
                                </div>

                                <div class="text-white mb-1">
                                    <i class="fas fa-box me-2 text-info"></i>
                                    {{ $order->waybill_number }}
                                </div>

                                @if($order->order_value)
                                    <div class="text-white-50 small">
                                        Amount: ₱{{ number_format($order->order_value, 2) }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-white-50 opacity-25 mb-3"></i>
                                <p class="text-white-50">No order history found</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Full Waybill History (Simplified) --}}
                @if(isset($waybills) && $waybills->count() > 0)
                    <div class="profile-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-white fw-bold mb-0">
                                <i class="fas fa-box me-2 text-info"></i> Order History
                            </h6>
                            <span class="badge bg-info bg-opacity-10 text-info px-3 py-2">
                                {{ $waybills->count() }} Orders
                            </span>
                        </div>

                        {{-- Simple Product Summary --}}
                        @if(isset($productCounts) && $productCounts->count() > 0)
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach($productCounts->sortDesc() as $product => $count)
                                    <span class="badge bg-dark border border-secondary px-3 py-2">
                                        {{ $product ?? 'Unknown' }}
                                        @if($count > 1)
                                            <span class="text-success fw-bold ms-1">+{{ $count }}</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Simple Order List --}}
                        <div class="custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                            @foreach($waybills as $waybill)
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-25">
                                    <div class="d-flex align-items-center gap-3">
                                        {{-- Status Icon --}}
                                        @php
                                            $statusIcon = match(strtolower($waybill->status)) {
                                                'delivered' => ['fa-check-circle', 'text-success'],
                                                'returned', 'for return' => ['fa-times-circle', 'text-danger'],
                                                'in transit' => ['fa-truck', 'text-info'],
                                                'delivering' => ['fa-motorcycle', 'text-primary'],
                                                default => ['fa-clock', 'text-warning'],
                                            };
                                        @endphp
                                        <i class="fas {{ $statusIcon[0] }} {{ $statusIcon[1] }}" style="font-size: 18px;"></i>
                                        
                                        <div>
                                            <div class="text-white">
                                                {{ $waybill->item_name ?? 'Unknown Product' }}
                                                @if(($waybill->order_count_for_product ?? 1) > 1)
                                                    <span class="text-success small">({{ $waybill->order_count_for_product }}×)</span>
                                                @endif
                                            </div>
                                            <div class="text-white-50 small">
                                                {{ $waybill->waybill_number }} • {{ $waybill->created_at->format('M d, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        @if($waybill->cod_amount > 0)
                                            <div class="text-white fw-bold">₱{{ number_format($waybill->cod_amount, 2) }}</div>
                                        @endif
                                        <span class="badge bg-{{ $statusIcon[1] == 'text-success' ? 'success' : ($statusIcon[1] == 'text-danger' ? 'danger' : 'secondary') }} bg-opacity-20 {{ $statusIcon[1] }}" style="font-size: 10px;">
                                            {{ strtoupper($waybill->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Recycling History --}}
                @if($customer->recyclingPool->count() > 0)
                    <div class="profile-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-white fw-bold mb-0">
                                <i class="fas fa-recycle me-2 text-info"></i> Recycling History
                            </h6>
                            <span class="badge bg-warning bg-opacity-10 text-warning">
                                {{ $customer->recyclingPool->count() }} entries
                            </span>
                        </div>

                        <div class="custom-scrollbar" style="max-height: 300px; overflow-y: auto;">
                            @foreach($customer->recyclingPool as $poolEntry)
                                <div class="bg-dark bg-opacity-50 rounded-3 p-3 mb-2 border border-white border-opacity-5">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="text-white fw-bold">{{ $poolEntry->created_at->format('M d, Y') }}</div>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            {{ $poolEntry->pool_status }}
                                        </span>
                                    </div>

                                    <div class="small text-white-50 mb-1">
                                        <i class="fas fa-tag me-1"></i> Reason: {{ $poolEntry->reason_label }}
                                    </div>

                                    <div class="small text-white-50 mb-1">
                                        <i class="fas fa-signal me-1"></i> Priority: {{ $poolEntry->priority_score }}
                                    </div>

                                    @if($poolEntry->assignedAgent)
                                        <div class="small text-white-50">
                                            <i class="fas fa-user me-1"></i> Assigned to: {{ $poolEntry->assignedAgent->name }}
                                        </div>
                                    @endif

                                    @if($poolEntry->processed_outcome)
                                        <div class="small text-success mt-1">
                                            <i class="fas fa-check me-1"></i> Outcome: {{ $poolEntry->processed_outcome }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Create Lead Modal --}}
<div class="modal fade" id="createLeadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <form action="{{ route('customers.createLead', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Create Lead for {{ $customer->name_display }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Assign to Agent <span class="text-danger">*</span></label>
                        <select name="agent_id" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">-- Select Agent --</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="product_name" class="form-control bg-dark text-white border-secondary" placeholder="Optional">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control bg-dark text-white border-secondary" rows="3" placeholder="Add any relevant notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Create Lead</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Blacklist Modal --}}
<div class="modal fade" id="blacklistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <form action="{{ route('customers.blacklist', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Blacklist Customer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger bg-opacity-10 border-danger">
                        <i class="fas fa-info-circle me-2"></i>
                        This will permanently blacklist the customer from receiving any calls or leads.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control bg-dark text-white border-secondary" rows="3" required placeholder="Explain why this customer is being blacklisted..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban me-2"></i> Blacklist Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.createElement('div');
        toast.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = '<i class="fas fa-check-circle me-2"></i>{{ session("success") }}';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    });
</script>
@endif

@endsection
