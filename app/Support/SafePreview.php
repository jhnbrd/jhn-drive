<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\HeaderUtils;

class SafePreview
{
    /**
     * Return a trusted inline MIME type based on a conservative extension map.
     */
    public static function mimeType(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            'mp4' => 'video/mp4',
            'm4v' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'ogv' => 'video/ogg',
            'mov' => 'video/quicktime',
            'qt' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            'opus' => 'audio/opus',
            'pdf' => 'application/pdf',
        ][$extension] ?? null;
    }

    public static function inlineHeaders(string $filename, string $mime): array
    {
        return [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                self::safeFallbackName($filename)
            ),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ];
    }

    public static function attachmentHeaders(string $filename, string $mime = 'application/octet-stream'): array
    {
        return [
            'Content-Type' => $mime,
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                self::safeFallbackName($filename)
            ),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ];
    }

    private static function safeFallbackName(string $filename): string
    {
        $fallback = preg_replace('/[^\x20-\x7E]/', '_', $filename) ?: 'preview';

        return str_replace(['%', '/', '\\'], '_', $fallback);
    }
}
