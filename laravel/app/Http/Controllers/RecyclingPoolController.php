<?php

namespace App\Http\Controllers;

use App\Models\LeadRecyclingPool;
use App\Models\User;
use App\Services\RecyclingPoolService;
use Illuminate\Http\Request;

class RecyclingPoolController extends Controller
{
    public function __construct(
        private RecyclingPoolService $recyclingPoolService
    ) {}

    /**
     * Get available leads from recycling pool
     * GET /recycling/pool
     */
    public function index(Request $request)
    {
        $request->validate([
            'count' => 'nullable|integer|min:1|max:100',
            'min_priority' => 'nullable|integer|min:0|max:100',
            'recycle_reason' => 'nullable|string',
            'customer_score_min' => 'nullable|integer|min:0|max:100',
            'pool_status' => 'nullable|string|in:AVAILABLE,ASSIGNED,CONVERTED,EXHAUSTED,EXPIRED',
        ]);

        // Get stats
        $stats = $this->recyclingPoolService->getPoolStats();

        // Get pool entries with pagination
        $perPage = $request->input('count', 50);
        $filters = $request->only(['min_priority', 'recycle_reason', 'customer_score_min', 'pool_status']);

        $query = LeadRecyclingPool::with(['customer', 'assignedAgent'])
            ->orderBy('priority_score', 'desc')
            ->orderBy('available_from', 'asc');

        // Apply filters
        if ($filters['min_priority'] ?? null) {
            $query->where('priority_score', '>=', $filters['min_priority']);
        }

        if ($filters['recycle_reason'] ?? null) {
            $query->where('recycle_reason', $filters['recycle_reason']);
        }

        if ($filters['customer_score_min'] ?? null) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('customer_score', '>=', $filters['customer_score_min']);
            });
        }

        if ($filters['pool_status'] ?? null) {
            $query->where('pool_status', $filters['pool_status']);
        } else {
            // Default to available and assigned
            $query->whereIn('pool_status', [LeadRecyclingPool::STATUS_AVAILABLE, LeadRecyclingPool::STATUS_ASSIGNED]);
        }

        $poolEntries = $query->paginate($perPage);

        // Get agents for assignment
        $agents = User::where('role', 'agent')
            ->orWhere('role', 'team_leader')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get unique reasons for filter
        $reasons = LeadRecyclingPool::select('recycle_reason')
            ->distinct()
            ->pluck('recycle_reason')
            ->toArray();

        // If JSON requested, return API response
        if ($request->wantsJson()) {
            $formattedLeads = $poolEntries->map(function ($poolEntry) {
                return [
                    'pool_id' => $poolEntry->id,
                    'customer' => [
                        'id' => $poolEntry->customer->id,
                        'name' => $poolEntry->customer->name_display,
                        'phone' => $poolEntry->customer->phone_primary,
                        'address' => $poolEntry->customer->primary_address,
                        'city' => $poolEntry->customer->city,
                        'score' => $poolEntry->customer->customer_score,
                        'risk_level' => $poolEntry->customer->risk_level,
                    ],
                    'history' => [
                        'total_orders' => $poolEntry->customer->total_orders,
                        'delivered' => $poolEntry->customer->total_delivered,
                        'returned' => $poolEntry->customer->total_returned,
                        'success_rate' => $poolEntry->customer->delivery_success_rate,
                    ],
                    'recycling' => [
                        'reason' => $poolEntry->recycle_reason,
                        'reason_label' => $poolEntry->reason_label,
                        'priority' => $poolEntry->priority_score,
                        'recycle_count' => $poolEntry->recycle_count,
                        'previous_outcome' => $poolEntry->original_outcome,
                        'available_from' => $poolEntry->available_from,
                        'expires_at' => $poolEntry->expires_at,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $formattedLeads->count(),
                'leads' => $formattedLeads
            ]);
        }

        // Return view for web interface
        return view('recycling.pool', compact('poolEntries', 'stats', 'agents', 'reasons'));
    }

    /**
     * Get pool statistics
     * GET /recycling/pool/stats
     */
    public function stats()
    {
        $stats = $this->recyclingPoolService->getPoolStats();

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Assign leads to agent
     * POST /recycling/assign
     */
    public function assign(Request $request)
    {
        $request->validate([
            'pool_ids' => 'required|array|min:1',
            'pool_ids.*' => 'required|uuid|exists:lead_recycling_pool,id',
            'agent_id' => 'required|integer|exists:users,id',
        ]);

        $agent = User::findOrFail($request->input('agent_id'));

        $results = $this->recyclingPoolService->assignToAgent(
            $request->input('pool_ids'),
            $agent
        );

        // If JSON requested, return API response
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'assigned' => $results['assigned'],
                'errors' => $results['errors'],
                'total' => $results['total']
            ]);
        }

        // Web response with flash message
        $message = "Successfully assigned {$results['assigned']} entries to {$agent->name}.";
        if (count($results['errors']) > 0) {
            $message .= " " . count($results['errors']) . " entries could not be assigned.";
        }

        return redirect()->route('recycling.pool')->with('success', $message);
    }

    /**
     * Process outcome of a recycled lead
     * POST /recycling/{poolId}/outcome
     */
    public function processOutcome(Request $request, string $poolId)
    {
        $request->validate([
            'outcome' => 'required|string|in:SALE,REORDER,NO_ANSWER,DECLINED,NOT_INTERESTED,DO_NOT_CALL,CALLBACK',
            'notes' => 'nullable|string',
            'callback_date' => 'nullable|date',
            // Sale data (required for SALE/REORDER outcomes)
            'product_name' => 'nullable|string',
            'product_brand' => 'nullable|string',
            'amount' => 'nullable|numeric',
        ]);

        $agent = $request->user();

        $saleData = null;
        if (in_array($request->input('outcome'), ['SALE', 'REORDER'])) {
            $saleData = [
                'product_name' => $request->input('product_name'),
                'product_brand' => $request->input('product_brand'),
                'amount' => $request->input('amount'),
            ];
        }

        $result = $this->recyclingPoolService->processOutcome(
            $poolId,
            $request->input('outcome'),
            $agent,
            $saleData,
            $request->input('notes'),
            $request->input('callback_date')
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Get recycling pool entries assigned to current agent
     * GET /recycling/mine
     */
    public function mine(Request $request)
    {
        $agent = $request->user();

        $assigned = LeadRecyclingPool::with(['customer'])
            ->assignedTo($agent->id)
            ->byPriority('desc')
            ->get();

        $formattedLeads = $assigned->map(function ($poolEntry) {
            return [
                'pool_id' => $poolEntry->id,
                'customer' => [
                    'id' => $poolEntry->customer->id,
                    'name' => $poolEntry->customer->name_display,
                    'phone' => $poolEntry->customer->phone_primary,
                    'address' => $poolEntry->customer->primary_address,
                    'city' => $poolEntry->customer->city,
                    'score' => $poolEntry->customer->customer_score,
                    'risk_level' => $poolEntry->customer->risk_level,
                ],
                'history' => [
                    'total_orders' => $poolEntry->customer->total_orders,
                    'delivered' => $poolEntry->customer->total_delivered,
                    'returned' => $poolEntry->customer->total_returned,
                    'success_rate' => $poolEntry->customer->delivery_success_rate,
                ],
                'recycling' => [
                    'reason' => $poolEntry->recycle_reason,
                    'reason_label' => $poolEntry->reason_label,
                    'priority' => $poolEntry->priority_score,
                    'recycle_count' => $poolEntry->recycle_count,
                    'assigned_at' => $poolEntry->assigned_at,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $formattedLeads->count(),
            'leads' => $formattedLeads
        ]);
    }

    /**
     * Manually trigger cleanup of expired entries
     * POST /recycling/cleanup
     */
    public function cleanup(Request $request)
    {
        $expiredCount = $this->recyclingPoolService->cleanupExpired();
        $staleCount = $this->recyclingPoolService->releaseStaleAssignments(24);

        // If JSON requested, return API response
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'expired_cleaned' => $expiredCount,
                'stale_released' => $staleCount,
                'message' => 'Cleanup completed successfully'
            ]);
        }

        // Web response with flash message
        $message = "Cleanup completed: {$expiredCount} expired entries cleaned, {$staleCount} stale assignments released.";
        return redirect()->route('recycling.pool')->with('success', $message);
    }

    /**
     * Get detailed view of a pool entry
     * GET /recycling/{poolId}
     */
    public function show(string $poolId)
    {
        $poolEntry = LeadRecyclingPool::with(['customer', 'assignedAgent', 'sourceLead'])
            ->findOrFail($poolId);

        return response()->json([
            'success' => true,
            'pool_entry' => [
                'id' => $poolEntry->id,
                'status' => $poolEntry->pool_status,
                'customer' => [
                    'id' => $poolEntry->customer->id,
                    'name' => $poolEntry->customer->name_display,
                    'phone' => $poolEntry->customer->phone_primary,
                    'address' => $poolEntry->customer->primary_address,
                    'city' => $poolEntry->customer->city,
                    'province' => $poolEntry->customer->province,
                    'score' => $poolEntry->customer->customer_score,
                    'risk_level' => $poolEntry->customer->risk_level,
                    'total_orders' => $poolEntry->customer->total_orders,
                    'delivered' => $poolEntry->customer->total_delivered,
                    'returned' => $poolEntry->customer->total_returned,
                    'success_rate' => $poolEntry->customer->delivery_success_rate,
                ],
                'recycling' => [
                    'reason' => $poolEntry->recycle_reason,
                    'reason_label' => $poolEntry->reason_label,
                    'priority' => $poolEntry->priority_score,
                    'recycle_count' => $poolEntry->recycle_count,
                    'original_outcome' => $poolEntry->original_outcome,
                    'source_waybill' => $poolEntry->source_waybill,
                ],
                'scheduling' => [
                    'available_from' => $poolEntry->available_from,
                    'expires_at' => $poolEntry->expires_at,
                ],
                'assignment' => [
                    'assigned_to' => $poolEntry->assignedAgent?->name,
                    'assigned_at' => $poolEntry->assigned_at,
                ],
                'processing' => [
                    'processed_at' => $poolEntry->processed_at,
                    'processed_outcome' => $poolEntry->processed_outcome,
                ],
                'timestamps' => [
                    'created_at' => $poolEntry->created_at,
                    'updated_at' => $poolEntry->updated_at,
                ],
            ]
        ]);
    }
}
