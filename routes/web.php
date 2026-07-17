<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\BookEntryController;
use App\Http\Controllers\BookItemController;
use App\Http\Controllers\BookSearchController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaComingUpController;
use App\Http\Controllers\MediaEntryController;
use App\Http\Controllers\MediaEpisodeWatchController;
use App\Http\Controllers\MediaHomeController;
use App\Http\Controllers\MediaItemController;
use App\Http\Controllers\MediaLibraryController;
use App\Http\Controllers\MediaSearchController;
use App\Http\Controllers\MediaSeasonController;
use App\Http\Controllers\MediaTogetherController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\TaskCompletionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UpcomingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Kept for starter-kit references; the real home is Today.
    Route::redirect('dashboard', '/')->name('dashboard');

    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::get('upcoming', [UpcomingController::class, 'index'])->name('upcoming');

    Route::post('occurrences/{occurrence}/complete', [TaskCompletionController::class, 'store'])
        ->name('occurrences.complete');
    Route::delete('occurrences/{occurrence}/complete', [TaskCompletionController::class, 'destroy'])
        ->name('occurrences.restore');

    Route::get('archive', [ArchiveController::class, 'index'])->name('archive');

    Route::get('family', [FamilyController::class, 'index'])->name('family');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

    // Media tracking — a separate, personal-per-user app context (see IMPLEMENTATION_PLAN.md §Media).
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaHomeController::class, 'index'])->name('home');
        Route::get('coming-up', [MediaComingUpController::class, 'index'])->name('comingUp');
        Route::get('library', [MediaLibraryController::class, 'index'])->name('library');
        Route::get('together', [MediaTogetherController::class, 'index'])->name('together');
        Route::get('search', [MediaSearchController::class, 'index'])->name('search');
        Route::post('entries', [MediaEntryController::class, 'store'])->name('entries.store');
        Route::patch('entries/{entry}', [MediaEntryController::class, 'update'])->name('entries.update');
        Route::delete('entries/{entry}', [MediaEntryController::class, 'destroy'])->name('entries.destroy');
        Route::get('items/{mediaItem}', [MediaItemController::class, 'show'])->name('items.show');
        Route::post('items/{mediaItem}/watch-all', [MediaItemController::class, 'markAllWatched'])->name('items.watchAll');
        Route::get('seasons/{season}/episodes', [MediaSeasonController::class, 'episodes'])->name('seasons.episodes');
        Route::post('seasons/{season}/watch-all', [MediaSeasonController::class, 'markWatched'])->name('seasons.watchAll');
        Route::post('episodes/{episode}/watch', [MediaEpisodeWatchController::class, 'store'])->name('episodes.watch.store');
        Route::delete('episodes/{episode}/watch', [MediaEpisodeWatchController::class, 'destroy'])->name('episodes.watch.destroy');
    });

    // Book tracking — folded into the same Media library/coming-up pages as movies/TV,
    // just backed by Google Books instead of TMDb (see IMPLEMENTATION_PLAN.md §Media).
    Route::prefix('books')->name('books.')->group(function () {
        Route::get('search', [BookSearchController::class, 'index'])->name('search');
        Route::post('entries', [BookEntryController::class, 'store'])->name('entries.store');
        Route::patch('entries/{entry}', [BookEntryController::class, 'update'])->name('entries.update');
        Route::delete('entries/{entry}', [BookEntryController::class, 'destroy'])->name('entries.destroy');
        Route::get('items/{bookItem}', [BookItemController::class, 'show'])->name('items.show');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
