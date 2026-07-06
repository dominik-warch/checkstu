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
        // Fresh-install guard: this seeder plants a fixed demo family. Without this
        // check, re-running it (e.g. RUN_SEEDER left on, or `db:seed` run by hand)
        // against a real household would silently inject 5 fake users + demo tasks
        // alongside real data — it wouldn't even crash, since demo usernames are
        // unlikely to collide with real ones. Never run this against a live household.
        if (User::query()->exists()) {
            $this->command?->warn('Users already exist — skipping DemoSeeder (fresh-install only).');

            return;
        }

        // --- Family: 2 parents (admin), 2 kids (member) ---
        $dominik = User::firstOrCreate(['username' => 'dominik'], ['name' => 'Dominik', 'email' => 'dominik@home.local', 'password' => 'password', 'role' => Role::Admin]);
        $sara = User::firstOrCreate(['username' => 'sara'], ['name' => 'Sara', 'email' => 'sara@home.local', 'password' => 'password', 'role' => Role::Admin]);
        $leo = User::firstOrCreate(['username' => 'leo'], ['name' => 'Leo', 'email' => null, 'password' => 'password', 'role' => Role::Member]);
        $leni = User::firstOrCreate(['username' => 'leni'], ['name' => 'Leni', 'email' => null, 'password' => 'password', 'role' => Role::Member]);
        // A guest helper (e.g. grandparent) — only sees tasks assigned to them.
        $opa = User::firstOrCreate(['username' => 'opa'], ['name' => 'Opa', 'email' => null, 'password' => 'password', 'role' => Role::Guest]);

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

        // A task assigned to the guest — the only thing Opa will see.
        $this->task('Rasen mähen', Priority::Normal, $dominik, $opa, today: 'today');

        // A private task — only Dominik can see it.
        $secret = Task::create([
            'title' => 'Geschenk für Sara besorgen',
            'priority' => Priority::Normal,
            'is_private' => true,
            'default_assignee_id' => $dominik->id,
            'created_by' => $dominik->id,
        ]);
        $secret->occurrences()->create([
            'due_date' => now()->addDays(5)->toDateString(),
            'assignee_id' => $dominik->id,
        ]);

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
