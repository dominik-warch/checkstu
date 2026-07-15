<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaEpisodeWatch extends Model
{
    /** @use HasFactory<\Database\Factories\MediaEpisodeWatchFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'media_episode_id',
        'watched_at',
    ];

    protected function casts(): array
    {
        return [
            'watched_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(MediaEpisode::class, 'media_episode_id');
    }
}
