<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A participant of one play — either an app user or a freeform guest
        // name (game nights often include friends without a login). Exactly
        // one of user_id/guest_name is enforced in the FormRequest, not here.
        Schema::create('board_game_play_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_game_play_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->boolean('is_winner')->default(false); // supports co-op wins/ties — not exclusive
            $table->timestamps();

            $table->index(['board_game_play_id', 'user_id']);
            $table->index(['user_id', 'is_winner']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_game_play_players');
    }
};
