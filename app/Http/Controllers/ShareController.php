<?php

namespace App\Http\Controllers;

use App\Models\SharedLink;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ShareController extends Controller
{
    /**
     * Storage disk name.
     */
    protected string $disk = 'local_drive';

    /**
     * Display the public landing page for a shared token (single file or folder).
     */
    public function show(Request $request, string $token)
    {
        $link = SharedLink::where('token', $token)->firstOrFail();
        $disk = Storage::disk($this->disk);
        $cleanPath = ltrim(str_replace('\\', '/', $link->file_path), '/');
        $fullPath = $disk->path($cleanPath);

        $rootPath = realpath($disk->path(''));
        $realFullPath = realpath($fullPath);

        if (!$realFullPath || !str_starts_with(str_replace('\\', '/', $realFullPath), str_replace('\\', '/', $rootPath)) || !file_exists($realFullPath)) {
            abort(404, 'The shared item no longer exists or is unavailable.');
        }

        // Folder view
        if ($link->is_folder || is_dir($realFullPath)) {
            $subPath = trim((string) $request->query('path', ''), '/\\');
            $subPath = str_replace(["\0", '..'], '', $subPath);

            $targetFolder = empty($subPath) ? $realFullPath : realpath($realFullPath . '/' . $subPath);
            $normalizedFolder = str_replace('\\', '/', $realFullPath);

            if (!$targetFolder || !str_starts_with(str_replace('\\', '/', $targetFolder), $normalizedFolder) || !is_dir($targetFolder)) {
                abort(404, 'Folder not found.');
            }

            // Read contents
            $dirEntries = scandir($targetFolder) ?: [];
            $folders = [];
            $files = [];

            foreach ($dirEntries as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                $entryFullPath = $targetFolder . '/' . $entry;
                $entrySubPath = empty($subPath) ? $entry : $subPath . '/' . $entry;
                $modifiedTime = filemtime($entryFullPath) ?: time();

                if (is_dir($entryFullPath)) {
                    $itemCount = count(glob($entryFullPath . '/*') ?: []);
                    $folders[] = [
                        'name' => $entry,
                        'subpath' => $entrySubPath,
                        'is_dir' => true,
                        'human_size' => $itemCount . ' ' . ($itemCount === 1 ? 'item' : 'items'),
                        'category' => 'folder',
                        'modified_at' => date('M j, Y', $modifiedTime),
                    ];
                } else {
                    $fileSize = filesize($entryFullPath) ?: 0;
                    $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                    $knownMime = \App\Http\Controllers\DriveController::getKnownMimeType($ext);
                    $mime = $knownMime;
                    if (!$mime && function_exists('mime_content_type')) {
                        $mime = mime_content_type($entryFullPath) ?: 'application/octet-stream';
                    }
                    if (!$mime) {
                        $mime = 'application/octet-stream';
                    }
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
                ['name' => $link->filename ?: basename($realFullPath), 'path' => '']
            ];
            if (!empty($subPath)) {
                $parts = explode('/', $subPath);
                $accumulated = '';
                foreach ($parts as $part) {
                    if (empty($part)) continue;
                    $accumulated = empty($accumulated) ? $part : $accumulated . '/' . $part;
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
        $fileName = basename($cleanPath);
        $fileSize = filesize($realFullPath) ?: 0;
        $modifiedTime = filemtime($realFullPath) ?: time();
        $extension = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
        $knownMime = \App\Http\Controllers\DriveController::getKnownMimeType($extension);
        $mimeType = $knownMime;
        if (!$mimeType && function_exists('mime_content_type')) {
            $mimeType = mime_content_type($realFullPath) ?: 'application/octet-stream';
        }
        if (!$mimeType) {
            $mimeType = 'application/octet-stream';
        }

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
        $disk = Storage::disk($this->disk);
        $cleanPath = ltrim(str_replace('\\', '/', $link->file_path), '/');
        $fullPath = $disk->path($cleanPath);

        $rootPath = realpath($disk->path(''));
        $realFullPath = realpath($fullPath);

        if (!$realFullPath || !str_starts_with(str_replace('\\', '/', $realFullPath), str_replace('\\', '/', $rootPath)) || !file_exists($realFullPath)) {
            abort(404, 'The requested item does not exist.');
        }

        $targetPath = $realFullPath;
        if ($link->is_folder || is_dir($realFullPath)) {
            $subPath = trim((string) $request->query('path', ''), '/\\');
            $subPath = str_replace(["\0", '..'], '', $subPath);
            if (empty($subPath)) {
                // If user clicks download on a folder directly, redirect to zip
                return $this->downloadZip($request, $token);
            }
            $targetPath = realpath($realFullPath . '/' . $subPath);
            if (!$targetPath || !str_starts_with(str_replace('\\', '/', $targetPath), str_replace('\\', '/', $realFullPath)) || is_dir($targetPath)) {
                abort(404, 'The requested file does not exist.');
            }
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
        $disk = Storage::disk($this->disk);
        $cleanPath = ltrim(str_replace('\\', '/', $link->file_path), '/');
        $fullPath = $disk->path($cleanPath);

        $rootPath = realpath($disk->path(''));
        $realFullPath = realpath($fullPath);

        if (!$realFullPath || !str_starts_with(str_replace('\\', '/', $realFullPath), str_replace('\\', '/', $rootPath)) || !file_exists($realFullPath)) {
            abort(404, 'The requested item does not exist.');
        }

        $targetFolder = $realFullPath;
        $subPath = trim((string) $request->query('path', ''), '/\\');
        $subPath = str_replace(["\0", '..'], '', $subPath);

        if (!empty($subPath)) {
            $targetFolder = realpath($realFullPath . '/' . $subPath);
            if (!$targetFolder || !str_starts_with(str_replace('\\', '/', $targetFolder), str_replace('\\', '/', $realFullPath)) || !is_dir($targetFolder)) {
                abort(404, 'Folder not found.');
            }
        }

        $folderName = empty($subPath) ? ($link->filename ?: basename($realFullPath)) : basename($targetFolder);
        $zipFileName = $folderName . '.zip';
        $tempZipPath = tempnam(sys_get_temp_dir(), 'jhn_zip_') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP archive.');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetFolder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $normalizedTarget = rtrim(str_replace('\\', '/', $targetFolder), '/');

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $normalizedFile = str_replace('\\', '/', $filePath);
                $relativePath = ltrim(substr($normalizedFile, strlen($normalizedTarget)), '/');
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        // Increment download counter
        $link->incrementDownloads();

        return response()->download($tempZipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Preview inline stream for shared images and videos with Range request support.
     */
    public function preview(Request $request, string $token)
    {
        $link = SharedLink::where('token', $token)->firstOrFail();
        $disk = Storage::disk($this->disk);
        $cleanPath = ltrim(str_replace('\\', '/', $link->file_path), '/');
        $fullPath = $disk->path($cleanPath);

        $rootPath = realpath($disk->path(''));
        $realFullPath = realpath($fullPath);

        if (!$realFullPath || !str_starts_with(str_replace('\\', '/', $realFullPath), str_replace('\\', '/', $rootPath)) || !file_exists($realFullPath)) {
            abort(404, 'File not found.');
        }

        $targetPath = $realFullPath;
        if ($link->is_folder || is_dir($realFullPath)) {
            $subPath = trim((string) $request->query('path', ''), '/\\');
            $subPath = str_replace(["\0", '..'], '', $subPath);
            if (empty($subPath)) {
                abort(400, 'Directories cannot be previewed.');
            }
            $targetPath = realpath($realFullPath . '/' . $subPath);
            if (!$targetPath || !str_starts_with(str_replace('\\', '/', $targetPath), str_replace('\\', '/', $realFullPath)) || is_dir($targetPath)) {
                abort(404, 'File not found.');
            }
        }

        $extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $knownMime = \App\Http\Controllers\DriveController::getKnownMimeType($extension);
        $mime = $knownMime ?: (function_exists('mime_content_type') ? (mime_content_type($targetPath) ?: 'application/octet-stream') : 'application/octet-stream');

        $size = filesize($targetPath);
        $file = fopen($targetPath, 'rb');

        $headers = [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="' . basename($targetPath) . '"',
        ];

        if ($request->header('Range')) {
            $range = $request->header('Range');
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = (int) $matches[1];
                $end = !empty($matches[2]) ? (int) $matches[2] : ($size - 1);

                if ($start >= $size || $end >= $size || $start > $end) {
                    return response('', 416, [
                        'Content-Range' => "bytes */{$size}",
                    ]);
                }

                $length = $end - $start + 1;
                fseek($file, $start);

                $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
                $headers['Content-Length'] = (string) $length;

                return response()->stream(function () use ($file, $length) {
                    $bufferSize = 1024 * 64;
                    $bytesSent = 0;
                    while (!feof($file) && $bytesSent < $length) {
                        $readLength = min($bufferSize, $length - $bytesSent);
                        $data = fread($file, $readLength);
                        echo $data;
                        flush();
                        $bytesSent += strlen($data);
                    }
                    fclose($file);
                }, 206, $headers);
            }
        }

        $headers['Content-Length'] = (string) $size;
        return response()->stream(function () use ($file) {
            fpassthru($file);
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Resolve icon category based on extension and mime type.
     */
    protected function resolveCategory(string $ext, string $mime): string
    {
        $ext = strtolower($ext);
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif', 'tiff'];
        $videoExts = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'qt', 'avi', 'mkv', 'm4v', 'flv', 'wmv', '3gp', 'ts'];
        $audioExts = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac', 'wma', 'opus'];

        if (str_starts_with($mime, 'image/') || in_array($ext, $imageExts)) return 'image';
        if (str_starts_with($mime, 'video/') || in_array($ext, $videoExts)) return 'video';
        if (str_starts_with($mime, 'audio/') || in_array($ext, $audioExts)) return 'audio';
        if ($ext === 'pdf' || $mime === 'application/pdf') return 'pdf';

        if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'iso'])) return 'archive';
        if (in_array($ext, ['doc', 'docx', 'odt', 'rtf', 'txt', 'md', 'xls', 'xlsx', 'csv', 'ppt', 'pptx'])) return 'document';
        if (in_array($ext, ['js', 'ts', 'jsx', 'tsx', 'php', 'py', 'html', 'css', 'json', 'yaml', 'yml', 'xml', 'sql', 'sh', 'bat', 'c', 'cpp', 'rs', 'go'])) return 'code';

        return 'other';
    }
}
