<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shared catalogue cache — fetched once from Google Books, reused
        // across every family member's personal book_entries row. A separate
        // table from media_items: Google Books volume ids are alphanumeric
        // strings (e.g. "zyTCAlFPjgYC"), not TMDb-style integers, and books
        // have no season/episode structure.
        Schema::create('book_items', function (Blueprint $table) {
            $table->id();
            $table->string('google_books_id')->unique();

            $table->string('title');
            $table->string('authors')->nullable(); // comma-joined, Google Books gives an array
            $table->text('overview')->nullable();

            $table->string('thumbnail_url')->nullable(); // Google Books returns a full URL, unlike TMDb's relative paths
            $table->date('published_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_items');
    }
};
