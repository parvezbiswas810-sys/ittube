<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuthSettingsController;
use App\Http\Controllers\Admin\CopyrightController as AdminCopyrightController;
use App\Http\Controllers\Admin\LiveStreamController as AdminLiveStreamController;
use App\Http\Controllers\Admin\PlatformController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ChannelController as AdminChannelController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HomeSectionController as AdminHomeSectionController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VideoController as AdminVideoController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Web\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));
Route::get('/terms', fn () => view('legal.terms'))->name('legal.terms');
Route::get('/privacy', fn () => view('legal.privacy'))->name('legal.privacy');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

Route::get('/media/videos/{video}/stream', [MediaController::class, 'video'])->name('media.video');
Route::get('/media/{path}', [MediaController::class, 'file'])->where('path', '.*')->name('media.file');

Route::middleware('not.installed')->group(function () {
    Route::get('/install', [InstallerController::class, 'index'])->name('install.index');
    Route::post('/install', [InstallerController::class, 'install'])->name('install.run');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:login')->name('login.attempt');
        Route::get('forgot-password', [AdminAuthController::class, 'showForgot'])->name('password.request');
        Route::post('forgot-password', [AdminAuthController::class, 'sendReset'])->middleware('throttle:login')->name('password.email');
        Route::get('reset-password/{token}', [AdminAuthController::class, 'showReset'])->name('password.reset');
        Route::post('reset-password', [AdminAuthController::class, 'reset'])->name('password.update');
    });

    Route::middleware(['installed', 'admin.auth'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/status', [AdminUserController::class, 'status'])->name('users.status');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('channels', [AdminChannelController::class, 'index'])->name('channels.index');
        Route::get('channels/{channel}', [AdminChannelController::class, 'show'])->name('channels.show');
        Route::put('channels/{channel}', [AdminChannelController::class, 'update'])->name('channels.update');
        Route::post('channels/{channel}/status', [AdminChannelController::class, 'status'])->name('channels.status');
        Route::post('channels/{channel}/feature', [AdminChannelController::class, 'feature'])->name('channels.feature');

        Route::get('videos', [AdminVideoController::class, 'index'])->name('videos.index');
        Route::get('videos/{video}', [AdminVideoController::class, 'show'])->name('videos.show');
        Route::put('videos/{video}', [AdminVideoController::class, 'update'])->name('videos.update');
        Route::post('videos/{video}/status', [AdminVideoController::class, 'status'])->name('videos.status');
        Route::post('videos/{video}/feature', [AdminVideoController::class, 'feature'])->name('videos.feature');
        Route::delete('videos/{video}', [AdminVideoController::class, 'destroy'])->name('videos.destroy');

        Route::get('shorts', [AdminVideoController::class, 'shorts'])->name('shorts.index');

        Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('comments', [AdminCommentController::class, 'index'])->name('comments.index');
        Route::post('comments/{comment}/status', [AdminCommentController::class, 'status'])->name('comments.status');
        Route::delete('comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');

        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::post('reports/{report}/resolve', [AdminReportController::class, 'resolve'])->name('reports.resolve');

        Route::get('notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications', [AdminNotificationController::class, 'store'])->name('notifications.store');
        Route::delete('notifications/{notification}', [AdminNotificationController::class, 'destroy'])->name('notifications.destroy');

        Route::get('home-page', [AdminHomeSectionController::class, 'index'])->name('home.index');
        Route::post('home-page', [AdminHomeSectionController::class, 'store'])->name('home.store');
        Route::put('home-page/{section}', [AdminHomeSectionController::class, 'update'])->name('home.update');
        Route::delete('home-page/{section}', [AdminHomeSectionController::class, 'destroy'])->name('home.destroy');
        Route::post('home-page/{section}/videos', [AdminHomeSectionController::class, 'attachVideos'])->name('home.videos');

        Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');

        Route::middleware('admin.perm:auth.manage')->group(function () {
            Route::get('authentication', [AuthSettingsController::class, 'index'])->name('auth.index');
            Route::put('authentication', [AuthSettingsController::class, 'update'])->name('auth.update');
            Route::post('authentication/test', [AuthSettingsController::class, 'test'])->name('auth.test');
            Route::post('authentication/toggle', [AuthSettingsController::class, 'toggle'])->name('auth.toggle');
            Route::post('authentication/reset', [AuthSettingsController::class, 'reset'])->name('auth.reset');
        });

        Route::middleware('admin.perm:copyright.manage')->group(function () {
            Route::get('copyright', [AdminCopyrightController::class, 'index'])->name('copyright.index');
            Route::get('copyright/{report}', [AdminCopyrightController::class, 'show'])->name('copyright.show');
            Route::post('copyright/{report}/resolve', [AdminCopyrightController::class, 'resolve'])->name('copyright.resolve');
        });

        Route::middleware('admin.perm:live.manage')->group(function () {
            Route::get('live-streams', [AdminLiveStreamController::class, 'index'])->name('live.index');
            Route::post('live-streams/{stream}/status', [AdminLiveStreamController::class, 'update'])->name('live.update');
        });

        Route::middleware('admin.perm:platform.manage')->group(function () {
            Route::get('platform', [PlatformController::class, 'index'])->name('platform.index');
            Route::put('platform', [PlatformController::class, 'update'])->name('platform.update');
            Route::post('platform/ads', [PlatformController::class, 'storePlacement'])->name('platform.ads.store');
            Route::post('platform/ads/{placement}/toggle', [PlatformController::class, 'togglePlacement'])->name('platform.ads.toggle');
            Route::delete('platform/ads/{placement}', [PlatformController::class, 'destroyPlacement'])->name('platform.ads.destroy');
            Route::post('platform/monetization/{profile}', [PlatformController::class, 'updateMonetization'])->name('platform.monetization.update');
        });

        Route::get('roles', [AdminRoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [AdminRoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('logs.index');

        Route::get('profile', [AdminProfileController::class, 'index'])->name('profile.index');
        Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [AdminProfileController::class, 'password'])->name('profile.password');
    });
});
