<?php

namespace Tests\Feature;

use App\Enums\RecurrenceType;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateRecurringTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_rrule_task_materializes_occurrences_immediately(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-04')); // a Saturday
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Staubsaugen',
                'recurrence_type' => 'rrule',
                'rrule' => 'FREQ=WEEKLY;BYDAY=SA',
                'anchor_date' => '2026-07-04',
            ])
            ->assertRedirect();

        $task = Task::firstWhere('title', 'Staubsaugen');
        $this->assertSame(RecurrenceType::Rrule, $task->recurrence_type);
        $this->assertGreaterThan(0, $task->occurrences()->count());
        $this->assertSame('2026-07-04', $task->occurrences()->orderBy('due_date')->first()->due_date->toDateString());

        Carbon::setTestNow();
    }

    public function test_creating_an_explicit_dates_task_seeds_dates_and_materializes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01'));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Müll rausbringen',
                'recurrence_type' => 'explicit_dates',
                'explicit_dates' => ['2026-07-10', '2026-08-15'],
            ])
            ->assertRedirect();

        $task = Task::firstWhere('title', 'Müll rausbringen');
        $this->assertSame(2, $task->recurrenceDates()->count());
        // Both dates fall within the default 60-day horizon from 2026-07-01.
        $this->assertSame(2, $task->occurrences()->count());

        Carbon::setTestNow();
    }

    public function test_creating_a_relative_task_creates_a_single_initial_occurrence(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Pflanzen gießen',
                'recurrence_type' => 'relative',
                'relative_interval_days' => 3,
                'due_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $task = Task::firstWhere('title', 'Pflanzen gießen');
        $this->assertSame(RecurrenceType::Relative, $task->recurrence_type);
        $this->assertSame(1, $task->occurrences()->count());
    }

    public function test_omitting_recurrence_type_still_defaults_to_one_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), ['title' => 'Fenster putzen'])
            ->assertRedirect();

        $task = Task::firstWhere('title', 'Fenster putzen');
        $this->assertSame(RecurrenceType::OneOff, $task->recurrence_type);
        $this->assertSame(1, $task->occurrences()->count());
    }

    public function test_rrule_is_required_when_recurrence_type_is_rrule(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Ohne Regel',
                'recurrence_type' => 'rrule',
                'anchor_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('rrule');
    }

    public function test_invalid_rrule_string_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Kaputte Regel',
                'recurrence_type' => 'rrule',
                'rrule' => 'NOT-A-VALID-RRULE',
                'anchor_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('rrule');
    }

    public function test_relative_interval_days_is_required_when_recurrence_type_is_relative(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Ohne Intervall',
                'recurrence_type' => 'relative',
            ])
            ->assertSessionHasErrors('relative_interval_days');
    }

    public function test_explicit_dates_are_required_when_recurrence_type_is_explicit_dates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Ohne Termine',
                'recurrence_type' => 'explicit_dates',
            ])
            ->assertSessionHasErrors('explicit_dates');
    }
}
