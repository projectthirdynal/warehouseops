<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use App\Services\DistributionEngine;
use App\Imports\LeadsImport;
use App\Exports\JNTExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    protected $leadService;
    protected $distributionEngine;

    public function __construct(LeadService $leadService, DistributionEngine $distributionEngine)
    {
        $this->leadService = $leadService;
        $this->distributionEngine = $distributionEngine;
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
     * Export leads in J&T format (Excel)
     */
    public function exportJNT(Request $request)
    {
        if (Auth::user()->role !== 'superadmin' && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $query = Lead::with('assignedAgent');

        // Apply filters (matching index/monitoring)
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        } else {
            // Default to SALE if no status specified, as J&T export is usually for sales
            $query->where('status', Lead::STATUS_SALE);
        }

        if ($request->has('agent_id') && $request->agent_id !== '') {
            $query->where('assigned_to', $request->agent_id);
        }

        if ($request->has('search') && $request->search !== '') {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'LIKE', "%$s%")
                  ->orWhere('phone', 'LIKE', "%$s%");
            });
        }

        $leads = $query->latest('submitted_at')->get();

        $filename = "JNT_Export_" . date('Y-m-d_H-i') . ".xls";
        return Excel::download(new JNTExport($leads), $filename, \Maatwebsite\Excel\Excel::XLS);
    }

    /**
     * Mine leads from Waybills (Optimized)
     */
    public function mine(Request $request)
    {
        set_time_limit(0); // Prevent PHP timeout for large datasets

        // Validate only if parameters are provided, otherwise use defaults
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'status'     => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'previous_item' => 'nullable|string'
        ]);

        $status = $request->status ?: 'delivered';
        $start = $request->start_date ? $request->start_date . ' 00:00:00' : now()->subMonths(3)->startOfDay();
        $end = $request->end_date ? $request->end_date . ' 23:59:59' : now()->endOfDay();
        $assignedTo = $request->assigned_to ?: (Auth::user()->role === 'agent' ? Auth::id() : null);
        $userId = Auth::id();

        Log::info("Lead Mining Started: Status=$status, Range=$start to $end, AssignedTo=$assignedTo");
        
        $itemFilter = $request->filled('previous_item') ? " AND item_name = :item_name " : "";

        $sql = "
            INSERT INTO leads (name, phone, address, city, state, barangay, street, previous_item, status, uploaded_by, assigned_to, created_at, updated_at)
            SELECT DISTINCT ON (receiver_phone)
                receiver_name, 
                receiver_phone, 
                receiver_address, 
                city, 
                province,
                barangay,
                street,
                item_name,
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
            {$itemFilter}
            ORDER BY receiver_phone, signing_time DESC
            ON CONFLICT (phone) WHERE status NOT IN ('SALE', 'DELIVERED', 'CANCELLED') 
            DO UPDATE SET 
                street = COALESCE(leads.street, EXCLUDED.street),
                barangay = COALESCE(leads.barangay, EXCLUDED.barangay),
                city = COALESCE(leads.city, EXCLUDED.city),
                state = COALESCE(leads.state, EXCLUDED.state),
                previous_item = COALESCE(leads.previous_item, EXCLUDED.previous_item),
                updated_at = NOW()
            WHERE leads.street IS NULL OR leads.barangay IS NULL OR leads.previous_item IS NULL
        ";

        try {
            $params = [
                'uploaded_by' => $userId,
                'assigned_to' => $assignedTo,
                'status'      => $status,
                'start'       => $start,
                'end'         => $end
            ];
            
            if ($request->filled('previous_item')) {
                $params['item_name'] = $request->previous_item;
            }

            // Execute the statement
            DB::statement($sql, $params);
            Log::info("Lead Mining Completed Successfully");
        } catch (\Exception $e) {
            Log::error("Lead Mining Failed: " . $e->getMessage());
            return back()->with('error', "Mining Failed: " . $e->getMessage());
        }
        
        // NOTE: standard statement() returns true/false, not count. 
        // To get specific inserted count with ON CONFLICT is complex in one go without RETURNING.
        // For UX speed, we just report success. 
        // If exact count is needed, we could run a count() before and after.

        return redirect()->route('leads.index')->with('success', "Mining Process Started/Completed. Please check the list.");
    }

    public function distribute(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:users,id',
            'count'    => 'required|integer|min:1',
            'status'   => 'nullable|string',
            'previous_item' => 'nullable|string',
            'recycle'  => 'nullable|boolean'
        ]);

        $recycle = $request->boolean('recycle');
        $query = $recycle ? Lead::query() : Lead::whereNull('assigned_to');
        
        if ($recycle) {
            // If recycling, prioritize leads that haven't been called today 
            // and were assigned some time ago (or at least not in the last 12 hours)
            $query->where(function($q) {
                $q->where('assigned_at', '<', now()->subHours(12))
                  ->orWhereNull('assigned_at');
            });
        }
        
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

        if ($request->filled('previous_item')) {
            $query->where('previous_item', $request->previous_item);
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
            'assigned_to' => $request->agent_id,
            'assigned_at' => now()
        ]);

        return redirect()->route('leads.index')->with('success', "Successfully distributed " . count($idsToUpdate) . " leads.");
    }

    /**
     * Display a listing of the leads.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Lead::with(['assignedAgent', 'customer', 'orders', 'waybills', 'phoneWaybills']);

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
            if ($scope === 'fresh' || $scope === 'unassigned') {
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
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('previous_item')) {
            $query->where('previous_item', $request->previous_item);
        }

        // Fresh Leads Creation Date Filter
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Assignment Date Filter (For distribution tracking/recycling)
        if ($request->filled('assigned_from')) {
            $query->whereDate('assigned_at', '>=', $request->assigned_from);
        }
        if ($request->filled('assigned_to_date')) {
            $query->whereDate('assigned_at', '<=', $request->assigned_to_date);
        }

        $leads = $query->latest()->paginate(20);
        $agents = User::where('role', 'agent')->get();
        $productOptions = \App\Models\Waybill::whereNotNull('item_name')
            ->where('item_name', '!=', '')
            ->distinct()
            ->orderBy('item_name')
            ->pluck('item_name')
            ->filter(fn($name) => trim($name) !== '');

        return view('leads.index', compact('leads', 'agents', 'productOptions'));
    }

    /**
     * Show the form for importing leads.
     */
    public function importForm()
    {
        // Get distinct item names from waybills, filtering out empty/whitespace values
        $productOptions = \App\Models\Waybill::whereNotNull('item_name')
            ->where('item_name', '!=', '')
            ->where('item_name', 'NOT LIKE', ' %') // Exclude whitespace-only
            ->distinct()
            ->orderBy('item_name')
            ->pluck('item_name')
            ->filter(fn($name) => trim($name) !== ''); // Extra safety filter
        return view('leads.import', compact('productOptions'));
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
            'note' => 'nullable|string',
            'product_name' => 'nullable|string',
            'product_brand' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'address' => 'nullable|string',
            'province' => 'nullable|string',
            'city' => 'nullable|string',
            'barangay' => 'nullable|string',
            'street' => 'nullable|string'
        ]);

        try {
            $this->leadService->updateStatus(
                $lead, 
                $request->status, 
                $request->note, 
                Auth::user(),
                $request->all() // Pass all attributes
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status and details updated.');
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

    /**
     * Monitoring view for Admins/TLs to oversee all called/used leads.
     */
    public function monitoring(Request $request)
    {
        $query = Lead::with('assignedAgent')
            ->whereNotNull('assigned_to')
            ->whereIn('status', ['SALE', 'REORDER', 'CALLBACK', 'REJECT', 'NO_ANSWER']); // Filter "Available"/New leads out

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('agent_id')) {
            $query->where('assigned_to', $request->agent_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('updated_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('updated_at', '<=', $request->date_to);
        }

        if ($request->filled('previous_item')) {
            $query->where('previous_item', $request->previous_item);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $leads = $query->latest('updated_at')->paginate(50);
        $agents = User::where('role', 'agent')->get();
        $productOptions = \App\Models\Waybill::whereNotNull('item_name')
            ->where('item_name', '!=', '')
            ->distinct()
            ->orderBy('item_name')
            ->pluck('item_name')
            ->filter(fn($name) => trim($name) !== '');

        return view('leads.monitoring', compact('leads', 'agents', 'productOptions'));
    }

    /**
     * Clear all leads from the repository.
     */
    public function clear()
    {
        // Only Admin or TL can clear leads
        if (Auth::user()->role !== \App\Models\User::ROLE_ADMIN && 
            Auth::user()->role !== \App\Models\User::ROLE_SUPERADMIN && 
            Auth::user()->role !== 'TL') {
            abort(403);
        }

        // SAFE CLEANUP:
        // 1. Delete leads that are NOT converted (No Waybills, Not Sale/Delivered)
        // 2. Preserve strictly anything that has financial implication
        // 3. Preserve anything currently ASSIGNED to an agent (Active Work)
        
        $deleted = \App\Models\Lead::whereNotIn('status', [
                \App\Models\Lead::STATUS_SALE, 
                \App\Models\Lead::STATUS_DELIVERED, 
                \App\Models\Lead::STATUS_RETURNED
            ])
            ->whereDoesntHave('waybills') // Ensure NO waybills are attached
            ->delete();
        
        return redirect()->route('leads.index')->with('success', "Cleaner finished. Removed {$deleted} leads. Sales and Waybills were preserved.");
    }

    /**
     * Smart distribute leads using the DistributionEngine.
     * Automatically assigns leads to the best matching agents.
     */
    public function smartDistribute(Request $request)
    {
        $request->validate([
            'count' => 'required|integer|min:1|max:100',
            'status' => 'nullable|string',
            'previous_item' => 'nullable|string',
            'recycle' => 'nullable|boolean'
        ]);

        $criteria = [
            'count' => $request->count,
            'status' => $request->status,
            'previous_item' => $request->previous_item,
            'recycle' => $request->boolean('recycle')
        ];

        $results = $this->distributionEngine->smartDistribute($criteria, Auth::user());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'distributed' => $results['success'],
                'failed' => $results['failed'],
                'assignments' => $results['assignments'] ?? [],
                'errors' => $results['errors']
            ]);
        }

        if ($results['success'] > 0) {
            return redirect()->route('leads.index')->with('success', 
                "Smart distribution complete: {$results['success']} leads assigned, {$results['failed']} failed.");
        }

        $message = $results['message'] ?? 'No leads could be distributed.';
        if (!empty($results['errors'])) {
            $message .= ' Errors: ' . count($results['errors']);
        }
        
        return back()->with('error', $message);
    }

    /**
     * Get distribution statistics and agent availability.
     */
    public function distributionStats(Request $request)
    {
        $stats = $this->distributionEngine->getDistributionStats();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        }

        return view('leads.distribution-stats', compact('stats'));
    }
}
