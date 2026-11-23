<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves stored media paths (product and category images) to public URLs on
 * the configured media disk, so moving photos to S3 and a CDN is a config change.
 */
class Media
{
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        // Absolute URLs and root-relative paths (e.g. bundled placeholders) pass through.
        if (Str::startsWith($path, ['http://', 'https://', '//', '/'])) {
            return $path;
        }

        return Storage::disk(config('shop.media_disk'))->url($path);
    }
}
