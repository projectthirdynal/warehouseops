<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QcController extends Controller
{
    /**
     * Display the Quality Control Dashboard/Queue
     */
    public function index()
    {
        // Enforce Role
        if (!auth()->user()->isChecker() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized. QA access only.');
        }

        // Stats
        $stats = [
            'pending' => Lead::where('status', Lead::STATUS_SALE)->where('qc_status', Lead::QC_STATUS_PENDING)->count(),
            'passed_today' => Lead::where('qc_status', Lead::QC_STATUS_PASSED)->whereDate('qc_at', now())->count(),
            'failed_today' => Lead::where('qc_status', Lead::QC_STATUS_FAILED)->whereDate('qc_at', now())->count(),
        ];

        // Queue
        $leads = Lead::where('status', Lead::STATUS_SALE) // Only Sales
                     ->where('qc_status', Lead::QC_STATUS_PENDING) // Only Pending QC
                     ->with(['user', 'customer']) // Eager Load
                     ->orderBy('updated_at', 'asc') // FIFO
                     ->paginate(20);

        return view('qc.index', compact('stats', 'leads'));
    }

    /**
     * Approve a sale (Pass QC)
     */
    public function approve(Request $request, Lead $lead)
    {
        $lead->update([
            'qc_status' => Lead::QC_STATUS_PASSED,
            'qc_by' => Auth::id(),
            'qc_at' => now(),
            'qc_notes' => $request->qc_notes
        ]);

        return response()->json(['success' => true, 'message' => 'Sale Approved (QC Passed)']);
    }

    /**
     * Reject a sale (Fail QC -> Cancel Lead)
     */
    public function reject(Request $request, Lead $lead)
    {
        $request->validate(['qc_notes' => 'required|string']);

        DB::transaction(function () use ($lead, $request) {
            $lead->update([
                'qc_status' => Lead::QC_STATUS_FAILED,
                'status'    => Lead::STATUS_CANCELLED, // Cancel the lead
                'qc_by'     => Auth::id(),
                'qc_at'     => now(),
                'qc_notes'  => $request->qc_notes,
                'reject_reason' => 'QC Failed (Fraud/SOP)',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Sale Rejected & Cancelled']);
    }

    /**
     * Recycle a sale (Back to Pool)
     */
    public function recycle(Request $request, Lead $lead)
    {
        $request->validate(['qc_notes' => 'required|string']);

        DB::transaction(function () use ($lead, $request) {
            $lead->update([
                'qc_status' => Lead::QC_STATUS_RECYCLED,
                'status'    => Lead::STATUS_NEW, // Reset to New
                'assigned_to' => null, // Unassign
                'qc_by'     => Auth::id(),
                'qc_at'     => now(),
                'qc_notes'  => $request->qc_notes
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Lead Recycled (Back to Pool)']);
    }
}
