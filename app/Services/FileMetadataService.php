<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class FileMetadataService
{
    public function mimeType(string $path, ?string $displayName = null): string
    {
        $extension = strtolower(pathinfo($displayName ?? $path, PATHINFO_EXTENSION));
        $known = $this->knownMimeType($extension);

        if ($known !== null) {
            return $known;
        }

        if (function_exists('mime_content_type') && is_file($path)) {
            return mime_content_type($path) ?: 'application/octet-stream';
        }

        return 'application/octet-stream';
    }

    public function knownMimeType(string $extension): ?string
    {
        return [
            'mp4' => 'video/mp4',
            'm4v' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'ogv' => 'video/ogg',
            'mov' => 'video/quicktime',
            'qt' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            'flv' => 'video/x-flv',
            'wmv' => 'video/x-ms-wmv',
            '3gp' => 'video/3gpp',
            'ts' => 'video/mp2t',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            'avif' => 'image/avif',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            'wma' => 'audio/x-ms-wma',
            'opus' => 'audio/opus',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'json' => 'application/json',
            'zip' => 'application/zip',
        ][strtolower($extension)] ?? null;
    }

    public function previewCategory(string $extension, string $mime): string
    {
        $extension = strtolower($extension);

        if ($extension === 'svg') {
            return 'other';
        }

        if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'avif'], true)) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/') || in_array($extension, ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', '3gp', 'ts'], true)) {
            return 'video';
        }

        if (str_starts_with($mime, 'audio/') || in_array($extension, ['mp3', 'wav', 'm4a', 'flac', 'aac', 'opus'], true)) {
            return 'audio';
        }

        return $extension === 'pdf' ? 'pdf' : 'other';
    }

    public function category(string $extension, string $mime): string
    {
        $extension = strtolower($extension);

        if ($extension === 'svg') {
            return 'other';
        }

        if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'avif', 'tiff'], true)) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/') || in_array($extension, ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'qt', 'avi', 'mkv', 'm4v', 'flv', 'wmv', '3gp', 'ts'], true)) {
            return 'video';
        }

        if (str_starts_with($mime, 'audio/') || in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac', 'wma', 'opus'], true)) {
            return 'audio';
        }

        if ($extension === 'pdf' || $mime === 'application/pdf') {
            return 'pdf';
        }

        if (in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'iso'], true)) {
            return 'archive';
        }

        if (in_array($extension, ['doc', 'docx', 'odt', 'rtf', 'txt', 'md', 'xls', 'xlsx', 'csv', 'ppt', 'pptx'], true)) {
            return 'document';
        }

        if (in_array($extension, ['js', 'ts', 'jsx', 'tsx', 'php', 'py', 'html', 'css', 'json', 'yaml', 'yml', 'xml', 'sql', 'sh', 'bat', 'c', 'cpp', 'rs', 'go'], true)) {
            return 'code';
        }

        return 'other';
    }

    public function formatBytes(int $bytes, int $precision = 1): string
    {
        return User::formatBytes($bytes, $precision);
    }

    public function formatTimeAgo(int $timestamp): string
    {
        $difference = time() - $timestamp;

        if ($difference < 60) {
            return 'just now';
        }

        if ($difference < 3600) {
            return floor($difference / 60).'m ago';
        }

        if ($difference < 86400) {
            return floor($difference / 3600).'h ago';
        }

        if ($difference < 2592000) {
            return floor($difference / 86400).'d ago';
        }

        return date('M j, Y', $timestamp);
    }

    public function breadcrumbs(string $path): array
    {
        $breadcrumbs = [['name' => 'My Drive', 'path' => '']];
        $accumulated = '';

        foreach (array_filter(explode('/', $path), fn (string $part) => $part !== '') as $part) {
            $accumulated = $accumulated === '' ? $part : $accumulated.'/'.$part;
            $breadcrumbs[] = ['name' => $part, 'path' => $accumulated];
        }

        return $breadcrumbs;
    }

    public function sanitizeFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $cleanName = trim((string) preg_replace('/[\\\\\/\:\*\?\"\<\>\|\x00-\x1F]/', '_', $name), ' .');

        if ($cleanName === '') {
            $cleanName = 'file_'.time();
        }

        return $extension === '' ? $cleanName : $cleanName.'.'.$extension;
    }

    public function uniqueFilename(string $directory, string $filename, string $diskName = 'local_drive'): string
    {
        $disk = Storage::disk($diskName);
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $suffix = $extension === '' ? '' : '.'.$extension;
        $candidate = $filename;
        $counter = 1;

        while ($disk->exists($directory === '' ? $candidate : $directory.'/'.$candidate)) {
            $candidate = "{$baseName} ({$counter}){$suffix}";
            $counter++;
        }

        return $candidate;
    }
}
