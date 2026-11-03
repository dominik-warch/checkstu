<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Private task titles used to be recorded in the shared title catalogue, where everyone saw them
 * as suggestions. Remove those entries again: titles only private tasks (deleted ones included)
 * ever used. Seeded suggestions (no creator) and titles a shared task also uses stay.
 */
return new class extends Migration
{
    public function up(): void
    {
        $normalize = fn (string $title) => mb_strtolower(trim($title));

        $shared = DB::table('tasks')->where('is_private', false)->pluck('title')->map($normalize)->flip();
        $leaked = DB::table('tasks')->where('is_private', true)->pluck('title')->map($normalize)
            ->reject(fn (string $title) => $shared->has($title))
            ->flip();

        if ($leaked->isEmpty()) {
            return;
        }

        $ids = DB::table('task_templates')->whereNotNull('created_by')->get(['id', 'name'])
            ->filter(fn ($template) => $leaked->has($normalize($template->name)))
            ->pluck('id');

        DB::table('task_templates')->whereIn('id', $ids)->delete();
    }
};
