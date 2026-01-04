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
            <!-- Metrics injected by JS -->
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
        </div>
    </div>

    <!-- Agent Grid -->
    <div class="row g-3" id="agent-grid">
        <!-- Agent cards injected by JS -->
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-secondary mt-2">Loading live data...</p>
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
    function updateDashboard() {
        fetch('{{ route("monitoring.stats") }}')
            .then(res => res.json())
            .then(data => {
                renderMetrics(data.metrics);
                renderAgents(data.agents);
            })
            .catch(err => console.error('Monitoring Error:', err));
    }

    function renderMetrics(metrics) {
        document.getElementById('stat-online-agents').innerText = metrics.online_agents;
        document.getElementById('stat-active-calls').innerText = metrics.active_calls;
        document.getElementById('stat-total-calls').innerText = metrics.total_calls;
        document.getElementById('stat-sales-today').innerText = metrics.sales_today;
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

    // Poll every 5 seconds
    setInterval(updateDashboard, 5000);
    updateDashboard(); // Initial load
</script>
@endsection
