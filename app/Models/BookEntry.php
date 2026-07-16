<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookEntry extends Model
{
    /** @use HasFactory<\Database\Factories\BookEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_item_id',
        'status',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WatchStatus::class,
            'read_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookItem(): BelongsTo
    {
        return $this->belongsTo(BookItem::class);
    }
}
