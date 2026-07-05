<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResolveDependenciesAction
{
    /**
     * Task IDs that are currently blocked: they depend on a task that still has
     * an open (not completed, not skipped) occurrence.
     *
     * @return Collection<int, int>
     */
    public function blockedTaskIds(): Collection
    {
        return DB::table('task_dependencies as d')
            ->join('task_occurrences as o', 'o.task_id', '=', 'd.depends_on_task_id')
            ->whereNull('o.completed_at')
            ->where('o.is_skipped', false)
            ->distinct()
            ->pluck('d.task_id');
    }
}
