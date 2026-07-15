<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class MediaEpisode extends Model
{
    /** @use HasFactory<\Database\Factories\MediaEpisodeFactory> */
    use HasFactory;

    protected $fillable = [
        'media_season_id',
        'tmdb_episode_id',
        'episode_number',
        'name',
        'air_date',
    ];

    protected function casts(): array
    {
        return [
            'air_date' => 'date',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(MediaSeason::class, 'media_season_id');
    }

    public function watches(): HasMany
    {
        return $this->hasMany(MediaEpisodeWatch::class);
    }

    public function hasAired(): bool
    {
        return $this->air_date !== null && $this->air_date->lte(Carbon::today());
    }
}
