@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header with Metrics -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 text-white">QC Dashboard <span class="badge bg-danger pulse">LIVE</span></h2>
            <p class="text-secondary mb-0">Review and verify agent sales.</p>
        </div>
        <div class="d-flex gap-2" id="metrics-container">
            <div class="metric-card">
                <span class="metric-label">Online</span>
                <span class="metric-value text-success" id="stat-online-agents">-</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Calls</span>
                <span class="metric-value text-warning" id="stat-active-calls">-</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Sales</span>
                <span class="metric-value text-info" id="stat-sales-today">-</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Rejected</span>
                <span class="metric-value text-danger" id="stat-rejected-today">-</span>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="row">
        <!-- Left: Compact Agent Status -->
        <div class="col-lg-3 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header border-secondary">
                    <h6 class="mb-0 text-white"><i class="fas fa-users me-2"></i>Agent Status</h6>
                </div>
                <div class="card-body p-2" id="agent-list" style="max-height: 500px; overflow-y: auto;">
                    <div class="text-center py-3 text-secondary">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Right: Sales Pending QC -->
        <div class="col-lg-9">
            <div class="card bg-dark border-secondary">
                <div class="card-header d-flex justify-content-between align-items-center border-secondary">
                    <h5 class="mb-0 text-white"><i class="fas fa-clipboard-check me-2"></i>Sales Pending QC</h5>
                    <span class="badge bg-warning text-dark px-3 py-2" id="pending-count">0 pending</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead class="table-secondary">
                                <tr class="text-uppercase small">
                                    <th style="width: 180px;">Agent</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th class="text-end" style="width: 100px;">Amount</th>
                                    <th style="width: 120px;">Updated</th>
                                    <th class="text-center" style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sales-queue">
                                <tr><td colspan="6" class="text-center text-secondary py-5">Loading sales...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-danger">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="fas fa-times-circle text-danger me-2"></i>Reject Sale</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeRejectModal()"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary mb-3">Rejecting sale for: <strong class="text-white" id="reject-lead-name"></strong></p>
                <div class="mb-3">
                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control bg-secondary text-white border-0" id="reject-notes" rows="3" placeholder="Why is this a false sale?"></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-reject-btn"><i class="fas fa-ban me-1"></i>Confirm Reject</button>
            </div>
        </div>
    </div>
</div>

