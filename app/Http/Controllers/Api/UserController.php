<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use App\Traits\HandlesFriends;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use HandlesFriends;
    use UploadImageTrait;

    public function index(Request $request)
    {
        $currentUserId = $request->user()->id;

        // Get the IDs of the current user's friends
        $currentUserFriends = $this->getFriends($currentUserId);

        // Fetch users who are active and not the current user or their friends
        $users = User::where('status', 'active')
            ->where('id', '!=', $currentUserId)
            ->whereNotIn('id', $currentUserFriends)
            ->get();

        $users = $users->map(function ($user) use ($currentUserId) {
            $currentUserFriends = $this->getFriends($currentUserId);
            $userFriends = $this->getFriends($user->id);

            $mutualFriendIds = $currentUserFriends->intersect($userFriends);

            $mutualFriends = User::whereIn('id', $mutualFriendIds)->get();

            $user->friend_request_status = $this->getFriendRequestStatus($currentUserId, $user->id);
            $user->mutual_friends = $mutualFriends;

            return $user;
        });

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
            ->where('name', 'like', '%'.$request->search.'%')
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
        $profilePicture = $this->simpleUploadImg($request->profile_picture, 'Users/'.$currentUser->username.'/Profiles-Pictures', $currentUser->profile_picture);
        $currentUser->profile_picture = $profilePicture;
        $currentUser->save();

        return response()->json($currentUser->toArray(), 200);
    }
}
