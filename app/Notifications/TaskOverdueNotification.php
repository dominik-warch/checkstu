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
        // Never print a private task's title in the OS notification banner —
        // same reasoning as TaskAssignedNotification.
        $count = $this->occurrences->count();

        if ($count === 1) {
            $only = $this->occurrences->first();
            $body = $only->task->is_private ? 'Eine private Aufgabe ist überfällig.' : $only->task->title;

            return (new WebPushMessage)
                ->title('Überfällige Aufgabe')
                ->body($body)
                ->icon('/icons/icon-192.png')
                ->data(['url' => route('tasks.show', $only->task)]);
        }

        $featured = $this->occurrences->first(fn (TaskOccurrence $o) => ! $o->task->is_private);
        $body = $featured !== null ? $count.' Aufgaben, u.a. '.$featured->task->title : $count.' Aufgaben';

        return (new WebPushMessage)
            ->title('Überfällige Aufgabe')
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->data(['url' => route('tasks.index')]);
    }
}
