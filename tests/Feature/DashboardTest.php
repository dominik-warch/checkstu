<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_home()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertOk();
    }

    public function test_today_only_shows_overdue_and_due_today_occurrences(): void
    {
        $user = User::factory()->create();

        $overdue = TaskOccurrence::factory()->for(Task::factory())->create(['due_date' => now()->subDay()]);
        $dueToday = TaskOccurrence::factory()->for(Task::factory())->create(['due_date' => now()]);
        TaskOccurrence::factory()->for(Task::factory())->create(['due_date' => now()->addDay()]);
        TaskOccurrence::factory()->for(Task::factory())->create(['due_date' => null]);

        $this->actingAs($user)->get(route('home', ['scope' => 'all']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('occurrences', 2)
                ->where('occurrences.0.id', $overdue->id)
                ->where('occurrences.1.id', $dueToday->id));
    }

    public function test_today_reports_only_the_users_own_progress(): void
    {
        $user = User::factory()->create();
        $sibling = User::factory()->create();

        TaskOccurrence::factory()->for(Task::factory())->create(['due_date' => now(), 'assignee_id' => $user->id]);
        TaskOccurrence::factory()->for(Task::factory())->create(['due_date' => now(), 'assignee_id' => $user->id, 'completed_at' => now(), 'completed_by' => $user->id]);
        TaskOccurrence::factory()->for(Task::factory())->create(['due_date' => now(), 'assignee_id' => $sibling->id]);
        TaskOccurrence::factory()->for(Task::factory())->create(['due_date' => now(), 'assignee_id' => $sibling->id, 'completed_at' => now(), 'completed_by' => $sibling->id]);

        $this->actingAs($user)->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('progress.done', 1)
                ->where('progress.open', 1));
    }
}
