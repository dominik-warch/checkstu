<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tasks\ResolveDependenciesAction;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Support\OccurrencePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UpcomingController extends Controller
{
    public function index(Request $request, ResolveDependenciesAction $deps): Response
    {
        $blocked = $deps->blockedTaskIds()->all();

        $occurrences = TaskOccurrence::query()
            ->whereNull('completed_at')
            ->where('is_skipped', false)
            ->whereNotNull('due_date')
            ->with(['task.categories', 'task.dependencies', 'assignee'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (TaskOccurrence $o) => OccurrencePresenter::toArray($o, $blocked))
            ->values();

        return Inertia::render('upcoming/index', [
            'occurrences' => $occurrences,
            'members' => User::orderBy('name')->get()
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'role' => $u->role->value]),
            'can' => [
                'completeOnBehalf' => $request->user()->can('completeOnBehalf', Task::class),
                'createTask' => $request->user()->can('create', Task::class),
            ],
        ]);
    }
}
