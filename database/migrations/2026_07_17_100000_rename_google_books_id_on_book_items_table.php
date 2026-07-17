<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Book metadata now comes from Open Library, not Google Books — see
        // App\Support\OpenLibrary\OpenLibraryClient. Stores the full resource key
        // (e.g. "/books/OL57502463M"), not a bare id: Open Library's own API takes
        // that path directly, and the "/books/" vs "/works/" prefix is meaningful.
        Schema::table('book_items', function (Blueprint $table) {
            $table->renameColumn('google_books_id', 'open_library_id');
        });
    }

    public function down(): void
    {
        Schema::table('book_items', function (Blueprint $table) {
            $table->renameColumn('open_library_id', 'google_books_id');
        });
    }
};
