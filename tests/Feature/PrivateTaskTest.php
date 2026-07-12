<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrivateTaskTest extends TestCase
{
    use RefreshDatabase;

    private function privateTaskFor(User $creator, int $assigneeId): TaskOccurrence
    {
        $task = Task::factory()->private()->create([
            'created_by' => $creator->id,
            'default_assignee_id' => $assigneeId,
        ]);

        return TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => $assigneeId,
            'due_date' => now()->toDateString(),
        ]);
    }

    public function test_private_task_assigned_to_self_is_visible_only_to_that_person(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $this->privateTaskFor($creator, $creator->id);

        $this->actingAs($creator)->get('/')
            ->assertInertia(fn (Assert $page) => $page->has('occurrences', 1));

        $this->actingAs($other)->get('/')
            ->assertInertia(fn (Assert $page) => $page->has('occurrences', 0));
    }

    public function test_private_task_handed_to_someone_else_is_visible_to_them_not_the_creator(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $this->privateTaskFor($creator, $assignee->id);

        $this->actingAs($assignee)->get('/')
            ->assertInertia(fn (Assert $page) => $page->has('occurrences', 1));

        // The creator loses access the moment it's handed to someone else.
        $this->actingAs($creator)->get('/')
            ->assertInertia(fn (Assert $page) => $page->has('occurrences', 0));
    }

    public function test_assignee_can_open_and_complete_a_private_task_handed_to_them(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $occurrence = $this->privateTaskFor($creator, $assignee->id);

        $this->actingAs($assignee)->get(route('tasks.show', $occurrence->task_id))->assertOk();

        $this->actingAs($assignee)
            ->post(route('occurrences.complete', $occurrence))
            ->assertRedirect();

        $this->assertNotNull($occurrence->refresh()->completed_at);
    }

    public function test_creator_cannot_view_or_complete_a_private_task_after_handing_it_off(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $occurrence = $this->privateTaskFor($creator, $assignee->id);

        $this->actingAs($creator)->get(route('tasks.show', $occurrence->task_id))->assertForbidden();

        $this->actingAs($creator)
            ->post(route('occurrences.complete', $occurrence))
            ->assertForbidden();

        $this->assertNull($occurrence->refresh()->completed_at);
    }

    public function test_others_cannot_view_or_complete_a_private_task(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->admin()->create(); // even an admin can't peek
        $occurrence = $this->privateTaskFor($creator, $creator->id);

        $this->actingAs($other)->get(route('tasks.show', $occurrence->task_id))->assertForbidden();

        $this->actingAs($other)
            ->post(route('occurrences.complete', $occurrence))
            ->assertForbidden();

        $this->assertNull($occurrence->refresh()->completed_at);
    }

    public function test_admin_cannot_complete_on_behalf_for_a_private_task_they_cannot_see(): void
    {
        $creator = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $occurrence = $this->privateTaskFor($creator, $creator->id);

        $this->actingAs($admin)
            ->post(route('occurrences.complete', $occurrence), ['completed_by_user_id' => $creator->id])
            ->assertForbidden();

        $this->assertNull($occurrence->refresh()->completed_at);
    }

    public function test_assignee_can_delete_a_private_task_even_as_member(): void
    {
        $creator = User::factory()->create(); // member role
        $occurrence = $this->privateTaskFor($creator, $creator->id);

        $this->actingAs($creator)
            ->delete(route('tasks.destroy', $occurrence->task_id))
            ->assertRedirect(route('tasks.index'));

        $this->assertSoftDeleted('tasks', ['id' => $occurrence->task_id]);
    }

    public function test_creator_cannot_delete_a_private_task_after_handing_it_off(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $occurrence = $this->privateTaskFor($creator, $assignee->id);

        $this->actingAs($creator)
            ->delete(route('tasks.destroy', $occurrence->task_id))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $occurrence->task_id, 'deleted_at' => null]);
    }

    public function test_creating_a_private_task_without_an_assignee_is_rejected(): void
    {
        $creator = User::factory()->create();

        $this->actingAs($creator)->post(route('tasks.store'), [
            'title' => 'Geheimnis',
            'is_private' => true,
            'due_date' => now()->toDateString(),
        ])->assertSessionHasErrors('default_assignee_id');

        $this->assertDatabaseMissing('tasks', ['title' => 'Geheimnis']);
    }
}
