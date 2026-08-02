<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per logged play session. Cascades from board_games — removing
        // a game from the catalogue is a rare, explicit action, and it's fine
        // for that game's play history to go with it.
        Schema::create('board_game_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_game_id')->constrained()->cascadeOnDelete();
            $table->date('played_at');
            $table->text('notes')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['board_game_id', 'played_at']);
            $table->index('played_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_game_plays');
    }
};
