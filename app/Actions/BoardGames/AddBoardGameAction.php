<?php

declare(strict_types=1);

namespace App\Actions\BoardGames;

use App\Enums\BoardGameStatus;
use App\Models\BoardGame;
use App\Models\User;
use App\Support\Bgg\BggClient;

class AddBoardGameAction
{
    public function __construct(
        private readonly BggClient $bgg,
    ) {}

    /**
     * @param  array{bgg_id?: int|null, name?: string|null, status: string}  $data
     */
    public function handle(array $data, User $user): BoardGame
    {
        $bggId = $data['bgg_id'] ?? null;

        // Manual entry — no BGG lookup, just the typed name.
        if ($bggId === null) {
            return BoardGame::create([
                'name' => $data['name'],
                'status' => $data['status'],
                'added_by' => $user->id,
            ]);
        }

        // updateOrCreate by bgg_id: re-adding a previously-known game refreshes
        // its cached metadata (and status) instead of creating a duplicate row.
        $details = $this->bgg->details($bggId);

        return BoardGame::updateOrCreate(
            ['bgg_id' => $bggId],
            [
                'name' => $details['name'],
                'thumbnail_url' => $details['thumbnail_url'],
                'year_published' => $details['year_published'],
                'min_players' => $details['min_players'],
                'max_players' => $details['max_players'],
                'playtime_minutes' => $details['playtime_minutes'],
                'status' => $data['status'],
                'added_by' => $user->id,
            ],
        );
    }
}
