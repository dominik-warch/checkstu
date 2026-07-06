<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Priority;
use App\Enums\RecurrenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'is_private',
        'default_assignee_id',
        'recurrence_type',
        'rrule',
        'anchor_date',
        'relative_interval_days',
        'recurrence_ends_on',
        'is_active',
        'created_by',
    ];

    /**
     * PHP-side mirror of the migration's column defaults. Without this,
     * Task::create([...]) that omits `is_active` leaves the in-memory attribute
     * NULL (Eloquent doesn't refresh from the DB default after insert), which
     * made `! $task->is_active` in MaterializeOccurrencesAction silently skip a
     * freshly created recurring task.
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'recurrence_type' => RecurrenceType::class,
            'anchor_date' => 'date',
            'recurrence_ends_on' => 'date',
            'is_active' => 'boolean',
            'is_private' => 'boolean',
        ];
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(TaskOccurrence::class);
    }

    /** The single open (not completed, not skipped) occurrence, earliest due first. */
    public function openOccurrence(): HasOne
    {
        return $this->hasOne(TaskOccurrence::class)
            ->whereNull('completed_at')
            ->where('is_skipped', false)
            ->oldestOfMany('due_date');
    }

    public function recurrenceDates(): HasMany
    {
        return $this->hasMany(TaskRecurrenceDate::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Tasks that block THIS task (this task depends on them). */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id',
        )->withTimestamps();
    }

    /** Tasks blocked BY this task (they depend on this one). */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id',
        )->withTimestamps();
    }
}
