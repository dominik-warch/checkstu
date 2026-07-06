<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TaskOccurrence extends Model
{
    /** @use HasFactory<\Database\Factories\TaskOccurrenceFactory> */
    use HasFactory;

    protected $fillable = [
        'task_id',
        'due_date',
        'assignee_id',
        'completed_at',
        'completed_by',
        'is_skipped',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'is_skipped' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isOpen(): bool
    {
        return $this->completed_at === null && ! $this->is_skipped;
    }

    /**
     * Restrict to what a user is allowed to see. Guests only see occurrences
     * assigned to them; everyone else sees the shared pool.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isGuest()) {
            $query->where('assignee_id', $user->id);
        }
    }

    /**
     * Derived, clock-dependent state. Never stored.
     * done | skipped | someday | overdue | due_soon | open
     */
    protected function status(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->completed_at !== null) {
                return 'done';
            }
            if ($this->is_skipped) {
                return 'skipped';
            }
            if ($this->due_date === null) {
                return 'someday';
            }

            $today = Carbon::today();
            if ($this->due_date->lt($today)) {
                return 'overdue';
            }
            if ($this->due_date->lte($today->copy()->addDays(2))) {
                return 'due_soon';
            }

            return 'open';
        });
    }
}
