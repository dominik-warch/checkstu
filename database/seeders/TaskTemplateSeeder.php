<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\RecurrenceType;
use App\Models\Category;
use App\Models\TaskTemplate;
use Illuminate\Database\Seeder;

/**
 * Shared starter catalogue of common household chores (plan §4.8).
 * created_by = null marks these as seeded system templates.
 */
class TaskTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $category = fn (string $name): ?int => Category::where('name', $name)->value('id');

        $templates = [
            ['name' => 'Staubsaugen', 'icon' => '🧹', 'priority' => Priority::Normal, 'recurrence_type' => RecurrenceType::Rrule, 'rrule' => 'FREQ=WEEKLY;BYDAY=SA', 'category' => 'Wohnzimmer'],
            ['name' => 'Bad putzen', 'icon' => '🛁', 'priority' => Priority::Normal, 'recurrence_type' => RecurrenceType::Rrule, 'rrule' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU', 'category' => 'Bad'],
            ['name' => 'Küche wischen', 'icon' => '🧽', 'priority' => Priority::Normal, 'recurrence_type' => RecurrenceType::Rrule, 'rrule' => 'FREQ=WEEKLY;BYDAY=WE', 'category' => 'Küche'],
            ['name' => 'Bettwäsche wechseln', 'icon' => '🛏️', 'priority' => Priority::Normal, 'recurrence_type' => RecurrenceType::Relative, 'relative_interval_days' => 14, 'category' => null],
            ['name' => 'Pflanzen gießen', 'icon' => '🪴', 'priority' => Priority::Low, 'recurrence_type' => RecurrenceType::Relative, 'relative_interval_days' => 3, 'category' => null],
            ['name' => 'Müll rausstellen', 'icon' => '🗑️', 'priority' => Priority::High, 'recurrence_type' => RecurrenceType::ExplicitDates, 'category' => 'Außen'],
            ['name' => 'Fenster putzen', 'icon' => '🪟', 'priority' => Priority::Low, 'recurrence_type' => RecurrenceType::Rrule, 'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=1', 'category' => null],
            ['name' => 'Kühlschrank reinigen', 'icon' => '🧊', 'priority' => Priority::Low, 'recurrence_type' => RecurrenceType::Rrule, 'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=15', 'category' => 'Küche'],
        ];

        foreach ($templates as $t) {
            TaskTemplate::updateOrCreate(
                ['name' => $t['name'], 'created_by' => null],
                [
                    'description' => null,
                    'priority' => $t['priority'],
                    'recurrence_type' => $t['recurrence_type'],
                    'rrule' => $t['rrule'] ?? null,
                    'relative_interval_days' => $t['relative_interval_days'] ?? null,
                    'suggested_category_id' => $t['category'] ? $category($t['category']) : null,
                    'icon' => $t['icon'],
                ],
            );
        }
    }
}
