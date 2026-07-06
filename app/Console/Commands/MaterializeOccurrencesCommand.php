<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Tasks\MaterializeOccurrencesAction;
use App\Enums\RecurrenceType;
use App\Models\Task;
use Illuminate\Console\Command;

class MaterializeOccurrencesCommand extends Command
{
    protected $signature = 'tasks:materialize';

    protected $description = 'Materialize upcoming occurrences for calendar-anchored recurring tasks (rrule, explicit_dates)';

    public function handle(MaterializeOccurrencesAction $action): int
    {
        $tasks = Task::query()
            ->where('is_active', true)
            ->whereIn('recurrence_type', [RecurrenceType::Rrule, RecurrenceType::ExplicitDates])
            ->get();

        $totalCreated = 0;
        foreach ($tasks as $task) {
            $totalCreated += $action->handle($task);
        }

        $this->info("Materialized {$totalCreated} occurrence(s) across {$tasks->count()} task(s).");

        return self::SUCCESS;
    }
}
