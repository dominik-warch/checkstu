<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Lets a user log the same episode watched more than once — see MarkEpisodeWatchedAction. */
    public function up(): void
    {
        Schema::table('media_episode_watches', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'media_episode_id']);
            $table->index(['user_id', 'media_episode_id']);
        });
    }

    public function down(): void
    {
        Schema::table('media_episode_watches', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'media_episode_id']);
            $table->unique(['user_id', 'media_episode_id']);
        });
    }
};
