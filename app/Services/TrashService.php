<?php

namespace App\Services;

use App\Models\SharedLink;
use App\Models\TrashedItem;
use App\Models\User;
use FilesystemIterator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

class TrashService
{
    private const DIRECTORY = '.trash';

    private string $diskName = 'local_drive';

    public function __construct(
        private readonly DrivePathService $paths,
        private readonly FileMetadataService $metadata,
        private readonly HomeSummaryService $homeSummary,
    ) {}

    public function move(User $user, string $diskPath): TrashedItem
    {
        $disk = Storage::disk($this->diskName);
        $source = $disk->path($diskPath);
        $originalPath = $this->paths->toUserPath($user, $diskPath);

        if ($originalPath === '' || $this->isReservedPath($originalPath) || ! file_exists($source)) {
            throw new RuntimeException('This item cannot be moved to Trash.');
        }

        $trashDirectory = $user->storageRelativePath().'/'.self::DIRECTORY;
        if (! $disk->exists($trashDirectory) && ! $disk->makeDirectory($trashDirectory)) {
            throw new RuntimeException('Trash storage could not be prepared.');
        }
        $isFolder = is_dir($source);
        $extension = $isFolder ? '' : strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
        $safeExtension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: '';
        $storageName = (string) Str::uuid();
        $storageName .= $safeExtension === '' ? '' : '.'.substr($safeExtension, 0, 32);
        $trashDiskPath = $trashDirectory.'/'.$storageName;
        $destination = $disk->path($trashDiskPath);
        $size = $this->sizeOf($source);

        if (! rename($source, $destination)) {
            throw new RuntimeException('The item could not be moved to Trash.');
        }

        try {
            $item = DB::transaction(function () use ($user, $diskPath, $originalPath, $storageName, $isFolder, $size) {
                $item = TrashedItem::create([
                    'user_id' => $user->id,
                    'storage_name' => $storageName,
                    'original_path' => $originalPath,
                    'original_name' => basename($originalPath),
                    'is_folder' => $isFolder,
                    'size' => $size,
                    'deleted_at' => now(),
                ]);

                SharedLink::query()
                    ->where('user_id', $user->id)
                    ->where(function ($query) use ($diskPath) {
                        $query->where('file_path', $diskPath)
                            ->orWhere('file_path', 'like', $diskPath.'/%');
                    })
                    ->delete();

                return $item;
            });
        } catch (Throwable $exception) {
            @rename($destination, $source);

            throw $exception;
        }

        $this->homeSummary->invalidate($user);

        return $item;
    }

    /**
     * @return Collection<int, TrashedItem>
     */
    public function items(User $user): Collection
    {
        return TrashedItem::query()
            ->where('user_id', $user->id)
            ->latest('deleted_at')
            ->get();
    }

    /**
     * @return array{path: string, name: string, collided: bool}
     */
    public function restore(User $user, TrashedItem $item): array
    {
        $this->assertOwner($user, $item);

        $disk = Storage::disk($this->diskName);
        $sourceDiskPath = $this->trashDiskPath($user, $item);
        $source = $disk->path($sourceDiskPath);

        if (! file_exists($source)) {
            throw new RuntimeException('The trashed item no longer exists.');
        }

        $targetUserPath = $this->uniqueRestorePath($user, $item->original_path, $item->is_folder);
        $targetDiskPath = $user->storageRelativePath().'/'.$targetUserPath;
        $target = $disk->path($targetDiskPath);
        $parent = dirname($target);

        if (! is_dir($parent) && ! mkdir($parent, 0755, true) && ! is_dir($parent)) {
            throw new RuntimeException('The original folder could not be recreated.');
        }

        if (! $this->paths->isWithin($this->paths->userRoot($user), $parent)) {
            throw new RuntimeException('The restore location is unsafe.');
        }

        if (! rename($source, $target)) {
            throw new RuntimeException('The item could not be restored.');
        }

        $item->delete();
        $this->homeSummary->invalidate($user);

        return [
            'path' => $targetUserPath,
            'name' => basename($targetUserPath),
            'collided' => $targetUserPath !== $item->original_path,
        ];
    }

