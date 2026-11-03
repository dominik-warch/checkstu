<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tasks\CompleteTaskAction;
use App\Actions\Tasks\ReopenTaskAction;
use App\Actions\Tasks\SkipOccurrenceAction;
use App\Http\Requests\Tasks\CompleteTaskRequest;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Support\LeaveTaskPage;
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

    /** Let a missed occurrence go. Same people who may delete the task: parents, or the owner of a private one. */
    public function skip(Request $request, TaskOccurrence $occurrence, SkipOccurrenceAction $action): RedirectResponse
    {
        Gate::authorize('delete', $occurrence->task);

        $action->handle($occurrence, $request->user());

        return LeaveTaskPage::redirect($request, $occurrence->task_id);
    }

    /** Undo an accidental completion or skip. Anyone who could complete the task can restore it. */
    public function destroy(Request $request, TaskOccurrence $occurrence, ReopenTaskAction $action): RedirectResponse
    {
        Gate::authorize('complete', $occurrence->task);

        $action->handle($occurrence, $request->user());

        return back();
    }
}
