<?php

namespace App\Http\Controllers\API;

use App\Enums\FriendRequestStatus;
use App\Events\FriendRequestAccepted as FriendRequestAcceptedEvent;
use App\Events\FriendRequestReceived as FriendRequestReceivedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFriendRequestRequest;
use App\Models\FriendRequest;
use App\Models\User;
use App\Notifications\FriendRequestAccepted;
use App\Notifications\FriendRequestReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendRequestController extends Controller
{
    public function store(StoreFriendRequestRequest $request)
    {
        $validated = $request->validated();
        $currentUser = $request->user();
        $existingRequest = FriendRequest::where('sender_id', $currentUser->id)
            ->where('recipient_id', $validated['recipient_id'])
            ->exists();

        if ($existingRequest) {
            return response()->json(['message' => 'Request already sent'], 409);
        }

        FriendRequest::create([
            'sender_id' => $currentUser->id,
            'recipient_id' => $validated['recipient_id'],
            'status' => FriendRequestStatus::Pending,
        ]);

        $recipient = User::find($validated['recipient_id']);
        $recipient->notify(new FriendRequestReceived($currentUser));
        event(new FriendRequestReceivedEvent($currentUser));

        return response()->json(['message' => 'Friend request sent successfully'], 201);
    }

    public function accept(FriendRequest $friendRequest, Request $request)
    {
        $currentUser = $request->user();

        DB::transaction(function () use ($friendRequest, $currentUser) {

            $friendRequest->update(['status' => FriendRequestStatus::Accepted]);

            $friendRequest->sender->notify(new FriendRequestAccepted($currentUser, 'sender'));

            $friendRequest->recipient->notify(new FriendRequestAccepted($currentUser, 'recipient'));
            event(new FriendRequestAcceptedEvent($currentUser));
        });

        return response()->json(['message' => 'Friend request accepted']);
    }
}
