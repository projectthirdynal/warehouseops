@extends('layouts.app')

@section('title', 'QC Dashboard')
@section('page-title', 'QC Dashboard')

@section('content')
    <!-- Agents Section -->
    <x-card class="mb-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-white">
                <i class="fas fa-users mr-2 text-cyan-500"></i>Agents
            </h2>
            <span class="text-sm text-dark-100">
                <span id="active-agent-count" class="text-white font-bold">0</span> /
                <span id="total-agent-count">0</span>
            </span>
        </div>
        <div id="agents-grid" class="flex flex-wrap gap-2">
            <div class="text-center w-full text-dark-100 py-4">Loading agents...</div>
        </div>
    </x-card>

    <!-- Sales Pending QC Section -->
    <x-table title="Sales Pending QC">
        <x-slot:header>
            <span class="text-sm font-semibold text-dark-100 uppercase" id="pending-count">0 PENDING</span>
        </x-slot:header>

        <x-slot:head>
            <x-table-th class="w-1/4">Agent</x-table-th>
            <x-table-th class="w-1/5">Customer</x-table-th>
            <x-table-th class="w-[30%]">Product</x-table-th>
            <x-table-th class="text-right w-[10%]">Amount</x-table-th>
            <x-table-th class="w-[15%]">Updated</x-table-th>
            <x-table-th class="text-right w-[15%]">Actions</x-table-th>
        </x-slot:head>

        <tbody id="sales-queue">
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-dark-100">Loading sales...</td>
            </tr>
        </tbody>
    </x-table>

    <!-- Reject Modal -->
    <x-modal name="reject-modal" title="Reject Sale" maxWidth="lg">
        <div class="space-y-4">
            <p class="text-dark-100">
                Rejecting sale for: <strong class="text-white" id="reject-lead-name"></strong>
            </p>
            <x-form.textarea
                id="reject-notes"
                name="notes"
                label="Rejection Reason"
                placeholder="Why is this a false sale?"
                rows="5"
                required
            />
        </div>

        <x-slot:footer>
            <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'reject-modal')">
                Cancel
            </x-button>
            <x-button type="button" variant="danger" id="confirm-reject-btn" onclick="confirmReject()">
                Confirm Reject
            </x-button>
        </x-slot:footer>
    </x-modal>
@endsection

@push('scripts')
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

    function selectAgent(agentId) {
        if (selectedAgentId === agentId) {
            selectedAgentId = null;
        } else {
            selectedAgentId = agentId;
        }

        renderSalesQueue(filterLeadsByAgent(allLeads));

        document.querySelectorAll('.agent-chip').forEach(el => {
            const id = parseInt(el.dataset.id);
            if (id === selectedAgentId) {
                el.classList.add('ring-2', 'ring-white');
            } else {
                el.classList.remove('ring-2', 'ring-white');
            }
        });
    }

    function renderAgents(agents) {
        const grid = document.getElementById('agents-grid');

        document.getElementById('total-agent-count').innerText = agents.length;

        const activeAgents = agents.filter(a => a.status === 'online' || a.status === 'busy');
        document.getElementById('active-agent-count').innerText = activeAgents.length;

        agents.sort((a, b) => {
            const aActive = (a.status === 'online' || a.status === 'busy');
            const bActive = (b.status === 'online' || b.status === 'busy');
            if (aActive && !bActive) return -1;
            if (!aActive && bActive) return 1;
            return a.name.localeCompare(b.name);
        });

        if (agents.length === 0) {
            grid.innerHTML = '<div class="text-dark-100 w-full text-center py-4">No agents online</div>';
            return;
        }

        grid.innerHTML = agents.map(agent => {
            const isActive = (agent.status === 'online' || agent.status === 'busy');
            const bgClass = isActive ? 'bg-success-500 text-white' : 'bg-dark-500 text-slate-300';
            const avatarBg = isActive ? 'bg-white/20' : 'bg-dark-400';
            const displayId = agent.name.toLowerCase().includes('tele agent') ? 'T' : agent.name.charAt(0);

            return `
                <button type="button" class="agent-chip inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium transition-all cursor-pointer hover:brightness-110 ${bgClass}" data-id="${agent.id}" onclick="selectAgent(${agent.id})">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold mr-2 ${avatarBg}">${displayId}</span>
                    <span>${agent.name}</span>
                </button>
            `;
        }).join('');
    }

    function renderSalesQueue(leads) {
        const tbody = document.getElementById('sales-queue');
        document.getElementById('pending-count').innerText = leads.length + ' PENDING';

        if (leads.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-dark-600 flex items-center justify-center">
                            <i class="fas fa-clipboard-check text-2xl text-dark-100"></i>
                        </div>
                        <h3 class="text-lg font-medium text-white mb-1">All Caught Up!</h3>
                        <p class="text-sm text-dark-100">No pending sales found</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = leads.map(lead => {
            const initials = lead.agent.substring(0, 2).toUpperCase();

            return `
                <tr class="hover:bg-dark-600 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-success-500 text-white flex items-center justify-center text-xs font-bold">
                                ${initials}
                            </div>
                            <span class="text-white font-medium">${lead.agent}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-white font-semibold">${lead.customer}</div>
                        <div class="text-xs text-info-500">${lead.phone || ''}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-white text-sm uppercase">${lead.product || 'N/A'}</div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-success-500 font-bold">PHP ${parseFloat(lead.amount || 0).toLocaleString()}</span>
                    </td>
                    <td class="px-4 py-3 text-dark-100 text-sm">
                        ${lead.updated_at}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            <button type="button" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold bg-success-500 text-white rounded-lg hover:bg-success-600 transition-colors" onclick="approveQc(${lead.id})">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button type="button" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold bg-dark-500 text-white rounded-lg hover:bg-dark-400 transition-colors" onclick="openRejectModal(${lead.id}, '${lead.customer.replace(/'/g, "\\'")}')">
                                <i class="fas fa-times"></i> Reject
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

        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'reject-modal' }));

        setTimeout(() => {
            document.getElementById('reject-notes').focus();
        }, 100);
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
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'reject-modal' }));
                updateDashboard();
            }
        });
    }

    // Poll every 5 seconds
    setInterval(updateDashboard, 5000);

    // Initial Load
    document.addEventListener('DOMContentLoaded', updateDashboard);

    // Enter key handler for textarea
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('reject-notes');
        if (textarea) {
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    confirmReject();
                }
            });
        }
    });
</script>
@endpush
