<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(string $action, ?string $description = null, ?Model $target = null): void
    {
        try {
            AdminActivityLog::create([
                'admin_id' => auth('admin')->id(),
                'action' => $action,
                'description' => $description,
                'target_type' => $target ? class_basename($target) : null,
                'target_id' => $target?->getKey(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 250),
            ]);
        } catch (\Throwable $e) {
            // Logging must never break the request.
            report($e);
        }
    }
}