    public function permanentlyDelete(User $user, TrashedItem $item): void
    {
        $this->assertOwner($user, $item);

        $disk = Storage::disk($this->diskName);
        $trashPath = $this->trashDiskPath($user, $item);
        $fullPath = $disk->path($trashPath);

        if (file_exists($fullPath)) {
            $deleted = is_dir($fullPath)
                ? $disk->deleteDirectory($trashPath)
                : $disk->delete($trashPath);

            if (! $deleted) {
                throw new RuntimeException('The item could not be permanently deleted.');
            }
        }

        $item->delete();
        $this->homeSummary->invalidate($user);
    }

    public function empty(User $user): int
    {
        $disk = Storage::disk($this->diskName);
        $trashDirectory = $user->storageRelativePath().'/'.self::DIRECTORY;
        $count = TrashedItem::where('user_id', $user->id)->count();

        if ($disk->exists($trashDirectory) && ! $disk->deleteDirectory($trashDirectory)) {
            throw new RuntimeException('Trash could not be emptied.');
        }

        TrashedItem::where('user_id', $user->id)->delete();
        $this->homeSummary->invalidate($user);

        return $count;
    }

    public function serialize(User $user, TrashedItem $item): array
    {
        $disk = Storage::disk($this->diskName);
        $fullPath = $disk->path($this->trashDiskPath($user, $item));
        $extension = strtolower(pathinfo($item->original_name, PATHINFO_EXTENSION));
        $mime = $item->is_folder || ! is_file($fullPath)
            ? ($item->is_folder ? 'directory' : 'application/octet-stream')
            : $this->metadata->mimeType($fullPath, $item->original_name);

        return [
            'id' => $item->id,
            'name' => $item->original_name,
            'original_path' => $item->original_path,
            'is_dir' => $item->is_folder,
            'size' => $item->size,
            'human_size' => $item->is_folder ? 'Folder' : $this->metadata->formatBytes($item->size),
            'category' => $item->is_folder ? 'folder' : $this->metadata->category($extension, $mime),
            'deleted_at' => $item->deleted_at?->toIso8601String(),
            'deleted_human' => $item->deleted_at
                ? $this->metadata->formatTimeAgo($item->deleted_at->timestamp)
                : 'recently',
            'exists' => file_exists($fullPath),
        ];
    }

    private function uniqueRestorePath(User $user, string $originalPath, bool $isFolder): string
    {
        $normalized = trim(str_replace('\\', '/', $originalPath), '/');
        $segments = explode('/', $normalized);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || $segment === self::DIRECTORY) {
                throw new RuntimeException('The original restore path is invalid.');
            }
        }

        $directory = dirname($normalized);
        $directory = $directory === '.' ? '' : str_replace('\\', '/', $directory);
        $name = basename($normalized);
        $extension = $isFolder ? '' : pathinfo($name, PATHINFO_EXTENSION);
        $base = $isFolder || $extension === '' ? $name : pathinfo($name, PATHINFO_FILENAME);
        $suffix = $extension === '' ? '' : '.'.$extension;
        $candidate = $normalized;
        $counter = 1;
        $disk = Storage::disk($this->diskName);

        while ($disk->exists($user->storageRelativePath().'/'.$candidate)) {
            $renamed = $base.' (restored '.$counter.')'.$suffix;
            $candidate = $directory === '' ? $renamed : $directory.'/'.$renamed;
            $counter++;
        }

        return $candidate;
    }

    private function trashDiskPath(User $user, TrashedItem $item): string
    {
        return $user->storageRelativePath().'/'.self::DIRECTORY.'/'.$item->storage_name;
    }

    private function assertOwner(User $user, TrashedItem $item): void
    {
        if ($item->user_id !== $user->id) {
            abort(404);
        }
    }

    private function isReservedPath(string $path): bool
    {
        $firstSegment = explode('/', trim(str_replace('\\', '/', $path), '/'))[0] ?? '';

        return $firstSegment === self::DIRECTORY;
    }

    private function sizeOf(string $path): int
    {
        if (is_file($path)) {
            return filesize($path) ?: 0;
        }

        $size = 0;

        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($files as $file) {
                if ($file->isFile() && ! $file->isLink()) {
                    $size += $file->getSize();
                }
            }
        } catch (Throwable) {
            return $size;
        }

        return $size;
    }
}
