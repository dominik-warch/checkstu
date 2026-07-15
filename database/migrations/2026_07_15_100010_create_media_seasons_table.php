<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tmdb_season_id');
            $table->unsignedSmallInteger('season_number'); // 0 = Specials
            $table->string('name');
            $table->unsignedSmallInteger('episode_count')->default(0);
            $table->date('air_date')->nullable();

            // TTL marker: episodes for this season are fetched lazily (on first
            // detail-page expand), not eagerly when the show is added.
            $table->timestamp('episodes_fetched_at')->nullable();

            $table->timestamps();

            $table->unique(['media_item_id', 'season_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_seasons');
    }
};
