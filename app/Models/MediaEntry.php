<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaEntry extends Model
{
    /** @use HasFactory<\Database\Factories\MediaEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'media_item_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => WatchStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }

    public function watches(): HasMany
    {
        return $this->hasMany(MediaEntryWatch::class)->orderBy('id');
    }
}
