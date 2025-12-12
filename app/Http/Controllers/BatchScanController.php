<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BatchSession;
use App\Models\Waybill;
use App\Models\ScannedWaybill;
use App\Models\BatchScanItem;
use Illuminate\Support\Facades\DB;

class BatchScanController extends Controller
{
    public function startSession(Request $request)
    {
        $request->validate(['scanned_by' => 'required|string']);

        // Close any active session for this user
        BatchSession::where('scanned_by', $request->scanned_by)
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'end_time' => now()]);

        $session = BatchSession::create([
            'scanned_by' => $request->scanned_by,
            'status' => 'active'
        ]);

        return response()->json(['success' => true, 'session_id' => $session->id]);
    }

    public function scan(Request $request)
    {
        $request->validate([
            'waybill_number' => 'required|string',
            'session_id' => 'required|exists:batch_sessions,id'
        ]);

        $waybillNumber = trim($request->waybill_number);
        $sessionId = $request->session_id;

        $session = BatchSession::find($sessionId);
        if ($session->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Session not active']);
        }

        // Check if duplicate in CURRENT session
        $duplicate = BatchScanItem::where('batch_session_id', $sessionId)
            ->where('waybill_number', $waybillNumber)
            ->where('scan_type', 'valid')
            ->exists();

        if ($duplicate) {
            $session->increment('duplicate_count');
            BatchScanItem::create([
                'batch_session_id' => $sessionId,
                'waybill_number' => $waybillNumber,
                'scan_type' => 'duplicate'
            ]);
            
            $waybill = Waybill::where('waybill_number', $waybillNumber)->first();
            return response()->json([
                'success' => true, 
                'scan_type' => 'duplicate', 
                'message' => 'Duplicate scan in current session',
                'waybill_number' => $waybillNumber,
                'waybill' => $waybill
            ]);
        }

        // Check if valid waybill exists
        $waybill = Waybill::where('waybill_number', $waybillNumber)->first();

        if (!$waybill) {
            $session->increment('error_count');
            BatchScanItem::create([
                'batch_session_id' => $sessionId,
                'waybill_number' => $waybillNumber,
                'scan_type' => 'error',
                'error_message' => 'Waybill not found'
            ]);
            return response()->json([
                'success' => true, 
                'scan_type' => 'error', 
                'message' => 'Waybill not found in database',
                'waybill_number' => $waybillNumber
            ]);
        }

        // Check if already dispatched
        if ($waybill->status === 'dispatched') {
            $session->increment('error_count');
            BatchScanItem::create([
                'batch_session_id' => $sessionId,
                'waybill_number' => $waybillNumber,
                'scan_type' => 'error',
                'error_message' => 'Waybill already dispatched'
            ]);
            return response()->json([
                'success' => true, 
                'scan_type' => 'error', 
                'message' => 'Waybill already dispatched',
                'waybill_number' => $waybillNumber
            ]);
        }
        
        // Allow scanning if status is 'pending' OR 'issue_pending'
        // (No specific check needed if we just check != dispatched, but good to be explicit if logic gets complex)

        // Valid scan
        $session->increment('total_scanned');
        BatchScanItem::create([
            'batch_session_id' => $sessionId,
            'waybill_number' => $waybillNumber,
            'scan_type' => 'valid'
        ]);

        return response()->json([
            'success' => true, 
            'scan_type' => 'valid', 
            'message' => 'Waybill scanned successfully',
            'waybill_number' => $waybillNumber,
            'waybill' => $waybill
        ]);
    }
    
    public function status(Request $request) {
        $session = BatchSession::find($request->session_id);
        if (!$session) return response()->json(['success' => false]);
        
        return response()->json([
            'success' => true,
            'counters' => [
                'valid' => $session->total_scanned,
                'duplicate' => $session->duplicate_count,
                'error' => $session->error_count,
                'total' => $session->total_scanned + $session->error_count
            ]
        ]);
    }

    public function pending(Request $request)
    {
        $limit = $request->input('limit', 100);
        
        // Exclude waybills that are currently in an active scanning session
        $activeScans = BatchScanItem::whereHas('session', function($q) {
            $q->where('status', 'active');
        })->pluck('waybill_number');

        $query = Waybill::where('status', '!=', 'dispatched')
            ->where('batch_ready', true)
            ->whereNotIn('waybill_number', $activeScans)
            ->orderBy('created_at', 'desc');
            
        $waybills = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'pending_waybills' => $waybills->items(),
            'count' => $waybills->count(),
            'total_count' => $waybills->total(),
            'page' => $waybills->currentPage(),
            'total_pages' => $waybills->lastPage(),
            'has_next' => $waybills->hasMorePages(),
            'has_prev' => $waybills->currentPage() > 1
        ]);
    }

    public function dispatch(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:batch_sessions,id'
        ]);

        $session = BatchSession::find($request->session_id);
        
        $dispatchedCount = 0;
        $pendingCount = 0;
        
        DB::transaction(function() use ($session, &$dispatchedCount, &$pendingCount) {
            // 1. Get all valid scans from this session
            $validScans = BatchScanItem::where('batch_session_id', $session->id)
                ->where('scan_type', 'valid')
                ->pluck('waybill_number')
                ->toArray();

            // 2. Mark scanned waybills as dispatched
            Waybill::whereIn('waybill_number', $validScans)
                ->update([
                    'status' => 'dispatched',
                    'batch_ready' => false
                ]);

            // 3. Create scanned records
            foreach ($validScans as $waybillNumber) {
                ScannedWaybill::create([
                    'waybill_number' => $waybillNumber,
                    'scanned_by' => $session->scanned_by,
                    'batch_session_id' => $session->id
                ]);
                $dispatchedCount++;
            }

            // 4. Handle REMAINING waybills in the batch (unscanned)
            // These are waybills that were 'batch_ready' but NOT in the valid scans list
            $pendingCount = Waybill::where('batch_ready', true)
                ->whereNotIn('waybill_number', $validScans)
                ->where('status', '!=', 'dispatched') // Safety check
                ->update([
                    'status' => 'issue_pending',
                    'batch_ready' => false,
                    'marked_pending_at' => now()
                ]);

            $session->update(['status' => 'completed', 'end_time' => now()]);
        });

        return response()->json([
            'success' => true,
            'message' => "Successfully dispatched {$dispatchedCount} waybills. {$pendingCount} waybills moved to Pending Section.",
            'session_id' => $session->id // Return session ID for manifest link
        ]);
    }

    public function printManifest($sessionId)
    {
        $session = BatchSession::findOrFail($sessionId);
        
        // Get all valid scans for this session
        // If the session is completed, we can also look at ScannedWaybill
        // But BatchScanItem (valid) is the source of truth for what was in the batch
        $scannedItems = BatchScanItem::where('batch_session_id', $session->id)
            ->where('scan_type', 'valid')
            ->get();
            
        // Get full waybill details
        $waybills = Waybill::whereIn('waybill_number', $scannedItems->pluck('waybill_number'))->get();
        
        // Map waybills to preserve scan order if possible, or just pass collection
        // Let's sort by scan time
        $items = $scannedItems->map(function($item) use ($waybills) {
            $waybill = $waybills->firstWhere('waybill_number', $item->waybill_number);
            return (object) [
                'waybill_number' => $item->waybill_number,
                'scan_time' => $item->scan_time,
                'waybill' => $waybill
            ];
        });

        return view('reports.manifest', [
            'session' => $session,
            'items' => $items,
            'total_count' => $items->count(),
            'date' => now()->format('F d, Y'),
            'time' => now()->format('h:i A')
        ]);
    }
    public function markAsPending(Request $request)
    {
        $request->validate([
            'waybill_number' => 'required|string',
            'session_id' => 'required|exists:batch_sessions,id'
        ]);

        $waybill = Waybill::where('waybill_number', $request->waybill_number)->first();
        if (!$waybill) {
            return response()->json(['success' => false, 'message' => 'Waybill not found']);
        }

        $waybill->update([
            'status' => 'issue_pending',
            'marked_pending_at' => now(),
            'batch_ready' => false // Remove from active batch list
        ]);

        return response()->json(['success' => true, 'message' => 'Waybill marked as pending issue']);
    }

    public function resumePending(Request $request)
    {
        $request->validate(['waybill_number' => 'required|string']);

        $waybill = Waybill::where('waybill_number', $request->waybill_number)->first();
        if (!$waybill) {
            return response()->json(['success' => false, 'message' => 'Waybill not found']);
        }

        $waybill->update([
            'status' => 'pending', // Or restore previous status if tracked
            'marked_pending_at' => null,
            'batch_ready' => true // Add back to active batch list
        ]);

        return response()->json(['success' => true, 'message' => 'Waybill resumed to batch']);
    }

    public function getPendingIssues(Request $request)
    {
        $limit = $request->input('limit', 50);
        
        $issues = Waybill::where('status', 'issue_pending')
            ->orderBy('marked_pending_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'issues' => $issues->items(),
            'total' => $issues->total(),
            'page' => $issues->currentPage(),
            'last_page' => $issues->lastPage()
        ]);
    }
    public function getBatchHistory(Request $request)
    {
        $limit = $request->input('limit', 20);
        
        $sessions = BatchSession::where('status', 'completed')
            ->orderBy('end_time', 'desc')
            ->paginate($limit);
            
        return response()->json([
            'success' => true,
            'sessions' => $sessions->items()
        ]);
    }
}
