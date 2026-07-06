<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable history for "who did what" + stats.
        Schema::create('task_completion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_occurrence_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignId('task_id')->constrained();
            $table->foreignId('user_id')->nullable()
                ->constrained()->nullOnDelete();                 // attributed completer (whose it counts as)
            $table->foreignId('acted_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();          // admin who did it on their behalf; null = self
            $table->string('action');                            // completed|skipped|reopened
            $table->date('due_date')->nullable();                // snapshot
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_completion_logs');
    }
};
