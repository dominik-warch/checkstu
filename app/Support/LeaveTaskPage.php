<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where to go after an action that leaves a task's own page behind (delete, or skipping a
 * one-off, which would then read "schon erledigt"): back to the list you opened it from.
 */
class LeaveTaskPage
{
    public static function redirect(Request $request, int $taskId): RedirectResponse
    {
        // From a list, stay there (the undo toast is shown on it).
        if (url()->previous() !== route('tasks.show', $taskId)) {
            return back();
        }

        // The page sends the list it came from; only Heute, Aufgaben or Archiv (with a query) are accepted,
        // so this can't be turned into a redirect to anywhere else.
        $to = $request->string('return_to')->toString();

        return preg_match('#^/(tasks|archive)?(\?[^/\\\\]*)?$#', $to) === 1
            ? redirect($to)
            : redirect()->route('tasks.index');
    }
}
