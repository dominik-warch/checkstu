<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Every task creation counts toward its title's place in the catalogue —
 * whether the title was picked from a suggestion or typed fresh. Matching is
 * case-insensitive so "Staubsaugen" and "staubsaugen" consolidate onto one
 * entry rather than fragmenting the usage count.
 */
class RecordTaskTitleUsageAction
{
    public function handle(string $title, User $creator): void
    {
        $title = trim($title);
        if ($title === '') {
            return;
        }

        $template = TaskTemplate::whereRaw('LOWER(name) = ?', [Str::lower($title)])->first();

        if ($template) {
            $template->increment('usage_count');

            return;
        }

        TaskTemplate::create([
            'name' => $title,
            'usage_count' => 1,
            'created_by' => $creator->id,
        ]);
    }
}
