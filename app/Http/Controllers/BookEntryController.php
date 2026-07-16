<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Books\CreateBookEntryAction;
use App\Actions\Books\DeleteBookEntryAction;
use App\Actions\Books\UpdateBookEntryAction;
use App\Http\Requests\Books\StoreBookEntryRequest;
use App\Http\Requests\Books\UpdateBookEntryRequest;
use App\Models\BookEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookEntryController extends Controller
{
    public function store(StoreBookEntryRequest $request, CreateBookEntryAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $request->user());

        return back();
    }

    public function update(UpdateBookEntryRequest $request, BookEntry $entry, UpdateBookEntryAction $action): RedirectResponse
    {
        $action->handle($entry, $request->validated());

        return back();
    }

    public function destroy(Request $request, BookEntry $entry, DeleteBookEntryAction $action): RedirectResponse
    {
        Gate::authorize('delete', $entry);

        $action->handle($entry);

        return back();
    }
}
