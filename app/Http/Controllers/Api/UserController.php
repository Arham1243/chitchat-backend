<?php

namespace App\Http\Controllers\Api;

use App\Enums\FriendRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use App\Models\UserSession;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use UploadImageTrait;

    public function index(Request $request)
    {
        $currentUserId = $request->user()->id;

        $users = User::where('status', 'active')
            ->where('id', '!=', $currentUserId)
            ->get();

        $users = $users->map(function ($user) use ($currentUserId) {
            $friendRequest = FriendRequest::where(function ($query) use ($user, $currentUserId) {
                $query->where('sender_id', $user->id)
                    ->where('recipient_id', $currentUserId);
            })->orWhere(function ($query) use ($user, $currentUserId) {
                $query->where('sender_id', $currentUserId)
                    ->where('recipient_id', $user->id);
            })->first();

            if ($friendRequest && $friendRequest->status === FriendRequestStatus::Accepted) {
                return null;
            }

            $currentUserFriends = FriendRequest::where('status', FriendRequestStatus::Accepted)
                ->where(function ($query) use ($currentUserId) {
                    $query->where('sender_id', $currentUserId)
                        ->orWhere('recipient_id', $currentUserId);
                })
                ->get()
                ->flatMap(function ($friend) use ($currentUserId) {
                    return $friend->sender_id === $currentUserId
                        ? [$friend->recipient_id]
                        : [$friend->sender_id];
                });

            $userFriends = FriendRequest::where('status', FriendRequestStatus::Accepted)
                ->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                        ->orWhere('recipient_id', $user->id);
                })
                ->get()
                ->flatMap(function ($friend) use ($user) {
                    return $friend->sender_id === $user->id
                        ? [$friend->recipient_id]
                        : [$friend->sender_id];
                });

            $mutualFriendIds = $currentUserFriends->intersect($userFriends);

            $mutualFriends = User::whereIn('id', $mutualFriendIds)->get();

            $user->friend_request_status = $friendRequest ? $friendRequest->status : null;
            $user->mutual_friends = $mutualFriends;

            return $user;
        });

        $users = $users->filter()->values();
        $users = $users->shuffle()->values();

        return response()->json($users, 200);
    }

    public function show(Request $request, $username)
    {
        $currentUserId = $request->user()->id;

        $user = User::where('username', $username)->firstOrFail();
        $expiresIn = now()->addMinutes(config('sanctum.expiration', 60))->timestamp;

        $friendRequest = FriendRequest::where(function ($query) use ($user, $currentUserId) {
            $query->where('sender_id', $user->id)
                ->where('recipient_id', $currentUserId);
        })->orWhere(function ($query) use ($user, $currentUserId) {
            $query->where('sender_id', $currentUserId)
                ->where('recipient_id', $user->id);
        })->first();

        $user->friend_request_status = $friendRequest ? $friendRequest->status : null;

        $session = UserSession::where('user_id', $user->id)->first();
        $user->is_online = $session && $session->created_at->timestamp < $expiresIn;

        $currentUserFriends = FriendRequest::where('status', FriendRequestStatus::Accepted)
            ->where(function ($query) use ($currentUserId) {
                $query->where('sender_id', $currentUserId)
                    ->orWhere('recipient_id', $currentUserId);
            })
            ->get()
            ->flatMap(function ($friend) use ($currentUserId) {
                return $friend->sender_id === $currentUserId
                    ? [$friend->recipient_id]
                    : [$friend->sender_id];
            });

        $userFriends = FriendRequest::where('status', FriendRequestStatus::Accepted)
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->get()
            ->flatMap(function ($friend) use ($user) {
                return $friend->sender_id === $user->id
                    ? [$friend->recipient_id]
                    : [$friend->sender_id];
            });

        $mutualFriendIds = $currentUserFriends->intersect($userFriends);

        $friends = User::whereIn('id', $userFriends)->get();
        $mutualFriends = User::whereIn('id', $mutualFriendIds)->get();

        $user->friends = $friends;
        $user->mutual_friends = $mutualFriends;

        return response()->json($user->toArray(), 200);
    }

    public function search(Request $request)
    {
        $currentUserId = $request->user()->id;
        $users = User::where('status', 'active')
            ->where('id', '!=', $currentUserId)
            ->where('name', 'like', '%'.$request->search.'%')
            ->get();
        $users = $users->map(function ($user) use ($currentUserId) {

            $friendRequest = FriendRequest::where(function ($query) use ($user, $currentUserId) {
                $query->where('sender_id', $user->id)
                    ->where('recipient_id', $currentUserId);
            })->orWhere(function ($query) use ($user, $currentUserId) {
                $query->where('sender_id', $currentUserId)
                    ->where('recipient_id', $user->id);
            })->first();

            $user->friend_request_status = $friendRequest ? $friendRequest->status : null;

            return $user;
        });
        $users = $users->filter()->values();

        return response()->json($users->toArray(), 200);
    }

    public function updateProfilePicture(Request $request)
    {
        $currentUser = $request->user();
        $request->validate([
            'profile_picture' => ['required', function ($attribute, $value, $fail) {
                if (! preg_match('/^data:image\/(\w+);base64,/', $value) && ! $value instanceof \Illuminate\Http\UploadedFile) {
                    $fail('Please upload a valid image file.');
                }
            }],
        ]);
        $profilePicture = $this->simpleUploadImg($request->profile_picture, 'Users/'.$currentUser->username.'/Profiles-Pictures', $currentUser->profile_picture);
        $currentUser->profile_picture = $profilePicture;
        $currentUser->save();

        return response()->json($currentUser->toArray(), 200);
    }
}
