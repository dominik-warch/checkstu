<?php

declare(strict_types=1);

namespace App\Actions\BoardGames;

use App\Models\BoardGame;

class UpdateBoardGameAction
{
    /**
     * @param  array{name?: string, status?: string}  $data
     */
    public function handle(BoardGame $game, array $data): BoardGame
    {
        $game->update($data);

        return $game;
    }
}
