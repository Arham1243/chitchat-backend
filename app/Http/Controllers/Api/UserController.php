<?php

namespace App\Http\Controllers\API;

use App\Enums\FriendRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
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

            $user->friend_request_status = $friendRequest ? $friendRequest->status : null;

            return $user;
        });
        $users = $users->filter()->values();

        return response()->json($users, 200);
    }

    public function show(Request $request, $username)
    {
        $currentUserId = $request->user()->id;
        $user = User::where('username', $username)
            ->first();

        $friendRequest = FriendRequest::where(function ($query) use ($user, $currentUserId) {
            $query->where('sender_id', $user->id)
                ->where('recipient_id', $currentUserId);
        })->orWhere(function ($query) use ($user, $currentUserId) {
            $query->where('sender_id', $currentUserId)
                ->where('recipient_id', $user->id);
        })->first();

        $user->friend_request_status = $friendRequest ? $friendRequest->status : null;

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

            if ($friendRequest && $friendRequest->status === FriendRequestStatus::Accepted) {
                return null;
            }

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

    public function getAllFriends(Request $request)
    {
        $currentUser = $request->user();

        $friends = FriendRequest::where(function ($query) use ($currentUser) {
            $query->where('sender_id', $currentUser->id)
                ->where('status', FriendRequestStatus::Accepted);
        })
            ->orWhere(function ($query) use ($currentUser) {
                $query->where('recipient_id', $currentUser->id)
                    ->where('status', FriendRequestStatus::Accepted);
            })
            ->get()
            ->map(function ($friendRequest) use ($currentUser) {
                return $friendRequest->sender_id === $currentUser->id
                    ? $friendRequest->recipient
                    : $friendRequest->sender;
            });

        return response()->json($friends, 200);
    }
}
