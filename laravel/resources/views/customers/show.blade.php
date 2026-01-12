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
                {{-- Action buttons removed to prevent data alteration --}}
            </div>
        </div>

        {{-- Profile Information - Horizontal Modular Grid --}}
        <div class="profile-card mb-4">
            <div class="d-flex flex-wrap gap-4 justify-content-center">
                {{-- Customer Score Module --}}
                <div class="d-flex align-items-center gap-3 p-2" style="min-width: 280px;">
                    @php
                        $score = $customer->customer_score;
                        $scoreClass = $score >= 70 ? 'high' : ($score >= 50 ? 'medium' : 'low');
                    @endphp
                    {{-- Score Circle (Left) --}}
                    <div class="score-circle score-{{ $scoreClass }} flex-shrink-0" style="width: 120px; height: 120px; font-size: 2.2rem;">
                        <div>{{ $score }}</div>
                        <div style="font-size: 0.75rem; font-weight: 600; opacity: 0.8;">/100</div>
                    </div>

                    {{-- Score Details (Right) --}}
                    <div class="d-flex flex-column align-items-start">
                        <h6 class="text-white fw-bold mb-1">Customer Score</h6>
                        <div class="d-flex gap-1 mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $score >= ($i * 20) ? 'text-warning' : 'text-white-50' }}" style="font-size: 12px;"></i>
                            @endfor
                        </div>
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
                        <span class="badge bg-{{ $riskColor }} bg-opacity-10 text-{{ $riskColor }} border border-{{ $riskColor }} border-opacity-30 px-2 py-1" style="font-size: 11px;">
                            <i class="fas fa-shield-alt me-1"></i>{{ $customer->risk_level }}
                        </span>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="border-start border-secondary" style="opacity: 0.3;"></div>

                {{-- Contact Information Module --}}
                <div class="flex-fill" style="min-width: 250px;">
                    <h6 class="text-white fw-bold mb-3">
                        <i class="fas fa-address-card me-2 text-info"></i> Contact Information
                    </h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="small text-white-50">Primary Phone</div>
                            <div class="text-white"><i class="fas fa-phone me-1 text-info" style="font-size: 11px;"></i>{{ $customer->phone_primary }}</div>
                        </div>
                        @if($customer->phone_secondary)
                        <div class="col-6">
                            <div class="small text-white-50">Secondary Phone</div>
                            <div class="text-white"><i class="fas fa-phone me-1 text-info" style="font-size: 11px;"></i>{{ $customer->phone_secondary }}</div>
                        </div>
                        @endif
                        <div class="col-12">
                            <div class="small text-white-50">Address</div>
                            <div class="text-white"><i class="fas fa-map-marker-alt me-1 text-info" style="font-size: 11px;"></i>{{ $customer->primary_address ?: 'Not set' }}</div>
                        </div>
                        @if($customer->city)
                        <div class="col-12">
                            <div class="small text-white-50">City</div>
                            <div class="text-white">{{ $customer->city }}, {{ $customer->province }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Divider --}}
                <div class="border-start border-secondary" style="opacity: 0.3;"></div>

                {{-- Key Dates Module --}}
                <div class="flex-fill" style="min-width: 220px;">
                    <h6 class="text-white fw-bold mb-3">
                        <i class="fas fa-calendar me-2 text-info"></i> Key Dates
                    </h6>
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="small text-white-50">First Seen</div>
                            <div class="text-white">
                                {{ $customer->first_seen_at ? $customer->first_seen_at->format('M d, Y') : 'N/A' }}
                                @if($customer->first_seen_at)
                                    <span class="text-white-50 small">({{ $metrics['days_since_first_order'] }}d)</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="small text-white-50">Last Order</div>
                            <div class="text-white">
                                {{ $customer->last_order_at ? $customer->last_order_at->format('M d, Y') : 'Never' }}
                                @if($metrics['days_since_last_order'])
                                    <span class="text-white-50 small">({{ $metrics['days_since_last_order'] }}d)</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="small text-white-50">Last Contact</div>
                            <div class="text-white">{{ $customer->last_contact_at ? $customer->last_contact_at->diffForHumans() : 'Never' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Metrics & History --}}
        <div class="row g-4">
            <div class="col-12">
                {{-- Performance Metrics --}}
                <div class="profile-card mb-4">
                    <h6 class="text-white fw-bold mb-3">
                        <i class="fas fa-chart-line me-2 text-info"></i> Performance Metrics
                    </h6>

                    {{-- All Metrics in Single Row --}}
                    <div class="d-flex gap-3">
                        <div class="metric-card text-center" style="flex: 1 1 0;">
                            <div class="metric-value">{{ $customer->total_orders }}</div>
                            <div class="metric-label">Total Orders</div>
                        </div>
                        <div class="metric-card text-center" style="flex: 1 1 0;">
                            <div class="metric-value text-success">{{ $customer->total_delivered }}</div>
                            <div class="metric-label">Delivered</div>
                        </div>
                        <div class="metric-card text-center" style="flex: 1 1 0;">
                            <div class="metric-value text-danger">{{ $customer->total_returned }}</div>
                            <div class="metric-label">Returned</div>
                        </div>
                        <div class="metric-card text-center" style="flex: 1 1 0;">
                            <div class="metric-value text-warning">{{ round($customer->delivery_success_rate) }}%</div>
                            <div class="metric-label">Success Rate</div>
                        </div>
                        <div class="metric-card text-center" style="flex: 1 1 0;">
                            <div class="metric-value text-info">₱{{ number_format($customer->total_delivered_value, 2) }}</div>
                            <div class="metric-label">Lifetime Value</div>
                        </div>
                        <div class="metric-card text-center" style="flex: 1 1 0;">
                            <div class="metric-value">₱{{ number_format($metrics['avg_order_value'], 2) }}</div>
                            <div class="metric-label">Avg Order Value</div>
                        </div>
                        <div class="metric-card text-center" style="flex: 1 1 0;">
                            <div class="metric-value">{{ $customer->recency_score ?? 0 }}/10</div>
                            <div class="metric-label">Recency Score</div>
                        </div>
                    </div>
                </div>

                {{-- Order History Row Grid - Cube Style --}}
                <div class="d-flex flex-row gap-4 mb-4 w-100" style="flex-wrap: nowrap;">
                    {{-- Order History Timeline (Left) --}}
                    <div style="flex: 0 0 320px; min-width: 0;">
                        <div class="profile-card" style="height: 280px; overflow: hidden;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-white fw-bold mb-0">
                                    <i class="fas fa-history me-2 text-info"></i> Order History Timeline
                                </h6>
                                <span class="badge bg-info bg-opacity-10 text-info">
                                    {{ $customer->orderHistory->count() }} orders
                                </span>
                            </div>

                            <div class="custom-scrollbar" style="max-height: 220px; overflow-y: auto;">
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
                    </div>

                    {{-- Order History (Right) --}}
                    <div style="flex: 1; min-width: 0;">
                        @if(isset($waybills) && $waybills->count() > 0)
                            <div class="profile-card" style="height: 280px; overflow: hidden;">
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
                                <div class="custom-scrollbar" style="max-height: 180px; overflow-y: auto;">
                                    @foreach($waybills as $waybill)
                                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom border-secondary border-opacity-25">
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
                                                    <div class="text-white fw-bold mb-1">₱{{ number_format($waybill->cod_amount, 2) }}</div>
                                                @endif
                                                <span class="badge bg-{{ $statusIcon[1] == 'text-success' ? 'success' : ($statusIcon[1] == 'text-danger' ? 'danger' : 'secondary') }} bg-opacity-20 {{ $statusIcon[1] }}" style="font-size: 10px;">
                                                    {{ strtoupper($waybill->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="profile-card" style="height: 200px; overflow: hidden;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-white fw-bold mb-0">
                                        <i class="fas fa-box me-2 text-info"></i> Order History
                                    </h6>
                                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2">
                                        0 Orders
                                    </span>
                                </div>
                                <div class="text-center py-5">
                                    <i class="fas fa-box-open fa-3x text-white-50 opacity-25 mb-3"></i>
                                    <p class="text-white-50">No orders found</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

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

{{-- Modals removed to prevent data alteration --}}

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
