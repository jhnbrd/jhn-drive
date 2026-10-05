<?php

namespace App\Services;

use App\Models\SharedLink;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class DrivePathService
{
    public function __construct(private readonly string $diskName = 'local_drive') {}

    public function userRoot(User $user): string
    {
        $user->ensureStorageDirectoryExists();
        $disk = $this->disk();
        $root = realpath($disk->path($user->storageRelativePath()));

        if ($root === false) {
            abort(500, 'The storage directory is unavailable.');
        }

        return $root;
    }

    /**
     * Resolve a user-facing path to a disk-relative path contained by that user.
     */
    public function resolveUserPath(User $user, string $path = '', bool $mustExist = false): string
    {
        $root = $this->userRoot($user);
        $cleanPath = ltrim(str_replace(["\0", '\\'], ['', '/'], $path), '/');
        $prefix = $user->storageRelativePath().'/';

        if (str_starts_with($cleanPath, $prefix)) {
            $cleanPath = substr($cleanPath, strlen($prefix));
        } elseif ($cleanPath === $user->storageRelativePath()) {
            $cleanPath = '';
        }

        $diskRelative = $cleanPath === ''
            ? $user->storageRelativePath()
            : $user->storageRelativePath().'/'.$cleanPath;
        $fullPath = $this->disk()->path($diskRelative);
        $target = realpath($fullPath);

        if ($target !== false) {
            if (! $this->isWithin($root, $target)) {
                abort(403, 'Access denied: Path traversal detected.');
            }

            return $diskRelative;
        }

        $parent = realpath(dirname($fullPath));
        if ($parent === false || ! $this->isWithin($root, $parent)) {
            abort(403, 'Access denied: Path traversal detected.');
        }

        if ($mustExist) {
            abort(404, 'The requested file or folder was not found.');
        }

        return $diskRelative;
    }

    public function toUserPath(User $user, string $diskPath): string
    {
        $normalized = str_replace('\\', '/', $diskPath);
        $prefix = $user->storageRelativePath().'/';

        if (str_starts_with($normalized, $prefix)) {
            return substr($normalized, strlen($prefix));
        }

        return $normalized === $user->storageRelativePath() ? '' : $normalized;
    }

    public function resolveSharedItem(SharedLink $link): string
    {
        $disk = $this->disk();
        $driveRoot = realpath($disk->path(''));
        $target = realpath($disk->path(ltrim(str_replace('\\', '/', $link->file_path), '/')));

        if ($driveRoot === false || $target === false || ! $this->isWithin($driveRoot, $target)) {
            abort(404, 'The shared item no longer exists or is unavailable.');
        }

        return $target;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function resolveWithinRoot(string $root, string $rawSubPath, bool $mustBeDirectory): array
    {
        $subPath = $this->normalizeSubPath($rawSubPath);
        $candidate = $subPath === ''
            ? realpath($root)
            : realpath($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $subPath));

        if ($candidate === false || ! $this->isWithin($root, $candidate)) {
            abort(404, 'The requested shared item was not found.');
        }

        if ($mustBeDirectory && ! is_dir($candidate)) {
            abort(404, 'Folder not found.');
        }

        if (! $mustBeDirectory && ! is_file($candidate)) {
            abort(404, 'File not found.');
        }

        return [$candidate, $subPath];
    }

    public function isWithin(string $root, string $target): bool
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $target = str_replace('\\', '/', $target);

        if (DIRECTORY_SEPARATOR === '\\') {
            $root = strtolower($root);
            $target = strtolower($target);
        }

        return $target === $root || str_starts_with($target, $root.'/');
    }

    private function normalizeSubPath(string $path): string
    {
        if (str_contains($path, "\0") || preg_match('/^[a-zA-Z]:[\\\\\/]/', $path)) {
            abort(404, 'The requested shared item was not found.');
        }

        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return '';
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                abort(404, 'The requested shared item was not found.');
            }
        }

        return implode('/', $segments);
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk($this->diskName);
    }
}
