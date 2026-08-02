<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\BoardGamePlay;
use App\Models\BoardGamePlayPlayer;
use App\Models\User;

class BoardGamePlayPresenter
{
    /**
     * Expects boardGame and players.user to already be eager-loaded.
     *
     * @return array<string, mixed>
     */
    public static function toArray(BoardGamePlay $play, User $user): array
    {
        return [
            'id' => $play->id,
            'played_at' => $play->played_at->toDateString(),
            'notes' => $play->notes,
            'board_game' => [
                'id' => $play->boardGame->id,
                'name' => $play->boardGame->name,
                'thumbnail_url' => $play->boardGame->thumbnail_url,
            ],
            'players' => $play->players
                ->map(fn (BoardGamePlayPlayer $player) => [
                    'id' => $player->id,
                    'user_id' => $player->user_id,
                    'name' => $player->user?->name ?? $player->guest_name,
                    'color' => $player->user?->color,
                    'is_winner' => $player->is_winner,
                ])
                ->values()
                ->all(),
            'can' => [
                'update' => $user->can('update', $play),
                'delete' => $user->can('delete', $play),
            ],
        ];
    }
}
