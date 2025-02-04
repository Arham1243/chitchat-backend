<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api')->group(function () {
    Route::get('/auth/me', function (Request $request) {
        return $request->user();
    });
    Route::post('/users/search', [UserController::class, 'search']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{username}', [UserController::class, 'show']);
    Route::post('/update-profile-picture', [UserController::class, 'updateProfilePicture']);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/password/forget', [PasswordResetController::class, 'sendResetLinkEmail']);
    Route::get('/password/reset', [PasswordResetController::class, 'redirectResetPassword'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
    Route::post('/google/callback', [SocialAuthController::class, 'handleGoogleLogin']);
    Route::post('/google/redirect', [SocialAuthController::class, 'index']);
    Route::get('/google/callback', [SocialAuthController::class, 'callback']);
});
