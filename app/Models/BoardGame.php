<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BoardGameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardGame extends Model
{
    /** @use HasFactory<\Database\Factories\BoardGameFactory> */
    use HasFactory;

    protected $fillable = [
        'bgg_id',
        'name',
        'thumbnail_url',
        'year_published',
        'min_players',
        'max_players',
        'playtime_minutes',
        'status',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BoardGameStatus::class,
        ];
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function plays(): HasMany
    {
        return $this->hasMany(BoardGamePlay::class);
    }
}
