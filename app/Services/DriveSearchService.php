<?php

namespace App\Services;

use App\Models\SharedLink;
use App\Models\User;
use FilesystemIterator;
use Illuminate\Support\Facades\Storage;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class DriveSearchService
{
    public const MAX_RESULTS = 500;

    private string $diskName = 'local_drive';

    public function __construct(
        private readonly DrivePathService $paths,
        private readonly FileMetadataService $metadata,
    ) {}

    /**
     * @return array{items: array<int, array<string, mixed>>, truncated: bool}
     */
    public function search(
        User $user,
        string $query,
        string $type = 'all',
        string $sort = 'name',
        string $direction = 'asc',
    ): array {
        $root = $this->paths->userRoot($user);
        $disk = Storage::disk($this->diskName);
        $links = SharedLink::where('user_id', $user->id)
            ->get()
            ->keyBy(fn (SharedLink $link): string => str_replace('\\', '/', $link->file_path));
        $items = [];
        $truncated = false;

        $directory = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
        $filtered = new RecursiveCallbackFilterIterator(
            $directory,
            static fn (SplFileInfo $entry): bool => ! $entry->isLink() && $entry->getFilename() !== '.trash',
        );
        $iterator = new RecursiveIteratorIterator($filtered, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $entry) {
            try {
                if ($entry->isLink() || ! $this->nameMatches($entry->getFilename(), $query)) {
                    continue;
                }

                $isFolder = $entry->isDir();
                $extension = $isFolder ? '' : strtolower($entry->getExtension());
                $mime = $isFolder
                    ? 'directory'
                    : $this->metadata->mimeType($entry->getPathname(), $entry->getFilename());
                $category = $isFolder ? 'folder' : $this->metadata->category($extension, $mime);

                if (! $this->matchesType($category, $type)) {
                    continue;
                }

                if (count($items) >= self::MAX_RESULTS) {
                    $truncated = true;
                    break;
                }

                $absolutePath = $entry->getPathname();
                $relativePath = ltrim(str_replace('\\', '/', substr($absolutePath, strlen($root))), '/');
                $diskPath = $user->storageRelativePath().'/'.$relativePath;
                $parentPath = str_contains($relativePath, '/')
                    ? str_replace('\\', '/', dirname($relativePath))
                    : '';
                $modified = $entry->getMTime();
                $size = $isFolder ? 0 : $entry->getSize();
                $share = $links->get($diskPath);

                $items[] = [
                    'name' => $entry->getFilename(),
                    'path' => $relativePath,
                    'parent_path' => $parentPath,
                    'location' => $parentPath === '' ? 'My Drive' : 'My Drive / '.str_replace('/', ' / ', $parentPath),
                    'is_dir' => $isFolder,
                    'extension' => $extension,
                    'size' => $size,
                    'human_size' => $isFolder ? 'Folder' : $this->metadata->formatBytes($size),
                    'mime_type' => $mime,
                    'category' => $category,
                    'modified_at' => date('Y-m-d H:i:s', $modified),
                    'modified_human' => $this->metadata->formatTimeAgo($modified),
                    'share_token' => $share?->token,
                    'share_url' => $share?->share_url,
                    'downloads_count' => $share?->downloads_count ?? 0,
                    'download_url' => $isFolder ? null : route('drive.download', ['path' => $relativePath]),
                    'preview_url' => ! $isFolder && in_array($category, ['image', 'video'], true)
                        ? route('drive.preview', ['path' => $relativePath])
                        : null,
                ];
            } catch (Throwable) {
                // Files may be changed externally while a filesystem search is running.
            }
        }

        $this->sort($items, $sort, $direction);

        return ['items' => $items, 'truncated' => $truncated];
    }

    private function nameMatches(string $name, string $query): bool
    {
        return function_exists('mb_stripos')
            ? mb_stripos($name, $query) !== false
            : stripos($name, $query) !== false;
    }

    private function matchesType(string $category, string $type): bool
    {
        return match ($type) {
            'all' => true,
            'folder' => $category === 'folder',
            'document' => in_array($category, ['document', 'pdf', 'code'], true),
            default => $category === $type,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function sort(array &$items, string $sort, string $direction): void
    {
        $multiplier = $direction === 'desc' ? -1 : 1;

        usort($items, static function (array $left, array $right) use ($sort, $multiplier): int {
            $comparison = match ($sort) {
                'modified' => strcmp($left['modified_at'], $right['modified_at']),
                'size' => $left['size'] <=> $right['size'],
                'type' => strcmp($left['category'], $right['category'])
                    ?: strnatcasecmp($left['name'], $right['name']),
                default => strnatcasecmp($left['name'], $right['name']),
            };

            return $comparison * $multiplier;
        });
    }
}
