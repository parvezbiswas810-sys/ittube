<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (! File::exists(storage_path('app/installed.lock'))) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'IT TUBE is not installed yet. Open /install.'], 503)
                : redirect('/install');
        }

        return $next($request);
    }
}
