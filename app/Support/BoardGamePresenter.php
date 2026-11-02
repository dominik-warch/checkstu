<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\BoardGame;
use App\Models\BoardGamePlayPlayer;
use App\Models\User;

class BoardGamePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(BoardGame $game, User $user): array
    {
        return [
            'id' => $game->id,
            'bgg_id' => $game->bgg_id,
            'name' => $game->name,
            'thumbnail_url' => $game->thumbnail_url,
            'year_published' => $game->year_published,
            'min_players' => $game->min_players,
            'max_players' => $game->max_players,
            'playtime_minutes' => $game->playtime_minutes,
            'status' => $game->status->value,
            // The shelf's "zuletzt gespielt" line: when, and who won (in their colour). Only when eager-loaded.
            'last_play' => $game->relationLoaded('latestPlay') && $game->latestPlay ? [
                'played_at' => $game->latestPlay->played_at->toDateString(),
                'winners' => $game->latestPlay->players
                    ->where('is_winner', true)
                    ->map(fn (BoardGamePlayPlayer $player) => [
                        'name' => $player->user?->name ?? $player->guest_name,
                        'color' => $player->user?->color,
                    ])
                    ->values()
                    ->all(),
            ] : null,
            'can' => [
                'delete' => $user->can('delete', $game),
            ],
        ];
    }
}
