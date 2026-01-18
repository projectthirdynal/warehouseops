<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::with(['category', 'user', 'assignedTo']);

        // Filter by Status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // If not Admin/IT, only show own tickets
        // Assuming 'admin' and 'superadmin' are IT roles. 
        // We really should have a permission like 'tickets_manage', but for now role is okay.
        // Or if we implemented the Gates in migration...
        // Let's rely on CheckRole middleware or checks here.
        
        $itRoles = ['superadmin', 'admin']; 
        if (!in_array($user->role, $itRoles)) {
            $query->where('user_id', $user->id);
        } else {
             // Admin filters
             if ($request->has('priority') && $request->priority !== 'all') {
                 $query->where('priority', $request->priority);
             }
             if ($request->has('assigned_to') && $request->assigned_to !== 'all') {
                 if ($request->assigned_to === 'unassigned') {
                     $query->whereNull('assigned_to');
                 } else {
                     $query->where('assigned_to', $request->assigned_to);
                 }
             }
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // For filter dropdowns
        $categories = TicketCategory::all();

        return view('tickets.index', compact('tickets', 'categories'));
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create()
    {
        $categories = TicketCategory::all();
        return view('tickets.create', compact('categories'));
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'category_id' => 'required|exists:ticket_categories,id',
            'priority' => 'required|in:low,normal,high,critical',
            'description' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        // Generate Ref No: TIC-YYYYMMDD-XXXX
        $date = now()->format('Ymd');
        $count = Ticket::whereDate('created_at', now()->toDateString())->count() + 1;
        $refNo = 'TIC-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $ticket = Ticket::create([
            'ref_no' => $refNo,
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'open',
        ]);

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket created successfully.');
    }

    /**
     * Display the specified ticket (Chat View).
     */
    public function show($id)
    {
        $ticket = Ticket::with(['messages.user', 'category', 'user', 'assignedTo'])->findOrFail($id);

        $user = Auth::user();
        $itRoles = ['superadmin', 'admin'];

        // Authorization check
        if ($ticket->user_id !== $user->id && !in_array($user->role, $itRoles)) {
            abort(403, 'Unauthorized access to this ticket.');
        }

        return view('tickets.show', compact('ticket'));
    }

    /**
     * Update the ticket (Status, Assignee).
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        
        // Only IT can update status/assignee directly
        $user = Auth::user();
        $itRoles = ['superadmin', 'admin'];
        if (!in_array($user->role, $itRoles)) {
            abort(403);
        }

        if ($request->has('status')) {
            $ticket->status = $request->status;
        }

        if ($request->has('priority')) {
            $ticket->priority = $request->priority;
        }

        if ($request->has('assigned_to')) {
             $ticket->assigned_to = $request->assigned_to;
        }

        $ticket->save();

        return back()->with('success', 'Ticket updated successfully.');
    }
}
