<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskCompletionLog;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_page_lists_completed_occurrences_latest_first(): void
    {
        $user = User::factory()->create();
        $older = TaskOccurrence::factory()->for(Task::factory())->create([
            'due_date' => now()->subDays(5)->toDateString(),
            'completed_at' => now()->subDays(2),
            'completed_by' => $user->id,
        ]);
        $newer = TaskOccurrence::factory()->for(Task::factory())->create([
            'due_date' => now()->subDays(1)->toDateString(),
            'completed_at' => now()->subHour(),
            'completed_by' => $user->id,
        ]);
        TaskOccurrence::factory()->for(Task::factory())->create(); // still open, must not appear

        $this->actingAs($user)
            ->get(route('archive', ['scope' => 'all']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('archive/index')
                ->has('occurrences', 2)
                ->where('occurrences.0.id', $newer->id)
                ->where('occurrences.1.id', $older->id)
            );
    }

    public function test_member_can_restore_their_own_completed_occurrence(): void
    {
        $member = User::factory()->create();
        $occurrence = TaskOccurrence::factory()
            ->for(Task::factory())
            ->create([
                'assignee_id' => $member->id,
                'completed_at' => now(),
                'completed_by' => $member->id,
            ]);

        $this->actingAs($member)
            ->delete(route('occurrences.restore', $occurrence))
            ->assertRedirect();

        $occurrence->refresh();
        $this->assertNull($occurrence->completed_at);
        $this->assertNull($occurrence->completed_by);

        $log = TaskCompletionLog::latest('id')->first();
        $this->assertSame('reopened', $log->action->value);
        $this->assertSame($member->id, $log->user_id);
        $this->assertNull($log->acted_by_user_id);
    }

    public function test_restoring_a_relative_tasks_completion_removes_the_auto_spawned_next_occurrence(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->relative(3)->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create([
            'due_date' => now()->toDateString(),
            'assignee_id' => $user->id,
        ]);

        $this->actingAs($user)->post(route('occurrences.complete', $occurrence))->assertRedirect();
        $this->assertSame(2, $task->occurrences()->count());

        $this->actingAs($user)
            ->delete(route('occurrences.restore', $occurrence))
            ->assertRedirect();

        $this->assertSame(1, $task->occurrences()->count());
        $this->assertNull($occurrence->refresh()->completed_at);
    }

    public function test_restoring_does_not_delete_the_spawned_occurrence_if_it_was_already_touched(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->relative(3)->create();
        $occurrence = TaskOccurrence::factory()->for($task)->create([
            'due_date' => now()->toDateString(),
            'assignee_id' => $user->id,
        ]);

        $this->actingAs($user)->post(route('occurrences.complete', $occurrence))->assertRedirect();
        $spawned = $task->occurrences()->whereNull('completed_at')->firstOrFail();
        $spawned->update(['is_skipped' => true]);

        $this->actingAs($user)
            ->delete(route('occurrences.restore', $occurrence))
            ->assertRedirect();

        $this->assertSame(2, $task->occurrences()->count());
        $this->assertTrue($spawned->refresh()->is_skipped);
    }
}
