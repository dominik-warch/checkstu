<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Tasks\CompleteTaskAction;
use App\Enums\Priority;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // --- Family: 2 parents (admin), 2 kids (member) ---
        $dominik = User::create(['name' => 'Dominik', 'username' => 'dominik', 'email' => 'dominik@home.local', 'password' => 'password', 'role' => Role::Admin]);
        $sara = User::create(['name' => 'Sara', 'username' => 'sara', 'email' => 'sara@home.local', 'password' => 'password', 'role' => Role::Admin]);
        $leo = User::create(['name' => 'Leo', 'username' => 'leo', 'email' => null, 'password' => 'password', 'role' => Role::Member]);
        $leni = User::create(['name' => 'Leni', 'username' => 'leni', 'email' => null, 'password' => 'password', 'role' => Role::Member]);

        // --- Categories ---
        foreach (['Küche', 'Bad', 'Wohnzimmer', 'Schlafzimmer', 'Außen'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
        $kueche = Category::where('name', 'Küche')->first();
        $wohnzimmer = Category::where('name', 'Wohnzimmer')->first();

        // --- One-off tasks (P1), each with one occurrence ---
        $bulb = $this->task('Glühbirne im Flur wechseln', Priority::High, $dominik, $dominik, today: '+1 day');

        $tidy = $this->task('Wohnzimmer aufräumen', Priority::Normal, $dominik, $leo, today: 'today');
        $tidy->categories()->attach($wohnzimmer);

        $vacuum = $this->task('Staubsaugen', Priority::Normal, $dominik, $leni, today: 'today');
        $vacuum->categories()->attach($wohnzimmer);
        // Staubsaugen is blocked until aufräumen is done.
        $vacuum->dependencies()->attach($tidy);

        $dishes = $this->task('Geschirrspüler ausräumen', Priority::Normal, $sara, $leo, today: 'today');
        $dishes->categories()->attach($kueche);

        $trash = $this->task('Müll rausbringen', Priority::High, $sara, $leni, today: '-1 day'); // overdue

        // --- Demonstrate admin completing a kid's task on their behalf ---
        // Sara (parent) marks Leni's overdue task done, attributed to Leni.
        app(CompleteTaskAction::class)->handle(
            $trash->openOccurrence,
            actor: $sara,
            completedBy: $leni,
        );
    }

    private function task(string $title, Priority $priority, User $creator, ?User $assignee, string $today): Task
    {
        $task = Task::create([
            'title' => $title,
            'priority' => $priority,
            'default_assignee_id' => $assignee?->id,
            'created_by' => $creator->id,
        ]);

        $task->occurrences()->create([
            'due_date' => $today === 'today' ? now()->toDateString() : now()->modify($today)->toDateString(),
            'assignee_id' => $assignee?->id,
        ]);

        return $task;
    }
}
