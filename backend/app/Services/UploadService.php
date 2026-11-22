<?php

namespace App\Services;

use App\Support\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{
    public function storeVideo(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $relative = 'videos/'.date('Y/m').'/'.Str::random(32).'.'.$extension;
        Storage::disk('private')->putFileAs(dirname($relative), $file, basename($relative));

        $absolute = Storage::disk('private')->path($relative);
        $meta = $this->probe($absolute);

        $thumbnail = null;
        if ($meta['width']) {
            $thumbRelative = 'thumbnails/'.date('Y/m').'/'.Str::random(32).'.jpg';
            if ($this->generateThumbnail($absolute, Storage::disk('public')->path($thumbRelative))) {
                $thumbnail = $thumbRelative;
            }
        }

        return [
            'video_path' => $relative,
            'thumbnail' => $thumbnail,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'duration' => $meta['duration'],
            'resolution' => $meta['width'] ? $meta['width'].'x'.$meta['height'] : null,
            'has_ffmpeg' => $this->ffmpegAvailable(),
        ];
    }

    public function ffmpegAvailable(): bool
    {
        return $this->binary('ffprobe') !== null && $this->binary('ffmpeg') !== null;
    }

    public function probe(string $absolutePath): array
    {
        $ffprobe = $this->binary('ffprobe');
        if (! $ffprobe) {
            return ['duration' => 0, 'width' => null, 'height' => null];
        }

        $cmd = escapeshellcmd($ffprobe)
            .' -v quiet -print_format json -show_format -show_streams '.escapeshellarg($absolutePath).' 2>/dev/null';
        $output = @shell_exec($cmd);
        $json = $output ? json_decode($output, true) : null;

        if (! is_array($json)) {
            return ['duration' => 0, 'width' => null, 'height' => null];
        }

        $duration = (int) round((float) ($json['format']['duration'] ?? 0));
        $width = $height = null;
        foreach ($json['streams'] ?? [] as $stream) {
            if (($stream['codec_type'] ?? null) === 'video') {
                $width = (int) ($stream['width'] ?? 0) ?: null;
                $height = (int) ($stream['height'] ?? 0) ?: null;
                if (! $duration && ! empty($stream['duration'])) {
                    $duration = (int) round((float) $stream['duration']);
                }
                break;
            }
        }

        return ['duration' => $duration, 'width' => $width, 'height' => $height];
    }

    public function generateThumbnail(string $videoPath, string $outputPath, int $atSecond = 1): bool
    {
        $ffmpeg = $this->binary('ffmpeg');
        if (! $ffmpeg) {
            return false;
        }

        if (! is_dir(dirname($outputPath))) {
            @mkdir(dirname($outputPath), 0755, true);
        }

        $cmd = escapeshellcmd($ffmpeg).' -y -ss '.$atSecond.' -i '.escapeshellarg($videoPath)
            .' -frames:v 1 -vf scale=640:-2 -q:v 4 '.escapeshellarg($outputPath).' 2>/dev/null';
        @shell_exec($cmd);

        return is_file($outputPath) && filesize($outputPath) > 0;
    }

    public function extractThumbnail(UploadedFile $file): ?string
    {
        return Media::storeImage($file, 'thumbnails/'.date('Y/m'));
    }

    protected function binary(string $name): ?string
    {
        foreach (['/usr/bin/'.$name, '/usr/local/bin/'.$name, '/opt/homebrew/bin/'.$name] as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        $which = @shell_exec('command -v '.escapeshellarg($name).' 2>/dev/null');

        return $which ? trim($which) : null;
    }
}