<style>
    .pulse { animation: pulse-animation 2s infinite; }
    @keyframes pulse-animation {
        0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
    }
    
    .metric-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 8px 16px;
        text-align: center;
        min-width: 80px;
    }
    .metric-label { display: block; font-size: 10px; text-transform: uppercase; color: #94a3b8; }
    .metric-value { display: block; font-size: 20px; font-weight: 700; }
    
    .agent-item {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        border-radius: 8px;
        margin-bottom: 6px;
        background: rgba(0,0,0,0.2);
        transition: all 0.2s;
    }
    .agent-item:hover { background: rgba(255,255,255,0.05); }
    .agent-item.online { border-left: 3px solid #22c55e; }
    .agent-item.busy { border-left: 3px solid #ef4444; }
    .agent-item.offline { border-left: 3px solid #64748b; opacity: 0.6; }
    
    .agent-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
        margin-right: 10px;
    }
    .agent-info { flex: 1; }
    .agent-name { color: #f1f5f9; font-size: 13px; font-weight: 500; }
    .agent-sip { color: #64748b; font-size: 11px; }
    .agent-badge { font-size: 10px; padding: 2px 8px; border-radius: 10px; }
    
    .table-secondary { background: rgba(51, 65, 85, 0.5) !important; }
    .table-secondary th { color: #94a3b8 !important; font-weight: 600; letter-spacing: 0.5px; border: none !important; }
</style>

<script>
    let currentRejectLeadId = null;

    function updateDashboard() {
        fetch('{{ route("monitoring.stats") }}')
            .then(res => res.json())
            .then(data => {
                renderMetrics(data.metrics);
                renderAgents(data.agents);
            })
            .catch(err => console.error('Monitoring Error:', err));

        fetch('{{ route("monitoring.salesQueue") }}')
            .then(res => res.json())
            .then(data => renderSalesQueue(data.leads))
            .catch(err => console.error('Sales Queue Error:', err));
    }

    function renderMetrics(metrics) {
        document.getElementById('stat-online-agents').innerText = metrics.online_agents;
        document.getElementById('stat-active-calls').innerText = metrics.active_calls;
        document.getElementById('stat-sales-today').innerText = metrics.sales_today;
        document.getElementById('stat-rejected-today').innerText = metrics.rejected_today || 0;
    }

    function renderAgents(agents) {
        const list = document.getElementById('agent-list');
        
        if (agents.length === 0) {
            list.innerHTML = '<div class="text-center py-3 text-secondary">No agents</div>';
            return;
        }

        list.innerHTML = agents.map(agent => {
            let statusClass = 'offline';
            let statusBadge = '<span class="agent-badge bg-secondary">Offline</span>';
            
            if (agent.status === 'online') {
                statusClass = 'online';
                statusBadge = '<span class="agent-badge bg-success">Online</span>';
            } else if (agent.status === 'busy') {
                statusClass = 'busy';
                statusBadge = '<span class="agent-badge bg-danger">In Call</span>';
            }

            return `
                <div class="agent-item ${statusClass}">
                    <div class="agent-avatar">${agent.avatar}</div>
                    <div class="agent-info">
                        <div class="agent-name">${agent.name}</div>
                        <div class="agent-sip">${agent.sip_account}</div>
                    </div>
                    ${statusBadge}
                </div>
            `;
        }).join('');
    }

    function renderSalesQueue(leads) {
        const tbody = document.getElementById('sales-queue');
        document.getElementById('pending-count').innerText = leads.length + ' pending';

        if (leads.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                        <div class="text-secondary">All sales have been reviewed!</div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = leads.map(lead => `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="agent-avatar" style="width: 32px; height: 32px; font-size: 12px;">${lead.agent_avatar}</div>
                        <div>
                            <div class="text-white small fw-medium">${lead.agent}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-white">${lead.customer}</div>
                    <div class="small text-info">${lead.phone}</div>
                </td>
                <td>
                    <div class="text-white">${lead.product || 'N/A'}</div>
                    <div class="small text-secondary">${lead.brand || ''}</div>
                </td>
                <td class="text-end text-success fw-bold">₱${parseFloat(lead.amount || 0).toLocaleString()}</td>
                <td class="text-secondary small">${lead.updated_at}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-success px-3 me-1" onclick="approveQc(${lead.id})">
                        <i class="fas fa-check me-1"></i>Approve
                    </button>
                    <button class="btn btn-sm btn-outline-danger px-3" onclick="openRejectModal(${lead.id}, '${lead.customer.replace(/'/g, "\\'")}')">
                        <i class="fas fa-times me-1"></i>Reject
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function approveQc(leadId) {
        fetch(`/monitoring/${leadId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateDashboard();
            }
        });
    }

    function openRejectModal(leadId, name) {
        currentRejectLeadId = leadId;
        document.getElementById('reject-lead-name').innerText = name;
        document.getElementById('reject-notes').value = '';
        // Show modal using classList
        document.getElementById('rejectModal').classList.add('show');
        document.getElementById('rejectModal').style.display = 'block';
        document.body.classList.add('modal-open');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('show');
        document.getElementById('rejectModal').style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    document.getElementById('confirm-reject-btn').addEventListener('click', function() {
        const notes = document.getElementById('reject-notes').value.trim();
        if (!notes) {
            alert('Please enter a rejection reason.');
            return;
        }

        fetch(`/monitoring/${currentRejectLeadId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ notes: notes })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeRejectModal();
                updateDashboard();
            }
        });
    });

    // Poll every 5 seconds
    setInterval(updateDashboard, 5000);
    updateDashboard();
</script>
@endsection
