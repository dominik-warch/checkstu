<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tasks\CompleteTaskAction;
use App\Http\Requests\Tasks\CompleteTaskRequest;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

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
}
