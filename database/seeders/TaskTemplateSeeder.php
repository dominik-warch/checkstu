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
            'Klavier üben',
            'Graue Tonne rausstellen',
            'Blaue Tonne rausstellen',
            'Gelbe Tonne rausstellen',
            'Braune Tonne rausstellen',
            'Hausaufgaben erledigen',
            'Bettwäsche wechseln',
            'Mülleimer voll? Rausbringen!',
            'Wäsche waschen',
            'Wäsche in den Schrank räumen',
            'Klamottenstapel aufräumen',
            'Powerbank aufladen',
            'Blumen gießen drinnen',
            'Garten gießen',
            'Spülmaschine ausräumen',
            'Kellerbad putzen',
            'Kühlschrank abtauen',
            'Gefrierschrank durchgehen und Zeug verbrauchen',
            'Altglas wegbringen',
            'Pfand wegbringen',
            'Schulplaner checken',
            'Taschengeld auszahlen (PAYDAY!)',
            'Zimmer aufräumen',
            'Brillen einsammeln und ins Gestell',
            'Yukis Kuscheltiere wegräumen',
            'Geschirr aus dem Zimmer in die Spülmaschine',
            'Windeln da? Kaufen!'
        ];

        foreach ($names as $name) {
            TaskTemplate::firstOrCreate(['name' => $name], ['created_by' => null]);
        }
    }
}
