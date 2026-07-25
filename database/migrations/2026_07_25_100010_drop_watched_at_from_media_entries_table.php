<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Carry every existing watched_at forward as that entry's first watch
        // before the column disappears — otherwise a movie a user already
        // marked completed would silently lose its watched date.
        $now = now();

        DB::table('media_entries')
            ->whereNotNull('watched_at')
            ->orderBy('id')
            ->get(['id', 'watched_at'])
            ->each(function (object $entry) use ($now): void {
                DB::table('media_entry_watches')->insert([
                    'media_entry_id' => $entry->id,
                    'watched_at' => $entry->watched_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('media_entries', function (Blueprint $table) {
            $table->dropColumn('watched_at');
        });
    }

    public function down(): void
    {
        Schema::table('media_entries', function (Blueprint $table) {
            $table->date('watched_at')->nullable();
        });
    }
};
