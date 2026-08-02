<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BoardGames\DeleteBoardGamePlayAction;
use App\Actions\BoardGames\LogBoardGamePlayAction;
use App\Actions\BoardGames\UpdateBoardGamePlayAction;
use App\Http\Requests\BoardGames\StoreBoardGamePlayRequest;
use App\Http\Requests\BoardGames\UpdateBoardGamePlayRequest;
use App\Models\BoardGame;
use App\Models\BoardGamePlay;
use App\Models\User;
use App\Support\BoardGamePlayPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BoardGamePlayController extends Controller
{
    public function index(Request $request): Response
    {
        $plays = BoardGamePlay::query()
            ->with(['boardGame', 'players.user'])
            ->orderByDesc('played_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (BoardGamePlay $play) => BoardGamePlayPresenter::toArray($play, $request->user()))
            ->values();

        return Inertia::render('board-games/plays', [
            'plays' => $plays,
            'games' => BoardGame::orderBy('name')->get(['id', 'name'])->values(),
            'members' => $this->members(),
        ]);
    }

    public function store(StoreBoardGamePlayRequest $request, LogBoardGamePlayAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $request->user());

        return back();
    }

    public function update(UpdateBoardGamePlayRequest $request, BoardGamePlay $play, UpdateBoardGamePlayAction $action): RedirectResponse
    {
        $action->handle($play, $request->validated());

        return back();
    }

    public function destroy(BoardGamePlay $play, DeleteBoardGamePlayAction $action): RedirectResponse
    {
        Gate::authorize('delete', $play);

        $action->handle($play);

        return back();
    }

    /**
     * @return list<array{id: int, name: string, color: ?string}>
     */
    private function members(): array
    {
        return User::orderBy('name')->get()
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'color' => $user->color])
            ->all();
    }
}
