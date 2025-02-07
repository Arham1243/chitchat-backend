<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->with(['userTwo', 'messages'])
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
                    $conversation->last_message->is_mine = $lastMessage->sender_id === $user->id;
                }
                $conversation->unread_count = $conversation->messages->filter(function ($message) use ($user) {
                    return is_null($message->read_at) && $message->sender_id !== $user->id;
                })->count();
                $conversation->messages = $conversation->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'message' => $message->message,
                        'read_at' => $message->read_at,
                    ];
                });

                return $conversation;
            });

        return response()->json($conversations, 200);
    }

    public function show(Request $request, $username)
    {
        $currentUser = $request->user();
        $currentUserId = $currentUser->id;

        $recipientUser = User::where('username', $username)->firstOrFail();
        $recipientUserId = $recipientUser->id;

        $conversation = Conversation::where(function ($query) use ($currentUserId, $recipientUserId) {
            $query->where('user_one_id', $currentUserId)
                ->where('user_two_id', $recipientUserId);
        })->orWhere(function ($query) use ($currentUserId, $recipientUserId) {
            $query->where('user_one_id', $recipientUserId)
                ->where('user_two_id', $currentUserId);
        })->first();

        $messages = $conversation ? $conversation->messages()->orderBy('created_at')->get() : null;

        if ($conversation) {
            $lastMessage = $conversation->messages()->latest()->first();
            if ($lastMessage) {
                $conversation->last_message = $lastMessage;
                $conversation->last_message->time = $lastMessage->created_at->format('Y-m-d H:i:s');
                $conversation->last_message->read_at = $lastMessage->read_at;
                $conversation->last_message->is_mine = $lastMessage->sender_id === $currentUserId;
            }
            $conversation->unread_count = $messages ? $messages->filter(function ($message) use ($currentUserId) {
                return is_null($message->read_at) && $message->sender_id !== $currentUserId;
            })->count() : 0;
        }

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

        return response()->json(['message' => 'Message sent successfully'], 200);
    }

    public function markMessagesAsRead(Request $request, $conversationId)
    {
        $messagesIds = $request->messagesIds;
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->messages()->whereIn('id', $messagesIds)->update(['read_at' => now()]);

        return response()->json(['message' => 'Message marked as read'], 200);
    }
}
