<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Builds browser-usable URLs for files on the public disk when the API is served
 * behind a real host (including subpaths like /public) and SPAs use another origin.
 * Avoids Storage::url() which depends on APP_URL and often breaks in production.
 */
final class PublicStorageUrl
{
    /**
     * Absolute URL for a path relative to the public disk root (e.g. profile-photos/x.jpg).
     */
    public static function url(Request $request, string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));

        return rtrim($request->root(), '/').'/storage/'.$relativePath;
    }

    /**
     * Normalize a stored attachment URL or path: relative /storage/..., full URL with wrong host,
     * or bare disk path — into an absolute URL for the current request.
     */
    public static function normalize(Request $request, string $reference): string
    {
        $reference = trim($reference);
        if ($reference === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $reference)) {
            $path = parse_url($reference, PHP_URL_PATH);
            if (is_string($path) && ($pos = strpos($path, '/storage/')) !== false) {
                $rel = substr($path, $pos + strlen('/storage/'));

                return self::url($request, $rel);
            }

            return $reference;
        }

        if (str_starts_with($reference, '/storage/')) {
            return self::url($request, substr($reference, strlen('/storage/')));
        }

        return self::url($request, $reference);
    }

    /**
     * Queue / artisan / mail (no Request).
     */
    public static function urlFromConfig(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));

        return Storage::disk('public')->url($relativePath);
    }
}
