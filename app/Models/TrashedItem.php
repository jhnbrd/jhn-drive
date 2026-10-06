<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrashedItem extends Model
{
    protected $fillable = [
        'user_id',
        'storage_name',
        'original_path',
        'original_name',
        'is_folder',
        'size',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_folder' => 'boolean',
            'size' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
