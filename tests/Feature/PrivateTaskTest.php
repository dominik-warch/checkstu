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

    private function privateTaskFor(User $creator, ?int $assigneeId = null): TaskOccurrence
    {
        $task = Task::factory()->private()->create(['created_by' => $creator->id]);

        return TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => $assigneeId,
            'due_date' => now()->toDateString(),
        ]);
    }

    public function test_private_task_is_visible_only_to_its_creator(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $this->privateTaskFor($creator, $creator->id);

        $this->actingAs($creator)->get('/')
            ->assertInertia(fn (Assert $page) => $page->has('occurrences', 1));

        $this->actingAs($other)->get('/')
            ->assertInertia(fn (Assert $page) => $page->has('occurrences', 0));
    }

    public function test_creator_can_open_their_private_task_but_others_cannot(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->admin()->create(); // even an admin can't peek
        $occurrence = $this->privateTaskFor($creator, $creator->id);

        $this->actingAs($creator)->get(route('tasks.show', $occurrence->task_id))->assertOk();
        $this->actingAs($other)->get(route('tasks.show', $occurrence->task_id))->assertForbidden();
    }

    public function test_others_cannot_complete_a_private_task(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->admin()->create();
        $occurrence = $this->privateTaskFor($creator, $creator->id);

        $this->actingAs($other)
            ->post(route('occurrences.complete', $occurrence))
            ->assertForbidden();

        $this->assertNull($occurrence->refresh()->completed_at);
    }

    public function test_creator_can_delete_their_own_private_task_even_as_member(): void
    {
        $creator = User::factory()->create(); // member role
        $occurrence = $this->privateTaskFor($creator, $creator->id);

        $this->actingAs($creator)
            ->delete(route('tasks.destroy', $occurrence->task_id))
            ->assertRedirect(route('tasks.index'));

        $this->assertSoftDeleted('tasks', ['id' => $occurrence->task_id]);
    }

    public function test_unassigned_private_task_does_not_leak_into_others_mine_tab(): void
    {
        $creator = User::factory()->create();
        $other = User::factory()->create();
        $this->privateTaskFor($creator, null); // unassigned + private

        // Normally unassigned = up-for-grabs in everyone's "Meine" — but private overrides that.
        $this->actingAs($other)->get(route('tasks.index', ['scope' => 'mine']))
            ->assertInertia(fn (Assert $page) => $page->has('occurrences', 0));

        $this->actingAs($creator)->get(route('tasks.index', ['scope' => 'mine']))
            ->assertInertia(fn (Assert $page) => $page->has('occurrences', 1));
    }
}
