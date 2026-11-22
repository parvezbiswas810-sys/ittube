<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ChunkUploadController;
use App\Http\Controllers\Api\V1\ChannelController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\CopyrightController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\DownloadController;
use App\Http\Controllers\Api\V1\HistoryController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\LiveStreamController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PlaylistController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\ShortController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\VideoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['installed', 'throttle:api'])->group(function () {
    Route::get('settings/public', [SettingController::class, 'publicSettings']);
    Route::get('app/config', [SettingController::class, 'appConfig']);

    Route::middleware('guest.browse')->group(function () {
        Route::get('home', [HomeController::class, 'index']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('search', [SearchController::class, 'index']);

        Route::get('videos', [VideoController::class, 'index']);
        Route::get('videos/{id}/related', [VideoController::class, 'related'])->whereNumber('id');
        Route::get('videos/{id}/comments', [CommentController::class, 'index'])->whereNumber('id');
        Route::get('videos/{id}', [VideoController::class, 'show'])->whereNumber('id');

        Route::get('shorts', [ShortController::class, 'index']);
        Route::get('shorts/{id}', [ShortController::class, 'show'])->whereNumber('id');

        Route::get('channels', [ChannelController::class, 'index']);
        Route::get('channels/{handle}', [ChannelController::class, 'show'])->where('handle', '^(?!mine$)[A-Za-z0-9_.]+$');

        Route::get('live-streams', [LiveStreamController::class, 'index']);
        Route::get('live-streams/{id}', [LiveStreamController::class, 'show'])->whereNumber('id');
        Route::get('live-streams/{id}/chat', [LiveStreamController::class, 'chat'])->whereNumber('id');
    });

    Route::get('videos/mine', [VideoController::class, 'mine'])->middleware('auth:sanctum');
    Route::post('videos/{id}/view', [VideoController::class, 'view'])->whereNumber('id');

    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:login');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/google', [AuthController::class, 'google'])->middleware('throttle:login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');
    Route::post('copyright/reports', [CopyrightController::class, 'store'])->middleware('throttle:reports');

    Route::get('auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')->name('api.email.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/email/resend', [AuthController::class, 'sendVerification'])->middleware('throttle:login');
        Route::post('auth/delete-account', [AuthController::class, 'deleteAccount']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('profile/password', [ProfileController::class, 'updatePassword']);

        Route::post('videos', [VideoController::class, 'store'])->middleware('throttle:uploads');
        Route::get('videos/upload/limits', [ChunkUploadController::class, 'limits']);
        Route::post('videos/chunk/init', [ChunkUploadController::class, 'init'])->middleware('throttle:uploads');
        Route::get('videos/chunk/{uuid}', [ChunkUploadController::class, 'status']);
        Route::post('videos/chunk/{uuid}', [ChunkUploadController::class, 'chunk']);
        Route::post('videos/chunk/{uuid}/complete', [ChunkUploadController::class, 'complete']);
        Route::delete('videos/chunk/{uuid}', [ChunkUploadController::class, 'cancel']);
        Route::put('videos/{id}', [VideoController::class, 'update']);
        Route::delete('videos/{id}', [VideoController::class, 'destroy']);
        Route::post('videos/{id}/like', [VideoController::class, 'like']);
        Route::post('videos/{id}/dislike', [VideoController::class, 'dislike']);
        Route::post('videos/{id}/report', [VideoController::class, 'report'])->middleware('throttle:reports');

        Route::get('channels/mine', [ChannelController::class, 'mine']);
        Route::post('channels', [ChannelController::class, 'store']);
        Route::put('channels/{id}', [ChannelController::class, 'update']);

        Route::get('subscriptions', [SubscriptionController::class, 'index']);
        Route::get('subscriptions/feed', [SubscriptionController::class, 'feed']);
        Route::post('channels/{id}/subscribe', [SubscriptionController::class, 'subscribe']);
        Route::delete('channels/{id}/subscribe', [SubscriptionController::class, 'unsubscribe']);

        Route::post('videos/{id}/comments', [CommentController::class, 'store']);
        Route::put('comments/{id}', [CommentController::class, 'update']);
        Route::delete('comments/{id}', [CommentController::class, 'destroy']);
        Route::post('comments/{id}/like', [CommentController::class, 'like']);
        Route::post('comments/{id}/report', [CommentController::class, 'report'])->middleware('throttle:reports');

        Route::get('playlists', [PlaylistController::class, 'index']);
        Route::post('playlists', [PlaylistController::class, 'store']);
        Route::get('playlists/{id}', [PlaylistController::class, 'show']);
        Route::put('playlists/{id}', [PlaylistController::class, 'update']);
        Route::delete('playlists/{id}', [PlaylistController::class, 'destroy']);
        Route::post('playlists/{id}/videos', [PlaylistController::class, 'addVideo']);
        Route::delete('playlists/{id}/videos/{videoId}', [PlaylistController::class, 'removeVideo']);
        Route::post('playlists/{id}/reorder', [PlaylistController::class, 'reorder']);

        Route::get('history', [HistoryController::class, 'index']);
        Route::delete('history/{id}', [HistoryController::class, 'destroy']);
        Route::delete('history', [HistoryController::class, 'clear']);
        Route::post('history/pause', [HistoryController::class, 'togglePause']);

        Route::post('auth/device-tokens', [DeviceTokenController::class, 'store']);
        Route::delete('auth/device-tokens', [DeviceTokenController::class, 'destroy']);

        Route::get('videos/{video}/download', [DownloadController::class, 'show'])
            ->whereNumber('video')
            ->middleware('throttle:downloads');

        Route::get('copyright/reports/mine', [CopyrightController::class, 'mine']);

        Route::get('live-streams/mine', [LiveStreamController::class, 'mine']);
        Route::post('live-streams', [LiveStreamController::class, 'store']);
        Route::put('live-streams/{id}', [LiveStreamController::class, 'update']);
        Route::get('live-streams/{id}/credentials', [LiveStreamController::class, 'credentials']);
        Route::post('live-streams/{id}/start', [LiveStreamController::class, 'start']);
        Route::post('live-streams/{id}/end', [LiveStreamController::class, 'end']);
        Route::post('live-streams/{id}/chat', [LiveStreamController::class, 'sendChat'])->middleware('throttle:comments');

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/preferences', [NotificationController::class, 'preferences']);
        Route::put('notifications/preferences', [NotificationController::class, 'updatePreferences']);
        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('notifications/{id}/read', [NotificationController::class, 'read']);
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

        Route::post('reports', [ReportController::class, 'store'])->middleware('throttle:reports');
        Route::get('reports/mine', [ReportController::class, 'mine']);
    });
});
