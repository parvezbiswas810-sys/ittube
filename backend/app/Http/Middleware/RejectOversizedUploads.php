<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use App\Support\UploadLimits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PHP rejects oversized files before Laravel sees them and reports an empty
 * field, which surfaces as "The video field is required". Turn that into a
 * clear 413 instead.
 */
class RejectOversizedUploads
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->allFiles() as $key => $file) {
            $files = is_array($file) ? $file : [$file];

            foreach ($files as $item) {
                if (! $item instanceof \Illuminate\Http\UploadedFile) {
                    continue;
                }

                if (in_array($item->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    $message = UploadLimits::tooLargeMessage();

                    return ApiResponse::error($message, [$key => [$message]], 413);
                }
            }
        }

        return $next($request);
    }
}
