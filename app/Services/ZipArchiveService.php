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

    /**
     * Create a bounded archive from user-selected files and directories.
     *
     * @param  list<string>  $selectedPaths
     */
    public function createFromPaths(
        string $root,
        array $selectedPaths,
        int $maxBytes = 2147483648,
        int $maxEntries = 10000,
    ): string {
        $realRoot = realpath($root);
        if ($realRoot === false) {
            throw new RuntimeException('Drive storage is unavailable.');
        }

        $temporaryBase = tempnam(sys_get_temp_dir(), 'jhn_selection_');
        if ($temporaryBase === false) {
            throw new RuntimeException('Could not allocate a temporary ZIP archive.');
        }

        $zipPath = $temporaryBase.'.zip';
        @unlink($temporaryBase);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create ZIP archive.');
        }

        $normalizedRoot = rtrim(str_replace('\\', '/', $realRoot), '/');
        $added = [];
        $totalBytes = 0;
        $entryCount = 0;

        $addFile = function (string $path) use (
            $zip,
            $normalizedRoot,
            &$added,
            &$totalBytes,
            &$entryCount,
            $maxBytes,
            $maxEntries,
        ): void {
            $realPath = realpath($path);
            if ($realPath === false || is_link($path) || ! $this->paths->isWithin($normalizedRoot, $realPath)) {
                return;
            }

            $entryName = ltrim(substr(str_replace('\\', '/', $realPath), strlen($normalizedRoot)), '/');
            if ($entryName === '' || isset($added[$entryName])) {
                return;
            }

            $size = filesize($realPath);
            $totalBytes += $size === false ? 0 : $size;
            $entryCount++;

            if ($totalBytes > $maxBytes || $entryCount > $maxEntries) {
                throw new RuntimeException('The selection is too large to archive safely.');
            }

            if (! $zip->addFile($realPath, $entryName)) {
                throw new RuntimeException('A selected file could not be added to the archive.');
            }

            $added[$entryName] = true;
        };

        try {
            foreach ($selectedPaths as $selectedPath) {
                $realPath = realpath($selectedPath);
                if ($realPath === false || is_link($selectedPath) || ! $this->paths->isWithin($realRoot, $realPath)) {
                    throw new RuntimeException('A selected item is unavailable or unsafe.');
                }

                if (is_file($realPath)) {
                    $addFile($realPath);

                    continue;
                }

                $directoryName = ltrim(substr(str_replace('\\', '/', $realPath), strlen($normalizedRoot)), '/');
                if ($directoryName !== '') {
                    $zip->addEmptyDir($directoryName);
                }

                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($realPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY,
                );

                foreach ($files as $file) {
                    if ($file->isFile() && ! $file->isLink()) {
                        $addFile($file->getPathname());
                    }
                }
            }

            if ($entryCount === 0 && $added === []) {
                // Empty selected folders are valid archive content.
                $entryCount = count($selectedPaths);
            }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($zipPath);

            throw $exception;
        }

        $zip->close();

        return $zipPath;
    }
}
