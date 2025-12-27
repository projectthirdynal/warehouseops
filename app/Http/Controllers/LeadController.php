<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use App\Imports\LeadsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    protected $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    /**
     * Export leads to CSV
     */
    public function export(Request $request)
    {
        if (Auth::user()->role !== 'superadmin' && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $query = Lead::with('assignedAgent');

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('agent_id') && $request->agent_id !== '') {
            $query->where('assigned_to', $request->agent_id);
        }

        $leads = $query->get();

        $filename = "leads_export_" . date('Y-m-d_H-i') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($leads) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Phone', 'City', 'Status', 'Assigned Agent', 'Last Called', 'Notes']);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->name,
                    $lead->phone,
                    $lead->city,
                    $lead->status,
                    $lead->assignedAgent ? $lead->assignedAgent->name : 'Unassigned',
                    $lead->last_called_at,
                    $lead->notes
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mine leads from Waybills (Optimized)
     */
    public function mine(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status'     => 'required|string'
        ]);

        $status = $request->status;
        $start = $request->start_date . ' 00:00:00';
        $end = $request->end_date . ' 23:59:59';
        
        $userId = Auth::id();
        $assignedTo = Auth::user()->role === 'agent' ? $userId : null;
        
        // Use Raw SQL for high performance (avoids timeouts with large datasets)
        // PostgreSQL syntax: INSERT ... SELECT ... ON CONFLICT DO NOTHING
        $sql = "
            INSERT INTO leads (name, phone, address, city, status, uploaded_by, assigned_to, created_at, updated_at)
            SELECT 
                receiver_name, 
                receiver_phone, 
                receiver_address, 
                destination, 
                'NEW', 
                :uploaded_by, 
                :assigned_to, 
                NOW(), 
                NOW()
            FROM waybills 
            WHERE status ILIKE :status 
            AND signing_time BETWEEN :start AND :end
            AND receiver_phone IS NOT NULL 
            AND receiver_phone != ''
            ON CONFLICT (phone) WHERE status NOT IN ('SALE', 'DELIVERED', 'CANCELLED') DO NOTHING
        ";

        // Execute the statement
        $affected = DB::statement($sql, [
            'uploaded_by' => $userId,
            'assigned_to' => $assignedTo,
            'status'      => $status,
            'start'       => $start,
            'end'         => $end
        ]);
        
        // NOTE: standard statement() returns true/false, not count. 
        // To get specific inserted count with ON CONFLICT is complex in one go without RETURNING.
        // For UX speed, we just report success. 
        // If exact count is needed, we could run a count() before and after.

        return redirect()->route('leads.index')->with('success', "Mining Process Started/Completed. Please check the list.");
    }

    /**
     * Distribute leads to an agent by count
     */
    public function distribute(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:users,id',
            'count'    => 'required|integer|min:1',
            'status'   => 'nullable|string'
        ]);

        $query = Lead::whereNull('assigned_to');
        
        $status = $request->input('status');

        if ($status === 'REORDER') {
            $query->where('source', 'reorder')->where('status', Lead::STATUS_NEW);
        } elseif ($status === 'NO_ANSWER') {
            $query->where('status', Lead::STATUS_NO_ANSWER);
        } elseif ($status === 'NEW' || empty($status)) {
            $query->where('status', Lead::STATUS_NEW)->where(function($q) {
                $q->whereNull('source')->orWhere('source', 'fresh');
            });
        } else {
            $query->where('status', $status);
        }

        // Get IDs to update
        $idsToUpdate = $query->limit($request->count)->pluck('id');

        if ($idsToUpdate->isEmpty()) {
            $msg = "No unassigned leads found matching criteria";
            if ($status === 'REORDER') $msg .= " (Reorder Type)";
            if ($status === 'NEW') $msg .= " (Fresh Type)";
            return back()->with('error', $msg . ".");
        }

        // Perform Update
        Lead::whereIn('id', $idsToUpdate)->update([
            'assigned_to' => $request->agent_id
        ]);

        return redirect()->route('leads.index')->with('success', "Successfully distributed " . count($idsToUpdate) . " leads.");
    }

    /**
     * Display a listing of the leads.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Lead::with('assignedAgent');

        // Agents only see their own leads
        if ($user->role === 'agent') {
            $query->where('assigned_to', $user->id);
            // Default view for agents is active leads only
            if (!$request->filled('status')) {
                $query->whereNotIn('status', [Lead::STATUS_SALE, Lead::STATUS_DELIVERED, Lead::STATUS_CANCELLED]);
            }
        } else {
            // Admins/TLs can filter by assignment scope
            $scope = $request->input('scope', 'all');
            if ($scope === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif ($scope === 'assigned') {
                $query->whereNotNull('assigned_to');
            }
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Source Filter
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Agent ID Filter (Admin only)
        if ($user->role !== 'agent' && $request->filled('agent_id')) {
            $query->where('assigned_to', $request->agent_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $leads = $query->latest()->paginate(20);
        $agents = User::where('role', 'agent')->get(); // For admin filter/assign

        return view('leads.index', compact('leads', 'agents'));
    }

    /**
     * Show the form for importing leads.
     */
    public function importForm()
    {
        return view('leads.import');
    }

    /**
     * Import leads from Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        // If agent, auto-assign to self. If admin, leave unassigned.
        $assignedTo = Auth::user()->role === 'agent' ? Auth::id() : null;

        Excel::import(new LeadsImport($assignedTo), $request->file('file'));

        return redirect()->route('leads.index')->with('success', 'Leads imported successfully (duplicates skipped).');
    }

    /**
     * Agent updates status (Call workflow).
     */
    public function updateStatus(Request $request, Lead $lead)
    {
        // Enforce ownership
        if (Auth::user()->role === 'agent' && $lead->assigned_to !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|string',
            'note' => 'nullable|string'
        ]);

        try {
            $this->leadService->updateStatus(
                $lead, 
                $request->status, 
                $request->note, 
                Auth::user()
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status updated.');
    }

    /**
     * Admin bulk assigns leads.
     */
    public function assign(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:users,id',
            'lead_ids' => 'required|array', // e.g., [1, 2, 3] or "all_selected" logic
            'lead_ids.*' => 'exists:leads,id'
        ]);

        $count = $this->leadService->assignLeads(
            $request->lead_ids,
            $request->agent_id,
            Auth::user()
        );

        return back()->with('success', "{$count} leads assigned successfully.");
    }
}
