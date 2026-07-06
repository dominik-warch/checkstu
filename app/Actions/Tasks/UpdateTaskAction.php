<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTaskAction
{
    /**
     * Update a task's definition fields, its categories, and — for a one-off /
     * currently-open occurrence — the due date and assignee shown to users.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     priority?: int,
     *     default_assignee_id?: int|null,
     *     due_date?: string|null,
     *     category_ids?: array<int>,
     * }  $data
     */
    public function handle(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data): Task {
            $task->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? $task->priority->value,
                'is_private' => $data['is_private'] ?? false,
                'default_assignee_id' => $data['default_assignee_id'] ?? null,
            ]);

            if (array_key_exists('category_ids', $data)) {
                $task->categories()->sync($data['category_ids'] ?? []);
            }

            if ($open = $task->openOccurrence) {
                $open->update([
                    'due_date' => $data['due_date'] ?? null,
                    'assignee_id' => $data['default_assignee_id'] ?? null,
                ]);
            }

            return $task;
        });
    }
}
