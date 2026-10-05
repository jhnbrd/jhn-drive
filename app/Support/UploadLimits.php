<?php

namespace App\Support;

class UploadLimits
{
    public static function uploadMaxBytes(): int
    {
        return self::parseIniSize((string) ini_get('upload_max_filesize'));
    }

    public static function postMaxBytes(): int
    {
        return self::parseIniSize((string) ini_get('post_max_size'));
    }

    public static function effectiveFileMaxBytes(): int
    {
        return min(self::uploadMaxBytes(), self::postMaxBytes());
    }

    public static function maxFileUploads(): int
    {
        return max(1, (int) ini_get('max_file_uploads'));
    }

    /**
     * Browser-safe upload configuration for progress UI and preflight checks.
     */
    public static function clientConfig(): array
    {
        $fileMax = self::effectiveFileMaxBytes();
        $postMax = self::postMaxBytes();

        return [
            'max_file_bytes' => $fileMax,
            'max_request_bytes' => $postMax,
            'max_files_per_request' => self::maxFileUploads(),
            'max_file_human' => self::formatBytes($fileMax),
            'max_request_human' => self::formatBytes($postMax),
        ];
    }

    public static function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), $precision).' '.$units[$power];
    }

    private static function parseIniSize(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }

        if (! preg_match('/^(\d+(?:\.\d+)?)\s*([KMGTP]?)B?$/i', $value, $matches)) {
            return (int) $value;
        }

        $number = (float) $matches[1];
        $unit = strtoupper($matches[2]);
        $powers = ['' => 0, 'K' => 1, 'M' => 2, 'G' => 3, 'T' => 4, 'P' => 5];

        return (int) floor($number * (1024 ** $powers[$unit]));
    }
}
