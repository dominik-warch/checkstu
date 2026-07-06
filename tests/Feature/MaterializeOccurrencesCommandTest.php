<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class MaterializeOccurrencesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_materializes_across_all_active_recurring_tasks(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-04')); // a Saturday

        $weekly = Task::factory()->rrule('FREQ=WEEKLY;BYDAY=SA')->create(['anchor_date' => '2026-07-04']);
        $trash = Task::factory()->explicitDates(['2026-07-10'])->create();
        $oneOff = Task::factory()->create();

        Artisan::call('tasks:materialize');

        $this->assertGreaterThan(0, $weekly->occurrences()->count());
        $this->assertSame(1, $trash->occurrences()->count());
        $this->assertSame(0, $oneOff->occurrences()->count());

        Carbon::setTestNow();
    }

    public function test_inactive_tasks_are_skipped(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-04'));
        $paused = Task::factory()->rrule('FREQ=WEEKLY;BYDAY=SA')->create([
            'anchor_date' => '2026-07-04',
            'is_active' => false,
        ]);

        Artisan::call('tasks:materialize');

        $this->assertSame(0, $paused->occurrences()->count());
        Carbon::setTestNow();
    }

    public function test_running_the_command_twice_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-04'));
        $task = Task::factory()->rrule('FREQ=WEEKLY;BYDAY=SA')->create(['anchor_date' => '2026-07-04']);

        Artisan::call('tasks:materialize');
        $countAfterFirst = TaskOccurrence::count();
        Artisan::call('tasks:materialize');
        $countAfterSecond = TaskOccurrence::count();

        $this->assertGreaterThan(0, $countAfterFirst);
        $this->assertSame($countAfterFirst, $countAfterSecond);

        Carbon::setTestNow();
    }

    public function test_command_is_registered_on_the_daily_schedule(): void
    {
        $events = collect(Schedule::events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'tasks:materialize'));

        $this->assertNotEmpty($events, 'tasks:materialize is not registered on the scheduler');
    }
}
