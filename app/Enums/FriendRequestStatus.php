<?php

namespace App\Enums;

enum FriendRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
}
