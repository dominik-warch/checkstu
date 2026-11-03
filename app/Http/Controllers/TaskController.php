<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\NotifyTaskAssignmentAction;
use App\Actions\Tasks\ResolveDependenciesAction;
use App\Actions\Tasks\UpdateTaskAction;
use App\Enums\RecurrenceType;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Support\LeaveTaskPage;
use App\Support\OccurrencePresenter;
use App\Support\TaskTemplatePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request, ResolveDependenciesAction $deps): Response
    {
        $scope = $request->string('scope', 'mine')->toString(); // 'all' | 'mine'
        $blocked = $deps->blockedTaskIds()->all();

        $occurrences = TaskOccurrence::query()
            ->visibleTo($request->user())
            ->current()
            ->when($scope === 'mine', fn ($q) => $q->mine($request->user()))
            ->with(['task.categories', 'task.dependencies', 'assignee'])
            ->orderByRaw('due_date IS NULL, due_date asc')
            ->get()
            ->map(fn (TaskOccurrence $o) => OccurrencePresenter::toArray($o, $request->user(), $blocked))
            ->values();

        return Inertia::render('tasks/index', [
            'occurrences' => $occurrences,
            'filters' => ['scope' => $scope],
            'members' => $this->members(),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'color']),
            'templates' => TaskTemplatePresenter::forPicker(),
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

    public function show(Request $request, Task $task, ResolveDependenciesAction $deps): Response
    {
        Gate::authorize('view', $task);

        $task->load(['categories', 'openOccurrence.assignee', 'openOccurrence.task.categories', 'dependencies.openOccurrence', 'dependents']);

        return Inertia::render('tasks/show', [
            'occurrence' => $task->openOccurrence
                ? OccurrencePresenter::toArray($task->openOccurrence, $request->user(), $deps->blockedTaskIds()->all())
                : null,
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority->value,
                'is_private' => $task->is_private,
                'is_recurring' => $task->recurrence_type !== RecurrenceType::OneOff,
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
                'complete' => $request->user()->can('complete', $task),
                'completeOnBehalf' => $request->user()->can('completeOnBehalf', Task::class),
            ],
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $action): RedirectResponse
    {
        $action->handle($task, $request->validated());

        return redirect()->route('tasks.show', $task);
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return LeaveTaskPage::redirect($request, $task->id);
    }

    /**
     * Hand a task to someone (or to "Für alle") straight from the list. Parents only, like deleting;
     * never for a private task, which would reveal it to whoever it was handed to.
     */
    public function assign(Request $request, Task $task, NotifyTaskAssignmentAction $notify): RedirectResponse
    {
        Gate::authorize('delete', $task);
        abort_if($task->is_private, 403);

        $assigneeId = $request->validate([
            'default_assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ])['default_assignee_id'] ?? null;

        $previous = $task->default_assignee_id;
        $task->update(['default_assignee_id' => $assigneeId]);
        $task->openOccurrence?->update(['assignee_id' => $assigneeId]);

        if ($assigneeId !== null && $assigneeId !== $previous) {
            $notify->handle($task, $assigneeId);
        }

        return back();
    }

    /** Undo a delete, from the toast or from "Gelöscht" in the archive. Whoever could delete it can bring it back. */
    public function restore(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->restore();

        return back();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function members(): array
    {
        return User::orderBy('name')->get()
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'role' => $u->role->value, 'color' => $u->color])
            ->all();
    }
}
