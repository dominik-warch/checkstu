<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replaces media_entries.watched_at (a single date) with a list of watch
        // dates per movie entry — see the following migration, which drops that column.
        Schema::create('media_entry_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_entry_id')->constrained()->cascadeOnDelete();
            $table->date('watched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_entry_watches');
    }
};
