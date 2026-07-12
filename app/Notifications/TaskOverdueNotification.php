<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TaskOccurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TaskOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, TaskOccurrence>  $occurrences
     */
    public function __construct(private readonly Collection $occurrences) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        $first = $this->occurrences->first();
        $count = $this->occurrences->count();

        $body = $count === 1
            ? $first->task->title
            : $count.' Aufgaben, u.a. '.$first->task->title;

        return (new WebPushMessage)
            ->title('Überfällige Aufgabe')
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->data(['url' => $count === 1 ? route('tasks.show', $first->task) : route('tasks.index')]);
    }
}
