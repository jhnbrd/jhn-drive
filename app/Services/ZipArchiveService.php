<?php

namespace App\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class ZipArchiveService
{
    public function __construct(private readonly DrivePathService $paths) {}

    public function createFromDirectory(string $directory): string
    {
        $temporaryBase = tempnam(sys_get_temp_dir(), 'jhn_zip_');
        if ($temporaryBase === false) {
            throw new RuntimeException('Could not allocate a temporary ZIP archive.');
        }

        $zipPath = $temporaryBase.'.zip';
        @unlink($temporaryBase);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create ZIP archive.');
        }

        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            $normalizedRoot = rtrim(str_replace('\\', '/', $directory), '/');

            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $path = $file->getRealPath();
                if ($path === false || ! $this->paths->isWithin($directory, $path)) {
                    continue;
                }

                $normalizedPath = str_replace('\\', '/', $path);
                $relativePath = ltrim(substr($normalizedPath, strlen($normalizedRoot)), '/');
                $zip->addFile($path, $relativePath);
            }
        } finally {
            $zip->close();
        }

        return $zipPath;
    }
}
