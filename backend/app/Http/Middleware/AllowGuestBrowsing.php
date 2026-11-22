<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class AllowGuestBrowsing
{
    public function handle(Request $request, Closure $next)
    {
        $authenticated = $request->user('sanctum') !== null;

        if (! $authenticated && ! SettingService::bool('guest_mode_enabled', true)) {
            return ApiResponse::error(
                'Guest browsing is disabled. Sign in to continue.',
                ['guest_mode' => ['Guest mode is turned off by the administrator.']],
                401
            );
        }

        return $next($request);
    }
}
