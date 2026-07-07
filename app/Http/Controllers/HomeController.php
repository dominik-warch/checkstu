<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tasks\ResolveDependenciesAction;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Support\OccurrencePresenter;
use App\Support\TaskTemplatePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
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
            ->map(fn (TaskOccurrence $o) => OccurrencePresenter::toArray($o, $blocked))
            ->values();

        return Inertia::render('home/today', [
            'occurrences' => $occurrences,
            'filters' => ['scope' => $scope],
            'members' => $this->members(),
            'templates' => TaskTemplatePresenter::forPicker(),
            'can' => [
                'completeOnBehalf' => $request->user()->can('completeOnBehalf', Task::class),
                'createTask' => $request->user()->can('create', Task::class),
                'manageUsers' => $request->user()->isAdmin(),
            ],
        ]);
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
