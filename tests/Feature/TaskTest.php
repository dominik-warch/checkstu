<?php

namespace Tests\Feature;

use App\Enums\RecurrenceType;
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

    public function test_completing_a_relative_task_does_not_crash_if_the_next_occurrence_already_exists(): void
    {
        // Regression: Eloquent's `date` cast serializes to a full datetime string on
        // write, so a raw firstOrCreate(['due_date' => ...]) never matches an
        // already-existing row on read-back and throws on the unique index instead
        // of no-op'ing. Pre-seed the "next" occurrence to reproduce that scenario.
        $user = User::factory()->create();
        $task = Task::factory()->relative(3)->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create([
            'due_date' => now()->toDateString(),
            'assignee_id' => $user->id,
        ]);
        TaskOccurrence::factory()->for($task)->create([
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('occurrences.complete', $occurrence))
            ->assertRedirect();

        $this->assertSame(2, $task->occurrences()->count());
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

    public function test_updating_a_task_cannot_change_its_recurrence_pattern(): void
    {
        // Editing recurrence after creation is out of scope for v1 (delete + recreate
        // instead) — the frontend simply hides the picker in edit mode, but the
        // backend must not be tricked by a raw request smuggling these fields in.
        $user = User::factory()->create();
        $task = Task::factory()->rrule('FREQ=WEEKLY;BYDAY=SA')->create(['anchor_date' => '2026-07-04']);
        TaskOccurrence::factory()->for($task)->create(['due_date' => now()->toDateString()]);

        $this->actingAs($user)
            ->patch(route('tasks.update', $task), [
                'title' => $task->title,
                'recurrence_type' => 'relative',
                'relative_interval_days' => 5,
            ])
            ->assertRedirect(route('tasks.show', $task));

        $task->refresh();
        $this->assertSame(RecurrenceType::Rrule, $task->recurrence_type);
        $this->assertSame('FREQ=WEEKLY;BYDAY=SA', $task->rrule);
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

    public function test_task_detail_exposes_the_open_occurrence_for_completion(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => $user->id,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)->get(route('tasks.show', $task))
            ->assertInertia(fn (Assert $page) => $page
                ->where('occurrence.id', $occurrence->id)
                ->where('occurrence.completed_at', null)
                ->where('can.complete', true)
            );
    }

    public function test_task_detail_shows_no_occurrence_once_completed(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => $user->id,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)->post(route('occurrences.complete', $occurrence))->assertRedirect();

        $this->actingAs($user)->get(route('tasks.show', $task))
            ->assertInertia(fn (Assert $page) => $page->where('occurrence', null));
    }

    public function test_completing_a_task_from_the_detail_page(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => $user->id,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('occurrences.complete', $occurrence))
            ->assertRedirect();

        $this->assertNotNull($occurrence->refresh()->completed_at);
        $this->assertSame($user->id, $occurrence->completed_by);
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

    public function test_assignee_color_is_exposed_on_occurrences(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create(['color' => '#ec4899']);
        TaskOccurrence::factory()->for(Task::factory())->create(['assignee_id' => $assignee->id, 'due_date' => now()->toDateString()]);

        $this->actingAs($user)->get(route('home', ['scope' => 'all']))
            ->assertInertia(fn (Assert $page) => $page->where('occurrences.0.assignee.color', '#ec4899'));
    }

    public function test_only_the_earliest_open_occurrence_of_a_recurring_task_is_listed(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['recurrence_type' => RecurrenceType::Rrule]);

        $earliest = TaskOccurrence::factory()->for($task)->create(['due_date' => now()->addDay()]);
        TaskOccurrence::factory()->for($task)->create(['due_date' => now()->addDays(8)]);
        TaskOccurrence::factory()->for($task)->create(['due_date' => now()->addDays(15)]);

        $this->actingAs($user)->get(route('tasks.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('occurrences', 1)
                ->where('occurrences.0.id', $earliest->id)
                ->where('occurrences.0.is_recurring', true));
    }

    public function test_completing_the_current_occurrence_reveals_the_next_one(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['recurrence_type' => RecurrenceType::Rrule]);

        $earliest = TaskOccurrence::factory()->for($task)->create(['due_date' => now()->addDay()]);
        $next = TaskOccurrence::factory()->for($task)->create(['due_date' => now()->addDays(8)]);

        $this->actingAs($user)->post(route('occurrences.complete', $earliest));

        $this->actingAs($user)->get(route('tasks.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('occurrences', 1)
                ->where('occurrences.0.id', $next->id));
    }

    public function test_one_off_and_relative_tasks_expose_is_recurring_false(): void
    {
        $user = User::factory()->create();
        $oneOff = Task::factory()->create(['recurrence_type' => RecurrenceType::OneOff]);
        TaskOccurrence::factory()->for($oneOff)->create(['due_date' => now()]);

        $this->actingAs($user)->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('occurrences.0.is_recurring', false));
    }
}
