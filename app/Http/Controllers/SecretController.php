<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Handles the PIN-gated secret vault.
 *
 * - No user login required
 * - Access is controlled by session (PIN verification)
 * - Files are stored encrypted on disk (AES-256-CBC via Laravel encrypt())
 * - Fast video streaming via ephemeral stream caching + HTTP Range (206)
 * - Cached thumbnail generation for images & videos
 * - All routes include noindex headers to block crawlers
 */
class SecretController extends Controller
{
    protected string $secretStoragePath;
    protected string $cacheStoragePath;

    public function __construct()
    {
        $this->secretStoragePath = storage_path('app/drive_storage/secret');
        $this->cacheStoragePath  = storage_path('app/drive_storage/secret/.cache');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PIN ENTRY PAGE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Show the PIN entry page.
     */
    public function showPin()
    {
        // If already authenticated, go straight to vault
        if (session('secret_vault_auth')) {
            $grantedAt = session('secret_vault_granted_at');
            $ttl = (int) config('secret.session_ttl_minutes', 120);
            if ($grantedAt && now()->diffInMinutes(\Carbon\Carbon::createFromTimestamp($grantedAt)) <= $ttl) {
                return redirect()->route('secret.vault');
            }
            // Expired — clear it
            session()->forget(['secret_vault_auth', 'secret_vault_granted_at']);
        }

        return response()
            ->view('secret.pin')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Verify the submitted PIN.
     */
    public function verifyPin(Request $request)
    {
        $request->validate(['pin' => ['required', 'string', 'max:20']]);

        $pin = (string) $request->input('pin');
        $config = $this->loadConfig();

        if (!$config['enabled']) {
            return back()->withErrors(['pin' => 'The secret vault is currently disabled.']);
        }

        if (!password_verify($pin, $config['pin_hash'])) {
            // Small delay to mitigate brute force
            sleep(1);
            return back()->withErrors(['pin' => 'Incorrect PIN. Access denied.']);
        }

        // Grant session access
        session([
            'secret_vault_auth'       => true,
            'secret_vault_granted_at' => now()->timestamp,
        ]);

        return redirect()->route('secret.vault');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VAULT BROWSER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Show the secret vault file browser.
     */
    public function showVault()
    {
        $this->ensureStorageExists();
        $this->pruneExpiredCache();
        $files = $this->listVaultFiles();

        return response()
            ->view('secret.vault', compact('files'))
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, noimageindex')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FILE STREAMING (PREVIEW & DOWNLOAD)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Stream a decrypted file for inline preview (supports HTTP Range for video/audio).
     * Optimizes performance by caching decrypted media files in an ephemeral folder
     * so that HTML5 video players do not decrypt 100MB+ in memory on every 16KB Range slice.
     */
    public function preview(Request $request)
    {
        try {
            $fileName = $this->sanitizeFileName((string) $request->query('file', ''));
            $filePath = $this->secretStoragePath . DIRECTORY_SEPARATOR . $fileName . '.enc';

            if (!file_exists($filePath) || !is_file($filePath)) {
                abort(404, 'File not found.');
            }

            $ext  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $mime = \App\Http\Controllers\DriveController::getKnownMimeType($ext) ?? 'application/octet-stream';
            $category = $this->resolveCategory($ext, $mime);

            // For videos and audios, use the ephemeral file stream for instant seek & low memory
            if (in_array($category, ['video', 'audio'])) {
                return $this->streamEphemeralMedia($filePath, $fileName, $mime, $request);
            }

            // For images / pdfs, decrypt in memory directly
            $decrypted = $this->decryptFile($filePath);
            $size      = strlen($decrypted);

            return response($decrypted, 200, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                'Content-Length'      => (string) $size,
                'X-Robots-Tag'        => 'noindex, nofollow',
                'Cache-Control'       => 'private, max-age=3600',
            ]);

        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Secret vault preview failed: ' . $e->getMessage());
            abort(404, 'Preview unavailable.');
        }
    }

    /**
     * Serve an optimized thumbnail for images or videos.
     */
    public function thumbnail(Request $request)
    {
        try {
            $fileName = $this->sanitizeFileName((string) $request->query('file', ''));
            $filePath = $this->secretStoragePath . DIRECTORY_SEPARATOR . $fileName . '.enc';

            if (!file_exists($filePath) || !is_file($filePath)) {
                abort(404, 'File not found.');
            }

            $ext  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $mime = \App\Http\Controllers\DriveController::getKnownMimeType($ext) ?? 'application/octet-stream';
            $category = $this->resolveCategory($ext, $mime);

            // 1. Check if a dedicated saved thumbnail exists (e.g. from video client capture)
            $dedicatedThumb = $this->secretStoragePath . DIRECTORY_SEPARATOR . '.' . $fileName . '.thumb.enc';
            if (file_exists($dedicatedThumb)) {
                $rawThumb = $this->decryptFile($dedicatedThumb);
                return response($rawThumb, 200, [
                    'Content-Type'        => 'image/jpeg',
                    'Content-Disposition' => 'inline; filename="thumb_' . $fileName . '.jpg"',
                    'Content-Length'      => (string) strlen($rawThumb),
                    'X-Robots-Tag'        => 'noindex, nofollow',
                    'Cache-Control'       => 'private, max-age=86400',
                ]);
            }

            // 2. For image files, generate a lightweight cached resized thumbnail using GD
            if ($category === 'image') {
                $cacheHash = md5($fileName . '_' . filemtime($filePath));
                $cachedThumbPath = $this->cacheStoragePath . DIRECTORY_SEPARATOR . 'thumb_' . $cacheHash . '.jpg';

                if (file_exists($cachedThumbPath) && filesize($cachedThumbPath) > 0) {
                    $thumbData = file_get_contents($cachedThumbPath);
                    return response($thumbData, 200, [
                        'Content-Type'        => 'image/jpeg',
                        'Content-Length'      => (string) strlen($thumbData),
                        'X-Robots-Tag'        => 'noindex, nofollow',
                        'Cache-Control'       => 'private, max-age=86400',
                    ]);
                }

                // Generate thumbnail via GD
                $rawImage = $this->decryptFile($filePath);
                $gdImg = @imagecreatefromstring($rawImage);
                if ($gdImg !== false) {
                    $origW = imagesx($gdImg);
                    $origH = imagesy($gdImg);

                    $maxDim = 320;
                    if ($origW > $maxDim || $origH > $maxDim) {
                        if ($origW > $origH) {
                            $newW = $maxDim;
                            $newH = (int) round(($origH / $origW) * $maxDim);
                        } else {
                            $newH = $maxDim;
                            $newW = (int) round(($origW / $origH) * $maxDim);
                        }
                    } else {
                        $newW = $origW;
                        $newH = $origH;
                    }

                    $thumbImg = imagecreatetruecolor($newW, $newH);
                    imagealphablending($thumbImg, false);
                    imagesavealpha($thumbImg, true);
                    imagecopyresampled($thumbImg, $gdImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

                    ob_start();
                    imagejpeg($thumbImg, null, 80);
                    $jpegData = ob_get_clean();

                    imagedestroy($gdImg);
                    imagedestroy($thumbImg);

                    if ($jpegData) {
                        @file_put_contents($cachedThumbPath, $jpegData);
                        return response($jpegData, 200, [
                            'Content-Type'        => 'image/jpeg',
                            'Content-Length'      => (string) strlen($jpegData),
                            'X-Robots-Tag'        => 'noindex, nofollow',
                            'Cache-Control'       => 'private, max-age=86400',
                        ]);
                    }
                }

                // Fallback if GD fails: return original image
                return response($rawImage, 200, [
                    'Content-Type'        => $mime,
                    'Content-Length'      => (string) strlen($rawImage),
                    'X-Robots-Tag'        => 'noindex, nofollow',
                    'Cache-Control'       => 'private, max-age=86400',
                ]);
            }

            abort(404, 'Thumbnail not available.');
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            abort(404, 'Thumbnail unavailable.');
        }
    }

    /**
     * Download a decrypted file.
     */
    public function download(Request $request)
    {
        try {
            $fileName = $this->sanitizeFileName((string) $request->query('file', ''));
            $filePath = $this->secretStoragePath . DIRECTORY_SEPARATOR . $fileName . '.enc';

            if (!file_exists($filePath) || !is_file($filePath)) {
                abort(404, 'File not found.');
            }

            $decrypted = $this->decryptFile($filePath);
            $mime      = \App\Http\Controllers\DriveController::getKnownMimeType(
                strtolower(pathinfo($fileName, PATHINFO_EXTENSION))
            ) ?? 'application/octet-stream';

            return response($decrypted, 200, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Content-Length'      => (string) strlen($decrypted),
                'X-Robots-Tag'        => 'noindex, nofollow',
                'Cache-Control'       => 'no-store',
            ]);

        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            abort(404, 'Download unavailable.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FILE UPLOAD TO VAULT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Upload and encrypt a file into the secret vault.
     * Optionally accepts a client-generated video/media thumbnail (base64 or file).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file'      => ['required', 'file', 'max:512000'], // 500 MB max per file
            'thumbnail' => ['nullable', 'string'],            // Optional base64 data URL
        ]);

        try {
            $this->ensureStorageExists();

            $uploaded  = $request->file('file');
            $origName  = $this->sanitizeFilenameForVault($uploaded->getClientOriginalName());
            $encPath   = $this->secretStoragePath . DIRECTORY_SEPARATOR . $origName . '.enc';

            // Encrypt raw bytes
            $raw       = file_get_contents($uploaded->getRealPath());
            $encrypted = encrypt($raw);
            file_put_contents($encPath, $encrypted);

            // If a client-generated thumbnail was attached (e.g. canvas frame from video)
            $thumbInput = $request->input('thumbnail');
            if (!empty($thumbInput) && str_starts_with($thumbInput, 'data:image/')) {
                $parts = explode(',', $thumbInput);
                if (count($parts) === 2) {
                    $thumbBytes = base64_decode($parts[1]);
                    if ($thumbBytes !== false && strlen($thumbBytes) > 10) {
                        $thumbEncPath = $this->secretStoragePath . DIRECTORY_SEPARATOR . '.' . $origName . '.thumb.enc';
                        file_put_contents($thumbEncPath, encrypt($thumbBytes));
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "'{$origName}' encrypted and stored.",
                'file'    => $origName,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Destroy the vault session (lock the vault) and purge ephemeral decrypted cache.
     */
    public function lock()
    {
        session()->forget(['secret_vault_auth', 'secret_vault_granted_at']);
        $this->purgeCache();
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Stream large encrypted media (video/audio) using an ephemeral decrypted cache file.
     * This avoids decrypting full files into PHP RAM on every HTTP 206 Range request.
     */
    private function streamEphemeralMedia(string $encPath, string $fileName, string $mime, Request $request)
    {
        $this->ensureStorageExists();

        // Unique cache key based on file name and last modified timestamp
        $cacheHash = md5($fileName . '_' . filemtime($encPath) . '_' . config('app.key'));
        $cachedDecryptedPath = $this->cacheStoragePath . DIRECTORY_SEPARATOR . $cacheHash . '.stream';

        // Decrypt only if not already cached
        if (!file_exists($cachedDecryptedPath) || filesize($cachedDecryptedPath) === 0) {
            $decrypted = $this->decryptFile($encPath);
            file_put_contents($cachedDecryptedPath, $decrypted, LOCK_EX);
            unset($decrypted); // free immediately from RAM
        } else {
            // Touch to extend access time
            @touch($cachedDecryptedPath);
        }

        $size = filesize($cachedDecryptedPath);
        $file = fopen($cachedDecryptedPath, 'rb');

        $headers = [
            'Content-Type'        => $mime,
            'Accept-Ranges'       => 'bytes',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'X-Robots-Tag'        => 'noindex, nofollow',
            'Cache-Control'       => 'private, max-age=3600',
        ];

        // Handle HTTP Range header (streaming & video seeking)
        if ($request->header('Range')) {
            $range = $request->header('Range');
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = (int) $matches[1];
                $end   = !empty($matches[2]) ? (int) $matches[2] : ($size - 1);

                if ($start >= $size || $end >= $size || $start > $end) {
                    fclose($file);
                    return response('', 416, ['Content-Range' => "bytes */{$size}"]);
                }

                $length = $end - $start + 1;
                fseek($file, $start);

                $headers['Content-Range']  = "bytes {$start}-{$end}/{$size}";
                $headers['Content-Length'] = (string) $length;

                return response()->stream(function () use ($file, $length) {
                    $chunkSize = 65536; // 64 KB buffer
                    $bytesSent = 0;
                    while (!feof($file) && $bytesSent < $length && connection_status() === CONNECTION_NORMAL) {
                        $toRead = min($chunkSize, $length - $bytesSent);
                        $buffer = fread($file, $toRead);
                        echo $buffer;
                        flush();
                        $bytesSent += strlen($buffer);
                    }
                    fclose($file);
                }, 206, $headers);
            }
        }

        $headers['Content-Length'] = (string) $size;

        return response()->stream(function () use ($file) {
            $chunkSize = 65536;
            while (!feof($file) && connection_status() === CONNECTION_NORMAL) {
                echo fread($file, $chunkSize);
                flush();
            }
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Load config from secret.php.local (gitignored) falling back to secret.php.example skeleton.
     */
    private function loadConfig(): array
    {
        $localPath = config_path('secret.php.local');
        if (file_exists($localPath)) {
            return require $localPath;
        }
        return [
            'enabled'              => (bool) config('secret.enabled', false),
            'pin_hash'             => (string) config('secret.pin_hash', ''),
            'session_ttl_minutes'  => (int) config('secret.session_ttl_minutes', 120),
        ];
    }

    /**
     * Ensure the secret storage directory and cache directory exist with strict permissions.
     */
    private function ensureStorageExists(): void
    {
        if (!is_dir($this->secretStoragePath)) {
            @mkdir($this->secretStoragePath, 0700, true);
        }
        if (!is_dir($this->cacheStoragePath)) {
            @mkdir($this->cacheStoragePath, 0700, true);
        }
    }

    /**
     * Prune cached stream and thumbnail files older than 2 hours.
     */
    private function pruneExpiredCache(): void
    {
        if (!is_dir($this->cacheStoragePath)) return;
        $expiry = time() - (2 * 3600); // 2 hours
        foreach (glob($this->cacheStoragePath . DIRECTORY_SEPARATOR . '*') as $file) {
            if (is_file($file) && filemtime($file) < $expiry) {
                @unlink($file);
            }
        }
    }

    /**
     * Purge all ephemeral cache files immediately on vault lock.
     */
    private function purgeCache(): void
    {
        if (!is_dir($this->cacheStoragePath)) return;
        foreach (glob($this->cacheStoragePath . DIRECTORY_SEPARATOR . '*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * List all .enc files in the vault directory (ignoring hidden files like .thumb.enc).
     */
    private function listVaultFiles(): array
    {
        $this->ensureStorageExists();
        $files = [];

        foreach (glob($this->secretStoragePath . DIRECTORY_SEPARATOR . '*.enc') as $encPath) {
            $encName  = basename($encPath);
            // Ignore hidden/dotfiles such as .video.mp4.thumb.enc
            if (str_starts_with($encName, '.')) {
                continue;
            }

            $origName = substr($encName, 0, -4); // remove .enc
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $size     = filesize($encPath) ?: 0;
            $modified = filemtime($encPath) ?: time();

            $mime     = \App\Http\Controllers\DriveController::getKnownMimeType($ext) ?? 'application/octet-stream';
            $category = $this->resolveCategory($ext, $mime);

            $hasDedicatedThumb = file_exists($this->secretStoragePath . DIRECTORY_SEPARATOR . '.' . $origName . '.thumb.enc');

            $files[] = [
                'name'          => $origName,
                'ext'           => $ext,
                'size'          => $size,
                'human_size'    => $this->formatBytes($size),
                'category'      => $category,
                'mime'          => $mime,
                'modified_at'   => date('Y-m-d H:i:s', $modified),
                'modified_human'=> $this->formatTimeAgo($modified),
                'preview_url'   => in_array($category, ['image', 'video', 'audio'])
                    ? route('secret.preview', ['file' => $origName])
                    : null,
                'thumbnail_url' => ($category === 'image' || $hasDedicatedThumb)
                    ? route('secret.thumbnail', ['file' => $origName])
                    : null,
                'has_thumb'     => $hasDedicatedThumb || $category === 'image',
                'download_url'  => route('secret.download', ['file' => $origName]),
            ];
        }

        // Sort: alphabetical by name
        usort($files, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $files;
    }

    /**
     * Decrypt an .enc file back to raw bytes. Uses Laravel's encrypt/decrypt.
     */
    private function decryptFile(string $encPath): string
    {
        $encrypted = file_get_contents($encPath);
        if ($encrypted === false) {
            throw new Exception('Cannot read encrypted file.');
        }
        return decrypt($encrypted);
    }

    /**
     * Sanitize a filename to prevent path traversal within the vault directory.
     */
    private function sanitizeFileName(string $name): string
    {
        $name = str_replace(["\0", '/', '\\', '..'], '', $name);
        $name = basename($name);
        if (empty($name)) {
            abort(400, 'Invalid file name.');
        }
        return $name;
    }

    /**
     * Sanitize an uploaded file name for storage.
     */
    private function sanitizeFilenameForVault(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $clean = preg_replace('/[\\\\\\/\:\*\?\"<>|\x00-\x1F]/', '_', $name);
        $clean = trim($clean, ' .');
        if (empty($clean)) {
            $clean = 'file_' . time();
        }
        return empty($ext) ? $clean : $clean . '.' . $ext;
    }

    private function resolveCategory(string $ext, string $mime): string
    {
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif'];
        $videoExts = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'avi', 'mkv', 'm4v', 'flv', 'wmv', '3gp', 'ts'];
        $audioExts = ['mp3', 'wav', 'm4a', 'flac', 'aac', 'opus'];

        if (str_starts_with($mime, 'image/') || in_array($ext, $imageExts)) return 'image';
        if (str_starts_with($mime, 'video/') || in_array($ext, $videoExts)) return 'video';
        if (str_starts_with($mime, 'audio/') || in_array($ext, $audioExts)) return 'audio';
        if ($ext === 'pdf') return 'pdf';
        return 'other';
    }

    private function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow   = min(floor(log($bytes, 1024)), count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    private function formatTimeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
        return date('M j, Y', $timestamp);
    }
}
