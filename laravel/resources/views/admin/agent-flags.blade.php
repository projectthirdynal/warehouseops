@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Agent Behavior Governance</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Active Flags ({{ $flags->total() }})</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Severity</th>
                            <th>Agent</th>
                            <th>Issue Type</th>
                            <th>Metric / Team Avg</th>
                            <th>Details</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($flags as $flag)
                            <tr>
                                <td>
                                    @if($flag->severity == 'CRITICAL')
                                        <span class="badge badge-danger">CRITICAL</span>
                                    @elseif($flag->severity == 'WARNING')
                                        <span class="badge badge-warning">WARNING</span>
                                    @else
                                        <span class="badge badge-info">INFO</span>
                                    @endif
                                </td>
                                <td>{{ $flag->agent->name }}</td>
                                <td>{{ $flag->type }}</td>
                                <td>
                                    <span class="text-danger font-weight-bold">{{ $flag->metric_value }}</span>
                                    <small class="text-muted d-block">Avg: {{ $flag->team_average }}</small>
                                </td>
                                <td>{{ $flag->details['message'] ?? '' }}</td>
                                <td>{{ $flag->created_at->diffForHumans() }}</td>
                                <td>
                                    <form action="{{ route('agents.flags.resolve', $flag->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            Resolve
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No active behavior flags found. Excellent!</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-3">
                {{ $flags->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
