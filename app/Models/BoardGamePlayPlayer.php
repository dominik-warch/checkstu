<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardGamePlayPlayer extends Model
{
    /** @use HasFactory<\Database\Factories\BoardGamePlayPlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'board_game_play_id',
        'user_id',
        'guest_name',
        'is_winner',
    ];

    protected function casts(): array
    {
        return [
            'is_winner' => 'bool',
        ];
    }

    public function play(): BelongsTo
    {
        return $this->belongsTo(BoardGamePlay::class, 'board_game_play_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
