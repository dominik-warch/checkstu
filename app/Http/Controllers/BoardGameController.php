<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BoardGames\AddBoardGameAction;
use App\Actions\BoardGames\DeleteBoardGameAction;
use App\Actions\BoardGames\UpdateBoardGameAction;
use App\Http\Requests\BoardGames\StoreBoardGameRequest;
use App\Http\Requests\BoardGames\UpdateBoardGameRequest;
use App\Models\BoardGame;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BoardGameController extends Controller
{
    public function store(StoreBoardGameRequest $request, AddBoardGameAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $request->user());

        return back();
    }

    public function update(UpdateBoardGameRequest $request, BoardGame $boardGame, UpdateBoardGameAction $action): RedirectResponse
    {
        $action->handle($boardGame, $request->validated());

        return back();
    }

    public function destroy(BoardGame $boardGame, DeleteBoardGameAction $action): RedirectResponse
    {
        Gate::authorize('delete', $boardGame);

        $action->handle($boardGame);

        return back();
    }
}
