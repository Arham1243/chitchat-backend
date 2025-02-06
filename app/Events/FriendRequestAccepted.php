<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new Channel('users');
    }

    public function broadcastAs()
    {
        return 'new';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => 'accepted your friend request.',
            'id' => $this->user->id,
            'name' => $this->user->name,
        ];
    }
}
