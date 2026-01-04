<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CallLog;
use App\Models\Lead;
use Illuminate\Support\Facades\Cache;

class MonitoringController extends Controller
{
    /**
     * Display the Monitoring Dashboard.
     */
    public function index()
    {
        return view('monitoring.index');
    }

    /**
     * API to get real-time stats for the dashboard.
     * Called via AJAX polling every 5-10 seconds.
     */
    public function getStats()
    {
        // 1. Agent Status (from Cache/Heartbeat)
        $agents = User::where('role', 'agent')->get()->map(function ($agent) {
            $lastActivity = Cache::get('agent_activity_' . $agent->id);
            $status = 'offline';
            $currentLead = null;

            if ($lastActivity) {
                // Considered online if active in last 2 minutes
                if (now()->diffInSeconds($lastActivity['time']) < 120) {
                    $status = $lastActivity['status'] ?? 'online'; // online or busy
                    $currentLead = $lastActivity['lead'] ?? null;
                }
            }

            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'avatar' => substr($agent->name, 0, 1),
                'status' => $status,
                'current_lead' => $currentLead,
                'sip_account' => $agent->sipAccount->username ?? '?',
            ];
        });

        // 2. Daily Metrics
        $today = now()->startOfDay();
        $metrics = [
            'total_calls' => CallLog::where('created_at', '>=', $today)->count(),
            'sales_today' => Lead::where('status', Lead::STATUS_SALE)->where('updated_at', '>=', $today)->count(),
            'rejected_today' => Lead::where('qc_status', Lead::QC_STATUS_FAILED)->where('qc_at', '>=', $today)->count(),
            'active_calls' => $agents->where('status', 'busy')->count(),
            'online_agents' => $agents->where('status', '!=', 'offline')->count(),
        ];

        return response()->json([
            'agents' => $agents,
            'metrics' => $metrics
        ]);
    }

    /**
     * Heartbeat API called by softphone.js
     * Updates the agent's status in Cache.
     */
    public function heartbeat(Request $request)
    {
        // Valid statuses: 'online', 'busy' (in-call)
        $status = $request->input('status', 'online');
        $leadInfo = $request->input('lead', null); // "John Doe (Product X)"

        Cache::put('agent_activity_' . auth()->id(), [
            'time' => now(),
            'status' => $status,
            'lead' => $leadInfo
        ], 120); // Expire after 2 mins

        return response()->json(['success' => true]);
    }
}
