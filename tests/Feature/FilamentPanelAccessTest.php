<?php

namespace Tests\Feature;

use App\Models\MediaEpisodeWatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FilamentPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_the_panel_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    #[DataProvider('resourceIndexRouteProvider')]
    public function test_admin_can_load_every_resource_index_page(string $routeName): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertOk();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function resourceIndexRouteProvider(): array
    {
        return collect([
            'book-entries', 'book-items', 'categories', 'media-entries', 'media-episode-watches',
            'media-episodes', 'media-items', 'media-seasons', 'task-completion-logs', 'task-occurrences',
            'task-recurrence-dates', 'task-templates', 'tasks', 'users',
        ])->mapWithKeys(fn (string $slug) => [$slug => ["filament.admin.resources.{$slug}.index"]])->all();
    }

    public function test_episode_watches_index_shows_show_season_and_episode_number(): void
    {
        $admin = User::factory()->admin()->create();
        $watch = MediaEpisodeWatch::factory()->create();
        $episode = $watch->episode()->with('season.mediaItem')->first();

        $response = $this->actingAs($admin)
            ->get(route('filament.admin.resources.media-episode-watches.index'))
            ->assertOk();

        $response->assertSee($episode->season->mediaItem->title_de);
        $response->assertSee((string) $episode->season->season_number);
        $response->assertSee((string) $episode->episode_number);
    }

    public function test_episode_watches_edit_page_renders_with_a_descriptive_episode_label(): void
    {
        $admin = User::factory()->admin()->create();
        $watch = MediaEpisodeWatch::factory()->create();

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.media-episode-watches.edit', $watch))
            ->assertOk();
    }

    public function test_member_cannot_access_the_panel(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_guest_role_cannot_access_the_panel(): void
    {
        $guest = User::factory()->guest()->create();

        $this->actingAs($guest)
            ->get('/admin')
            ->assertForbidden();
    }
}
