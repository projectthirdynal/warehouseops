@extends('layouts.app')

@section('title', 'Ticket & Work Reports')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">IT Operations Report</h1>
            <p class="text-slate-400 text-sm mt-1">Overview of ticket volume, agent performance, and work logs.</p>
        </div>
        <div class="text-sm text-slate-500 font-mono flex items-center gap-4">
            {{ now()->format('M d, Y') }}
            <a href="{{ route('reports.tickets.export') }}" class="inline-flex items-center gap-2 bg-dark-700 hover:bg-dark-600 text-slate-300 hover:text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors border border-dark-600">
                <i class="fab fa-markdown text-blue-400"></i> Download Report (.md)
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Created Today -->
        <div class="glass-panel bg-dark-800 rounded-xl p-5 border border-dark-700 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-plus-circle text-6xl text-blue-500"></i>
            </div>
            <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Created Today</div>
            <div class="text-3xl font-bold text-white">{{ $stats['created_today'] }}</div>
            <div class="mt-2 text-xs text-blue-400 flex items-center gap-1">
                <i class="fas fa-ticket-alt"></i> New Tickets
            </div>
        </div>

        <!-- Resolved Today -->
        <div class="glass-panel bg-dark-800 rounded-xl p-5 border border-dark-700 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-check-circle text-6xl text-green-500"></i>
            </div>
            <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Resolved Today</div>
            <div class="text-3xl font-bold text-white">{{ $stats['resolved_today'] }}</div>
            <div class="mt-2 text-xs text-green-400 flex items-center gap-1">
                <i class="fas fa-check"></i> Completed
            </div>
        </div>

        <!-- Open Tickets -->
        <div class="glass-panel bg-dark-800 rounded-xl p-5 border border-dark-700 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-envelope-open text-6xl text-yellow-500"></i>
            </div>
            <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Open Tickets</div>
            <div class="text-3xl font-bold text-white">{{ $stats['open_tickets'] }}</div>
            <div class="mt-2 text-xs text-yellow-400 flex items-center gap-1">
                 <i class="fas fa-clock"></i> Pending Action
            </div>
        </div>

        <!-- In Progress -->
        <div class="glass-panel bg-dark-800 rounded-xl p-5 border border-dark-700 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-spinner text-6xl text-purple-500"></i>
            </div>
            <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">In Progress</div>
            <div class="text-3xl font-bold text-white">{{ $stats['in_progress'] }}</div>
             <div class="mt-2 text-xs text-purple-400 flex items-center gap-1">
                 <i class="fas fa-tools"></i> Being Worked On
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Ticket Volume Chart -->
        <div class="lg:col-span-2 glass-panel bg-dark-800 rounded-xl border border-dark-700 p-6">
            <h3 class="text-lg font-bold text-white mb-6">Ticket Volume (Last 7 Days)</h3>
            <div class="relative h-64 w-full">
                <canvas id="ticketVolumeChart"></canvas>
            </div>
        </div>

        <!-- Agent Leaderboard -->
        <div class="glass-panel bg-dark-800 rounded-xl border border-dark-700 p-6">
            <h3 class="text-lg font-bold text-white mb-4">Agent Performance (This Month)</h3>
            <div class="overflow-y-auto max-h-[300px] custom-scrollbar space-y-4">
                @foreach($agents as $index => $agent)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-dark-900/50 border border-dark-700/50">
                        <div class="flex items-center gap-3">
                            <div class="font-mono text-slate-500 text-sm w-4">#{{ $index + 1 }}</div>
                            <div class="w-8 h-8 rounded-full bg-dark-700 flex items-center justify-center text-xs text-slate-300 font-bold border border-dark-600">
                                {{ substr($agent->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white">{{ $agent->name }}</div>
                                <div class="text-[10px] text-slate-500">{{ $agent->logged_minutes }} mins logged</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-gold-400">{{ $agent->resolved_count }}</div>
                            <div class="text-[10px] text-slate-500 uppercase tracking-wide">Resolved</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Today's Worklogs -->
    <div class="glass-panel bg-dark-800 rounded-xl border border-dark-700 p-6">
        <h3 class="text-lg font-bold text-white mb-4">Live Work Log Stream</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-slate-500 uppercase border-b border-dark-700">
                        <th class="py-3 px-4">Time</th>
                        <th class="py-3 px-4">Agent</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Ticket</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 text-right">Duration</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-dark-700">
                    @forelse($worklogsToday as $log)
                        <tr class="hover:bg-dark-700/30 transition-colors">
                            <td class="py-3 px-4 text-slate-400 font-mono text-xs">{{ $log->created_at->format('H:i') }}</td>
                            <td class="py-3 px-4 font-medium text-white">{{ $log->user->name }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-dark-700 text-gold-400 border border-dark-600">
                                    {{ ucfirst($log->action_type) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <a href="{{ route('tickets.show', $log->ticket_id) }}" class="text-blue-400 hover:text-blue-300 hover:underline">
                                    {{ $log->ticket->ref_no }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-slate-300 max-w-md truncate" title="{{ $log->description }}">
                                {{ $log->description }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-400">{{ $log->time_spent }}m</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 text-sm italic">
                                No work logged today (yet).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('ticketVolumeChart').getContext('2d');
        
        // Gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(234, 179, 8, 0.2)'); // Gold
        gradient.addColorStop(1, 'rgba(234, 179, 8, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartData['labels']),
                datasets: [{
                    label: 'New Tickets',
                    data: @json($chartData['values']),
                    borderColor: '#EAB308', // Gold-500
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointBackgroundColor: '#1F2937', // Dark-800
                    pointBorderColor: '#EAB308',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#111827',
                        titleColor: '#F3F4F6',
                        bodyColor: '#D1D5DB',
                        borderColor: '#374151',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#374151', drawBorder: false }, // Dark-700
                        ticks: { color: '#9CA3AF', stepSize: 1 } // Slate-400
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9CA3AF' }
                    }
                }
            }
        });
    });
</script>
@endpush
