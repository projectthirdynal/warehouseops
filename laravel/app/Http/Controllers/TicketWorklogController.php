<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketWorklog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketWorklogController extends Controller
{
    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
        // Authorization: Only admin/staff/assignee can log work
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin', 'it_staff']) && $ticket->assigned_to !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'action_type' => 'required|string',
            'description' => 'required|string',
            'time_spent' => 'nullable|integer|min:0',
            'status_after' => 'nullable|string',
            // Attachments validation can be added here if needed
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                 $path = $file->store('ticket-attachments', 'public');
                 $attachments[] = $path;
            }
        }

        $worklog = TicketWorklog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'action_type' => $request->action_type,
            'description' => $request->description,
            'time_spent' => $request->time_spent ?? 0,
            'status_after' => $request->status_after ?? $ticket->status,
            'attachments' => $attachments,
        ]);

        return back()->with('success', 'Worklog added successfully.');
    }
}
