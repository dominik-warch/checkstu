<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Explicit irregular dates — the garbage-collection case (recurrence_type = explicit_dates).
        Schema::create('task_recurrence_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->date('due_on');
            $table->boolean('is_consumed')->default(false); // occurrence already spawned
            $table->timestamps();

            $table->unique(['task_id', 'due_on']);
            $table->index(['task_id', 'is_consumed', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_recurrence_dates');
    }
};
