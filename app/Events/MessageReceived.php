<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public $recipientName;

    public function __construct($message, $recipientName)
    {
        $this->message = $message;
        $this->recipientName = $recipientName;
    }

    public function broadcastOn()
    {
        return new Channel('messages');
    }

    public function broadcastAs()
    {
        return 'new';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message,
            'recipient' => $this->recipientName,
        ];
    }
}
