<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
