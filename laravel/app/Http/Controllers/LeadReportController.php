<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadCycle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadReportController extends Controller
{
    /**
     * Agent performance report.
     * Shows sales rate, call volume, rejection rate per agent.
     */
    public function agentPerformance(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $agents = User::where('role', 'agent')
            ->where('is_active', true)
            ->get();

        $report = [];

        foreach ($agents as $agent) {
            $cycles = LeadCycle::where('agent_id', $agent->id)
                ->whereBetween('opened_at', [$startDate, $endDate . ' 23:59:59'])
                ->get();

            $totalCycles = $cycles->count();
            $sales = $cycles->where('status', LeadCycle::STATUS_CLOSED_SALE)->count();
            $rejects = $cycles->where('status', LeadCycle::STATUS_CLOSED_REJECT)->count();
            $returns = $cycles->where('status', LeadCycle::STATUS_CLOSED_RETURNED)->count();
            $active = $cycles->where('status', LeadCycle::STATUS_ACTIVE)->count();
            $totalCalls = $cycles->sum('call_attempts');

            $report[] = [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'total_cycles' => $totalCycles,
                'sales' => $sales,
                'rejects' => $rejects,
                'returns' => $returns,
                'active' => $active,
                'total_calls' => $totalCalls,
                'sales_rate' => $totalCycles > 0 ? round(($sales / $totalCycles) * 100, 2) : 0,
                'rejection_rate' => $totalCycles > 0 ? round(($rejects / $totalCycles) * 100, 2) : 0,
                'avg_calls_per_cycle' => $totalCycles > 0 ? round($totalCalls / $totalCycles, 1) : 0
            ];
        }

        // Sort by sales rate descending
        usort($report, fn($a, $b) => $b['sales_rate'] <=> $a['sales_rate']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'data' => $report
            ]);
        }

        return view('leads.reports.agent-performance', [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Recycling patterns report.
     * Shows leads recycled most, cycle counts, exhaustion rate.
     */
    public function recyclingPatterns(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Summary statistics
        $totalLeads = Lead::count();
        $exhaustedLeads = Lead::where('is_exhausted', true)->count();
        $recycledLeads = Lead::where('total_cycles', '>', 1)->count();
        $neverRecycled = Lead::where('total_cycles', '<=', 1)->count();

        // Cycle distribution
        $cycleDistribution = Lead::select('total_cycles', DB::raw('count(*) as count'))
            ->groupBy('total_cycles')
            ->orderBy('total_cycles')
            ->get();

        // Most recycled leads (top 20)
        $mostRecycled = Lead::where('total_cycles', '>', 2)
            ->orderBy('total_cycles', 'desc')
            ->limit(20)
            ->get(['id', 'name', 'phone', 'total_cycles', 'max_cycles', 'is_exhausted', 'status']);

        // Agent recycling patterns
        $agentRecycling = LeadCycle::select('agent_id', DB::raw('count(*) as total_cycles'))
            ->whereBetween('opened_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('cycle_number', '>', 1) // Only recycled leads
            ->groupBy('agent_id')
            ->orderBy('total_cycles', 'desc')
            ->with('agent:id,name')
            ->get();

        $data = [
            'summary' => [
                'total_leads' => $totalLeads,
                'exhausted_leads' => $exhaustedLeads,
                'exhaustion_rate' => $totalLeads > 0 ? round(($exhaustedLeads / $totalLeads) * 100, 2) : 0,
                'recycled_leads' => $recycledLeads,
                'never_recycled' => $neverRecycled
            ],
            'cycle_distribution' => $cycleDistribution,
            'most_recycled' => $mostRecycled,
            'agent_recycling' => $agentRecycling
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'data' => $data
            ]);
        }

        return view('leads.reports.recycling-patterns', array_merge($data, [
            'startDate' => $startDate,
            'endDate' => $endDate
        ]));
    }

    /**
     * Lead cycle history for a specific lead.
     */
    public function leadHistory(Lead $lead)
    {
        $cycles = $lead->cycles()
            ->with(['agent:id,name', 'waybill:id,waybill_number,status'])
            ->orderBy('cycle_number', 'desc')
            ->get();

        $validation = $lead->canRecycle();

        return response()->json([
            'success' => true,
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'status' => $lead->status,
                'total_cycles' => $lead->total_cycles,
                'max_cycles' => $lead->max_cycles,
                'is_exhausted' => $lead->is_exhausted,
                'can_recycle' => $validation === true,
                'recycle_reason' => $validation === true ? null : $validation
            ],
            'cycles' => $cycles
        ]);
    }
}
