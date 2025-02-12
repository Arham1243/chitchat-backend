<?php

namespace App\Http\Controllers\Api;

use App\Enums\FriendRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use App\Models\UserSession;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;
use App\Traits\HandlesFriends;


class UserController extends Controller
{
    use UploadImageTrait;
    use HandlesFriends;


    public function index(Request $request)
    {
        $currentUserId = $request->user()->id;

        $users = User::where('status', 'active')
            ->where('id', '!=', $currentUserId)
            ->get();

        $users = $users->map(function ($user) use ($currentUserId) {
            $friendRequest = FriendRequest::where(function ($query) use ($user, $currentUserId) {
                $query->whereIn('sender_id', [$user->id, $currentUserId])
                    ->whereIn('recipient_id', [$user->id, $currentUserId]);
            })->first();

            if ($friendRequest && $friendRequest->status === FriendRequestStatus::Accepted) {
                return null;
            }

            $currentUserFriends = $this->getFriends($currentUserId);

            $userFriends = $this->getFriends($user->id);

            $mutualFriendIds = $currentUserFriends->intersect($userFriends);

            $user->friend_request_status = $this->getFriendRequestStatus($currentUserId, $user->id);
            $user->mutual_friends = User::whereIn('id', $mutualFriendIds)->get();

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


        $user->friend_request_status = $this->getFriendRequestStatus($currentUserId, $user->id);

        $session = UserSession::where('user_id', $user->id)->first();
        $user->is_online = $session && $session->created_at->timestamp < $expiresIn;

        $currentUserFriends = $this->getFriends($currentUserId);

        $userFriends = $this->getFriends($user->id);

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
            ->where('name', 'like', '%' . $request->search . '%')
            ->get();

        $users = $users->map(function ($user) use ($currentUserId) {
            $user->friend_request_status = $this->getFriendRequestStatus($currentUserId, $user->id);
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
        $profilePicture = $this->simpleUploadImg($request->profile_picture, 'Users/' . $currentUser->username . '/Profiles-Pictures', $currentUser->profile_picture);
        $currentUser->profile_picture = $profilePicture;
        $currentUser->save();

        return response()->json($currentUser->toArray(), 200);
    }
}
