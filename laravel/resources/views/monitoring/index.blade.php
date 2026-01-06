@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 text-white">Live Monitoring <span class="badge bg-danger pulse">LIVE</span></h2>
            <p class="text-secondary mb-0">Real-time agent activity and call metrics.</p>
        </div>
        <div class="d-flex gap-3" id="metrics-container">
            <div class="card bg-dark border-secondary px-4 py-2 text-center" style="min-width: 120px;">
                <span class="text-secondary small text-uppercase">Online Agents</span>
                <h3 class="text-white mb-0" id="stat-online-agents">-</h3>
            </div>
            <div class="card bg-dark border-secondary px-4 py-2 text-center" style="min-width: 120px;">
                <span class="text-secondary small text-uppercase">Active Calls</span>
                <h3 class="text-warning mb-0" id="stat-active-calls">-</h3>
            </div>
            <div class="card bg-dark border-secondary px-4 py-2 text-center" style="min-width: 120px;">
                <span class="text-secondary small text-uppercase">Total Calls</span>
                <h3 class="text-info mb-0" id="stat-total-calls">-</h3>
            </div>
            <div class="card bg-dark border-secondary px-4 py-2 text-center" style="min-width: 120px;">
                <span class="text-secondary small text-uppercase">Sales Today</span>
                <h3 class="text-success mb-0" id="stat-sales-today">-</h3>
            </div>
            <div class="card bg-dark border-secondary px-4 py-2 text-center" style="min-width: 120px;">
                <span class="text-secondary small text-uppercase">Rejected</span>
                <h3 class="text-danger mb-0" id="stat-rejected-today">-</h3>
            </div>
        </div>
    </div>

    <!-- Agent Grid -->
    <div class="row g-3 mb-4" id="agent-grid">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-secondary mt-2">Loading live data...</p>
        </div>
    </div>

    <!-- Sales Pending QC -->
    <div class="card bg-dark border-secondary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="fas fa-clipboard-check me-2"></i>Sales Pending QC</h5>
            <span class="badge bg-warning text-dark" id="pending-count">0</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr class="text-secondary small text-uppercase">
                        <th>Agent</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="sales-queue">
                    <tr><td colspan="6" class="text-center text-secondary py-4">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-danger">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="fas fa-times-circle text-danger me-2"></i>Reject Sale</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary">Rejecting sale for: <strong id="reject-lead-name"></strong></p>
                <div class="mb-3">
                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control bg-secondary text-white border-0" id="reject-notes" rows="3" placeholder="Why is this a false sale?"></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
    .agent-card { transition: all 0.3s ease; }
    .agent-status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .status-online { background-color: #22c55e; box-shadow: 0 0 8px rgba(34, 197, 94, 0.5); }
    .status-offline { background-color: #64748b; }
    .status-busy { background-color: #ef4444; box-shadow: 0 0 8px rgba(239, 68, 68, 0.5); }
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

        // Fetch Sales Queue
        fetch('{{ route("monitoring.salesQueue") }}')
            .then(res => res.json())
            .then(data => renderSalesQueue(data.leads))
            .catch(err => console.error('Sales Queue Error:', err));
    }

    function renderMetrics(metrics) {
        document.getElementById('stat-online-agents').innerText = metrics.online_agents;
        document.getElementById('stat-active-calls').innerText = metrics.active_calls;
        document.getElementById('stat-total-calls').innerText = metrics.total_calls;
        document.getElementById('stat-sales-today').innerText = metrics.sales_today;
        document.getElementById('stat-rejected-today').innerText = metrics.rejected_today || 0;
    }

    function renderAgents(agents) {
        const grid = document.getElementById('agent-grid');
        grid.innerHTML = '';

        agents.forEach(agent => {
            let statusColor = 'status-offline';
            let statusText = 'Offline';
            let cardBorder = 'border-secondary';
            let opacity = '0.7';

            if (agent.status === 'online') {
                statusColor = 'status-online';
                statusText = 'Online';
                cardBorder = 'border-success';
                opacity = '1';
            } else if (agent.status === 'busy') {
                statusColor = 'status-busy';
                statusText = 'In Call';
                cardBorder = 'border-danger';
                opacity = '1';
            }

            const html = `
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <div class="card bg-dark ${cardBorder} agent-card h-100" style="opacity: ${opacity}">
                        <div class="card-body p-3 text-center">
                            <div class="position-relative d-inline-block mb-2">
                                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 48px; height: 48px; font-size: 20px;">
                                    ${agent.avatar}
                                </div>
                                <div class="agent-status-dot ${statusColor}" style="position: absolute; bottom: 0; right: 0; border: 2px solid #1e293b;"></div>
                            </div>
                            <h6 class="text-white mb-0 text-truncate">${agent.name}</h6>
                            <div class="small text-secondary mb-2">${agent.sip_account}</div>
                            
                            <div class="badge bg-dark border border-secondary text-secondary rounded-pill px-3">
                                ${statusText}
                            </div>

                            ${agent.current_lead ? `
                                <div class="mt-2 pt-2 border-top border-secondary">
                                    <div class="small text-warning text-truncate"><i class="fas fa-phone me-1"></i> Calling...</div>
                                    <div class="small text-white text-truncate" title="${agent.current_lead}">${agent.current_lead}</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            grid.innerHTML += html;
        });
    }

    function renderSalesQueue(leads) {
        const tbody = document.getElementById('sales-queue');
        document.getElementById('pending-count').innerText = leads.length;

        if (leads.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-secondary py-4"><i class="fas fa-check-circle me-2"></i>All sales have been reviewed!</td></tr>';
            return;
        }

        tbody.innerHTML = leads.map(lead => `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 12px;">
                            ${lead.agent_avatar}
                        </div>
                        <span class="text-white">${lead.agent}</span>
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
                <td class="text-success">₱${lead.amount || 0}</td>
                <td class="text-secondary small">${lead.updated_at}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-success me-1" onclick="approveQc(${lead.id})">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="openRejectModal(${lead.id}, '${lead.customer}')">
                        <i class="fas fa-times"></i> Reject
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
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
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
                bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
                updateDashboard();
            }
        });
    });

    // Poll every 5 seconds
    setInterval(updateDashboard, 5000);
    updateDashboard(); // Initial load
</script>
@endsection
