<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('priority')->default(1); // 0=low 1=normal 2=high 3=urgent
            $table->boolean('is_private')->default(false);       // visible only to created_by

            // Default assignee for spawned occurrences (nullable = unassigned pool).
            $table->foreignId('default_assignee_id')->nullable()
                ->constrained('users')->nullOnDelete();

            // Recurrence config (see plan §4.3). Engine (materialization) lands in P2;
            // in P1 a task is created with a single one-off occurrence.
            $table->string('recurrence_type')->default('one_off'); // one_off|rrule|explicit_dates|relative
            $table->string('rrule')->nullable();                   // RFC5545 for rrule
            $table->date('anchor_date')->nullable();               // DTSTART for rrule / first due date
            $table->unsignedSmallInteger('relative_interval_days')->nullable(); // for 'relative'
            $table->date('recurrence_ends_on')->nullable();        // optional series end

            $table->boolean('is_active')->default(true);           // pause a series without deleting
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();            // task persists if the creator is removed
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('default_assignee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
