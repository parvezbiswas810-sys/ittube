<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        if (SettingService::bool('maintenance_mode')) {
            return ApiResponse::error(
                SettingService::get('maintenance_message', 'IT TUBE is under maintenance.'),
                ['maintenance' => true],
                503
            );
        }

        return $next($request);
    }
}
