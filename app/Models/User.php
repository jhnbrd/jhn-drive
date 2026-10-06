<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Storage quota in bytes: 20 GB (20 * 1024 * 1024 * 1024).
     */
    public const STORAGE_QUOTA_BYTES = 21474836480;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_superadmin',
        'status',
        'request_note',
        'approved_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Check if user account is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if user account is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if user account was rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if user is superadmin.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    /**
     * Relative storage path inside local_drive disk.
     */
    public function storageRelativePath(): string
    {
        return 'users/'.$this->id;
    }

    /**
     * Full local disk path to user storage folder.
     */
    public function storageFullPath(): string
    {
        return Storage::disk('local_drive')->path($this->storageRelativePath());
    }

    /**
     * Ensure the dedicated user storage folder exists.
     */
    public function ensureStorageDirectoryExists(): string
    {
        $path = $this->storageFullPath();
        if (! is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path;
    }

    /**
     * Calculate total bytes consumed by this user.
     */
    public function usedStorageBytes(): int
    {
        $dir = $this->storageFullPath();
        if (! is_dir($dir)) {
            return 0;
        }

        $size = 0;
        try {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
                try {
                    $size += $file->getSize();
                } catch (\Exception) {
                    // Ignore transient lock issues
                }
            }
        } catch (\Exception) {
            return 0;
        }

        return $size;
    }

    /**
     * Calculate remaining storage bytes out of 20GB.
     */
    public function remainingStorageBytes(): int
    {
        return max(0, self::STORAGE_QUOTA_BYTES - $this->usedStorageBytes());
    }

    /**
     * Percentage of 20GB quota used.
     */
    public function storagePercentage(): float
    {
        $used = $this->usedStorageBytes();

        return round(($used / self::STORAGE_QUOTA_BYTES) * 100, 1);
    }

    /**
     * Check if user has sufficient quota for incoming bytes.
     */
    public function hasStorageFor(int $incomingBytes): bool
    {
        return ($this->usedStorageBytes() + $incomingBytes) <= self::STORAGE_QUOTA_BYTES;
    }

    /**
     * Format bytes into human readable string.
     */
    public static function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Human-readable used storage.
     */
    public function humanUsedStorage(): string
    {
        return self::formatBytes($this->usedStorageBytes());
    }

    /**
     * Human-readable 20 GB quota.
     */
    public function humanStorageQuota(): string
    {
        return self::formatBytes(self::STORAGE_QUOTA_BYTES);
    }

    /**
     * Items currently held in this user's Trash.
     */
    public function trashedItems(): HasMany
    {
        return $this->hasMany(TrashedItem::class);
    }

    /**
     * Shared links created by this user.
     */
    public function sharedLinks(): HasMany
    {
        return $this->hasMany(SharedLink::class);
    }
}
