<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogue of previously-used task names (autocomplete + "most used" chips
        // in the create form) — just a name and how often it's been used. Recurrence,
        // priority, category etc. are NOT templated; every task is created through the
        // normal form regardless of how its title was entered.
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('usage_count')->default(0);
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete(); // null = seeded starter name
            $table->timestamps();

            $table->index('usage_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
