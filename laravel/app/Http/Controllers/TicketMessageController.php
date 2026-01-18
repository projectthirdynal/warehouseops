<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketMessageController extends Controller
{
    /**
     * Store a reply/message on a ticket.
     */
    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
        // Authorization
        $user = Auth::user();
        $itRoles = ['superadmin', 'admin'];
        if ($ticket->user_id !== $user->id && !in_array($user->role, $itRoles)) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048'
        ]);

        // Handle File Uploads ( Placeholder )
        $attachments = [];
        if ($request->hasFile('attachments')) {
            // foreach logic here to store and populate $attachments
        }

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'attachments' => $attachments, // Casted to array in model
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'message' => nl2br(e($message->message)),
                    'created_at_human' => $message->created_at->diffForHumans(),
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'initial' => strtoupper(substr($message->user->name, 0, 1)),
                    ],
                    'is_me' => true,
                ]
            ]);
        }

        return back()->with('success', 'Message posted.');
    }

    /**
     * Handle "User is typing" event.
     */
    public function typing(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        // Auth check... can reuse middleware or policy later, simple check for now
        $user = Auth::user();
        if ($ticket->user_id !== $user->id && !in_array($user->role, ['superadmin', 'admin'])) abort(403);

        // Store typing status in Cache for 3 seconds
        // Key: ticket_{id}_typing_{user_id}
        \Illuminate\Support\Facades\Cache::put("ticket_{$ticketId}_typing_{$user->id}", $user->name, now()->addSeconds(3));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Fetch new messages for a ticket (JSON).
     */
    public function fetchMessages(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
        // Authorization
        $user = Auth::user();
        $itRoles = ['superadmin', 'admin'];
        if ($ticket->user_id !== $user->id && !in_array($user->role, $itRoles)) {
            abort(403);
        }

        $lastId = $request->query('last_id', 0);

        $messages = TicketMessage::where('ticket_id', $ticket->id)
            ->where('id', '>', $lastId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        // Check who is typing
        $typingUsers = [];
        // Check ticket owner
        if ($user->id !== $ticket->user_id && \Illuminate\Support\Facades\Cache::has("ticket_{$ticketId}_typing_{$ticket->user_id}")) {
             $typingUsers[] = $ticket->user->name;
        }
        // Check assigned staff
        if ($ticket->assigned_to && $user->id !== $ticket->assigned_to && \Illuminate\Support\Facades\Cache::has("ticket_{$ticketId}_typing_{$ticket->assigned_to}")) {
             $typingUsers[] = $ticket->assignedTo->name;
        }
        // Check other admins? Might be overkill to scan all admins. 
        // For now, checking the "other party" is usually enough (Agent vs Assignee/Admin).

        // If ticket isn't assigned, check if ANY admin is typing? 
        // Simplifying: Scan active admins (cached active users list) would be ideal, 
        // but let's just assume we want to know if the "other person" in the convo is typing.
        // Let's infer "other person" as: if I am Agent, check Assignee. If I am Assignee, check Agent.
        
        // Better approach: Get all users who have messaged this ticket + ticket owner + assignee
        $participantIds = $ticket->messages()->pluck('user_id')->unique();
        $participantIds->push($ticket->user_id);
        if ($ticket->assigned_to) $participantIds->push($ticket->assigned_to);
        
        $uniqueParticipants = $participantIds->unique()->reject(fn($id) => $id === $user->id);
        
        foreach($uniqueParticipants as $pId) {
             if (\Illuminate\Support\Facades\Cache::has("ticket_{$ticketId}_typing_{$pId}")) {
                 $name = \App\Models\User::find($pId)?->name;
                 if ($name && !in_array($name, $typingUsers)) {
                     $typingUsers[] = $name;
                 }
             }
        }
            
        return response()->json([
            'messages' => $messages->map(function($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'message' => nl2br(e($msg->message)),
                    'created_at_human' => $msg->created_at->diffForHumans(),
                    'user' => [
                        'id' => $msg->user->id,
                        'name' => $msg->user->name,
                        'initial' => strtoupper(substr($msg->user->name, 0, 1)),
                    ],
                    'is_me' => $msg->user_id === $user->id
                ];
            }),
            'typing' => $typingUsers // Array of names
        ]);
    }
}
