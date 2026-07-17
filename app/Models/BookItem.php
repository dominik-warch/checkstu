<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookItem extends Model
{
    /** @use HasFactory<\Database\Factories\BookItemFactory> */
    use HasFactory;

    protected $fillable = [
        'open_library_id',
        'title',
        'authors',
        'overview',
        'thumbnail_url',
        'published_date',
    ];

    protected function casts(): array
    {
        return [
            'published_date' => 'date',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(BookEntry::class);
    }
}
