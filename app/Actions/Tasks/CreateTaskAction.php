<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    /**
     * Create a task and its first occurrence.
     *
     * P1 handles one-off tasks: exactly one occurrence at the given due date.
     * (Recurring materialization is P2; the schema already carries the config.)
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
    public function handle(array $data, User $creator): Task
    {
        return DB::transaction(function () use ($data, $creator): Task {
            $task = Task::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 1,
                'default_assignee_id' => $data['default_assignee_id'] ?? null,
                'created_by' => $creator->id,
            ]);

            if (! empty($data['category_ids'])) {
                $task->categories()->sync($data['category_ids']);
            }

            $task->occurrences()->create([
                'due_date' => $data['due_date'] ?? null,
                'assignee_id' => $data['default_assignee_id'] ?? null,
            ]);

            return $task;
        });
    }
}
