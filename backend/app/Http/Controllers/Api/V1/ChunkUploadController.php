<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\UploadSession;
use App\Services\SettingService;
use App\Services\UploadService;
use App\Services\VideoService;
use App\Support\ApiResponse;
use App\Support\UploadLimits;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resumable uploads for large files.
 *
 * Shared hosting usually caps a single POST (post_max_size), so the app
 * sends a file in fixed-size chunks and the server appends them in order.
 */
class ChunkUploadController extends Controller
{
    public function limits()
    {
        return ApiResponse::success(
            UploadLimits::limitsPayload() + ['uploads_enabled' => SettingService::bool('uploads_enabled', true)],
            'Upload limits loaded.'
        );
    }

    public function init(Request $request)
    {
        if (! SettingService::bool('uploads_enabled', true)) {
            return ApiResponse::error('Uploads are currently disabled.', [], 403);
        }

        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:10000'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['nullable', 'in:video,short'],
            'visibility' => ['nullable', 'in:public,unlisted,private'],
            'tags' => ['nullable', 'string', 'max:400'],
            'allow_comments' => ['nullable', 'boolean'],
        ]);

        $maxBytes = UploadLimits::maxVideoMb() * 1024 * 1024;
        if ($data['size'] > $maxBytes) {
            return ApiResponse::error(
                'This file is '.$this->human($data['size']).' but the limit is '.UploadLimits::maxVideoMb().' MB.',
                ['size' => ['File exceeds the '.UploadLimits::maxVideoMb().' MB limit.']],
                422
            );
        }

        $extension = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        $allowed = UploadLimits::allowedFormats();
        if ($allowed !== [] && ! in_array($extension, $allowed, true)) {
            return ApiResponse::error(
                'Unsupported video format: '.$extension.'. Allowed: '.implode(', ', $allowed).'.',
                ['filename' => ['Allowed formats: '.implode(', ', $allowed).'.']],
                422
            );
        }

        $session = UploadSession::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'original_name' => $data['filename'],
            'mime_type' => $data['mime_type'] ?? null,
            'total_size' => $data['size'],
            'total_chunks' => $data['total_chunks'],
            'received_chunks' => 0,
            'status' => 'uploading',
            'payload' => [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'type' => $data['type'] ?? 'video',
                'visibility' => $data['visibility'] ?? 'public',
                'tags' => $data['tags'] ?? null,
                'allow_comments' => (bool) ($request->input('allow_comments', true)),
            ],
        ]);

        $this->directory($session->uuid, true);

        return ApiResponse::success([
            'upload_id' => $session->uuid,
            'chunk_size' => UploadLimits::safeChunkBytes(),
            'total_chunks' => $session->total_chunks,
            'received_chunks' => 0,
            'progress' => 0,
        ], 'Upload session created.', 201);
    }

    public function status(Request $request, string $uuid)
    {
        $session = $this->session($request, $uuid);
        if (! $session) {
            return ApiResponse::error('Upload session not found.', [], 404);
        }

        return ApiResponse::success([
            'upload_id' => $session->uuid,
            'status' => $session->status,
            'total_chunks' => $session->total_chunks,
            'received_chunks' => $session->received_chunks,
            'received_indexes' => $this->receivedIndexes($session),
            'progress' => $session->progress(),
            'video_id' => $session->video_id,
        ], 'Upload status loaded.');
    }

    public function chunk(Request $request, string $uuid)
    {
        $session = $this->session($request, $uuid);
        if (! $session) {
            return ApiResponse::error('Upload session not found.', [], 404);
        }

        if ($session->status !== 'uploading') {
            return ApiResponse::error('This upload session is '.$session->status.' and cannot accept more chunks.', [], 409);
        }

        $data = $request->validate([
            'index' => ['required', 'integer', 'min:0'],
            'chunk' => ['required', 'file', 'max:'.(int) ceil(UploadLimits::safeChunkBytes() / 1024) + 64],
        ]);

        if ($data['index'] >= $session->total_chunks) {
            return ApiResponse::error('Chunk index is out of range.', ['index' => ['Must be below '.$session->total_chunks.'.']], 422);
        }

        $request->file('chunk')->storeAs($this->directory($session->uuid), $data['index'].'.part', 'local');

        $received = count($this->receivedIndexes($session));
        $session->update(['received_chunks' => $received]);

        return ApiResponse::success([
            'upload_id' => $session->uuid,
            'received_chunks' => $received,
            'total_chunks' => $session->total_chunks,
            'progress' => $session->progress(),
        ], 'Chunk stored.');
    }

    public function complete(Request $request, string $uuid)
    {
        $session = $this->session($request, $uuid);
        if (! $session) {
            return ApiResponse::error('Upload session not found.', [], 404);
        }

        if ($session->status === 'completed') {
            return ApiResponse::error('This upload was already completed.', ['upload_id' => [$session->uuid]], 409);
        }

        $indexes = $this->receivedIndexes($session);
        if (count($indexes) !== $session->total_chunks) {
            return ApiResponse::error(
                'Upload is incomplete: '.count($indexes).' of '.$session->total_chunks.' chunks received.',
                ['chunks' => ['Missing chunk indexes: '.implode(', ', array_diff(range(0, $session->total_chunks - 1), $indexes)).'.']],
                422
            );
        }

        $session->update(['status' => 'assembling']);

        $extension = strtolower(pathinfo($session->original_name, PATHINFO_EXTENSION)) ?: 'mp4';
        $tmpDir = 'tmp';
        $tmpRelative = $tmpDir.'/'.$session->uuid.'.'.$extension;
        $absolute = Storage::disk('local')->path($tmpRelative);

        if (! is_dir(dirname($absolute))) {
            @mkdir(dirname($absolute), 0755, true);
        }

        $out = fopen($absolute, 'wb');
        if ($out === false) {
            $session->update(['status' => 'failed']);

            return ApiResponse::error('Could not create the assembled file. Check storage permissions.', [], 500);
        }

        try {
            for ($i = 0; $i < $session->total_chunks; $i++) {
                $part = Storage::disk('local')->path($this->directory($session->uuid).'/'.$i.'.part');
                if (! is_file($part)) {
                    throw new \RuntimeException('Missing chunk '.$i);
                }
                $in = fopen($part, 'rb');
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        } catch (\Throwable $e) {
            fclose($out);
            @unlink($absolute);
            $session->update(['status' => 'failed']);

            return ApiResponse::error('Could not assemble the upload: '.$e->getMessage(), [], 500);
        }

        fclose($out);

        $assembledSize = (int) filesize($absolute);
        if ($assembledSize !== $session->total_size) {
            @unlink($absolute);
            $session->update(['status' => 'failed']);

            return ApiResponse::error(
                'Assembled size mismatch ('.$assembledSize.' bytes vs '.$session->total_size.' expected). Please upload again.',
                [],
                422
            );
        }

        $payload = $session->payload ?? [];
        $payload['title'] ??= pathinfo($session->original_name, PATHINFO_FILENAME);

        $request->validate([
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            $file = new UploadedFile(
                $absolute,
                $session->original_name,
                $session->mime_type ?: (mime_content_type($absolute) ?: 'video/mp4'),
                null,
                true
            );

            $result = VideoService::createFromUpload(
                $request->user(),
                $file,
                $payload,
                $request->file('thumbnail'),
                app(UploadService::class)
            );
        } catch (\Throwable $e) {
            @unlink($absolute);
            $session->update(['status' => 'failed']);
            report($e);

            return ApiResponse::error('Video processing failed: '.$e->getMessage(), [], 500);
        }

        @unlink($absolute);
        Storage::disk('local')->deleteDirectory($this->directory($session->uuid));

        $session->update([
            'status' => 'completed',
            'video_id' => $result['video']->id,
            'completed_at' => now(),
        ]);

        $video = $result['video']->load(['channel', 'category', 'tags']);

        return ApiResponse::success([
            'video' => new VideoResource($video),
            'ffmpeg_available' => $result['ffmpeg_available'],
        ], $video->status === 'published' ? 'Video uploaded successfully.' : 'Video uploaded and is pending review.', 201);
    }

    public function cancel(Request $request, string $uuid)
    {
        $session = $this->session($request, $uuid);
        if (! $session) {
            return ApiResponse::error('Upload session not found.', [], 404);
        }

        Storage::disk('local')->deleteDirectory($this->directory($session->uuid));
        $session->delete();

        return ApiResponse::success(null, 'Upload cancelled.');
    }

    private function session(Request $request, string $uuid): ?UploadSession
    {
        return UploadSession::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->first();
    }

    private function directory(string $uuid, bool $create = false): string
    {
        $path = 'chunks/'.$uuid;

        if ($create && ! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->makeDirectory($path);
        }

        return $path;
    }

    /**
     * @return list<int>
     */
    private function receivedIndexes(UploadSession $session): array
    {
        $files = Storage::disk('local')->files($this->directory($session->uuid));

        $indexes = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (str_ends_with($name, '.part') && ctype_digit(substr($name, 0, -5))) {
                $indexes[] = (int) substr($name, 0, -5);
            }
        }

        sort($indexes);

        return $indexes;
    }

    private function human(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : round($bytes / 1024).' KB';
    }
}
