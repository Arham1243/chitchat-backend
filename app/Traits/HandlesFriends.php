<?php

namespace App\Traits;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;

trait HandlesFriends
{
    public function getFriendRequestStatus($senderId, $recipientId)
    {

        $friendRequest = FriendRequest::where(function ($query) use ($senderId, $recipientId) {
            $query->where('sender_id', $senderId)
                ->where('recipient_id', $recipientId);
        })->orWhere(function ($query) use ($senderId, $recipientId) {
            $query->where('sender_id', $recipientId)
                ->where('recipient_id', $senderId);
        })->first();

        return $friendRequest ? $friendRequest->status : null;
    }

    public function getFriends($userId)
    {
        $friends = FriendRequest::where('status', FriendRequestStatus::Accepted)
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('recipient_id', $userId);
            })
            ->get()
            ->flatMap(function ($friend) use ($userId) {
                return $friend->sender_id === $userId
                    ? [$friend->recipient_id]
                    : [$friend->sender_id];
            });

        return !empty($friends) ? $friends : [];
    }
}
