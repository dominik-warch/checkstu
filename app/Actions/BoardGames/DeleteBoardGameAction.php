<?php

declare(strict_types=1);

namespace App\Actions\BoardGames;

use App\Models\BoardGame;

class DeleteBoardGameAction
{
    /**
     * Cascades to that game's logged plays — see the board_game_plays
     * migration for why that's the intended behaviour here.
     */
    public function handle(BoardGame $game): void
    {
        $game->delete();
    }
}
