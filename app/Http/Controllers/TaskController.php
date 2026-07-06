<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\ResolveDependenciesAction;
use App\Actions\Tasks\UpdateTaskAction;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Support\OccurrencePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request, ResolveDependenciesAction $deps): Response
    {
        $scope = $request->string('scope', 'all')->toString(); // 'all' | 'mine'
        $blocked = $deps->blockedTaskIds()->all();

        $occurrences = TaskOccurrence::query()
            ->visibleTo($request->user())
            ->whereNull('completed_at')
            ->where('is_skipped', false)
            ->when($scope === 'mine', function ($q) use ($request) {
                $user = $request->user();
                $q->where(function ($q2) use ($user) {
                    $q2->where('assignee_id', $user->id);
                    // Unassigned tasks are "up for grabs" — they count as everyone's,
                    // except guests (who only ever see their own assigned tasks).
                    if (! $user->isGuest()) {
                        $q2->orWhereNull('assignee_id');
                    }
                });
            })
            ->with(['task.categories', 'task.dependencies', 'assignee'])
            ->orderByRaw('due_date IS NULL, due_date asc')
            ->get()
            ->map(fn (TaskOccurrence $o) => OccurrencePresenter::toArray($o, $blocked))
            ->values();

        return Inertia::render('tasks/index', [
            'occurrences' => $occurrences,
            'filters' => ['scope' => $scope],
            'members' => $this->members(),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'color']),
            'can' => [
                'completeOnBehalf' => $request->user()->can('completeOnBehalf', Task::class),
                'createTask' => $request->user()->can('create', Task::class),
            ],
        ]);
    }

    public function store(StoreTaskRequest $request, CreateTaskAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $request->user());

        return back();
    }

    public function show(Request $request, Task $task): Response
    {
        Gate::authorize('view', $task);

        $task->load(['categories', 'openOccurrence.assignee', 'dependencies.openOccurrence', 'dependents']);

        return Inertia::render('tasks/show', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority->value,
                'is_private' => $task->is_private,
                'due_date' => $task->openOccurrence?->due_date?->toDateString(),
                'assignee_id' => $task->default_assignee_id,
                'assignee' => $task->openOccurrence?->assignee
                    ? ['id' => $task->openOccurrence->assignee->id, 'name' => $task->openOccurrence->assignee->name]
                    : null,
                'category_ids' => $task->categories->pluck('id'),
                'categories' => $task->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'color' => $c->color]),
                // A blocker is satisfied once it has no open occurrence.
                'blocked_by' => $task->dependencies->map(fn (Task $b) => [
                    'id' => $b->id,
                    'title' => $b->title,
                    'done' => $b->openOccurrence === null,
                ])->values(),
                'blocks' => $task->dependents->map(fn (Task $d) => ['id' => $d->id, 'title' => $d->title])->values(),
            ],
            'members' => $this->members(),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'color']),
            'can' => [
                'update' => $request->user()->can('update', $task),
                'delete' => $request->user()->can('delete', $task),
            ],
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $action): RedirectResponse
    {
        $action->handle($task, $request->validated());

        return redirect()->route('tasks.show', $task);
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function members(): array
    {
        return User::orderBy('name')->get()
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'role' => $u->role->value])
            ->all();
    }
}
