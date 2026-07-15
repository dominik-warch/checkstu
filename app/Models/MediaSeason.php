<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaSeason extends Model
{
    /** @use HasFactory<\Database\Factories\MediaSeasonFactory> */
    use HasFactory;

    protected $fillable = [
        'media_item_id',
        'tmdb_season_id',
        'season_number',
        'name',
        'episode_count',
        'air_date',
        'episodes_fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'air_date' => 'date',
            'episodes_fetched_at' => 'datetime',
        ];
    }

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(MediaEpisode::class)->orderBy('episode_number');
    }

    public function isSpecials(): bool
    {
        return $this->season_number === 0;
    }

    public function isEpisodesCached(): bool
    {
        return $this->episodes_fetched_at !== null;
    }
}
