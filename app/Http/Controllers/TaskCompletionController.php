<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tasks\CompleteTaskAction;
use App\Actions\Tasks\ReopenTaskAction;
use App\Http\Requests\Tasks\CompleteTaskRequest;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskCompletionController extends Controller
{
    public function store(
        CompleteTaskRequest $request,
        TaskOccurrence $occurrence,
        CompleteTaskAction $action,
    ): RedirectResponse {
        $completedBy = $request->filled('completed_by_user_id')
            ? User::findOrFail($request->integer('completed_by_user_id'))
            : null;

        $action->handle($occurrence, $request->user(), $completedBy);

        return back();
    }

    /** Undo an accidental completion. Anyone who could complete the task can restore it. */
    public function destroy(Request $request, TaskOccurrence $occurrence, ReopenTaskAction $action): RedirectResponse
    {
        Gate::authorize('complete', $occurrence->task);

        $action->handle($occurrence, $request->user());

        return back();
    }
}
