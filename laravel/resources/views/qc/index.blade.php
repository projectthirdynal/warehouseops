@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 text-white">Quality Control (QA)</h2>
            <p class="text-secondary mb-0">Verify sales compliance and ensure data quality.</p>
        </div>
        <div class="d-flex gap-3">
             <div class="card bg-dark border-secondary px-4 py-2 text-center">
                <span class="text-secondary small text-uppercase">Pending</span>
                <h3 class="text-warning mb-0">{{ $stats['pending'] }}</h3>
            </div>
            <div class="card bg-dark border-secondary px-4 py-2 text-center">
                <span class="text-secondary small text-uppercase">Passed Today</span>
                <h3 class="text-success mb-0">{{ $stats['passed_today'] }}</h3>
            </div>
             <div class="card bg-dark border-secondary px-4 py-2 text-center">
                <span class="text-secondary small text-uppercase">Failed Today</span>
                <h3 class="text-danger mb-0">{{ $stats['failed_today'] }}</h3>
            </div>
        </div>
    </div>

    <!-- Queue Table -->
    <div class="card border-secondary bg-darker">
        <div class="card-header border-secondary bg-transparent d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0">Sales Verification Queue</h5>
            <span class="badge bg-warning text-dark">{{ $leads->total() }} Pending</span>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-secondary text-uppercase small">
                        <th>Date</th>
                        <th>Agent</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Recording</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                    <tr>
                        <td class="text-white">
                            {{ $lead->updated_at->format('M d, H:i') }}
                            <div class="small text-secondary">{{ $lead->updated_at->diffForHumans() }}</div>
                        </td>
                         <td>
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-sm bg-primary rounded-circle me-2">{{ substr($lead->user->name ?? '?', 0, 1) }}</span>
                                <div>
                                    <div class="text-white">{{ $lead->user->name ?? 'Unassigned' }}</div>
                                    <div class="small text-secondary">#{{ $lead->user->id ?? '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-white fw-bold">{{ $lead->name }}</div>
                            <div class="small text-info"><i class="fas fa-phone me-1"></i> {{ $lead->phone }}</div>
                        </td>
                        <td>
                            <div class="badge bg-secondary text-white">{{ $lead->product_name }}</div>
                            <div class="small text-secondary mt-1">Qty: {{ $lead->product_qty }} • {{ number_format($lead->product_price) }}</div>
                        </td>
                        <td>
                            @if(false) 
                                <!-- Placeholder for recording player -->
                                <button class="btn btn-sm btn-outline-info"><i class="fas fa-play me-1"></i> Play</button>
                            @else
                                <span class="text-secondary small"><i class="fas fa-microphone-slash me-1"></i> No Rec</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button class="btn btn-success btn-sm me-1" onclick="approveSale({{ $lead->id }})">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-outline-danger btn-sm" onclick="openRejectModal({{ $lead->id }}, 'reject')">
                                    <i class="fas fa-times me-1"></i> Reject
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="openRejectModal({{ $lead->id }}, 'recycle')">
                                    <i class="fas fa-recycle me-1"></i> Recycle
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-secondary mb-3"><i class="fas fa-check-circle fa-3x"></i></div>
                            <h5 class="text-white">All Caught Up!</h5>
                            <p class="text-secondary">No pending sales to verify.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leads->hasPages())
        <div class="card-footer border-secondary bg-transparent">
            {{ $leads->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalTitle">Reject Sale</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="actionForm">
                    <input type="hidden" id="actionLeadId">
                    <input type="hidden" id="actionType">
                    <div class="mb-3">
                        <label class="form-label text-secondary">Reason / Notes</label>
                        <textarea class="form-control bg-darker border-secondary text-white" id="actionNotes" rows="3" placeholder="Enter reason for rejection/recycling..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="modalSubmitBtn" onclick="submitAction()">Confirm Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple AJAX Actions
    function approveSale(id) {
        if(!confirm('Confirm clean sale?')) return;
        
        fetch(`/qc/${id}/approve`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    }

    let actionModal;
    function openRejectModal(id, type) {
        document.getElementById('actionLeadId').value = id;
        document.getElementById('actionType').value = type;
        document.getElementById('actionNotes').value = '';
        
        const btn = document.getElementById('modalSubmitBtn');
        const title = document.getElementById('modalTitle');
        
        if(type === 'reject') {
            title.innerText = 'Reject & Cancel Sale';
            btn.className = 'btn btn-danger';
            btn.innerText = 'Confirm Reject';
        } else {
            title.innerText = 'Recycle Lead (Back to Pool)';
            btn.className = 'btn btn-info text-white';
            btn.innerText = 'Confirm Recycle';
        }

        if(!actionModal) actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
        actionModal.show();
    }

    function submitAction() {
        const id = document.getElementById('actionLeadId').value;
        const type = document.getElementById('actionType').value;
        const notes = document.getElementById('actionNotes').value;
        
        if(!notes) return alert('Please enter a reason.');

        fetch(`/qc/${id}/${consttype}`, { // type is reject or recycle
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ qc_notes: notes })
        }).then(res => res.json()).then(data => {
            if(data.success) location.reload();
        });
    }
</script>

<style>
    .bg-darker { background-color: #0f172a; }
</style>
@endsection
