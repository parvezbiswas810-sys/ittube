<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\SettingService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function show(Video $video): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        if (! SettingService::bool('downloads_enabled')) {
            return ApiResponse::error('Downloads are currently disabled.', [], 403);
        }

        $user = request()->user('sanctum');

        if (! $video->isVisibleTo($user)) {
            return ApiResponse::error('You are not authorized to download this video.', [], 403);
        }

        if (! $video->allow_download && $video->user_id !== $user?->id) {
            return ApiResponse::error('The creator has disabled downloads for this video.', [], 403);
        }

        $disk = Storage::disk('private');
        if (! $video->video_path || ! $disk->exists($video->video_path)) {
            return ApiResponse::error('The video file is not available for download.', [], 404);
        }

        $extension = pathinfo($video->video_path, PATHINFO_EXTENSION) ?: 'mp4';
        $filename = \Illuminate\Support\Str::slug($video->title) ?: 'it-tube-video';

        return $disk->download($video->video_path, $filename.'.'.$extension, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
