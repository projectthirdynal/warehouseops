@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 bg-gray-900 min-vh-100">
    
    <!-- Agents Section -->
    <div class="card bg-gray-800 border-0 shadow-lg mb-4 rounded-3">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3 pb-0">
            <h5 class="text-white mb-0 fw-bold"><i class="fas fa-users me-2"></i>Agents</h5>
            <span class="text-secondary small fw-bold"><span id="active-agent-count" class="text-white">0</span> / <span id="total-agent-count">0</span></span>
        </div>
        <div class="card-body pt-3">
            <div id="agents-grid" class="d-flex flex-wrap gap-2">
                <div class="text-center w-100 text-secondary py-3">Loading agents...</div>
            </div>
        </div>
    </div>

    <!-- Sales Pending QC Section -->
    <div class="card bg-gray-800 border-0 shadow-lg mb-4 rounded-3">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
            <h5 class="text-white mb-0 fw-bold"><i class="fas fa-clipboard-check me-2"></i>Sales Pending QC</h5>
            <span class="text-secondary small fw-bold text-uppercase" id="pending-count">0 PENDING</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle custom-table">
                    <thead>
                        <tr class="text-uppercase small text-secondary">
                            <th class="ps-4" style="width: 25%;">Agent</th>
                            <th style="width: 20%;">Customer</th>
                            <th style="width: 30%;">Product</th>
                            <th class="text-end" style="width: 10%;">Amount</th>
                            <th style="width: 15%;" class="ps-4">Updated</th>
                            <th class="text-end pe-4" style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sales-queue" class="border-top-0">
                        <tr><td colspan="6" class="text-center text-secondary py-5">Loading sales...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-gray-900 text-white border border-secondary shadow-2xl">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-times-circle text-danger me-2"></i>Reject Sale</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeRejectModal()"></button>
            </div>
            <div class="modal-body pt-4">
                <p class="text-secondary mb-2">Rejecting sale for: <strong class="text-white" id="reject-lead-name"></strong></p>
                <div class="mb-3">
                    <label class="form-label text-danger small fw-bold">Rejection Reason *</label>
                    <textarea class="form-control bg-gray-800 text-white border-secondary focus-ring-danger p-3" id="reject-notes" rows="6" placeholder="Why is this a false sale?"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                 <!-- Close buttons are handled nicely in header or programmatic, keeping footer clean but functional -->
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeRejectModal()">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirm-reject-btn" onclick="confirmReject()">Confirm Reject</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Dark Theme Colors */
    .bg-gray-900 { background-color: #0f172a !important; } /* Slate 900 */
    .bg-gray-800 { background-color: #1e293b !important; } /* Slate 800 */
    .bg-emerald-500 { background-color: #10b981 !important; } /* Emerald 500 */
    .text-emerald-400 { color: #34d399 !important; }
    
    /* Table Styling */
    .custom-table thead th {
        background-color: #1e293b;
        border-bottom: 1px solid #334155;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }
    .custom-table tbody td {
        background-color: #1e293b; /* Match card bg */
        border-bottom: 1px solid #334155; /* Slate 700 */
        font-size: 0.875rem;
        color: #e2e8f0;
    }
    .custom-table tbody tr:last-child td { border-bottom: none; }
    
    /* Input Focus */
    .focus-ring-danger:focus {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 0.25rem rgba(239, 68, 68, 0.25) !important;
    }

    /* Agent Chips */
    .agent-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 16px 6px 6px;
        border-radius: 9999px; /* Pill shape */
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.2s;
        border: 1px solid transparent;
        cursor:pointer;
    }
    .agent-chip:hover { filter: brightness(1.1); }
    
    /* Active State (Green) */
    .agent-chip.active {
        background-color: #10b981; /* Emerald 500 */
        color: white;
    }
    .agent-chip.active .avatar {
        background-color: rgba(255,255,255,0.2);
        color: white;
    }

    /* Inactive State (Dark Grey) */
    .agent-chip.inactive {
        background-color: #334155; /* Slate 700 */
        color: #cbd5e1; /* Slate 300 */
    }
    .agent-chip.inactive .avatar {
        background-color: #475569; /* Slate 600 */
        color: #94a3b8;
    }

    /* Avatar Circle */
    .agent-chip .avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        margin-right: 10px;
        text-transform: uppercase;
    }

    /* Status Circle for Table Amount */
    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;
    }
</style>

<script>
    let currentRejectLeadId = null;
    let selectedAgentId = null;
    let allLeads = [];

    function updateDashboard() {
        console.log('Updating dashboard...');
        fetch('{{ route("monitoring.stats") }}')
            .then(res => res.json())
            .then(data => {
                renderAgents(data.agents);
            })
            .catch(err => console.error('Monitoring Error:', err));

        fetch('{{ route("monitoring.salesQueue") }}')
            .then(res => res.json())
            .then(data => {
                allLeads = data.leads;
                renderSalesQueue(filterLeadsByAgent(allLeads));
            })
            .catch(err => console.error('Sales Queue Error:', err));
    }

    function filterLeadsByAgent(leads) {
        if (!selectedAgentId) return leads;
        return leads.filter(lead => lead.agent_id === selectedAgentId);
    }

    // Agent Selection Logic
    function selectAgent(agentId) {
        // Find the agent to get current visual state is not strictly needed but good for UX debugging
        if (selectedAgentId === agentId) {
            selectedAgentId = null; // Toggle off
        } else {
            selectedAgentId = agentId;
        }
        
        // Re-render
        renderSalesQueue(filterLeadsByAgent(allLeads));
        
        // Update visual selection state
        document.querySelectorAll('.agent-chip').forEach(el => {
            const id = parseInt(el.dataset.id);
            if(id === selectedAgentId) {
                el.style.boxShadow = '0 0 0 2px white';
            } else {
                el.style.boxShadow = 'none';
            }
        });
    }

    function renderAgents(agents) {
        const grid = document.getElementById('agents-grid');
        
        // Update Total Count
        document.getElementById('total-agent-count').innerText = agents.length;
        
        // Active Count
        const activeAgents = agents.filter(a => a.status === 'online' || a.status === 'busy');
        document.getElementById('active-agent-count').innerText = activeAgents.length;

        // Sort: Active first, then name
        agents.sort((a, b) => {
            const aActive = (a.status === 'online' || a.status === 'busy');
            const bActive = (b.status === 'online' || b.status === 'busy');
            if (aActive && !bActive) return -1;
            if (!aActive && bActive) return 1;
            return a.name.localeCompare(b.name);
        });

        if (agents.length === 0) {
            grid.innerHTML = '<div class="text-secondary w-100 text-center">No agents online</div>';
            return;
        }

        grid.innerHTML = agents.map(agent => {
            const isActive = (agent.status === 'online' || agent.status === 'busy');
            const stateClass = isActive ? 'active' : 'inactive';
            const avatarText = agent.name.charAt(0);
            // If name is "Tele Agent X", use "T"
            const displayId = agent.name.toLowerCase().includes('tele agent') ? 'T' : avatarText;
            
            return `
                <div class="agent-chip ${stateClass}" data-id="${agent.id}" onclick="selectAgent(${agent.id})">
                    <span class="avatar">${displayId}</span>
                    <span class="name">${agent.name}</span>
                </div>
            `;
        }).join('');
    }

    function renderSalesQueue(leads) {
        const tbody = document.getElementById('sales-queue');
        document.getElementById('pending-count').innerText = leads.length + ' PENDING';

        if (leads.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-secondary py-5">No pending sales found.</td></tr>`;
            return;
        }

        tbody.innerHTML = leads.map(lead => {
            // Agent Avatar Calculation
            const initials = lead.agent.substring(0,2).toUpperCase();
            
            return `
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-emerald-500 text-white d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-size: 0.8rem; font-weight: bold;">
                            ${initials}
                        </div>
                        <span class="text-white fw-medium">${lead.agent}</span>
                    </div>
                </td>
                <td>
                    <div class="text-white fw-bold">${lead.customer}</div>
                    <div class="small text-info">${lead.phone || ''}</div>
                </td>
                <td>
                    <div class="text-white small text-uppercase">${lead.product || 'N/A'}</div>
                </td>
                <td class="text-end">
                    <span class="text-emerald-400 fw-bold">₱${parseFloat(lead.amount || 0).toLocaleString()}</span>
                </td>
                <td class="ps-4 text-secondary small">
                   ${lead.updated_at}
                </td>
                <td class="text-end pe-4">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-success btn-sm px-3 fw-bold d-flex align-items-center" onclick="approveQc(${lead.id})">
                            <i class="fas fa-check me-2"></i> Approve
                        </button>
                        <button class="btn btn-light btn-sm px-3 fw-bold d-flex align-items-center" onclick="openRejectModal(${lead.id}, '${lead.customer.replace(/'/g, "\\'")}')">
                            <i class="fas fa-times me-2"></i> Reject
                        </button>
                    </div>
                </td>
            </tr>
            `;
        }).join('');
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
        
        // Manual Show
        const modal = document.getElementById('rejectModal');
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');

        // Focus and Enter key handler
        const textArea = document.getElementById('reject-notes');
        textArea.focus();
        
        // Remove old listener if any to prevent duplicates
        const newTextArea = textArea.cloneNode(true);
        textArea.parentNode.replaceChild(newTextArea, textArea);
        
        newTextArea.addEventListener('keydown', function(e) {
             if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                confirmReject();
             }
        });
    }

    function closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
    
    function confirmReject() {
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
    }

    // Poll every 5 seconds
    setInterval(updateDashboard, 5000);
    
    // Initial Load
    document.addEventListener('DOMContentLoaded', updateDashboard);
</script>
@endsection
