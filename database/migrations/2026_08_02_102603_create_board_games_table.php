<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The shared household catalogue — one row per game owned or wanted.
        // Unlike Media, this is never personal-per-user.
        Schema::create('board_games', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('bgg_id')->nullable(); // null for a manually-entered game
            $table->string('name');
            $table->string('thumbnail_url')->nullable();
            $table->unsignedSmallInteger('year_published')->nullable();
            $table->unsignedTinyInteger('min_players')->nullable();
            $table->unsignedTinyInteger('max_players')->nullable();
            $table->unsignedSmallInteger('playtime_minutes')->nullable();
            $table->string('status')->default('wishlist'); // App\Enums\BoardGameStatus
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('bgg_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_games');
    }
};
