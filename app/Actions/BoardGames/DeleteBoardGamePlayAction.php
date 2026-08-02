<?php

declare(strict_types=1);

namespace App\Actions\BoardGames;

use App\Models\BoardGamePlay;

class DeleteBoardGamePlayAction
{
    public function handle(BoardGamePlay $play): void
    {
        $play->delete();
    }
}
