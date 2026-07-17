<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MediaTogetherTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_a_selected_member_no_items_are_returned(): void
    {
        $user = User::factory()->create();
        User::factory()->create();

        $this->actingAs($user)->get(route('media.together'))->assertInertia(
            fn (Assert $page) => $page->where('selectedMemberId', null)->has('items', 0)->has('members', 1),
        );
    }

    public function test_it_returns_items_both_users_have_on_their_watchlist(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $shared = MediaItem::factory()->create();
        MediaEntry::factory()->for($user)->for($shared, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);
        MediaEntry::factory()->for($other)->for($shared, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.together', ['member' => $other->id]))->assertInertia(
            fn (Assert $page) => $page->where('selectedMemberId', $other->id)->has('items', 1)->where('items.0.id', $shared->id),
        );
    }

    public function test_it_excludes_items_only_one_of_them_has(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create(['status' => WatchStatus::Watchlist]);
        MediaEntry::factory()->for($other)->for(MediaItem::factory(), 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.together', ['member' => $other->id]))->assertInertia(
            fn (Assert $page) => $page->has('items', 0),
        );
    }

    public function test_it_excludes_items_that_are_not_on_both_watchlists(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $shared = MediaItem::factory()->create();
        MediaEntry::factory()->for($user)->for($shared, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);
        MediaEntry::factory()->for($other)->for($shared, 'mediaItem')->create(['status' => WatchStatus::Completed]);

        $this->actingAs($user)->get(route('media.together', ['member' => $other->id]))->assertInertia(
            fn (Assert $page) => $page->has('items', 0),
        );
    }

    public function test_the_current_user_is_never_listed_as_a_selectable_member(): void
    {
        $user = User::factory()->create();
        User::factory()->create();
        User::factory()->create();

        $this->actingAs($user)->get(route('media.together'))->assertInertia(
            fn (Assert $page) => $page->has('members', 2),
        );
    }
}
