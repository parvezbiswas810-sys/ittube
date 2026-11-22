<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media
{
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url('/media/'.ltrim($path, '/'));
    }

    public static function storeImage(?UploadedFile $file, string $directory, ?string $oldPath = null): ?string
    {
        if (! $file) {
            return $oldPath;
        }

        if ($oldPath && ! Str::startsWith($oldPath, ['http://', 'https://'])) {
            Storage::disk('public')->delete($oldPath);
        }

        $name = Str::random(24).'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');

        return $file->storeAs($directory, $name, 'public');
    }

    /**
     * Stores evidence documents on the private disk so they are never publicly reachable.
     */
    public static function storeDocument(?UploadedFile $file, string $directory): ?string
    {
        if (! $file) {
            return null;
        }

        $name = Str::random(24).'.'.strtolower($file->getClientOriginalExtension() ?: 'bin');

        return $file->storeAs($directory, $name, 'private');
    }

    public static function deletePublic(?string $path): void
    {
        if ($path && ! Str::startsWith($path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($path);
        }
    }
}
