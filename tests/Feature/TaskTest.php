<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskCompletionLog;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_task_with_a_single_one_off_occurrence(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Fenster putzen',
                'priority' => 2,
                'due_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $task = Task::firstWhere('title', 'Fenster putzen');

        $this->assertNotNull($task);
        $this->assertSame(1, $task->occurrences()->count());
        $this->assertSame(now()->toDateString(), $task->occurrences()->first()->due_date->toDateString());
    }

    public function test_member_can_complete_their_own_occurrence(): void
    {
        $member = User::factory()->create();
        $occurrence = TaskOccurrence::factory()
            ->for(Task::factory())
            ->create(['assignee_id' => $member->id]);

        $this->actingAs($member)
            ->post(route('occurrences.complete', $occurrence))
            ->assertRedirect();

        $occurrence->refresh();
        $this->assertNotNull($occurrence->completed_at);
        $this->assertSame($member->id, $occurrence->completed_by);

        $log = TaskCompletionLog::latest('id')->first();
        $this->assertSame($member->id, $log->user_id);
        $this->assertNull($log->acted_by_user_id);
    }

    public function test_admin_can_complete_a_task_on_behalf_of_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $kid = User::factory()->create();
        $occurrence = TaskOccurrence::factory()
            ->for(Task::factory())
            ->create(['assignee_id' => $kid->id]);

        $this->actingAs($admin)
            ->post(route('occurrences.complete', $occurrence), [
                'completed_by_user_id' => $kid->id,
            ])
            ->assertRedirect();

        $occurrence->refresh();
        $this->assertSame($kid->id, $occurrence->completed_by);

        $log = TaskCompletionLog::latest('id')->first();
        $this->assertSame($kid->id, $log->user_id);
        $this->assertSame($admin->id, $log->acted_by_user_id);
    }

    public function test_member_cannot_complete_on_behalf_of_another_user(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $occurrence = TaskOccurrence::factory()
            ->for(Task::factory())
            ->create(['assignee_id' => $member->id]);

        $this->actingAs($member)
            ->post(route('occurrences.complete', $occurrence), [
                'completed_by_user_id' => $other->id,
            ])
            ->assertForbidden();

        $this->assertNull($occurrence->refresh()->completed_at);
    }

    public function test_completing_a_relative_task_spawns_the_next_occurrence(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->relative(3)->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create([
            'due_date' => now()->toDateString(),
            'assignee_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('occurrences.complete', $occurrence))
            ->assertRedirect();

        $this->assertSame(1, $task->occurrences()->whereNull('completed_at')->count());
    }

    public function test_a_user_can_update_a_task_and_its_open_occurrence(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['title' => 'Alt']);
        TaskOccurrence::factory()->for($task)->create(['due_date' => now()->toDateString()]);

        $this->actingAs($user)
            ->patch(route('tasks.update', $task), [
                'title' => 'Neu',
                'priority' => 3,
                'due_date' => now()->addWeek()->toDateString(),
            ])
            ->assertRedirect(route('tasks.show', $task));

        $task->refresh();
        $this->assertSame('Neu', $task->title);
        $this->assertSame(now()->addWeek()->toDateString(), $task->openOccurrence->due_date->toDateString());
    }

    public function test_member_cannot_delete_a_task(): void
    {
        $member = User::factory()->create();
        $task = Task::factory()->create();

        $this->actingAs($member)
            ->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertNotSoftDeleted($task);
    }

    public function test_admin_can_delete_a_task(): void
    {
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->create();

        $this->actingAs($admin)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertSoftDeleted($task);
    }

    public function test_task_detail_and_upcoming_pages_render(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        TaskOccurrence::factory()->for($task)->create(['due_date' => now()->toDateString()]);

        $this->actingAs($user)->get(route('tasks.show', $task))->assertOk();
        $this->actingAs($user)->get(route('upcoming'))->assertOk();
    }

    public function test_mine_tab_includes_unassigned_tasks(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        TaskOccurrence::factory()->for(Task::factory())->create(['assignee_id' => $user->id]);
        TaskOccurrence::factory()->for(Task::factory())->create(['assignee_id' => null]);   // up for grabs
        TaskOccurrence::factory()->for(Task::factory())->create(['assignee_id' => $other->id]);

        // "Meine" shows mine + unassigned, but not someone else's.
        $this->actingAs($user)
            ->get(route('tasks.index', ['scope' => 'mine']))
            ->assertInertia(fn (Assert $page) => $page->component('tasks/index')->has('occurrences', 2));
    }
}
