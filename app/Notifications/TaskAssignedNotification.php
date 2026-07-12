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
        return (new WebPushMessage)
            ->title('Neue Aufgabe')
            ->body($this->task->title)
            ->icon('/icons/icon-192.png')
            ->data(['url' => route('tasks.show', $this->task)]);
    }
}
