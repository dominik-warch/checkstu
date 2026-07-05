<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Self-referencing "blocked_by": task_id is blocked by depends_on_task_id.
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'depends_on_task_id']);
            $table->index('depends_on_task_id');
            // App-level guard: task_id != depends_on_task_id AND no cycles (§5).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
