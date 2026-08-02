<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BoardGameStatus;
use App\Models\BoardGame;
use App\Support\BoardGamePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardGameCatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $status = BoardGameStatus::tryFrom($request->string('status', '')->toString());

        $games = BoardGame::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->get()
            ->map(fn (BoardGame $game) => BoardGamePresenter::toArray($game, $request->user()))
            ->values();

        return Inertia::render('board-games/catalogue', [
            'games' => $games,
            'filters' => [
                'status' => $status?->value ?? 'all',
            ],
        ]);
    }
}
