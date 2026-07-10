<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\RecurrenceType;
use App\Models\TaskOccurrence;

class OccurrencePresenter
{
    /**
     * Shape a task occurrence for the Inertia frontend.
     *
     * @param  array<int, int>  $blockedTaskIds
     * @return array<string, mixed>
     */
    public static function toArray(TaskOccurrence $occurrence, array $blockedTaskIds = []): array
    {
        $task = $occurrence->task;

        return [
            'id' => $occurrence->id,
            'task_id' => $occurrence->task_id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority->value,
            'is_private' => $task->is_private,
            'is_recurring' => $task->recurrence_type !== RecurrenceType::OneOff,
            'due_date' => $occurrence->due_date?->toDateString(),
            'completed_at' => $occurrence->completed_at?->toIso8601String(),
            'status' => $occurrence->status,
            'is_blocked' => in_array($occurrence->task_id, $blockedTaskIds, true),
            'blocking_titles' => $occurrence->task->relationLoaded('dependencies')
                ? $task->dependencies->pluck('title')->all()
                : [],
            'assignee' => $occurrence->assignee ? [
                'id' => $occurrence->assignee->id,
                'name' => $occurrence->assignee->name,
                'color' => $occurrence->assignee->color,
            ] : null,
            'completed_by' => $occurrence->completedBy ? [
                'id' => $occurrence->completedBy->id,
                'name' => $occurrence->completedBy->name,
            ] : null,
            'categories' => $task->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'color' => $c->color,
            ])->all(),
        ];
    }
}
