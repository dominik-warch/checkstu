<?php

declare(strict_types=1);

namespace App\Actions\BoardGames;

use App\Models\BoardGamePlay;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LogBoardGamePlayAction
{
    /**
     * @param  array{board_game_id: int, played_at: string, notes?: string|null, players: list<array{user_id?: int|null, guest_name?: string|null, is_winner?: bool}>}  $data
     */
    public function handle(array $data, User $user): BoardGamePlay
    {
        return DB::transaction(function () use ($data, $user): BoardGamePlay {
            $play = BoardGamePlay::create([
                'board_game_id' => $data['board_game_id'],
                'played_at' => $data['played_at'],
                'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
                'logged_by' => $user->id,
            ]);

            foreach ($data['players'] as $player) {
                $play->players()->create([
                    'user_id' => $player['user_id'] ?? null,
                    'guest_name' => filled($player['guest_name'] ?? null) ? $player['guest_name'] : null,
                    'is_winner' => $player['is_winner'] ?? false,
                ]);
            }

            return $play;
        });
    }
}
