<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function file(Request $request, string $path)
    {
        abort_if(str_contains($path, '..'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function video(Request $request, Video $video): StreamedResponse
    {
        $user = $request->user('sanctum') ?? auth('web')->user();

        if (! $video->isVisibleTo($user)) {
            abort(403, 'You are not authorized to view this video.');
        }

        $disk = Storage::disk('private');
        abort_unless($video->video_path && $disk->exists($video->video_path), 404);

        $absolute = $disk->path($video->video_path);
        $size = filesize($absolute);
        $mime = $video->mime_type ?: 'video/mp4';
        $start = 0;
        $end = $size - 1;
        $status = 200;

        $range = $request->header('Range');
        if ($range && preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
            $start = $matches[1] !== '' ? (int) $matches[1] : 0;
            $end = $matches[2] !== '' ? (int) $matches[2] : $size - 1;
            $end = min($end, $size - 1);
            if ($start > $end) {
                abort(416);
            }
            $status = 206;
        }

        $length = $end - $start + 1;

        return response()->stream(function () use ($absolute, $start, $length) {
            $handle = fopen($absolute, 'rb');
            fseek($handle, $start);
            $remaining = $length;
            while ($remaining > 0 && ! feof($handle)) {
                $chunk = fread($handle, min(1024 * 512, $remaining));
                if ($chunk === false) {
                    break;
                }
                echo $chunk;
                $remaining -= strlen($chunk);
                flush();
            }
            fclose($handle);
        }, $status, [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Content-Length' => (string) $length,
            'Content-Range' => $status === 206 ? "bytes {$start}-{$end}/{$size}" : null,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
