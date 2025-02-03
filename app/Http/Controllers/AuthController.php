<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
