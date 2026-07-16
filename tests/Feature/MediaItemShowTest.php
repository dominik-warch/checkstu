<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MediaItemShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_shows_detail_page_renders_from_cache_without_calling_tmdb(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['tv_status' => 'Returning Series']);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        Http::fake();

        $this->actingAs($user)->get(route('media.items.show', $item))->assertInertia(
            fn (Assert $page) => $page->component('media/show')->where('item.tmdb_id', $item->tmdb_id),
        );
        Http::assertNothingSent();
    }

    public function test_a_movies_detail_page_redirects_to_the_library(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create();

        $this->actingAs($user)->get(route('media.items.show', $item))->assertRedirect(route('media.library'));
    }
}
