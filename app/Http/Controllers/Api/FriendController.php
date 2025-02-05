<?php

namespace App\Http\Controllers\API;

use App\Enums\FriendRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function getAllFriends(Request $request)
    {
        $currentUser = $request->user();

        $friends = FriendRequest::where(function ($query) use ($currentUser) {
            $query->where('sender_id', $currentUser->id)
                ->where('status', FriendRequestStatus::Accepted);
        })
            ->orWhere(function ($query) use ($currentUser) {
                $query->where('recipient_id', $currentUser->id)
                    ->where('status', FriendRequestStatus::Accepted);
            })
            ->get()
            ->map(function ($friendRequest) use ($currentUser) {
                return $friendRequest->sender_id === $currentUser->id
                    ? $friendRequest->recipient
                    : $friendRequest->sender;
            });

        return response()->json($friends, 200);
    }

    public function friendRequestsReceived(Request $request)
    {
        $currentUser = $request->user();
        $requests = $currentUser->receivedPendingFriendRequests()->with('sender')->get();

        return response()->json($requests, 200);
    }

    public function removeFriend($friendId, Request $request)
    {
        $currentUser = $request->user();

        $friendRequest = FriendRequest::where(function ($query) use ($currentUser, $friendId) {
            $query->where('sender_id', $currentUser->id)
                ->where('recipient_id', $friendId);
        })
            ->orWhere(function ($query) use ($currentUser, $friendId) {
                $query->where('recipient_id', $currentUser->id)
                    ->where('sender_id', $friendId);
            })
            ->first();

        if (! $friendRequest) {
            return response()->json(['message' => 'Friend request not found'], 404);
        }

        $friendRequest->delete();

        return response()->json(['message' => 'Friend removed successfully']);
    }
}
