<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRecurrenceDate extends Model
{
    protected $fillable = [
        'task_id',
        'due_on',
        'is_consumed',
    ];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'is_consumed' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
