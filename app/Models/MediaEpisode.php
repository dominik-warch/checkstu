<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MediaEpisodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class MediaEpisode extends Model
{
    /** @use HasFactory<MediaEpisodeFactory> */
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

    /** A null air_date means "not yet scheduled", not "upcoming" — it has no date to sort by. */
    public function isUpcoming(): bool
    {
        return $this->air_date !== null && $this->air_date->gt(Carbon::today());
    }
}
