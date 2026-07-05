<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Concrete, completable instances of a task.
        Schema::create('task_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->date('due_date')->nullable(); // null = "someday"
            $table->foreignId('assignee_id')->nullable()
                ->constrained('users')->nullOnDelete(); // overrides task default per-instance

            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()
                ->constrained('users')->nullOnDelete(); // attributed completer

            $table->boolean('is_skipped')->default(false);
            $table->timestamps();

            $table->unique(['task_id', 'due_date']); // idempotency for materialization (P2)
            $table->index('due_date');
            $table->index(['assignee_id', 'due_date']);
        });

        // Partial index for the dominant "open items" query.
        DB::statement('CREATE INDEX task_occurrences_open_idx
            ON task_occurrences (due_date)
            WHERE completed_at IS NULL AND is_skipped = 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('task_occurrences');
    }
};
