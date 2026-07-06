<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaskTemplate;
use Illuminate\Database\Seeder;

/**
 * Starter catalogue of common household chore names, so the create form's
 * autocomplete/chips aren't empty on first use. created_by = null marks these
 * as seeded (vs. names that grow organically from actual usage).
 */
class TaskTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Staubsaugen',
            'Bad putzen',
            'Küche wischen',
            'Bettwäsche wechseln',
            'Pflanzen gießen',
            'Müll rausstellen',
            'Fenster putzen',
            'Kühlschrank reinigen',
        ];

        foreach ($names as $name) {
            TaskTemplate::firstOrCreate(['name' => $name], ['created_by' => null]);
        }
    }
}
