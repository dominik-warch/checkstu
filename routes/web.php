<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TaskCompletionController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Kept for starter-kit references; the real home is Today.
    Route::redirect('dashboard', '/')->name('dashboard');

    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');

    Route::post('occurrences/{occurrence}/complete', [TaskCompletionController::class, 'store'])
        ->name('occurrences.complete');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
