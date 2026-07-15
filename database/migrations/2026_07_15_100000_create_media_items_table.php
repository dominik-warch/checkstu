<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shared catalogue cache — fetched once from TMDb, reused across every
        // family member's personal media_entries row.
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tmdb_id');
            $table->string('type'); // movie|tv — see App\Enums\MediaType

            $table->string('title_de');
            $table->string('title_en');
            $table->text('overview')->nullable(); // German if TMDb has a translation, else English

            $table->string('poster_path')->nullable(); // relative path, build the full URL client-side
            $table->date('release_date')->nullable();  // movie release, or tv first-air
            $table->string('tv_status')->nullable();   // raw TMDb string, tv only: Returning Series|Ended|Canceled

            $table->timestamps();

            $table->unique(['tmdb_id', 'type']); // TMDb ids are only unique within a type namespace
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
