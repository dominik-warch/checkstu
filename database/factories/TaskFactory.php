<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\RecurrenceType;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->boolean(40) ? fake()->sentence() : null,
            'priority' => fake()->randomElement(Priority::cases())->value,
            'default_assignee_id' => null,
            'recurrence_type' => RecurrenceType::OneOff,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => ['is_private' => true]);
    }

    public function relative(int $days = 3): static
    {
        return $this->state(fn () => [
            'recurrence_type' => RecurrenceType::Relative,
            'relative_interval_days' => $days,
        ]);
    }

    public function rrule(string $rrule = 'FREQ=WEEKLY;BYDAY=SA'): static
    {
        return $this->state(fn () => [
            'recurrence_type' => RecurrenceType::Rrule,
            'rrule' => $rrule,
            'anchor_date' => now()->toDateString(),
        ]);
    }

    /**
     * @param  array<int, string>  $dates  Y-m-d date strings to seed as task_recurrence_dates
     */
    public function explicitDates(array $dates = []): static
    {
        return $this->state(fn () => [
            'recurrence_type' => RecurrenceType::ExplicitDates,
        ])->afterCreating(function (Task $task) use ($dates) {
            foreach ($dates as $date) {
                $task->recurrenceDates()->create(['due_on' => $date]);
            }
        });
    }
}
