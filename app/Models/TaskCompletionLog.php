<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompletionAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCompletionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_occurrence_id',
        'task_id',
        'user_id',
        'acted_by_user_id',
        'action',
        'due_date',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => CompletionAction::class,
            'due_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    /** The user the completion is attributed to (whose it counts as). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The admin who performed it on someone else's behalf (null = self). */
    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
