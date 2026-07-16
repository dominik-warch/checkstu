<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\BookEntry;
use App\Models\BookItem;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MediaLibraryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_all_entries_by_default(): void
    {
        $user = User::factory()->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create(['status' => WatchStatus::Watchlist]);
        MediaEntry::factory()->for($user)->for(MediaItem::factory()->tv(), 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)->get(route('media.library'))->assertInertia(
            fn (Assert $page) => $page->has('entries', 2)->where('filters.status', 'all')->where('filters.type', 'all'),
        );
    }

    public function test_it_filters_by_status(): void
    {
        $user = User::factory()->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create(['status' => WatchStatus::Watchlist]);
        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create(['status' => WatchStatus::Completed]);

        $this->actingAs($user)->get(route('media.library', ['status' => 'completed']))->assertInertia(
            fn (Assert $page) => $page->has('entries', 1)->where('entries.0.status', 'completed'),
        );
    }

    public function test_it_filters_by_type(): void
    {
        $user = User::factory()->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory()->tv(), 'mediaItem')->create();

        $this->actingAs($user)->get(route('media.library', ['type' => 'tv']))->assertInertia(
            fn (Assert $page) => $page->has('entries', 1)->where('entries.0.media_item.type', 'tv'),
        );
    }

    public function test_it_combines_status_and_type_filters(): void
    {
        $user = User::factory()->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory()->tv(), 'mediaItem')->create(['status' => WatchStatus::Watching]);
        MediaEntry::factory()->for($user)->for(MediaItem::factory()->tv(), 'mediaItem')->create(['status' => WatchStatus::Completed]);
        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)->get(route('media.library', ['status' => 'watching', 'type' => 'tv']))->assertInertia(
            fn (Assert $page) => $page->has('entries', 1)->where('entries.0.media_item.type', 'tv')->where('entries.0.status', 'watching'),
        );
    }

    public function test_an_invalid_filter_value_is_ignored_rather_than_erroring(): void
    {
        $user = User::factory()->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create();

        $this->actingAs($user)->get(route('media.library', ['status' => 'not-a-status']))->assertInertia(
            fn (Assert $page) => $page->has('entries', 1)->where('filters.status', 'all'),
        );
    }

    public function test_books_appear_in_the_default_unified_listing(): void
    {
        $user = User::factory()->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create();
        BookEntry::factory()->for($user)->for(BookItem::factory(), 'bookItem')->create();

        $this->actingAs($user)->get(route('media.library'))->assertInertia(fn (Assert $page) => $page->has('entries', 2));
    }

    public function test_filtering_by_book_returns_only_books(): void
    {
        $user = User::factory()->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create();
        BookEntry::factory()->for($user)->for(BookItem::factory(), 'bookItem')->create();

        $this->actingAs($user)->get(route('media.library', ['type' => 'book']))->assertInertia(
            fn (Assert $page) => $page->has('entries', 1)->where('entries.0.kind', 'book'),
        );
    }

    public function test_filtering_by_movie_excludes_books(): void
    {
        $user = User::factory()->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create();
        BookEntry::factory()->for($user)->for(BookItem::factory(), 'bookItem')->create();

        $this->actingAs($user)->get(route('media.library', ['type' => 'movie']))->assertInertia(
            fn (Assert $page) => $page->has('entries', 1)->where('entries.0.kind', 'media'),
        );
    }

    public function test_status_filter_applies_to_books_too(): void
    {
        $user = User::factory()->create();
        BookEntry::factory()->for($user)->for(BookItem::factory(), 'bookItem')->create(['status' => WatchStatus::Watchlist]);
        BookEntry::factory()->for($user)->for(BookItem::factory(), 'bookItem')->create(['status' => WatchStatus::Completed]);

        $this->actingAs($user)->get(route('media.library', ['status' => 'completed']))->assertInertia(
            fn (Assert $page) => $page->has('entries', 1)->where('entries.0.status', 'completed'),
        );
    }

    public function test_media_and_book_entries_are_interleaved_by_recency(): void
    {
        $user = User::factory()->create();
        $baseline = now();

        Carbon::setTestNow($baseline->copy()->subHours(2));
        MediaEntry::factory()->for($user)->for(MediaItem::factory()->state(['title_de' => 'Älterer Film']), 'mediaItem')->create();

        Carbon::setTestNow($baseline->copy()->subHour());
        BookEntry::factory()->for($user)->for(BookItem::factory()->state(['title' => 'Neueres Buch']), 'bookItem')->create();

        Carbon::setTestNow($baseline);
        MediaEntry::factory()->for($user)->for(MediaItem::factory()->state(['title_de' => 'Neuester Film']), 'mediaItem')->create();

        Carbon::setTestNow();

        $this->actingAs($user)->get(route('media.library'))->assertInertia(
            fn (Assert $page) => $page
                ->has('entries', 3)
                ->where('entries.0.media_item.title_de', 'Neuester Film')
                ->where('entries.1.book_item.title', 'Neueres Buch')
                ->where('entries.2.media_item.title_de', 'Älterer Film'),
        );
    }
}
