<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadCycle;
use App\Models\User;
use App\Models\Waybill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MonitoringController extends Controller
{
    /**
     * Display the main monitoring dashboard.
     */
    public function dashboard()
    {
        if (!Auth::user()->can('leads_manage')) {
             abort(403);
        }

        return view('monitoring.dashboard');
    }

    /**
     * Get live stats for agent cycles.
     */
    public function liveStats()
    {
        // Get all agents with active profiles
        $agents = User::whereHas('profile')
            ->with(['profile', 'leadCycles' => function ($q) {
                $q->where('status', LeadCycle::STATUS_ACTIVE);
            }])
            ->get()
            ->map(function ($agent) {
                $activeCount = $agent->leadCycles->count();
                $max = $agent->profile->max_active_cycles;
                $load = $max > 0 ? round(($activeCount / $max) * 100) : 0;

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'active_cycles' => $activeCount,
                    'max_cycles' => $max,
                    'load_percentage' => $load,
                    'is_online' => $agent->profile->is_available // Simplified proxy
                ];
            });

        return response()->json($agents);
    }

    /**
     * Get stuck cycles (Guardian Logic).
     */
    public function stuckCycles()
    {
        $cycles = LeadCycle::where('status', LeadCycle::STATUS_ACTIVE)
            ->where('created_at', '<', now()->subHours(24))
            ->where('call_attempts', 0)
            ->with(['agent', 'lead'])
            ->limit(50)
            ->get();

        return response()->json($cycles);
    }

    /**
     * Get leads blocked by active waybills.
     */
    public function blockedLeads()
    {
        // Leads that have waybills in PENDING or IN_TRANSIT
        // Using Waybill statuses (assuming standard naming from Waybill system)
        $leads = Lead::whereHas('waybills', function ($q) {
                $q->whereIn('status', ['PENDING', 'IN_TRANSIT', 'OUT_FOR_DELIVERY']);
            })
            ->withCount('waybills')
            ->limit(50)
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'status' => $lead->status,
                    'waybill_count' => $lead->waybills_count
                ];
            });

        return response()->json($leads);
    }

    /**
     * Get recycle heatmap data (Last 24 hours).
     */
    public function recycleHeatmap()
    {
        // Count cycles closed as REJECT per hour
        $data = LeadCycle::where('status', LeadCycle::STATUS_CLOSED_REJECT)
            ->where('closed_at', '>=', now()->subHours(24))
            ->select(
                DB::raw('HOUR(closed_at) as hour'),
                DB::raw('count(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        // Fill missing hours
        $formatted = [];
        for ($i = 0; $i < 24; $i++) {
            $formatted[$i] = $data[$i] ?? 0;
        }

        return response()->json($formatted);
    }
}
