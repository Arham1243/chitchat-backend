<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()
            ->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function getUnreadNotifications(Request $request)
    {
        $user = $request->user();

        $unreadNotifications = $user->unreadNotifications()
            ->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($unreadNotifications);
    }

    public function markAsRead(DatabaseNotification $notification)
    {
        if ($notification->notifiable_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
