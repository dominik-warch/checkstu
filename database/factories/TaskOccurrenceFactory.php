<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskOccurrence>
 */
class TaskOccurrenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'due_date' => fake()->dateTimeBetween('-3 days', '+10 days')->format('Y-m-d'),
            'assignee_id' => null,
            'completed_at' => null,
            'completed_by' => null,
            'is_skipped' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'completed_at' => now(),
        ]);
    }
}
