<?php

namespace App\Services;

use App\Models\SharedLink;
use App\Models\User;
use FilesystemIterator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

class DriveTransferService
{
    public const MAX_ITEMS = 100;

    public const MAX_COPY_BYTES = 2 * 1024 * 1024 * 1024;

    private string $diskName = 'local_drive';

    public function __construct(
        private readonly DrivePathService $paths,
        private readonly HomeSummaryService $homeSummary,
    ) {}

    /**
     * @param  array<int, string>  $rawPaths
     * @return array<int, array{source: string, path: string, name: string}>
     */
    public function transfer(User $user, array $rawPaths, string $destination, string $operation): array
    {
        if (! in_array($operation, ['move', 'copy'], true)) {
            throw new RuntimeException('Choose either move or copy.');
        }

        if ($rawPaths === [] || count($rawPaths) > self::MAX_ITEMS) {
            throw new RuntimeException('Select between 1 and '.self::MAX_ITEMS.' items.');
        }

        $disk = Storage::disk($this->diskName);
        $destinationDiskPath = $this->paths->resolveUserPath($user, $destination, true);
        $destinationFullPath = $disk->path($destinationDiskPath);

        if (! is_dir($destinationFullPath) || is_link($destinationFullPath)) {
            throw new RuntimeException('The destination folder is unavailable.');
        }

        $destinationUserPath = $this->paths->toUserPath($user, $destinationDiskPath);
        $sources = [];

        foreach (array_values(array_unique($rawPaths)) as $rawPath) {
            if (trim($rawPath, '/\\') === '') {
                throw new RuntimeException('The Drive root cannot be moved or copied.');
            }

            $sourceDiskPath = $this->paths->resolveUserPath($user, $rawPath, true);
            $sourceUserPath = $this->paths->toUserPath($user, $sourceDiskPath);
            $sourceFullPath = $disk->path($sourceDiskPath);

            if (is_link($sourceFullPath)) {
                throw new RuntimeException('Symbolic links cannot be moved or copied.');
            }

            $sources[$sourceUserPath] = [
                'disk_path' => $sourceDiskPath,
                'full_path' => $sourceFullPath,
                'is_folder' => is_dir($sourceFullPath),
            ];
        }

        $this->assertNoNestedSelection(array_keys($sources));

        $reserved = [];
        $plans = [];
        $copyBytes = 0;

        foreach ($sources as $sourceUserPath => $source) {
            if ($source['is_folder'] && $this->isSameOrDescendant($destinationUserPath, $sourceUserPath)) {
                throw new RuntimeException('A folder cannot be moved or copied into itself.');
            }

            $name = basename($sourceUserPath);
            $targetName = $this->availableName(
                $destinationDiskPath,
                $name,
                $source['is_folder'],
                $reserved,
            );
            $targetDiskPath = $this->join($destinationDiskPath, $targetName);
            $targetUserPath = $this->paths->toUserPath($user, $targetDiskPath);
            $targetFullPath = $disk->path($targetDiskPath);

            if (! $this->paths->isWithin($this->paths->userRoot($user), dirname($targetFullPath))) {
                throw new RuntimeException('The destination is outside your Drive.');
            }

            if ($operation === 'copy') {
                $copyBytes += $this->sizeOf($source['full_path']);
                if ($copyBytes > self::MAX_COPY_BYTES) {
                    throw new RuntimeException('This copy is too large to complete safely in one operation.');
                }
            }

            $reserved[] = $targetDiskPath;
            $plans[] = [
                'source' => $sourceUserPath,
                'source_disk_path' => $source['disk_path'],
                'source_full_path' => $source['full_path'],
                'target_disk_path' => $targetDiskPath,
                'target_full_path' => $targetFullPath,
                'target_user_path' => $targetUserPath,
                'target_name' => $targetName,
                'is_folder' => $source['is_folder'],
            ];
        }

        if ($operation === 'copy' && ! $user->hasStorageFor($copyBytes)) {
            throw new RuntimeException('There is not enough free storage to copy this selection.');
        }

        $completed = [];

        try {
            foreach ($plans as $plan) {
                // Track the active plan so a partially written copy is also removed on failure.
                $completed[] = $plan;
                if ($operation === 'move') {
                    if (! rename($plan['source_full_path'], $plan['target_full_path'])) {
                        throw new RuntimeException("Unable to move '{$plan['source']}'.");
                    }
                } else {
                    $this->copyNode($plan['source_full_path'], $plan['target_full_path']);
                }
            }

            if ($operation === 'move') {
                DB::transaction(function () use ($user, $plans): void {
                    foreach ($plans as $plan) {
                        $this->rewriteSharePaths(
                            $user,
                            $plan['source_disk_path'],
                            $plan['target_disk_path'],
                        );
                    }
                });
            }
        } catch (Throwable $exception) {
            $this->rollback($completed, $operation);
            throw $exception;
        }

        $this->homeSummary->invalidate($user);

        return array_map(
            static fn (array $plan): array => [
                'source' => $plan['source'],
                'path' => $plan['target_user_path'],
                'name' => $plan['target_name'],
            ],
            $plans,
        );
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function assertNoNestedSelection(array $paths): void
    {
        usort($paths, static fn (string $left, string $right): int => strlen($left) <=> strlen($right));

        foreach ($paths as $index => $path) {
            foreach (array_slice($paths, $index + 1) as $candidate) {
                if ($this->isSameOrDescendant($candidate, $path)) {
                    throw new RuntimeException('Do not select both a folder and an item inside it.');
                }
            }
        }
    }

    /**
     * @param  array<int, string>  $reserved
     */
    private function availableName(string $directory, string $name, bool $isFolder, array $reserved): string
    {
        $disk = Storage::disk($this->diskName);
        $extension = $isFolder ? '' : pathinfo($name, PATHINFO_EXTENSION);
        $base = $isFolder || $extension === '' ? $name : pathinfo($name, PATHINFO_FILENAME);
        $suffix = $extension === '' ? '' : '.'.$extension;
        $candidate = $name;
        $counter = 1;

        while ($disk->exists($this->join($directory, $candidate))
            || in_array($this->join($directory, $candidate), $reserved, true)) {
            $candidate = "{$base} ({$counter}){$suffix}";
            $counter++;
        }

        return $candidate;
    }

    private function rewriteSharePaths(User $user, string $source, string $target): void
    {
        SharedLink::query()
            ->where('user_id', $user->id)
            ->get()
            ->filter(static fn (SharedLink $link): bool => $link->file_path === $source
                || str_starts_with($link->file_path, $source.'/'))
            ->each(function (SharedLink $link) use ($source, $target): void {
                $link->update([
                    'file_path' => $target.substr($link->file_path, strlen($source)),
                ]);
            });
    }

    private function sizeOf(string $path): int
    {
        if (is_link($path)) {
            throw new RuntimeException('Symbolic links cannot be copied.');
        }

        if (is_file($path)) {
            return filesize($path) ?: 0;
        }

        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new RuntimeException('Folders containing symbolic links cannot be copied.');
            }

            if ($item->isFile()) {
                $size += $item->getSize();
            }
        }

