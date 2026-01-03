<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Waybill;

class PendingController extends Controller
{
    public function index()
    {
        $pendingCount = Waybill::where('status', 'issue_pending')->count();
        return view('pending', compact('pendingCount'));
    }

    public function list(Request $request)
    {
        $limit = $request->input('limit', 10);
        
        $waybills = Waybill::where('status', 'issue_pending')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json($waybills);
    }

    public function dispatch(Request $request)
    {
        $request->validate([
            'waybill_number' => 'required|string'
        ]);

        $waybill = Waybill::where('waybill_number', $request->waybill_number)->first();

        if (!$waybill) {
            return response()->json([
                'success' => false,
                'message' => 'Waybill not found'
            ], 404);
        }

        if ($waybill->status === 'dispatched') {
            return response()->json([
                'success' => false,
                'message' => 'Waybill already dispatched'
            ], 400);
        }

        // Only allow dispatching if it's pending or issue_pending
        // We can be lenient here and allow 'pending' (ready for batch) too, 
        // as this is a "Quick Dispatch" feature.
        if (!in_array($waybill->status, ['pending', 'issue_pending'])) {
             return response()->json([
                'success' => false,
                'message' => 'Waybill is not in a pending state (Status: ' . $waybill->status . ')'
            ], 400);
        }

        $waybill->update([
            'status' => 'dispatched',
            'batch_ready' => false,
            // We might want to clear marked_pending_at if it was set
            'marked_pending_at' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Waybill ' . $waybill->waybill_number . ' dispatched successfully'
        ]);
    }
}
