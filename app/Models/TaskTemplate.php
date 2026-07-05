<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Priority;
use App\Enums\RecurrenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\TaskTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'created_by',
        'name',
        'description',
        'priority',
        'recurrence_type',
        'rrule',
        'relative_interval_days',
        'suggested_category_id',
        'icon',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'recurrence_type' => RecurrenceType::class,
        ];
    }

    public function suggestedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'suggested_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
