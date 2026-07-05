<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogue of predefined todos (plan §4.8). Shared across the household.
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete(); // null = seeded system template
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('priority')->default(1);
            $table->string('recurrence_type')->default('one_off'); // explicit_dates templates store no dates
            $table->string('rrule')->nullable();
            $table->unsignedSmallInteger('relative_interval_days')->nullable();
            $table->foreignId('suggested_category_id')->nullable()
                ->constrained('categories')->nullOnDelete();
            $table->string('icon')->nullable();               // optional emoji for a quick visual pick
            $table->unsignedInteger('usage_count')->default(0); // catalogue sorts most-used first
            $table->timestamps();

            $table->index('usage_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
