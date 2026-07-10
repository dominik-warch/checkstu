<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Support\OccurrencePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArchiveController extends Controller
{
    /** Most-recently-completed 200 occurrences; older history isn't needed day-to-day. */
    private const LIMIT = 200;

    public function index(Request $request): Response
    {
        $scope = $request->string('scope', 'mine')->toString(); // 'all' | 'mine'

        $occurrences = TaskOccurrence::query()
            ->visibleTo($request->user())
            ->whereNotNull('completed_at')
            ->when($scope === 'mine', fn ($q) => $q->mine($request->user()))
            ->with(['task.categories', 'assignee', 'completedBy'])
            ->orderByDesc('completed_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (TaskOccurrence $o) => OccurrencePresenter::toArray($o))
            ->values();

        return Inertia::render('archive/index', [
            'occurrences' => $occurrences,
            'filters' => ['scope' => $scope],
            'can' => [
                'completeOnBehalf' => $request->user()->can('completeOnBehalf', Task::class),
            ],
        ]);
    }
}
