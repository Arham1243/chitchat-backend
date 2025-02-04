<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use UploadImageTrait;

    public function index(Request $request)
    {
        $currentUser = $request->user();
        $users = User::where('status', 'active')
            ->where('id', '!=', $currentUser->id)
            ->get();

        return response()->json($users->toArray(), 200);
    }

    public function show($username)
    {
        $user = User::where('username', $username)
            ->first();

        return response()->json($user->toArray(), 200);
    }

    public function search(Request $request)
    {
        $currentUser = $request->user();
        $users = User::where('status', 'active')
            ->where('id', '!=', $currentUser->id)
            ->where('name', 'like', '%'.$request->search.'%')
            ->get();

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
