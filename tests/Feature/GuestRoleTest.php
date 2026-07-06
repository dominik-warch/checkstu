<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuestRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_tasks_assigned_to_them(): void
    {
        $admin = User::factory()->admin()->create();
        $guest = User::factory()->guest()->create();

        TaskOccurrence::factory()->for(Task::factory())->create([
            'assignee_id' => $guest->id,
            'due_date' => now()->toDateString(),
        ]);
        TaskOccurrence::factory()->for(Task::factory())->create([
            'assignee_id' => $admin->id,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($guest)
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page->component('home/today')->has('occurrences', 1));
    }

    public function test_guest_cannot_view_a_task_not_assigned_to_them(): void
    {
        $admin = User::factory()->admin()->create();
        $guest = User::factory()->guest()->create();
        $task = Task::factory()->create();
        TaskOccurrence::factory()->for($task)->create(['assignee_id' => $admin->id]);

        $this->actingAs($guest)->get(route('tasks.show', $task))->assertForbidden();
    }

    public function test_guest_can_view_and_complete_their_own_task(): void
    {
        $guest = User::factory()->guest()->create();
        $task = Task::factory()->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create(['assignee_id' => $guest->id]);

        $this->actingAs($guest)->get(route('tasks.show', $task))->assertOk();

        $this->actingAs($guest)
            ->post(route('occurrences.complete', $occurrence))
            ->assertRedirect();

        $this->assertNotNull($occurrence->refresh()->completed_at);
    }

    public function test_guest_cannot_complete_a_task_not_assigned_to_them(): void
    {
        $admin = User::factory()->admin()->create();
        $guest = User::factory()->guest()->create();
        $occurrence = TaskOccurrence::factory()->for(Task::factory())->create(['assignee_id' => $admin->id]);

        $this->actingAs($guest)
            ->post(route('occurrences.complete', $occurrence))
            ->assertForbidden();

        $this->assertNull($occurrence->refresh()->completed_at);
    }

    public function test_guest_cannot_create_a_task(): void
    {
        $guest = User::factory()->guest()->create();

        $this->actingAs($guest)
            ->post(route('tasks.store'), ['title' => 'Verboten'])
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', ['title' => 'Verboten']);
    }

    public function test_guest_cannot_access_the_family_screen(): void
    {
        $guest = User::factory()->guest()->create();

        $this->actingAs($guest)->get(route('family'))->assertForbidden();
    }
}
