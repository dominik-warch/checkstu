<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Tasks\NotifyOverdueOccurrencesAction;
use Illuminate\Console\Command;

class NotifyOverdueOccurrencesCommand extends Command
{
    protected $signature = 'tasks:notify-overdue';

    protected $description = 'Push-notify assignees (or all members if unassigned) about currently overdue tasks';

    public function handle(NotifyOverdueOccurrencesAction $action): int
    {
        $action->handle();

        $this->info('Overdue push notifications dispatched.');

        return self::SUCCESS;
    }
}
