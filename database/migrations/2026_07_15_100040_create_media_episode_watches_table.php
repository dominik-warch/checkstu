<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deliberately no FK to media_entries — episode-watch history survives
        // removing/re-adding a show to the library.
        Schema::create('media_episode_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_episode_id')->constrained()->cascadeOnDelete();
            $table->date('watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'media_episode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_episode_watches');
    }
};
