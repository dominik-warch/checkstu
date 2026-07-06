<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskTemplate>
 */
class TaskTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => null, // seeded/system name by default
            'name' => fake()->unique()->words(2, true),
            'usage_count' => 0,
        ];
    }
}
