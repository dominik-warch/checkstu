<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\TaskTemplate;

class TaskTemplatePresenter
{
    /**
     * Name catalogue for the create form's autocomplete + "most used" chips,
     * most-used first. Household scale (a few dozen distinct chore names at
     * most) — no pagination needed, filtering happens client-side.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forPicker(): array
    {
        return TaskTemplate::orderByDesc('usage_count')
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'usage_count'])
            ->map(fn (TaskTemplate $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'usage_count' => $t->usage_count,
            ])
            ->all();
    }
}
