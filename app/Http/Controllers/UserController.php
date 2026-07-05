<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return back();
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->handle($user, $request->validated());

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return back();
    }
}
