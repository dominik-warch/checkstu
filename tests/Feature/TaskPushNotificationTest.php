<?php

namespace Tests\Feature;

use App\Actions\Tasks\NotifyOverdueOccurrencesAction;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_task_notifies_the_assignee(): void
    {
        Notification::fake();

        $creator = User::factory()->admin()->create();
        $assignee = User::factory()->create();

        $this->actingAs($creator)->post(route('tasks.store'), [
            'title' => 'Müll rausbringen',
            'default_assignee_id' => $assignee->id,
            'due_date' => now()->toDateString(),
        ])->assertRedirect();

        Notification::assertSentTo($assignee, TaskAssignedNotification::class);
        Notification::assertNotSentTo($creator, TaskAssignedNotification::class);
    }

    public function test_creating_an_unassigned_task_notifies_every_non_guest_member(): void
    {
        Notification::fake();

        $creator = User::factory()->admin()->create();
        $member = User::factory()->create();
        $guest = User::factory()->guest()->create();

        $this->actingAs($creator)->post(route('tasks.store'), [
            'title' => 'Einkaufen',
            'due_date' => now()->toDateString(),
        ])->assertRedirect();

        Notification::assertSentTo($creator, TaskAssignedNotification::class);
        Notification::assertSentTo($member, TaskAssignedNotification::class);
        Notification::assertNotSentTo($guest, TaskAssignedNotification::class);
    }

    public function test_creating_a_private_task_notifies_only_the_assignee(): void
    {
        Notification::fake();

        $creator = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($creator)->post(route('tasks.store'), [
            'title' => 'Geheimnis',
            'is_private' => true,
            'default_assignee_id' => $other->id,
            'due_date' => now()->toDateString(),
        ])->assertRedirect();

        Notification::assertSentTo($other, TaskAssignedNotification::class);
        Notification::assertNotSentTo($creator, TaskAssignedNotification::class);
    }

    public function test_creating_a_private_task_never_reveals_its_title_in_the_push_body(): void
    {
        Notification::fake();

        $creator = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($creator)->post(route('tasks.store'), [
            'title' => 'Geburtstagsgeschenk für Mama',
            'is_private' => true,
            'default_assignee_id' => $other->id,
            'due_date' => now()->toDateString(),
        ])->assertRedirect();

        Notification::assertSentTo($other, TaskAssignedNotification::class, function (TaskAssignedNotification $notification) use ($other) {
            $message = $notification->toWebPush($other, $notification)->toArray();

            return ! str_contains($message['body'], 'Geburtstagsgeschenk');
        });
    }

    public function test_reassigning_a_task_on_update_notifies_the_new_assignee(): void
    {
        $creator = User::factory()->admin()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();

        $task = Task::factory()->create(['created_by' => $creator->id, 'default_assignee_id' => $first->id]);
        TaskOccurrence::factory()->for($task)->create(['assignee_id' => $first->id, 'due_date' => now()->toDateString()]);

        Notification::fake();

        $this->actingAs($creator)->patch(route('tasks.update', $task), [
            'title' => $task->title,
            'default_assignee_id' => $second->id,
            'due_date' => now()->toDateString(),
        ])->assertRedirect();

        Notification::assertSentTo($second, TaskAssignedNotification::class);
        Notification::assertNotSentTo($first, TaskAssignedNotification::class);
    }

    public function test_updating_a_task_without_changing_the_assignee_does_not_renotify(): void
    {
        $creator = User::factory()->admin()->create();
        $assignee = User::factory()->create();

        $task = Task::factory()->create(['created_by' => $creator->id, 'default_assignee_id' => $assignee->id]);
        TaskOccurrence::factory()->for($task)->create(['assignee_id' => $assignee->id, 'due_date' => now()->toDateString()]);

        Notification::fake();

        $this->actingAs($creator)->patch(route('tasks.update', $task), [
            'title' => 'Neuer Titel',
            'default_assignee_id' => $assignee->id,
            'due_date' => now()->toDateString(),
        ])->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_overdue_action_notifies_the_assignee_with_a_batched_push(): void
    {
        Notification::fake();

        $assignee = User::factory()->create();
        $task = Task::factory()->create();
        TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => $assignee->id,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        app(NotifyOverdueOccurrencesAction::class)->handle();

        Notification::assertSentTo($assignee, TaskOverdueNotification::class);
        Notification::assertSentToTimes($assignee, TaskOverdueNotification::class, 1);
    }

    public function test_overdue_action_notifies_a_private_tasks_assignee_without_revealing_its_title(): void
    {
        Notification::fake();

        $creator = User::factory()->admin()->create();
        $assignee = User::factory()->create();
        $task = Task::factory()->create([
            'created_by' => $creator->id,
            'is_private' => true,
            'default_assignee_id' => $assignee->id,
            'title' => 'Geburtstagsgeschenk für Mama',
        ]);
        TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => $assignee->id,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        app(NotifyOverdueOccurrencesAction::class)->handle();

        Notification::assertSentTo($assignee, TaskOverdueNotification::class, function (TaskOverdueNotification $notification) use ($assignee) {
            $message = $notification->toWebPush($assignee, $notification)->toArray();

            return ! str_contains($message['body'], 'Geburtstagsgeschenk');
        });
        Notification::assertNotSentTo($creator, TaskOverdueNotification::class);
    }

    /** Defensive: this state is unreachable via the UI/validation, but the query must never broadcast it if it existed. */
    public function test_overdue_action_never_broadcasts_an_unassigned_private_task(): void
    {
        Notification::fake();

        $creator = User::factory()->admin()->create();
        $member = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $creator->id, 'is_private' => true, 'default_assignee_id' => null]);
        TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => null,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        app(NotifyOverdueOccurrencesAction::class)->handle();

        Notification::assertNotSentTo($member, TaskOverdueNotification::class);
    }

    public function test_overdue_action_ignores_completed_and_future_occurrences(): void
    {
        Notification::fake();

        $assignee = User::factory()->create();
        $task = Task::factory()->create();

        TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => $assignee->id,
            'due_date' => now()->subDays(2)->toDateString(),
            'completed_at' => now(),
        ]);
        TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => $assignee->id,
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        app(NotifyOverdueOccurrencesAction::class)->handle();

        Notification::assertNothingSent();
    }

    public function test_overdue_action_notifies_all_non_guests_for_unassigned_occurrences(): void
    {
        Notification::fake();

        $member = User::factory()->create();
        $guest = User::factory()->guest()->create();
        $task = Task::factory()->create();
        TaskOccurrence::factory()->for($task)->create([
            'assignee_id' => null,
            'due_date' => now()->subDay()->toDateString(),
        ]);

        app(NotifyOverdueOccurrencesAction::class)->handle();

        Notification::assertSentTo($member, TaskOverdueNotification::class);
        Notification::assertNotSentTo($guest, TaskOverdueNotification::class);
    }
}
