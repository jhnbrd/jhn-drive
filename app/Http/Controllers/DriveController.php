<?php

namespace App\Http\Controllers;

use App\Models\SharedLink;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DriveController extends Controller
{
    /**
     * Storage disk name.
     */
    protected string $disk = 'local_drive';

    /**
     * Get the realpath of the authenticated user's isolated root storage directory.
     */
    protected function getUserRootPath(User $user): string
    {
        $user->ensureStorageDirectoryExists();
        $disk = Storage::disk($this->disk);
        $userRel = $user->storageRelativePath(); // e.g. 'users/1'
        $resolved = realpath($disk->path($userRel));

        if (!$resolved) {
            $raw = $disk->path($userRel);
            @mkdir($raw, 0755, true);
            $resolved = realpath($raw);
        }

        return $resolved;
    }

    /**
     * Ensure path is safe from traversal attacks and stays within the user's isolated storage root.
     * Returns the disk-relative path (e.g. 'users/1/folder/file.txt').
     */
    protected function getSafePath(string $path = '', bool $mustExist = false, ?User $user = null): string
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $userRoot = $this->getUserRootPath($user);
        $disk = Storage::disk($this->disk);

        // Sanitize path: strip null bytes, normalize slashes
        $path = str_replace(["\0", '\\'], ['', '/'], $path);
        $cleanPath = ltrim($path, '/');

        // If path already starts with users/{id}, strip it to avoid duplication
        $userPrefix = $user->storageRelativePath() . '/';
        if (str_starts_with($cleanPath, $userPrefix)) {
            $cleanPath = substr($cleanPath, strlen($userPrefix));
        } elseif ($cleanPath === $user->storageRelativePath()) {
            $cleanPath = '';
        }

        // Relative path inside disk
        $diskRelative = empty($cleanPath) ? $user->storageRelativePath() : $user->storageRelativePath() . '/' . $cleanPath;
        $fullPath = $disk->path($diskRelative);

        $normalizedRoot = str_replace('\\', '/', $userRoot);
        $resolvedTarget = realpath($fullPath);

        // Disallow path traversal escaping the user's isolated root
        if ($resolvedTarget !== false) {
            $normalizedResolved = str_replace('\\', '/', $resolvedTarget);
            if ($normalizedResolved !== $normalizedRoot && !str_starts_with($normalizedResolved, $normalizedRoot . '/')) {
                abort(403, 'Access denied: Path traversal detected.');
            }
        } else {
            // Target does not exist on disk
            $parentDir = dirname($fullPath);
            $resolvedParent = realpath($parentDir);

            // If parent cannot be resolved or escapes root: 403 Forbidden
            if ($resolvedParent === false) {
                abort(403, 'Access denied: Path traversal detected.');
            }

            $normalizedParent = str_replace('\\', '/', $resolvedParent);
            if ($normalizedParent !== $normalizedRoot && !str_starts_with($normalizedParent, $normalizedRoot . '/')) {
                abort(403, 'Access denied: Path traversal detected.');
            }

            // Path is within user bounds, but file does not exist
            if ($mustExist) {
                abort(404, 'The requested file or folder was not found.');
            }
        }

        return $diskRelative;
    }

    /**
     * Convert disk-relative path (users/1/foo) to user-facing path (foo).
     */
    protected function toUserPath(string $diskPath, User $user): string
    {
        $normalized = str_replace('\\', '/', $diskPath);
        $prefix = $user->storageRelativePath() . '/';
        if (str_starts_with($normalized, $prefix)) {
            return substr($normalized, strlen($prefix));
        }
        if ($normalized === $user->storageRelativePath()) {
            return '';
        }
        return $normalized;
    }

    /**
     * Display the main Drive dashboard view.
     */
    public function index()
    {
        $user = Auth::user();
        return view('drive.index', compact('user'));
    }

    /**
     * Get directory contents with full metadata for the current user.
     */
    public function listFiles(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $rawPath = (string) $request->query('path', '');
            $diskPath = $this->getSafePath($rawPath, false, $user);

            $disk = Storage::disk($this->disk);
            $fullPath = $disk->path($diskPath);

            if (!is_dir($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Directory not found.',
                ], 404);
            }

            $rawDirs = $disk->directories($diskPath);
            $rawFiles = $disk->files($diskPath);

            $items = [];

            // Query active shared links for the user
            $userLinks = SharedLink::where('user_id', $user->id)
                ->pluck('token', 'file_path')
                ->toArray();

            // Process directories
            foreach ($rawDirs as $dirPath) {
                $dirName = basename($dirPath);
                $dirFullPath = $disk->path($dirPath);
                $modifiedTime = filemtime($dirFullPath) ?: time();
                $userRelPath = $this->toUserPath($dirPath, $user);
                $normalizedRelative = str_replace('\\', '/', $dirPath);

                $dirItemsCount = count(glob($dirFullPath . '/*') ?: []);
                $shareToken = $userLinks[$normalizedRelative] ?? null;

                $items[] = [
                    'name' => $dirName,
                    'path' => $userRelPath,
                    'is_dir' => true,
                    'extension' => '',
                    'size' => 0,
                    'human_size' => $dirItemsCount . ' ' . ($dirItemsCount === 1 ? 'item' : 'items'),
                    'mime_type' => 'directory',
                    'category' => 'folder',
                    'modified_at' => date('Y-m-d H:i:s', $modifiedTime),
                    'modified_human' => $this->formatTimeAgo($modifiedTime),
                    'share_token' => $shareToken,
                    'share_url' => $shareToken ? url('/s/' . $shareToken) : null,
                ];
            }

            // Process files
            foreach ($rawFiles as $filePath) {
                $fileName = basename($filePath);
                $fileFullPath = $disk->path($filePath);
                $fileSize = file_exists($fileFullPath) ? (filesize($fileFullPath) ?: 0) : 0;
                $modifiedTime = file_exists($fileFullPath) ? (filemtime($fileFullPath) ?: time()) : time();
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $normalizedRelative = str_replace('\\', '/', $filePath);
                $userRelPath = $this->toUserPath($filePath, $user);

                $knownMime = $this->getKnownMimeType($extension);
                $mimeType = $knownMime;
                if (!$mimeType && function_exists('mime_content_type') && file_exists($fileFullPath)) {
                    $mimeType = mime_content_type($fileFullPath) ?: 'application/octet-stream';
                }
                if (!$mimeType) {
                    $mimeType = 'application/octet-stream';
                }

                $category = $this->resolveCategory($extension, $mimeType);
                $shareToken = $userLinks[$normalizedRelative] ?? null;

                $items[] = [
                    'name' => $fileName,
                    'path' => $userRelPath,
                    'is_dir' => false,
                    'extension' => $extension,
                    'size' => $fileSize,
                    'human_size' => $this->formatBytes($fileSize),
                    'mime_type' => $mimeType,
                    'category' => $category,
                    'modified_at' => date('Y-m-d H:i:s', $modifiedTime),
                    'modified_human' => $this->formatTimeAgo($modifiedTime),
                    'share_token' => $shareToken,
                    'share_url' => $shareToken ? url('/s/' . $shareToken) : null,
                    'download_url' => route('drive.download', ['path' => $userRelPath]),
                    'preview_url' => in_array($category, ['image', 'video']) ? route('drive.preview', ['path' => $userRelPath]) : null,
                ];
            }

            $userCurrentPath = $this->toUserPath($diskPath, $user);
            $breadcrumbs = $this->buildBreadcrumbs($userCurrentPath);
            $parentPath = '';
            if (!empty($userCurrentPath)) {
                $parts = explode('/', $userCurrentPath);
                array_pop($parts);
                $parentPath = implode('/', $parts);
            }

            return response()->json([
                'success' => true,
                'current_path' => $userCurrentPath,
                'parent_path' => $parentPath,
                'breadcrumbs' => $breadcrumbs,
                'items' => $items,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload files into current user directory with 20GB quota enforcement.
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $targetDir = (string) $request->input('path', '');
            $cleanDiskDir = $this->getSafePath($targetDir, false, $user);

            $disk = Storage::disk($this->disk);
            $destinationFolder = $disk->path($cleanDiskDir);

            if (!is_dir($destinationFolder)) {
                @mkdir($destinationFolder, 0755, true);
            }

            $files = $request->allFiles();
            if (empty($files)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files were uploaded.',
                ], 422);
            }

            // Flatten files list
            $fileList = [];
            $totalIncomingBytes = 0;
            foreach ($files as $key => $fileOrFiles) {
                if (is_array($fileOrFiles)) {
                    foreach ($fileOrFiles as $f) {
                        $fileList[] = $f;
                        $totalIncomingBytes += $f->getSize();
                    }
                } else {
                    $fileList[] = $fileOrFiles;
                    $totalIncomingBytes += $fileOrFiles->getSize();
                }
            }

            // 20GB Quota Enforcement
            if (!$user->hasStorageFor($totalIncomingBytes)) {
                $used = $user->humanUsedStorage();
                $quota = $user->humanStorageQuota();
                $incoming = $this->formatBytes($totalIncomingBytes);
                return response()->json([
                    'success' => false,
                    'message' => "Upload rejected: Storage quota exceeded. This upload ({$incoming}) would exceed your 20 GB limit. Current usage: {$used} / {$quota}.",
                ], 422);
            }

            $uploadedFiles = [];
            foreach ($fileList as $file) {
                if (!$file->isValid()) {
                    continue;
                }

                $originalName = $file->getClientOriginalName();
                $sanitizedName = $this->sanitizeFilename($originalName);

                // Check for duplicate filename and auto-rename
                $finalName = $this->getUniqueFilename($cleanDiskDir, $sanitizedName);

                $targetPath = $cleanDiskDir . '/' . $finalName;
                $file->storeAs($cleanDiskDir, $finalName, $this->disk);

                $userPath = $this->toUserPath($targetPath, $user);

                $uploadedFiles[] = [
                    'name' => $finalName,
                    'path' => $userPath,
                    'size' => $file->getSize(),
                    'human_size' => $this->formatBytes($file->getSize()),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully.',
                'files' => $uploadedFiles,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new folder inside user's directory.
     */
    public function mkdir(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\\\\\\/\\:\\*\\?\\"\\<\\>\\|]+$/'],
            'path' => ['nullable', 'string'],
        ]);

        try {
            $user = Auth::user();
            $parentPath = (string) $request->input('path', '');
            $cleanParentDisk = $this->getSafePath($parentPath, false, $user);
            $folderName = trim((string) $request->input('name'));

            $targetRelative = $cleanParentDisk . '/' . $folderName;
            $this->getSafePath($this->toUserPath($targetRelative, $user), false, $user);

            $disk = Storage::disk($this->disk);
            if ($disk->exists($targetRelative)) {
                return response()->json([
                    'success' => false,
                    'message' => 'A folder or file with this name already exists.',
                ], 422);
            }

            $disk->makeDirectory($targetRelative);

            return response()->json([
                'success' => true,
                'message' => "Folder '{$folderName}' created successfully.",
                'path' => $this->toUserPath($targetRelative, $user),
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rename a file or directory within user's storage.
     */
    public function rename(Request $request): JsonResponse
    {
        $request->validate([
            'path'     => ['required', 'string'],
            'new_name' => ['required', 'string', 'max:255', 'regex:/^[^\\\\\\/\:\*\?\"<>|]+$/'],
        ]);

        try {
            $user = Auth::user();
            $rawPath  = (string) $request->input('path');
            $newName  = trim((string) $request->input('new_name'));

            if (empty(trim($rawPath, '/\\'))) {
                return response()->json(['success' => false, 'message' => 'Cannot rename the root folder.'], 403);
            }

            $oldDiskPath = $this->getSafePath($rawPath, true, $user);
            $disk        = Storage::disk($this->disk);
            $fullOldPath = $disk->path($oldDiskPath);

            // Determine new disk path (same parent directory)
            $parentDiskPath = dirname($oldDiskPath);
            $newDiskPath    = ($parentDiskPath === '.' ? '' : $parentDiskPath . '/') . $newName;

            // Validate new path stays within user root
            $userRelNewPath = $this->toUserPath($newDiskPath, $user);
            $this->getSafePath($userRelNewPath, false, $user);

            if ($disk->exists($newDiskPath)) {
                return response()->json(['success' => false, 'message' => "A file or folder named '{$newName}' already exists here."], 422);
            }

            // Perform rename (works for both files and directories)
            if (is_dir($fullOldPath)) {
                // Rename directory via PHP rename for cross-OS compatibility
                $fullNewPath = $disk->path($newDiskPath);
                if (!rename($fullOldPath, $fullNewPath)) {
                    return response()->json(['success' => false, 'message' => 'Failed to rename folder.'], 500);
                }
                // Update shared links for folder and its children
                SharedLink::where('file_path', $oldDiskPath)->update(['file_path' => $newDiskPath]);
                SharedLink::where('file_path', 'LIKE', $oldDiskPath . '/%')->get()->each(function ($link) use ($oldDiskPath, $newDiskPath) {
                    $link->update(['file_path' => $newDiskPath . substr($link->file_path, strlen($oldDiskPath))]);
                });
            } else {
                // Rename file
                $disk->move($oldDiskPath, $newDiskPath);
                SharedLink::where('file_path', $oldDiskPath)->update(['file_path' => $newDiskPath]);
            }

            return response()->json([
                'success'  => true,
                'message'  => "Renamed to '{$newName}' successfully.",
                'new_path' => $this->toUserPath($newDiskPath, $user),
                'new_name' => $newName,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Rename failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a file or directory.
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        try {
            $user = Auth::user();
            $rawPath = (string) $request->input('path');
            if (empty(trim($rawPath, '/\\'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the root folder.',
                ], 403);
            }

            $cleanDiskPath = $this->getSafePath($rawPath, true, $user);
            $disk = Storage::disk($this->disk);
            $fullPath = $disk->path($cleanDiskPath);

            $deletedName = basename($cleanDiskPath);

            if (is_dir($fullPath)) {
                $disk->deleteDirectory($cleanDiskPath);
                SharedLink::where('file_path', 'LIKE', $cleanDiskPath . '/%')
                    ->orWhere('file_path', $cleanDiskPath)
                    ->delete();
            } else {
                $disk->delete($cleanDiskPath);
                SharedLink::where('file_path', $cleanDiskPath)->delete();
            }

            return response()->json([
                'success' => true,
                'message' => "'{$deletedName}' was deleted successfully.",
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream download for a file in the user's drive.
     */
    public function download(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $user = Auth::user();
            $rawPath = (string) $request->query('path', '');
            $cleanDiskPath = $this->getSafePath($rawPath, true, $user);

            $disk = Storage::disk($this->disk);
            $fullPath = $disk->path($cleanDiskPath);

            if (is_dir($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folders cannot be directly downloaded as a single file.',
                ], 400);
            }

            $fileName = basename($cleanDiskPath);
            return response()->download($fullPath, $fileName);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download error: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Generate or fetch a public share token for a file or folder.
     */
    public function share(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        try {
            $user = Auth::user();
            $rawPath = (string) $request->input('path');
            $cleanDiskPath = $this->getSafePath($rawPath, true, $user);

            $disk = Storage::disk($this->disk);
            $fullPath = $disk->path($cleanDiskPath);

            $isFolder = is_dir($fullPath);
            $sharedLink = SharedLink::forPath($cleanDiskPath, $user->id, $isFolder);

            return response()->json([
                'success' => true,
                'token' => $sharedLink->token,
                'share_url' => $sharedLink->share_url,
                'file_name' => $sharedLink->filename,
                'is_folder' => $sharedLink->is_folder,
                'downloads_count' => $sharedLink->downloads_count,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Unshare a file or folder by path or token.
     */
    public function unshare(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['nullable', 'string'],
            'token' => ['nullable', 'string'],
        ]);

        try {
            $user = Auth::user();
            $path = $request->input('path');
            $token = $request->input('token');

            if (!$path && !$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Path or token is required to unshare.',
                ], 422);
            }

            $query = SharedLink::where('user_id', $user->id);
            if ($token) {
                $query->where('token', $token);
            } else {
                $cleanDiskPath = $this->getSafePath((string) $path, false, $user);
                $query->where('file_path', $cleanDiskPath);
            }

            $deleted = $query->delete();

            return response()->json([
                'success' => true,
                'message' => $deleted > 0 ? 'Link unshared successfully.' : 'No active shared link found.',
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Return all active shared files and folders for the authenticated user.
     */
    public function sharedList(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $links = SharedLink::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $disk = Storage::disk($this->disk);
            $items = [];

            foreach ($links as $link) {
                $fullPath = $disk->path($link->file_path);
                $exists = file_exists($fullPath);
                $isDir = $link->is_folder || ($exists && is_dir($fullPath));
                $userRelPath = $this->toUserPath($link->file_path, $user);
                $fileName = $link->filename ?: basename($link->file_path);

                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $mimeType = $isDir ? 'directory' : 'application/octet-stream';
                if (!$isDir && function_exists('mime_content_type') && $exists) {
                    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
                }
                $category = $isDir ? 'folder' : $this->resolveCategory($extension, $mimeType);
                $size = (!$isDir && $exists) ? (filesize($fullPath) ?: 0) : 0;

                $items[] = [
                    'id' => $link->id,
                    'token' => $link->token,
                    'name' => $fileName,
                    'path' => $userRelPath,
                    'is_dir' => $isDir,
                    'category' => $category,
                    'extension' => $extension,
                    'size' => $size,
                    'human_size' => $isDir ? 'Folder' : $this->formatBytes($size),
                    'share_url' => $link->share_url,
                    'downloads_count' => $link->downloads_count,
                    'created_at' => $link->created_at?->format('Y-m-d H:i:s'),
                    'created_human' => $link->created_at ? $this->formatTimeAgo($link->created_at->timestamp) : 'recently',
                    'preview_url' => (!$isDir && in_array($category, ['image', 'video'])) ? route('drive.preview', ['path' => $userRelPath]) : null,
                ];
            }

            return response()->json([
                'success' => true,
                'items' => $items,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Inline preview stream for images and videos with Range request support.
     */
    public function preview(Request $request)
    {
        try {
            $user = Auth::user();
            $rawPath = (string) $request->query('path', '');
            $cleanDiskPath = $this->getSafePath($rawPath, true, $user);

            $disk = Storage::disk($this->disk);
            $fullPath = $disk->path($cleanDiskPath);

            if (is_dir($fullPath)) {
                abort(400, 'Directories cannot be previewed.');
            }

            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $knownMime = $this->getKnownMimeType($extension);
            $mime = $knownMime ?: (function_exists('mime_content_type') ? (mime_content_type($fullPath) ?: 'application/octet-stream') : 'application/octet-stream');

            $size = filesize($fullPath);
            $file = fopen($fullPath, 'rb');

            $headers = [
                'Content-Type' => $mime,
                'Accept-Ranges' => 'bytes',
                'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
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
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            abort(404, 'Preview unavailable: ' . $e->getMessage());
        }
    }

    /**
     * Return user's 20GB quota stats and system stats.
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $usedBytes = $user->usedStorageBytes();
            $quotaBytes = User::STORAGE_QUOTA_BYTES;
            $freeBytes = $user->remainingStorageBytes();
            $percentUsed = $user->storagePercentage();

            return response()->json([
                'success' => true,
                'used_bytes' => $usedBytes,
                'quota_bytes' => $quotaBytes,
                'free_bytes' => $freeBytes,
                'percent_used' => $percentUsed,
                'used_human' => $user->humanUsedStorage(),
                'quota_human' => $user->humanStorageQuota(),
                'free_human' => User::formatBytes($freeBytes),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_superadmin' => $user->isSuperAdmin(),
                ],
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build breadcrumbs array from relative path.
     */
    protected function buildBreadcrumbs(string $cleanPath): array
    {
        $crumbs = [
            ['name' => 'My Drive', 'path' => '']
        ];

        if (empty($cleanPath)) {
            return $crumbs;
        }

        $parts = explode('/', $cleanPath);
        $accumulated = '';

        foreach ($parts as $part) {
            if (empty($part)) continue;
            $accumulated = empty($accumulated) ? $part : $accumulated . '/' . $part;
            $crumbs[] = [
                'name' => $part,
                'path' => $accumulated,
            ];
        }

        return $crumbs;
    }

    /**
     * Sanitize filename to avoid invalid Windows/Linux characters.
     */
    protected function sanitizeFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $cleanName = preg_replace('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|\\x00-\\x1F]/', '_', $name);
        $cleanName = trim($cleanName, ' .');

        if (empty($cleanName)) {
            $cleanName = 'file_' . time();
        }

        return empty($ext) ? $cleanName : $cleanName . '.' . $ext;
    }

    /**
     * Generate unique filename if already exists in directory.
     */
    protected function getUniqueFilename(string $dir, string $filename): string
    {
        $disk = Storage::disk($this->disk);
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $dotExt = empty($ext) ? '' : '.' . $ext;

        $target = empty($dir) ? $filename : $dir . '/' . $filename;
        if (!$disk->exists($target)) {
            return $filename;
        }

        $counter = 1;
        do {
            $candidateName = "{$baseName} ({$counter}){$dotExt}";
            $target = empty($dir) ? $candidateName : $dir . '/' . $candidateName;
            $counter++;
        } while ($disk->exists($target));

        return $candidateName;
    }

    /**
     * Format bytes to human readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 1): string
    {
        return User::formatBytes($bytes, $precision);
    }

    /**
     * Format timestamp to human readable relative time.
     */
    protected function formatTimeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
        return date('M j, Y', $timestamp);
    }

    /**
     * Return canonical MIME type for media files based on extension.
     */
    public static function getKnownMimeType(string $ext): ?string
    {
        $map = [
            // Video
            'mp4' => 'video/mp4',
            'm4v' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'ogv' => 'video/ogg',
            'mov' => 'video/quicktime',
            'qt' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            'flv' => 'video/x-flv',
            'wmv' => 'video/x-ms-wmv',
            '3gp' => 'video/3gpp',
            'ts' => 'video/mp2t',

            // Images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            'avif' => 'image/avif',

            // Audio
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            'wma' => 'audio/x-ms-wma',
            'opus' => 'audio/opus',

            // Documents
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'json' => 'application/json',
            'zip' => 'application/zip',
        ];

        return $map[strtolower($ext)] ?? null;
    }

    /**
     * Determine category for icon presentation and preview capability.
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
