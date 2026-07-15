<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A user's personal watched/watching/watchlist state for one media_item.
        // Unlike tasks, this is never shared across the household.
        Schema::create('media_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_item_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // watchlist|watching|completed — see App\Enums\WatchStatus
            $table->date('watched_at')->nullable(); // movie completion date, or show-fully-finished date
            $table->timestamps();

            $table->unique(['user_id', 'media_item_id']);
            $table->index(['user_id', 'status']); // the exact predicate the home widget filters on
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_entries');
    }
};
