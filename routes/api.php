<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use App\Http\Controllers\API\Auth\SocialAuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\FriendController;
use App\Http\Controllers\API\FriendRequestController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api')->group(function () {
    Route::get('/auth/me', function (Request $request) {
        return $request->user();
    });

    Route::post('/users/search', [UserController::class, 'search']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{username}', [UserController::class, 'show']);

    Route::get('/friends', [FriendController::class, 'getAllFriends']);
    Route::delete('/friends/{friendId}', [FriendController::class, 'removeFriend']);
    Route::get('/friends/requests/received', [FriendController::class, 'friendRequestsReceived']);
    Route::post('/update-profile-picture', [UserController::class, 'updateProfilePicture']);
    Route::post('/friend-requests', [FriendRequestController::class, 'store']);
    Route::put('/friend-requests/{friendRequest}/accept', [FriendRequestController::class, 'accept']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
    Route::post('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);

    Route::prefix('chat')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/{username}', [ChatController::class, 'show'])->name('chat.show');
        Route::post('/{recipientId}', [ChatController::class, 'sendMessage'])->name('chat.send');
        Route::post('/create', [ChatController::class, 'createConversation'])->name('chat.create');
    });
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
