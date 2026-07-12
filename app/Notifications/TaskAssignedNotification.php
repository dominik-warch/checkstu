<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Task $task)
    {
        // Wait for the creating/updating transaction to commit before sending.
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        // A private task's title never appears in the OS notification banner —
        // anyone glancing at the recipient's lock screen could read it, which
        // would defeat the whole point (e.g. "buy a present for mom").
        $body = $this->task->is_private ? 'Dir wurde eine private Aufgabe zugewiesen.' : $this->task->title;

        return (new WebPushMessage)
            ->title('Neue Aufgabe')
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->data(['url' => route('tasks.show', $this->task)]);
    }
}
