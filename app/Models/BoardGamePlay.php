<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardGamePlay extends Model
{
    /** @use HasFactory<\Database\Factories\BoardGamePlayFactory> */
    use HasFactory;

    protected $fillable = [
        'board_game_id',
        'played_at',
        'notes',
        'logged_by',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'date',
        ];
    }

    public function boardGame(): BelongsTo
    {
        return $this->belongsTo(BoardGame::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function players(): HasMany
    {
        return $this->hasMany(BoardGamePlayPlayer::class);
    }
}
