<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CallController extends Controller
{
    /**
     * Initiate a new call - creates call log entry
     * POST /api/calls/initiate
     */
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'lead_id' => 'nullable|exists:leads,id',
            'direction' => 'in:outbound,inbound',
        ]);

        $callLog = CallLog::create([
            'user_id' => Auth::id(),
            'lead_id' => $request->lead_id,
            'phone_number' => $request->phone_number,
            'call_id' => $request->call_id ?? 'web-' . Str::uuid(),
            'direction' => $request->direction ?? CallLog::DIRECTION_OUTBOUND,
            'status' => CallLog::STATUS_INITIATED,
            'started_at' => now(),
            'metadata' => $request->metadata ?? [],
        ]);

        return response()->json([
            'success' => true,
            'call' => $callLog,
        ]);
    }

    /**
     * Update call status
     * PATCH /api/calls/{callId}/status
     */
    public function updateStatus(Request $request, string $callId): JsonResponse
    {
        $callLog = CallLog::where('call_id', $callId)->firstOrFail();

        // Verify ownership (agent can only update their own calls)
        if ($callLog->user_id !== Auth::id() && !Auth::user()->canAccess('calls_manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:ringing,answered,ended,failed,missed,busy',
            'notes' => 'nullable|string|max:1000',
        ]);

        $status = $request->status;

        switch ($status) {
            case 'answered':
                $callLog->markAnswered();
                break;

            case 'ended':
                $callLog->endCall($request->notes);
                break;

            case 'failed':
                $callLog->markFailed($request->notes);
                break;

            default:
                $callLog->update(['status' => $status]);
        }

        return response()->json([
            'success' => true,
            'call' => $callLog->fresh(),
        ]);
    }

    /**
     * Get call history for current agent
     * GET /api/calls
     */
    public function index(Request $request): JsonResponse
    {
        $query = CallLog::with(['lead:id,name,phone'])
            ->forAgent(Auth::id())
            ->latest();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $calls = $query->paginate($request->per_page ?? 25);

        return response()->json($calls);
    }

    /**
     * Get single call details
     * GET /api/calls/{callId}
     */
    public function show(string $callId): JsonResponse
    {
        $callLog = CallLog::with(['lead', 'user:id,name'])
            ->where('call_id', $callId)
            ->firstOrFail();

        // Check permission
        if ($callLog->user_id !== Auth::id() && !Auth::user()->canAccess('calls_manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($callLog);
    }

    /**
     * Admin: Get all active calls (monitoring)
     * GET /api/calls/monitoring
     */
    public function monitoring(Request $request): JsonResponse
    {
        if (!Auth::user()->canAccess('calls_manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activeCalls = CallLog::with(['user:id,name', 'lead:id,name,phone'])
            ->active()
            ->latest('started_at')
            ->get();

        // Get stats
        $todayStats = [
            'total_calls' => CallLog::today()->count(),
            'answered' => CallLog::today()->where('status', CallLog::STATUS_ENDED)->count(),
            'missed' => CallLog::today()->where('status', CallLog::STATUS_MISSED)->count(),
            'failed' => CallLog::today()->where('status', CallLog::STATUS_FAILED)->count(),
            'total_duration' => CallLog::today()->sum('duration_seconds'),
            'avg_duration' => (int) CallLog::today()->where('duration_seconds', '>', 0)->avg('duration_seconds'),
        ];

        // Per-agent stats
        $agentStats = CallLog::today()
            ->selectRaw('user_id, COUNT(*) as call_count, SUM(duration_seconds) as total_duration')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->get();

        return response()->json([
            'active_calls' => $activeCalls,
            'today_stats' => $todayStats,
            'agent_stats' => $agentStats,
        ]);
    }

    /**
     * Admin: Get call history (all agents)
     * GET /api/calls/history
     */
    public function history(Request $request): JsonResponse
    {
        if (!Auth::user()->canAccess('calls_manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = CallLog::with(['user:id,name', 'lead:id,name,phone'])
            ->latest();

        if ($request->filled('agent_id')) {
            $query->where('user_id', $request->agent_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('phone')) {
            $query->where('phone_number', 'like', '%' . $request->phone . '%');
        }

        $calls = $query->paginate($request->per_page ?? 50);

        return response()->json($calls);
    }

    /**
     * Add notes to an existing call
     * PATCH /api/calls/{callId}/notes
     */
    public function addNotes(Request $request, string $callId): JsonResponse
    {
        $callLog = CallLog::where('call_id', $callId)->firstOrFail();

        if ($callLog->user_id !== Auth::id() && !Auth::user()->canAccess('calls_manage')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        $callLog->update(['notes' => $request->notes]);

        return response()->json([
            'success' => true,
            'call' => $callLog,
        ]);
    }
}
