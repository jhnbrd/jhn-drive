<?php

namespace App\Http\Controllers;

use App\Models\SharedLink;
use App\Services\DrivePathService;
use App\Services\FileMetadataService;
use App\Services\FileResponseService;
use App\Services\ZipArchiveService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShareController extends Controller
{
    /**
     * Storage disk name.
     */
    protected string $disk = 'local_drive';

    public function __construct(
        private readonly DrivePathService $paths,
        private readonly FileMetadataService $metadata,
        private readonly FileResponseService $responses,
        private readonly ZipArchiveService $archives,
    ) {}

    /**
     * Display the public landing page for a shared token (single file or folder).
     */
    public function show(Request $request, string $token)
    {
        $link = SharedLink::where('token', $token)->firstOrFail();
        $realFullPath = $this->resolveSharedItem($link);

        // Folder view
        if ($link->is_folder || is_dir($realFullPath)) {
            [$targetFolder, $subPath] = $this->resolveWithinSharedRoot($realFullPath, (string) $request->query('path', ''), true);

            // Read contents
            $dirEntries = scandir($targetFolder) ?: [];
            $folders = [];
            $files = [];

            foreach ($dirEntries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $entryFullPath = realpath($targetFolder.DIRECTORY_SEPARATOR.$entry);

                if (! $entryFullPath || ! $this->paths->isWithin($realFullPath, $entryFullPath)) {
                    continue;
                }

                $entrySubPath = empty($subPath) ? $entry : $subPath.'/'.$entry;
                $modifiedTime = filemtime($entryFullPath) ?: time();

                if (is_dir($entryFullPath)) {
                    $itemCount = count(glob($entryFullPath.'/*') ?: []);
                    $folders[] = [
                        'name' => $entry,
                        'subpath' => $entrySubPath,
                        'is_dir' => true,
                        'human_size' => $itemCount.' '.($itemCount === 1 ? 'item' : 'items'),
                        'category' => 'folder',
                        'modified_at' => date('M j, Y', $modifiedTime),
                    ];
                } else {
                    $fileSize = filesize($entryFullPath) ?: 0;
                    $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                    $mime = $this->metadata->mimeType($entryFullPath, $entry);
                    $category = $this->resolveCategory($ext, $mime);

                    $files[] = [
                        'name' => $entry,
                        'subpath' => $entrySubPath,
                        'is_dir' => false,
                        'size' => $fileSize,
                        'human_size' => $this->formatBytes($fileSize),
                        'extension' => $ext,
                        'mime_type' => $mime,
                        'category' => $category,
                        'modified_at' => date('M j, Y', $modifiedTime),
                        'download_url' => route('share.download', ['token' => $link->token, 'path' => $entrySubPath]),
                        'preview_url' => in_array($category, ['image', 'video']) ? route('share.preview', ['token' => $link->token, 'path' => $entrySubPath]) : null,
                    ];
                }
            }

            // Breadcrumbs inside shared folder
            $breadcrumbs = [
                ['name' => $link->filename ?: basename($realFullPath), 'path' => ''],
            ];
            if (! empty($subPath)) {
                $parts = explode('/', $subPath);
                $accumulated = '';
                foreach ($parts as $part) {
                    if (empty($part)) {
                        continue;
                    }
                    $accumulated = empty($accumulated) ? $part : $accumulated.'/'.$part;
                    $breadcrumbs[] = [
                        'name' => $part,
                        'path' => $accumulated,
                    ];
                }
            }

            $folderData = [
                'name' => $link->filename ?: basename($realFullPath),
                'token' => $link->token,
                'subpath' => $subPath,
                'breadcrumbs' => $breadcrumbs,
                'folders' => $folders,
                'files' => $files,
                'total_items' => count($folders) + count($files),
                'downloads_count' => $link->downloads_count,
                'zip_url' => route('share.zip', ['token' => $link->token, 'path' => $subPath]),
            ];

            return view('share.show', [
                'link' => $link,
                'isFolder' => true,
                'folderData' => $folderData,
                'fileData' => null,
            ]);
        }

        // Single file view
        $fileName = basename($realFullPath);
        $fileSize = filesize($realFullPath) ?: 0;
        $modifiedTime = filemtime($realFullPath) ?: time();
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = $this->metadata->mimeType($realFullPath, $fileName);

        $category = $this->resolveCategory($extension, $mimeType);

        $fileData = [
            'token' => $link->token,
            'name' => $fileName,
            'size' => $fileSize,
            'human_size' => $this->formatBytes($fileSize),
            'extension' => $extension,
            'mime_type' => $mimeType,
            'category' => $category,
            'modified_at' => date('F j, Y \a\t g:i A', $modifiedTime),
            'downloads_count' => $link->downloads_count,
            'download_url' => route('share.download', ['token' => $link->token]),
            'preview_url' => in_array($category, ['image', 'video']) ? route('share.preview', ['token' => $link->token]) : null,
        ];

        return view('share.show', [
            'link' => $link,
            'isFolder' => false,
            'fileData' => $fileData,
            'folderData' => null,
        ]);
    }

    /**
     * Direct download action triggered from the share link or folder item.
     */
    public function download(Request $request, string $token): BinaryFileResponse
    {
        $link = SharedLink::where('token', $token)->firstOrFail();
        $realFullPath = $this->resolveSharedItem($link);

        $targetPath = $realFullPath;
        if ($link->is_folder || is_dir($realFullPath)) {
            $rawSubPath = (string) $request->query('path', '');
            if ($rawSubPath === '') {
                // If user clicks download on a folder directly, redirect to zip
                return $this->downloadZip($request, $token);
            }
            [$targetPath] = $this->resolveWithinSharedRoot($realFullPath, $rawSubPath, false);
        }

        // Increment download counter
        $link->incrementDownloads();

        $fileName = basename($targetPath);

        return response()->download($targetPath, $fileName);
    }

    /**
     * Download entire shared folder or subfolder as a ZIP archive.
     */
    public function downloadZip(Request $request, string $token): BinaryFileResponse
    {
        $link = SharedLink::where('token', $token)->firstOrFail();
        $sharedRoot = $this->resolveSharedItem($link);
        [$folder, $subPath] = $this->resolveWithinSharedRoot(
            $sharedRoot,
            (string) $request->query('path', ''),
            true
        );

        $folderName = $subPath === '' ? ($link->filename ?: basename($sharedRoot)) : basename($folder);

        try {
            $zipPath = $this->archives->createFromDirectory($folder);
        } catch (Throwable $exception) {
            report($exception);
            abort(500, 'Could not create ZIP archive.');
        }

        $link->incrementDownloads();

        return response()->download($zipPath, $folderName.'.zip')->deleteFileAfterSend(true);
    }

    /**
     * Preview inline stream for shared images and videos with Range request support.
     */
    public function preview(Request $request, string $token)
    {
        $link = SharedLink::where('token', $token)->firstOrFail();
        $sharedRoot = $this->resolveSharedItem($link);
        $target = $sharedRoot;

        if ($link->is_folder || is_dir($sharedRoot)) {
            $subPath = (string) $request->query('path', '');
            if ($subPath === '') {
                abort(400, 'Directories cannot be previewed.');
            }

            [$target] = $this->resolveWithinSharedRoot($sharedRoot, $subPath, false);
        }

        return $this->responses->preview($request, $target);
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 1): string
    {
        return $this->metadata->formatBytes($bytes, $precision);
    }

    /**
     * Resolve a database share path and prove it remains inside the drive root.
     */
    private function resolveSharedItem(SharedLink $link): string
    {
        return $this->paths->resolveSharedItem($link);
    }

    /**
     * Resolve a public subpath without accepting traversal, absolute paths,
     * prefix-confused siblings, or symlinks that leave the shared root.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveWithinSharedRoot(string $root, string $rawSubPath, bool $mustBeDirectory): array
    {
        return $this->paths->resolveWithinRoot($root, $rawSubPath, $mustBeDirectory);
    }

    /**
     * Resolve icon category based on extension and mime type.
     */
    protected function resolveCategory(string $ext, string $mime): string
    {
        return $this->metadata->category($ext, $mime);
    }
}
