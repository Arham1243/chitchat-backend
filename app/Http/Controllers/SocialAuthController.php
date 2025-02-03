<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function index()
    {
        $social = 'google';
        $redirectUrl = Socialite::driver($social)->stateless()->redirect()->getTargetUrl();

        return response()->json(['redirect_url' => $redirectUrl]);
    }

    /**
     * Obtain the user information from the social platform.
     *
     * @param  string  $social
     * @return \Illuminate\Http\Response
     */
    public function callback()
    {
        $social = 'google';
        try {
            $socialUser = Socialite::driver($social)->stateless()->user();

            $existingUser = User::where('email', $socialUser->email)->first();

            if ($existingUser) {
                $existingUser->update([
                    'name' => $socialUser->name,
                    'email' => $socialUser->email ?? $socialUser->nickname.'@'.$social.'.com',
                    'social_id' => $socialUser->id,
                    'signup_method' => $social,
                    'social_token' => $socialUser->token,
                    'profile_picture' => $socialUser->avatar,
                ]);

                $token = $existingUser->createToken('google-token')->plainTextToken;

                $expiresIn = now()->addMinutes(config('sanctum.expiration', 60))->timestamp;
            } else {
                $user = User::updateOrCreate([
                    'social_id' => $socialUser->id,
                ], [
                    'signup_method' => $social,
                    'name' => $socialUser->name,
                    'email' => $socialUser->email ?? $socialUser->nickname.'@'.$social.'.com', // Handle missing email
                    'social_token' => $socialUser->token,
                    'profile_picture' => $socialUser->avatar,
                ]);

                $token = $user->createToken('google-token')->plainTextToken;

                $expiresIn = now()->addMinutes(config('sanctum.expiration', 60))->timestamp;
            }

            $url = env('APP_FRONTEND_URL').'/auth/login/?access_token='.$token.'&expires_in='.$expiresIn;

            return redirect()->away($url);

        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}
