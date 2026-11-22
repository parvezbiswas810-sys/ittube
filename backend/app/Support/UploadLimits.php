<?php

namespace App\Support;

class UploadLimits
{
    public static function maxUploadMb(): int
    {
        $configured = (int) \App\Services\SettingService::get('max_upload_size_mb', 512);
        if ($configured <= 0) {
            $configured = 512;
        }

        $phpLimit = self::phpIniMb('post_max_size');
        $uploadLimit = self::phpIniMb('upload_max_filesize');
        $hardLimits = array_filter([$phpLimit, $uploadLimit]);

        if ($hardLimits === []) {
            return $configured;
        }

        return max(1, min($configured, min($hardLimits)));
    }

    /**
     * Total video size allowed through resumable uploads. Only the admin
     * setting applies; PHP's per-request limits are irrelevant because the
     * file arrives in chunks.
     */
    public static function maxVideoMb(): int
    {
        $configured = (int) \App\Services\SettingService::get('max_upload_size_mb', 512);

        return max(1, min($configured > 0 ? $configured : 512, 2048));
    }

    /**
     * Largest chunk the current PHP configuration can accept in one request.
     */
    public static function safeChunkBytes(): int
    {
        $cap = 8 * 1024 * 1024;
        $limits = array_filter([
            self::phpIniMb('upload_max_filesize'),
            self::phpIniMb('post_max_size'),
        ]);

        if ($limits === []) {
            return $cap;
        }

        $bytes = (int) floor(min($limits) * 1024 * 1024 * 0.8);

        return max(256 * 1024, min($cap, $bytes));
    }

    /**
     * @return list<string>
     */
    public static function allowedFormats(): array
    {
        $raw = (string) \App\Services\SettingService::get('allowed_video_formats', 'mp4,mov,mkv,webm');

        return array_values(array_filter(array_map(
            fn ($f) => strtolower(trim($f)),
            explode(',', $raw)
        )));
    }

    public static function limitsPayload(): array
    {
        return [
            'max_upload_mb' => self::maxVideoMb(),
            'max_direct_upload_mb' => self::maxUploadMb(),
            'chunk_size' => self::safeChunkBytes(),
            'allowed_formats' => self::allowedFormats(),
        ];
    }

    public static function postMaxMb(): ?int
    {
        return self::phpIniMb('post_max_size');
    }

    public static function tooLargeMessage(): string
    {
        $limits = [];
        $post = self::postMaxMb();
        $upload = self::phpIniMb('upload_max_filesize');

        if ($upload !== null) {
            $limits[] = 'upload_max_filesize '.$upload.' MB';
        }
        if ($post !== null) {
            $limits[] = 'post_max_size '.$post.' MB';
        }

        $message = 'The uploaded file is too large for this server';

        if ($limits !== []) {
            $message .= ' (PHP '.implode(', ', $limits).')';
        }

        return $message.'. The app will retry with resumable chunk uploads; you can also raise these limits in cPanel > MultiPHP INI Editor.';
    }

    public static function phpIniMb(string $key): ?int
    {
        $raw = trim((string) ini_get($key));
        if ($raw === '' || $raw === '-1') {
            return null;
        }

        $value = (int) $raw;
        $unit = strtolower(substr($raw, -1));

        return match ($unit) {
            'g' => $value * 1024,
            'm' => $value,
            'k' => (int) ceil($value / 1024),
            default => (int) ceil($value / 1048576),
        };
    }
}
