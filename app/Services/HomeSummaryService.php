<?php

namespace App\Services;

use App\Models\SharedLink;
use App\Models\User;
use FilesystemIterator;
use Illuminate\Support\Facades\Cache;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class HomeSummaryService
{
    private const CACHE_TTL_SECONDS = 300;

    private const RECENT_LIMIT = 6;

    private const SHARED_LIMIT = 4;

    public function __construct(private readonly FileMetadataService $metadata) {}

    public function summary(User $user, bool $refresh = false): array
    {
        return [
            ...$this->filesystemSummary($user, $refresh),
            'shared' => $this->recentShared($user),
            'cache_ttl_seconds' => self::CACHE_TTL_SECONDS,
        ];
    }

    public function filesystemSummary(User $user, bool $refresh = false): array
    {
        if ($refresh) {
            $this->invalidate($user);
        }

        return Cache::remember(
            $this->cacheKey($user),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => $this->scan($user),
        );
    }

    public function invalidate(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    private function scan(User $user): array
    {
        $root = $user->ensureStorageDirectoryExists();
        $categories = [
            'image' => ['key' => 'image', 'label' => 'Images', 'bytes' => 0, 'count' => 0],
            'video' => ['key' => 'video', 'label' => 'Video', 'bytes' => 0, 'count' => 0],
            'document' => ['key' => 'document', 'label' => 'Documents', 'bytes' => 0, 'count' => 0],
            'audio' => ['key' => 'audio', 'label' => 'Audio', 'bytes' => 0, 'count' => 0],
            'archive' => ['key' => 'archive', 'label' => 'Archives', 'bytes' => 0, 'count' => 0],
            'other' => ['key' => 'other', 'label' => 'Other', 'bytes' => 0, 'count' => 0],
        ];
        $recent = [];
        $usedBytes = 0;
        $fileCount = 0;

        try {
            $directory = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
            $filtered = new RecursiveCallbackFilterIterator(
                $directory,
                static fn (SplFileInfo $entry): bool => ! $entry->isLink() && $entry->getFilename() !== '.trash',
            );
            $files = new RecursiveIteratorIterator($filtered, RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($files as $file) {
                if (! $file->isFile() || $file->isLink()) {
                    continue;
                }

                try {
                    $size = $file->getSize();
                    $modified = $file->getMTime();
                    $absolutePath = $file->getPathname();
                    $relativePath = ltrim(str_replace('\\', '/', substr($absolutePath, strlen($root))), '/');
                    $name = $file->getFilename();
                    $extension = strtolower($file->getExtension());
                    $mime = $this->metadata->mimeType($absolutePath, $name);
                    $category = $this->homeCategory($this->metadata->category($extension, $mime));

                    $usedBytes += $size;
                    $fileCount++;
                    $categories[$category]['bytes'] += $size;
                    $categories[$category]['count']++;

                    $recent[] = [
                        'name' => $name,
                        'path' => $relativePath,
                        'parent_path' => str_contains($relativePath, '/') ? str_replace('\\', '/', dirname($relativePath)) : '',
                        'category' => $category,
                        'extension' => $extension,
                        'size' => $size,
                        'human_size' => $this->metadata->formatBytes($size),
                        'modified_at' => date('Y-m-d H:i:s', $modified),
                        'modified_human' => $this->metadata->formatTimeAgo($modified),
                        'download_url' => route('drive.download', ['path' => $relativePath]),
                        'preview_url' => in_array($category, ['image', 'video'], true)
                            ? route('drive.preview', ['path' => $relativePath])
                            : null,
                        '_modified_timestamp' => $modified,
                    ];

                    usort($recent, static fn (array $left, array $right): int => $right['_modified_timestamp'] <=> $left['_modified_timestamp']);
                    $recent = array_slice($recent, 0, self::RECENT_LIMIT);
                } catch (Throwable) {
                    // A file may disappear or become unreadable during a scan.
                }
            }
        } catch (Throwable) {
            // Return an empty, usable summary if the root is temporarily unreadable.
        }

        foreach ($recent as &$item) {
            unset($item['_modified_timestamp']);
        }
        unset($item);

        foreach ($categories as &$category) {
            $category['human_size'] = $this->metadata->formatBytes($category['bytes']);
            $category['percent'] = $usedBytes > 0
                ? round(($category['bytes'] / $usedBytes) * 100, 1)
                : 0;
        }
        unset($category);

        $quotaBytes = User::STORAGE_QUOTA_BYTES;
        $freeBytes = max(0, $quotaBytes - $usedBytes);

        return [
            'storage' => [
                'used_bytes' => $usedBytes,
                'quota_bytes' => $quotaBytes,
                'free_bytes' => $freeBytes,
                'percent_used' => round(($usedBytes / $quotaBytes) * 100, 1),
                'used_human' => $this->metadata->formatBytes($usedBytes),
                'quota_human' => $this->metadata->formatBytes($quotaBytes),
                'free_human' => $this->metadata->formatBytes($freeBytes),
                'file_count' => $fileCount,
            ],
            'categories' => array_values($categories),
            'recent' => $recent,
            'scanned_at' => now()->toIso8601String(),
        ];
    }

    private function recentShared(User $user): array
    {
        return SharedLink::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(self::SHARED_LIMIT)
            ->get()
            ->map(fn (SharedLink $link): array => [
                'id' => $link->id,
                'name' => $link->filename,
                'path' => $this->toUserPath($user, $link->file_path),
                'is_dir' => $link->is_folder,
                'share_url' => $link->share_url,
                'downloads_count' => $link->downloads_count,
                'created_human' => $link->created_at
                    ? $this->metadata->formatTimeAgo($link->created_at->timestamp)
                    : 'recently',
            ])
            ->all();
    }

    private function homeCategory(string $category): string
    {
        return in_array($category, ['pdf', 'code'], true) ? 'document' : $category;
    }

    private function toUserPath(User $user, string $diskPath): string
    {
        $prefix = trim(str_replace('\\', '/', $user->storageRelativePath()), '/').'/';
        $normalized = ltrim(str_replace('\\', '/', $diskPath), '/');

        return str_starts_with($normalized, $prefix) ? substr($normalized, strlen($prefix)) : $normalized;
    }

    private function cacheKey(User $user): string
    {
        return 'drive-home-summary:user:'.$user->id;
    }
}
