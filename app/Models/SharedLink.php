<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SharedLink extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shared_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'file_path',
        'is_folder',
        'downloads_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'downloads_count' => 'integer',
        'user_id' => 'integer',
        'is_folder' => 'boolean',
    ];

    /**
     * Owning user relation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a cryptographically secure, unique token for sharing.
     */
    public static function generateUniqueToken(int $length = 16): string
    {
        do {
            $token = Str::random($length);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Get or create a shared link for the specified relative file path.
     */
    public static function forPath(string $filePath, ?int $userId = null, bool $isFolder = false): self
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $filePath), '/');

        $query = static::where('file_path', $normalizedPath);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $existing = $query->first();
        if ($existing) {
            return $existing;
        }

        return static::create([
            'user_id' => $userId,
            'token' => static::generateUniqueToken(),
            'file_path' => $normalizedPath,
            'is_folder' => $isFolder,
            'downloads_count' => 0,
        ]);
    }

    /**
     * Get the base filename.
     */
    public function getFilenameAttribute(): string
    {
        return basename($this->file_path);
    }

    /**
     * Get the file extension in lowercase.
     */
    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }

    /**
     * Get the full public share URL.
     */
    public function getShareUrlAttribute(): string
    {
        return url('/s/' . $this->token);
    }

    /**
     * Increment download counter atomically.
     */
    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
    }
}
