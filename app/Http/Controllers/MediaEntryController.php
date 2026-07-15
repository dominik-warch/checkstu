<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\CreateMediaEntryAction;
use App\Actions\Media\DeleteMediaEntryAction;
use App\Actions\Media\UpdateMediaEntryAction;
use App\Http\Requests\Media\StoreMediaEntryRequest;
use App\Http\Requests\Media\UpdateMediaEntryRequest;
use App\Models\MediaEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MediaEntryController extends Controller
{
    public function store(StoreMediaEntryRequest $request, CreateMediaEntryAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $request->user());

        return back();
    }

    public function update(UpdateMediaEntryRequest $request, MediaEntry $entry, UpdateMediaEntryAction $action): RedirectResponse
    {
        $action->handle($entry, $request->validated());

        return back();
    }

    public function destroy(Request $request, MediaEntry $entry, DeleteMediaEntryAction $action): RedirectResponse
    {
        Gate::authorize('delete', $entry);

        $action->handle($entry);

        return back();
    }
}
