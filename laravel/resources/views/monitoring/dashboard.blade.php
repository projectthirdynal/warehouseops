@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Operations Monitoring</h1>
        <div>
            <span class="badge badge-success px-3 py-2" id="status-badge">Live</span>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Cycles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-active-cycles">--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sync fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Stuck / Zombie Cycles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-stuck-cycles">--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Blocked (Waybills)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-blocked-leads">--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Live Agent Load -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Live Agent Load</h6>
                </div>
                <div class="card-body" id="agent-load-container">
                    <div class="text-center p-3">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Recycle Heatmap -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recycles (Last 24h)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <!-- Placeholder for Chart.js or simple CSS bars -->
                        <div id="heatmap-container" class="d-flex align-items-end justify-content-between h-100 pt-4 pb-2">
                            <!-- Bars injected via JS -->
                        </div>
                    </div>
                    <div class="d-flex justify-content-between text-xs text-muted mt-2">
                        <span>00:00</span>
                        <span>12:00</span>
                        <span>23:00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tables Row -->
    <div class="row">
         <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                 <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Stuck Cycles (Zombie Detection)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="stuck-table" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Cycle ID</th>
                                    <th>Agent</th>
                                    <th>Lead</th>
                                    <th>Opened</th>
                                    <th>Idle Time</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
         </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function fetchLiveStats() {
        fetch('{{ route("monitoring.live-stats") }}')
            .then(response => response.json())
            .then(data => {
                let html = '';
                let totalActive = 0;
                
                data.forEach(agent => {
                    totalActive += agent.active_cycles;
                    let color = 'primary';
                    if (agent.load_percentage > 90) color = 'danger';
                    else if (agent.load_percentage > 70) color = 'warning';
                    
                    html += `
                        <h4 class="small font-weight-bold">${agent.name} <span class="float-right">${agent.active_cycles}/${agent.max_cycles}</span></h4>
                        <div class="progress mb-4">
                            <div class="progress-bar bg-${color}" role="progressbar" style="width: ${agent.load_percentage}%"></div>
                        </div>
                    `;
                });
                
                document.getElementById('agent-load-container').innerHTML = html;
                document.getElementById('kpi-active-cycles').innerText = totalActive;
            });
    }

    function fetchStuckCycles() {
        fetch('{{ route("monitoring.stuck-cycles") }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('kpi-stuck-cycles').innerText = data.length;
                let tbody = document.querySelector('#stuck-table tbody');
                tbody.innerHTML = '';
                
                data.forEach(cycle => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${cycle.id}</td>
                            <td>${cycle.agent ? cycle.agent.name : 'Unknown'}</td>
                            <td>${cycle.lead_id}</td>
                            <td>${new Date(cycle.created_at).toLocaleString()}</td>
                            <td class="text-danger">Active w/ 0 calls</td>
                        </tr>
                    `;
                });
            });
    }

    function fetchBlockedLeads() {
        fetch('{{ route("monitoring.blocked-leads") }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('kpi-blocked-leads').innerText = data.length;
            });
    }

    function fetchHeatmap() {
        fetch('{{ route("monitoring.recycle-heatmap") }}')
            .then(response => response.json())
            .then(data => {
                let html = '';
                // data is object {0: count, 1: count...}
                let max = Math.max(...Object.values(data)) || 1;
                
                for(let i=0; i<24; i++) {
                    let count = data[i] || 0;
                    let height = (count / max) * 100;
                    html += `
                        <div class="d-flex flex-column align-items-center" style="width: 3%;">
                            <div class="bg-info w-100" style="height: ${height}%; min-height: 1px;" title="${i}:00 - ${count} recycles"></div>
                        </div>
                    `;
                }
                document.getElementById('heatmap-container').innerHTML = html;
            });
    }

    // Initial Load
    fetchLiveStats();
    fetchStuckCycles();
    fetchBlockedLeads();
    fetchHeatmap();

    // Poll every 30s
    setInterval(() => {
        fetchLiveStats();
        fetchStuckCycles();
        fetchBlockedLeads();
    }, 30000);
});
</script>
@endsection
