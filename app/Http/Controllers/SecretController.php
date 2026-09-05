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
 * - All routes include noindex headers to block crawlers
 */
class SecretController extends Controller
{
    protected string $secretStoragePath;

    public function __construct()
    {
        $this->secretStoragePath = storage_path('app/drive_storage/secret');
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
     * Stream a decrypted file for inline preview (supports HTTP Range for video).
     */
    public function preview(Request $request)
    {
        try {
            $fileName = $this->sanitizeFileName((string) $request->query('file', ''));
            $filePath = $this->secretStoragePath . DIRECTORY_SEPARATOR . $fileName . '.enc';

            if (!file_exists($filePath) || !is_file($filePath)) {
                abort(404, 'File not found.');
            }

            $decrypted = $this->decryptFile($filePath);
            $size      = strlen($decrypted);
            $mime      = \App\Http\Controllers\DriveController::getKnownMimeType(
                strtolower(pathinfo($fileName, PATHINFO_EXTENSION))
            ) ?? 'application/octet-stream';

            $headers = [
                'Content-Type'        => $mime,
                'Accept-Ranges'       => 'bytes',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                'X-Robots-Tag'        => 'noindex, nofollow',
                'Cache-Control'       => 'no-store',
            ];

            // Handle Range requests (for video seeking)
            if ($request->header('Range')) {
                $range = $request->header('Range');
                if (preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
                    $start  = (int) $m[1];
                    $end    = !empty($m[2]) ? (int) $m[2] : ($size - 1);

                    if ($start >= $size || $end >= $size || $start > $end) {
                        return response('', 416, ['Content-Range' => "bytes */{$size}"]);
                    }

                    $length = $end - $start + 1;
                    $chunk  = substr($decrypted, $start, $length);

                    $headers['Content-Range']  = "bytes {$start}-{$end}/{$size}";
                    $headers['Content-Length'] = (string) $length;

                    return response($chunk, 206, $headers);
                }
            }

            $headers['Content-Length'] = (string) $size;
            return response($decrypted, 200, $headers);

        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            abort(404, 'Preview unavailable.');
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
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100 MB max per file
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

            return response()->json(['success' => true, 'message' => "'{$origName}' encrypted and stored."]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Destroy the vault session (lock the vault).
     */
    public function lock()
    {
        session()->forget(['secret_vault_auth', 'secret_vault_granted_at']);
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Load config from secret.php.local (gitignored) falling back to secret.php.example skeleton.
     */
    private function loadConfig(): array
    {
        $localPath = config_path('secret.php.local');
        if (file_exists($localPath)) {
            return require $localPath;
        }
        // Fall back to config/secret (if registered normally)
        return [
            'enabled'              => (bool) config('secret.enabled', false),
            'pin_hash'             => (string) config('secret.pin_hash', ''),
            'session_ttl_minutes'  => (int) config('secret.session_ttl_minutes', 120),
        ];
    }

    /**
     * Ensure the secret storage directory exists.
     */
    private function ensureStorageExists(): void
    {
        if (!is_dir($this->secretStoragePath)) {
            @mkdir($this->secretStoragePath, 0700, true);
        }
    }

    /**
     * List all .enc files in the vault directory.
     */
    private function listVaultFiles(): array
    {
        $this->ensureStorageExists();
        $files = [];

        foreach (glob($this->secretStoragePath . DIRECTORY_SEPARATOR . '*.enc') as $encPath) {
            $encName  = basename($encPath);
            $origName = substr($encName, 0, -4); // remove .enc
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $size     = filesize($encPath) ?: 0;
            $modified = filemtime($encPath) ?: time();

            $mime     = \App\Http\Controllers\DriveController::getKnownMimeType($ext) ?? 'application/octet-stream';
            $category = $this->resolveCategory($ext, $mime);

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
                'download_url'  => route('secret.download', ['file' => $origName]),
            ];
        }

        // Sort: folders/dirs first, then by name
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
        // Strip directory separators, null bytes, dot-dot sequences
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
