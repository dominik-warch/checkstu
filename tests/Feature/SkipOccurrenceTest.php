<?php

namespace Tests\Feature;

use App\Enums\CompletionAction;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkipOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_can_skip_an_occurrence_and_it_leaves_the_open_lists(): void
    {
        $admin = User::factory()->admin()->create();
        $occurrence = TaskOccurrence::factory()->for(Task::factory())->create(['due_date' => now()->subWeek()->toDateString()]);

        $this->actingAs($admin)->post(route('occurrences.skip', $occurrence))->assertRedirect();

        $this->assertTrue($occurrence->fresh()->is_skipped);
        $this->assertNull($occurrence->fresh()->completed_at);
        $this->assertDatabaseHas('task_completion_logs', [
            'task_occurrence_id' => $occurrence->id,
            'user_id' => $admin->id,
            'action' => CompletionAction::Skipped->value,
        ]);
        $this->assertSame(0, TaskOccurrence::current()->count());
    }

    public function test_skipping_from_the_task_page_returns_to_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        $occurrence = TaskOccurrence::factory()->for(Task::factory())->create();

        $this->actingAs($admin)
            ->from(route('tasks.show', $occurrence->task_id))
            ->post(route('occurrences.skip', $occurrence))
            ->assertRedirect(route('tasks.index'));
    }

    public function test_a_child_cannot_skip_a_shared_task(): void
    {
        $member = User::factory()->create();
        $occurrence = TaskOccurrence::factory()->for(Task::factory())->create(['assignee_id' => $member->id]);

        $this->actingAs($member)->post(route('occurrences.skip', $occurrence))->assertForbidden();

        $this->assertFalse($occurrence->fresh()->is_skipped);
    }

    public function test_skipping_a_relative_task_schedules_the_next_one_from_today(): void
    {
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->relative(3)->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create(['due_date' => now()->subWeek()->toDateString()]);

        $this->actingAs($admin)->post(route('occurrences.skip', $occurrence));

        $this->assertTrue($task->occurrences()->whereDate('due_date', now()->addDays(3))->where('is_skipped', false)->exists());
    }

    public function test_undoing_a_skip_reopens_it_and_drops_the_spawned_next_one(): void
    {
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->relative(3)->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create(['due_date' => now()->subWeek()->toDateString()]);

        $this->actingAs($admin)->post(route('occurrences.skip', $occurrence));
        $this->actingAs($admin)->delete(route('occurrences.restore', $occurrence))->assertRedirect();

        $this->assertFalse($occurrence->fresh()->is_skipped);
        $this->assertSame(1, $task->occurrences()->count());
    }
}
