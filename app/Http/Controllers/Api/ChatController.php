<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->with(['userOne', 'userTwo'])
            ->get()
            ->map(function ($conversation) use ($user) {
                $recipient = ($conversation->user_one_id == $user->id) ? $conversation->userTwo : $conversation->userOne;

                $expiresIn = now()->addMinutes(config('sanctum.expiration', 60))->timestamp;
                $session = UserSession::where('user_id', $recipient->id)->first();
                $recipient->is_online = $session && $session->created_at->timestamp < $expiresIn;

                $conversation->recipient = $recipient;

                $lastMessage = $conversation->messages()->latest()->first();
                if ($lastMessage) {
                    $conversation->last_message = $lastMessage;
                    $conversation->last_message->time = $lastMessage->created_at->format('Y-m-d H:i:s');
                    $conversation->last_message->read_at = $lastMessage->read_at;
                }

                return $conversation;
            });

        return response()->json($conversations, 200);
    }

    public function show(Request $request, $userId)
    {
        $currentUser = $request->user();
        $currentUserId = $currentUser->id;
        $recipientUserId = $userId;

        $conversation = Conversation::where(function ($query) use ($currentUserId, $recipientUserId) {
            $query->where('user_one_id', $currentUserId)
                ->where('user_two_id', $recipientUserId);
        })->orWhere(function ($query) use ($currentUserId, $recipientUserId) {
            $query->where('user_one_id', $recipientUserId)
                ->where('user_two_id', $currentUserId);
        })->first();

        $messages = $conversation ? $conversation->messages()->orderBy('created_at')->get() : null;

        $recipientUser = User::findOrFail($recipientUserId);

        $expiresIn = now()->addMinutes(config('sanctum.expiration', 60))->timestamp;
        $session = UserSession::where('user_id', $recipientUser->id)->first();

        $recipientUser->is_online = $session && $session->created_at->timestamp < $expiresIn;

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
            'recipient' => $recipientUser,
        ], 200);
    }

    public function sendMessage(Request $request, $recipientId)
    {
        $currentUser = $request->user();

        $data = $request->validate([
            'message' => 'required|string',
        ]);

        $conversation = Conversation::where(function ($query) use ($currentUser, $recipientId) {
            $query->where('user_one_id', $currentUser->id)
                ->where('user_two_id', $recipientId);
        })->orWhere(function ($query) use ($currentUser, $recipientId) {
            $query->where('user_one_id', $recipientId)
                ->where('user_two_id', $currentUser->id);
        })->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'user_one_id' => $currentUser->id,
                'user_two_id' => $recipientId,
            ]);
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $currentUser->id,
            'message' => $data['message'],
        ]);

        return redirect()->route('chat.show', $conversation->id);
    }

    public function createConversation(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userOneId = min(Auth::id(), $data['user_id']);
        $userTwoId = max(Auth::id(), $data['user_id']);

        $conversation = Conversation::firstOrCreate([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);

        return redirect()->route('chat.show', $conversation->id);
    }
}
