<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_season_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tmdb_episode_id');
            $table->unsignedSmallInteger('episode_number');
            $table->string('name'); // single language — the dual-language ask is item-level, not per-episode
            $table->date('air_date')->nullable();
            $table->timestamps();

            $table->unique(['media_season_id', 'episode_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_episodes');
    }
};
