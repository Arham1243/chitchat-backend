<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    public function __construct($conversation)
    {
        $this->conversation = $conversation;
    }

    public function broadcastOn()
    {
        return new Channel('messages');
    }

    public function broadcastAs()
    {
        return 'read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->conversation->user_one_id,
        ];
    }
}
