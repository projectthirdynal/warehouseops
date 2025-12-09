<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications for the fetch poll
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'unread_count' => 0,
                'notifications' => []
            ]);
        }

        $notifications = $user->unreadNotifications()->limit(10)->get();
        
        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        
        if ($user) {
            $notification = $user->notifications()->find($id);
            if ($notification) {
                $notification->markAsRead();
            }
        }
        
        return response()->json(['success' => true]);
    }
}
