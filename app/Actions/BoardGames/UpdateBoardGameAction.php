<?php

declare(strict_types=1);

namespace App\Actions\BoardGames;

use App\Models\BoardGame;

class UpdateBoardGameAction
{
    /**
     * @param  array{status: string}  $data
     */
    public function handle(BoardGame $game, array $data): BoardGame
    {
        $game->update(['status' => $data['status']]);

        return $game;
    }
}
