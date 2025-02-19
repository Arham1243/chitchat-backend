<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        $notifications = $currentUser->notifications()
            ->with('notifiable')
            ->orderBy('created_at', 'asc')
            ->get();

        $notifications = $notifications->filter(function ($notification) use ($currentUser) {
            return $notification->data['id'] !== $currentUser->id;
        });

        $notifications = $notifications->map(function ($notification) {
            $sender = User::find($notification->data['id']);
            $notification->sender = $sender;

            return $notification;
        });

        return response()->json($notifications);
    }

    public function getUnreadNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = $user->unreadNotifications()
            ->with('notifiable')
            ->orderBy('created_at', 'asc')
            ->get();

        $notifications = $notifications->map(function ($notification) {
            $sender = User::find($notification->data['id']);
            $notification->sender = $sender;

            return $notification;
        });

        return response()->json($notifications);
    }

    public function markAsRead(DatabaseNotification $notification)
    {
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }
}
