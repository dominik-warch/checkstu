<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\ResolveDependenciesAction;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Support\OccurrencePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request, ResolveDependenciesAction $deps): Response
    {
        $scope = $request->string('scope', 'all')->toString(); // 'all' | 'mine'
        $blocked = $deps->blockedTaskIds()->all();

        $occurrences = TaskOccurrence::query()
            ->whereNull('completed_at')
            ->where('is_skipped', false)
            ->when($scope === 'mine', fn ($q) => $q->where('assignee_id', $request->user()->id))
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
