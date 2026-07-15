<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaItem extends Model
{
    /** @use HasFactory<\Database\Factories\MediaItemFactory> */
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'type',
        'title_de',
        'title_en',
        'overview',
        'poster_path',
        'release_date',
        'tv_status',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'release_date' => 'date',
        ];
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(MediaSeason::class)->orderBy('season_number');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MediaEntry::class);
    }
}
