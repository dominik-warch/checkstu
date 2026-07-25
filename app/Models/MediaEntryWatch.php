<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaEntryWatch extends Model
{
    /** @use HasFactory<\Database\Factories\MediaEntryWatchFactory> */
    use HasFactory;

    protected $fillable = [
        'media_entry_id',
        'watched_at',
    ];

    protected function casts(): array
    {
        return [
            'watched_at' => 'date',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(MediaEntry::class, 'media_entry_id');
    }
}