        return $size;
    }

    private function copyNode(string $source, string $target): void
    {
        if (is_file($source)) {
            if (! copy($source, $target)) {
                throw new RuntimeException('A file could not be copied.');
            }

            return;
        }

        if (! mkdir($target, 0755, true) && ! is_dir($target)) {
            throw new RuntimeException('The destination folder could not be created.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new RuntimeException('Folders containing symbolic links cannot be copied.');
            }

            $relative = substr($item->getPathname(), strlen($source) + 1);
            $destination = $target.DIRECTORY_SEPARATOR.$relative;

            if ($item->isDir()) {
                if (! is_dir($destination) && ! mkdir($destination, 0755, true) && ! is_dir($destination)) {
                    throw new RuntimeException('A destination folder could not be created.');
                }
            } elseif (! copy($item->getPathname(), $destination)) {
                throw new RuntimeException('A file could not be copied.');
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $completed
     */
    private function rollback(array $completed, string $operation): void
    {
        $disk = Storage::disk($this->diskName);

        foreach (array_reverse($completed) as $plan) {
            try {
                if ($operation === 'move') {
                    if (file_exists($plan['target_full_path']) && ! file_exists($plan['source_full_path'])) {
                        rename($plan['target_full_path'], $plan['source_full_path']);
                    }
                } elseif (is_dir($plan['target_full_path'])) {
                    $disk->deleteDirectory($plan['target_disk_path']);
                } elseif (file_exists($plan['target_full_path'])) {
                    $disk->delete($plan['target_disk_path']);
                }
            } catch (Throwable $rollbackError) {
                report($rollbackError);
            }
        }
    }

    private function isSameOrDescendant(string $candidate, string $parent): bool
    {
        $candidate = $this->comparable($candidate);
        $parent = rtrim($this->comparable($parent), '/');

        return $candidate === $parent || str_starts_with($candidate, $parent.'/');
    }

    private function comparable(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        return DIRECTORY_SEPARATOR === '\\' ? strtolower($normalized) : $normalized;
    }

    private function join(string $directory, string $name): string
    {
        return rtrim($directory, '/').'/'.ltrim($name, '/');
    }
}
