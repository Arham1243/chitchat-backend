<?php

namespace App\Http\Controllers\API\Auth;

use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8    ',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
        ]);
        $dateOfBirth = Carbon::parse($validated['date_of_birth'])->format('Y-m-d H:i:s');

        $user = User::create([
            'signup_method' => 'email',
            'name' => $validated['full_name'],
            'username' => Str::slug($validated['full_name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'date_of_birth' => $dateOfBirth,
            'gender' => $validated['gender'],
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;
        $expiresIn = now()->addMinutes(config('sanctum.expiration', 60))->timestamp;

        return response()->json([
            'status' => true,
            'message' => 'User logged in successfully',
            'access_token' => $token,
            'expires_in' => $expiresIn,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = User::where(['email' => $credentials['email']])->first();
            $token = $user->createToken('auth-token')->plainTextToken;

            $expiresIn = now()->addMinutes(config('sanctum.expiration', 60))->timestamp;
            UserSession::create([
                'user_id' => $user->id,
                'created_at' => Carbon::now(),
            ]);
            $expirationTime = Carbon::now()->subMinutes(config('sanctum.expiration', 60));
            UserSession::where('created_at', '<', $expirationTime)
                ->delete();
            event(new UserLoggedIn($user));

            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'access_token' => $token,
                'expires_in' => $expiresIn,
            ], 201);
        }

        return response()->json([
            'message' => 'Invalid credentials.',
            'errors' => [
                'email' => [
                    'Invalid credentials.',
                ],
            ],
        ], 401);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens->each(function ($token) {
            $token->delete();
        });
        $expirationTime = Carbon::now()->subMinutes(config('sanctum.expiration', 60));
        UserSession::where('created_at', '<', $expirationTime)
            ->delete();
        UserSession::where('user_id', $user->id)
            ->delete();

        UserSession::where('user_id', $user->id)->delete();

        event(new UserLoggedOut($request->user()));

        return response()->json(['message' => 'Logged out successfully']);
    }
}
