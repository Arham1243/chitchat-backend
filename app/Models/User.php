<?php

namespace App\Models;

use App\Enums\FriendRequestStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'date_of_birth',
        'gender',
        'profile_picture',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getProfilePictureAttribute($value)
    {
        return $value ? asset($value) : asset('assets/images/placeholder-user.png');
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function sentFriendRequests()
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    public function receivedFriendRequests()
    {
        return $this->hasMany(FriendRequest::class, 'recipient_id');
    }

    public function receivedPendingFriendRequests()
    {
        return $this->hasMany(FriendRequest::class, 'recipient_id')->where('status', FriendRequestStatus::Pending);
    }

    public function friendRequests()
    {
        return $this->sentFriendRequests()->union($this->receivedFriendRequests());
    }

    public function isOnline()
    {
        return UserSession::where('user_id', $this->id)->exists();
    }

    public function conversations()
    {
        return Conversation::where('user_one_id', $this->id)
            ->orWhere('user_two_id', $this->id);
    }
}
