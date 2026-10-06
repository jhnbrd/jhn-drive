<?php

namespace App\Http\Controllers;

use App\Models\SharedLink;
use App\Models\User;
use App\Services\DrivePathService;
use App\Services\FileMetadataService;
use App\Services\FileResponseService;
use App\Services\HomeSummaryService;
use App\Services\TrashService;
use App\Services\ZipArchiveService;
use App\Support\UploadLimits;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DriveController extends Controller
{
    /**
     * Storage disk name.
     */
    protected string $disk = 'local_drive';

    public function __construct(
        private readonly DrivePathService $paths,
        private readonly FileMetadataService $metadata,
        private readonly FileResponseService $responses,
        private readonly HomeSummaryService $homeSummary,
        private readonly TrashService $trash,
        private readonly ZipArchiveService $archives,
    ) {}

    /**
     * Get the realpath of the authenticated user's isolated root storage directory.
     */
    protected function getUserRootPath(User $user): string
    {
        return $this->paths->userRoot($user);
    }

    /**
     * Ensure path is safe from traversal attacks and stays within the user's isolated storage root.
     * Returns the disk-relative path (e.g. 'users/1/folder/file.txt').
     */
    protected function getSafePath(string $path = '', bool $mustExist = false, ?User $user = null): string
    {
        $user ??= Auth::user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        return $this->paths->resolveUserPath($user, $path, $mustExist);
    }

    /**
     * Convert disk-relative path (users/1/foo) to user-facing path (foo).
     */
    protected function toUserPath(string $diskPath, User $user): string
    {
        return $this->paths->toUserPath($user, $diskPath);
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
     * Return the cached filesystem-backed Home summary for the current user.
     */
    public function home(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $summary = $this->homeSummary->summary($user, $request->boolean('refresh'));

            return response()->json([
                'success' => true,
                ...$summary,
                'upload_limits' => UploadLimits::clientConfig(),
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load Home right now.',
            ], 500);
        }
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

            if (! is_dir($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Directory not found.',
                ], 404);
            }

            $rawDirs = array_values(array_filter(
                $disk->directories($diskPath),
                fn (string $directory): bool => basename($directory) !== '.trash',
            ));
            $rawFiles = $disk->files($diskPath);

            $items = [];

            // Query active shared links for the user
            $userLinks = SharedLink::where('user_id', $user->id)
                ->get()
                ->keyBy(fn (SharedLink $link): string => str_replace('\\', '/', $link->file_path));

            // Process directories
            foreach ($rawDirs as $dirPath) {
                $dirName = basename($dirPath);
                $dirFullPath = $disk->path($dirPath);
                $modifiedTime = filemtime($dirFullPath) ?: time();
                $userRelPath = $this->toUserPath($dirPath, $user);
                $normalizedRelative = str_replace('\\', '/', $dirPath);

                $dirItemsCount = count(glob($dirFullPath.'/*') ?: []);
                $shareLink = $userLinks->get($normalizedRelative);

                $items[] = [
                    'name' => $dirName,
                    'path' => $userRelPath,
                    'is_dir' => true,
                    'extension' => '',
                    'size' => 0,
                    'human_size' => $dirItemsCount.' '.($dirItemsCount === 1 ? 'item' : 'items'),
                    'item_count' => $dirItemsCount,
                    'mime_type' => 'directory',
                    'category' => 'folder',
                    'modified_at' => date('Y-m-d H:i:s', $modifiedTime),
                    'modified_human' => $this->formatTimeAgo($modifiedTime),
                    'share_token' => $shareLink?->token,
                    'share_url' => $shareLink?->share_url,
                    'downloads_count' => $shareLink?->downloads_count ?? 0,
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

                $knownMime = $this->metadata->knownMimeType($extension);
                $mimeType = $knownMime;
                if (! $mimeType && function_exists('mime_content_type') && file_exists($fileFullPath)) {
                    $mimeType = mime_content_type($fileFullPath) ?: 'application/octet-stream';
                }
                if (! $mimeType) {
                    $mimeType = 'application/octet-stream';
                }

                $category = $this->resolveCategory($extension, $mimeType);
                $shareLink = $userLinks->get($normalizedRelative);

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
                    'share_token' => $shareLink?->token,
                    'share_url' => $shareLink?->share_url,
                    'downloads_count' => $shareLink?->downloads_count ?? 0,
                    'download_url' => route('drive.download', ['path' => $userRelPath]),
                    'preview_url' => in_array($category, ['image', 'video']) ? route('drive.preview', ['path' => $userRelPath]) : null,
                ];
            }

            $userCurrentPath = $this->toUserPath($diskPath, $user);
            $breadcrumbs = $this->buildBreadcrumbs($userCurrentPath);
            $parentPath = '';
            if (! empty($userCurrentPath)) {
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
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load this folder right now.',
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

            if (! is_dir($destinationFolder)) {
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

            $uploadLimits = UploadLimits::clientConfig();

            if (count($fileList) > $uploadLimits['max_files_per_request']) {
                return response()->json([
                    'success' => false,
                    'message' => "Too many files. The server accepts at most {$uploadLimits['max_files_per_request']} files per request.",
                    'upload_limits' => $uploadLimits,
                ], 422);
            }

            foreach ($fileList as $file) {
                if ($file->getSize() > $uploadLimits['max_file_bytes']) {
                    return response()->json([
                        'success' => false,
                        'message' => "{$file->getClientOriginalName()} exceeds the server limit of {$uploadLimits['max_file_human']} per file.",
                        'upload_limits' => $uploadLimits,
                    ], 422);
                }
            }

            if ($totalIncomingBytes > $uploadLimits['max_request_bytes']) {
                return response()->json([
                    'success' => false,
                    'message' => "The selected files exceed the server request limit of {$uploadLimits['max_request_human']}.",
                    'upload_limits' => $uploadLimits,
                ], 422);
            }

            // 20GB Quota Enforcement
            if (! $user->hasStorageFor($totalIncomingBytes)) {
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
                if (! $file->isValid()) {
                    continue;
                }

                $originalName = $file->getClientOriginalName();
                $sanitizedName = $this->sanitizeFilename($originalName);

                // Check for duplicate filename and auto-rename
                $finalName = $this->getUniqueFilename($cleanDiskDir, $sanitizedName);

                $targetPath = $cleanDiskDir.'/'.$finalName;
                $file->storeAs($cleanDiskDir, $finalName, $this->disk);

                $userPath = $this->toUserPath($targetPath, $user);

                $uploadedFiles[] = [
                    'name' => $finalName,
                    'path' => $userPath,
                    'size' => $file->getSize(),
                    'human_size' => $this->formatBytes($file->getSize()),
                ];
            }

            if ($uploadedFiles !== []) {
                $this->homeSummary->invalidate($user);
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles).' file(s) uploaded successfully.',
                'files' => $uploadedFiles,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed. Please try again.',
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

            $targetRelative = $cleanParentDisk.'/'.$folderName;
            $this->getSafePath($this->toUserPath($targetRelative, $user), false, $user);

            $disk = Storage::disk($this->disk);
            if ($disk->exists($targetRelative)) {
                return response()->json([
                    'success' => false,
                    'message' => 'A folder or file with this name already exists.',
                ], 422);
            }

            $disk->makeDirectory($targetRelative);
            $this->homeSummary->invalidate($user);

            return response()->json([
                'success' => true,
                'message' => "Folder '{$folderName}' created successfully.",
                'path' => $this->toUserPath($targetRelative, $user),
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create the folder.',
            ], 400);
        }
    }

    /**
     * Rename a file or directory within user's storage.
     */
    public function rename(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
            'new_name' => ['required', 'string', 'max:255', 'regex:/^[^\\\\\\/\:\*\?\"<>|]+$/'],
        ]);

        try {
            $user = Auth::user();
            $rawPath = (string) $request->input('path');
            $newName = trim((string) $request->input('new_name'));

            if (empty(trim($rawPath, '/\\'))) {
                return response()->json(['success' => false, 'message' => 'Cannot rename the root folder.'], 403);
            }

            $oldDiskPath = $this->getSafePath($rawPath, true, $user);
            $disk = Storage::disk($this->disk);
            $fullOldPath = $disk->path($oldDiskPath);

            // Determine new disk path (same parent directory)
            $parentDiskPath = dirname($oldDiskPath);
            $newDiskPath = ($parentDiskPath === '.' ? '' : $parentDiskPath.'/').$newName;

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
                if (! rename($fullOldPath, $fullNewPath)) {
                    return response()->json(['success' => false, 'message' => 'Failed to rename folder.'], 500);
                }
                // Update shared links for folder and its children
                SharedLink::where('file_path', $oldDiskPath)->update(['file_path' => $newDiskPath]);
                SharedLink::where('file_path', 'LIKE', $oldDiskPath.'/%')->get()->each(function ($link) use ($oldDiskPath, $newDiskPath) {
                    $link->update(['file_path' => $newDiskPath.substr($link->file_path, strlen($oldDiskPath))]);
                });
            } else {
                // Rename file
                $disk->move($oldDiskPath, $newDiskPath);
                SharedLink::where('file_path', $oldDiskPath)->update(['file_path' => $newDiskPath]);
            }
            $this->homeSummary->invalidate($user);

            return response()->json([
                'success' => true,
                'message' => "Renamed to '{$newName}' successfully.",
                'new_path' => $this->toUserPath($newDiskPath, $user),
                'new_name' => $newName,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to rename this item.',
            ], 500);
        }
    }

    /**
     * Move a file or directory into the user's recoverable Trash.
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
            $item = $this->trash->move($user, $cleanDiskPath);
            $deletedName = $item->original_name;

            return response()->json([
                'success' => true,
                'message' => "'{$deletedName}' was moved to Trash.",
                'trash_id' => $item->id,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete this item.',
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
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'The file could not be downloaded.',
            ], 404);
        }
    }

    /**
     * Download selected files and folders as a bounded ZIP archive.
     */
    public function downloadSelection(Request $request): BinaryFileResponse|JsonResponse
    {
        $validated = $request->validate([
            'paths' => ['required', 'array', 'min:1', 'max:100'],
            'paths.*' => ['required', 'string', 'distinct'],
        ]);

        try {
            $user = Auth::user();
            $disk = Storage::disk($this->disk);
            $fullPaths = [];

            foreach ($validated['paths'] as $path) {
                $fullPaths[] = $disk->path($this->getSafePath($path, true, $user));
            }

            $zipPath = $this->archives->createFromPaths(
                $this->getUserRootPath($user),
                $fullPaths,
            );
            $filename = 'jhn-drive-selection-'.now()->format('Ymd-His').'.zip';

            return response()
                ->download($zipPath, $filename, ['Content-Type' => 'application/zip'])
                ->deleteFileAfterSend(true);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\RuntimeException $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'The selected items could not be downloaded.',
            ], 500);
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
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create a share link.',
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

            if (! $path && ! $token) {
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
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to revoke the share link.',
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
                if (! $isDir && function_exists('mime_content_type') && $exists) {
                    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
                }
                $category = $isDir ? 'folder' : $this->resolveCategory($extension, $mimeType);
                $size = (! $isDir && $exists) ? (filesize($fullPath) ?: 0) : 0;

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
                    'preview_url' => (! $isDir && in_array($category, ['image', 'video'])) ? route('drive.preview', ['path' => $userRelPath]) : null,
                ];
            }

            return response()->json([
                'success' => true,
                'items' => $items,
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load shared items.',
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
            $diskPath = $this->getSafePath((string) $request->query('path', ''), true, $user);
            $fullPath = Storage::disk($this->disk)->path($diskPath);

            if (is_dir($fullPath)) {
                abort(400, 'Directories cannot be previewed.');
            }

            return $this->responses->preview($request, $fullPath);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);
            abort(404, 'Preview unavailable.');
        }
    }

    /**
     * Return user's 20GB quota stats and system stats.
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $storage = $this->homeSummary->filesystemSummary($user)['storage'];

            return response()->json([
                'success' => true,
                ...$storage,
                'upload_limits' => UploadLimits::clientConfig(),
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
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to calculate storage information.',
            ], 500);
        }
    }

    /**
     * Build breadcrumbs array from relative path.
     */
    protected function buildBreadcrumbs(string $cleanPath): array
    {
        return $this->metadata->breadcrumbs($cleanPath);
    }

    /**
     * Sanitize filename to avoid invalid Windows/Linux characters.
     */
    protected function sanitizeFilename(string $filename): string
    {
        return $this->metadata->sanitizeFilename($filename);
    }

    /**
     * Generate unique filename if already exists in directory.
     */
    protected function getUniqueFilename(string $dir, string $filename): string
    {
        return $this->metadata->uniqueFilename($dir, $filename, $this->disk);
    }

    /**
     * Format bytes to human readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 1): string
    {
        return $this->metadata->formatBytes($bytes, $precision);
    }

    /**
     * Format timestamp to human readable relative time.
     */
    protected function formatTimeAgo(int $timestamp): string
    {
        return $this->metadata->formatTimeAgo($timestamp);
    }

    /**
     * Return canonical MIME type for media files based on extension.
     */

    /**
     * Determine category for icon presentation and preview capability.
     */
    protected function resolveCategory(string $ext, string $mime): string
    {
        return $this->metadata->category($ext, $mime);
    }
}
