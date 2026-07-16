<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A user's personal to-read/read state for one book_item — mirrors
        // media_entries. Books only ever use watchlist|completed (WatchStatus)
        // — there's no episode-like sub-structure to reach "watching" through.
        Schema::create('book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_item_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->date('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'book_item_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_entries');
    }
};
