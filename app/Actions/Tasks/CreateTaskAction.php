<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\RecurrenceType;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    public function __construct(
        private readonly MaterializeOccurrencesAction $materialize,
        private readonly RecordTaskTitleUsageAction $recordTitleUsage,
    ) {}

    /**
     * Create a task. What happens next depends on its recurrence type:
     * - one_off / relative: a single initial occurrence at the given due date.
     * - rrule / explicit_dates: materialized immediately (rolling horizon) so the
     *   task has occurrences right away instead of waiting for tomorrow's cron.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     priority?: int,
     *     is_private?: bool,
     *     default_assignee_id?: int|null,
     *     due_date?: string|null,
     *     category_ids?: array<int>,
     *     recurrence_type?: string,
     *     rrule?: string|null,
     *     anchor_date?: string|null,
     *     relative_interval_days?: int|null,
     *     explicit_dates?: array<int, string>,
     *     recurrence_ends_on?: string|null,
     * }  $data
     */
    public function handle(array $data, User $creator): Task
    {
        return DB::transaction(function () use ($data, $creator): Task {
            $recurrenceType = isset($data['recurrence_type'])
                ? RecurrenceType::from($data['recurrence_type'])
                : RecurrenceType::OneOff;

            $task = Task::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 1,
                'is_private' => $data['is_private'] ?? false,
                'default_assignee_id' => $data['default_assignee_id'] ?? null,
                'recurrence_type' => $recurrenceType,
                'rrule' => $data['rrule'] ?? null,
                'anchor_date' => $data['anchor_date'] ?? null,
                'relative_interval_days' => $data['relative_interval_days'] ?? null,
                'recurrence_ends_on' => $data['recurrence_ends_on'] ?? null,
                'created_by' => $creator->id,
            ]);

            if (! empty($data['category_ids'])) {
                $task->categories()->sync($data['category_ids']);
            }

            match ($recurrenceType) {
                RecurrenceType::OneOff, RecurrenceType::Relative => $task->occurrences()->create([
                    'due_date' => $data['due_date'] ?? now()->toDateString(),
                    'assignee_id' => $data['default_assignee_id'] ?? null,
                ]),
                RecurrenceType::ExplicitDates => $this->seedExplicitDates($task, $data['explicit_dates'] ?? []),
                RecurrenceType::Rrule => null, // materialized below for every recurring type
            };

            if ($recurrenceType === RecurrenceType::Rrule || $recurrenceType === RecurrenceType::ExplicitDates) {
                $this->materialize->handle($task);
            }

            $this->recordTitleUsage->handle($data['title'], $creator);

            return $task;
        });
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function seedExplicitDates(Task $task, array $dates): void
    {
        foreach ($dates as $date) {
            $task->recurrenceDates()->create(['due_on' => $date]);
        }
    }
}
