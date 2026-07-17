<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Tasks\CompleteTaskAction;
use App\Enums\MediaType;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\WatchStatus;
use App\Models\BookEntry;
use App\Models\BookItem;
use App\Models\Category;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Fresh-install guard: this seeder plants a fixed demo family. Without this
        // check, re-running it (e.g. RUN_SEEDER left on, or `db:seed` run by hand)
        // against a real household would silently inject 5 fake users + demo tasks
        // alongside real data — it wouldn't even crash, since demo usernames are
        // unlikely to collide with real ones. Never run this against a live household.
        if (User::query()->exists()) {
            $this->command?->warn('Users already exist — skipping DemoSeeder (fresh-install only).');

            return;
        }

        // --- Family: 2 parents (admin), 2 kids (member) ---
        // Colors are set here (not just left null) so member-tinted UI — task cards,
        // and the media "shared with" dots — has something to show out of the box.
        $dominik = User::firstOrCreate(['username' => 'dominik'], ['name' => 'Dominik', 'email' => 'dominik@home.local', 'password' => 'password', 'role' => Role::Admin, 'color' => '#2563eb']);
        $sara = User::firstOrCreate(['username' => 'sara'], ['name' => 'Sara', 'email' => 'sara@home.local', 'password' => 'password', 'role' => Role::Admin, 'color' => '#db2777']);
        $leo = User::firstOrCreate(['username' => 'leo'], ['name' => 'Leo', 'email' => null, 'password' => 'password', 'role' => Role::Member, 'color' => '#16a34a']);
        $leni = User::firstOrCreate(['username' => 'leni'], ['name' => 'Leni', 'email' => null, 'password' => 'password', 'role' => Role::Member, 'color' => '#f59e0b']);
        // A guest helper (e.g. grandparent) — only sees tasks assigned to them.
        $opa = User::firstOrCreate(['username' => 'opa'], ['name' => 'Opa', 'email' => null, 'password' => 'password', 'role' => Role::Guest]);

        // --- Categories ---
        foreach (['Küche', 'Bad', 'Wohnzimmer', 'Schlafzimmer', 'Außen'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
        $kueche = Category::where('name', 'Küche')->first();
        $wohnzimmer = Category::where('name', 'Wohnzimmer')->first();

        // --- One-off tasks (P1), each with one occurrence ---
        $bulb = $this->task('Glühbirne im Flur wechseln', Priority::High, $dominik, $dominik, today: '+1 day');

        $tidy = $this->task('Wohnzimmer aufräumen', Priority::Normal, $dominik, $leo, today: 'today');
        $tidy->categories()->attach($wohnzimmer);

        $vacuum = $this->task('Staubsaugen', Priority::Normal, $dominik, $leni, today: 'today');
        $vacuum->categories()->attach($wohnzimmer);
        // Staubsaugen is blocked until aufräumen is done.
        $vacuum->dependencies()->attach($tidy);

        $dishes = $this->task('Geschirrspüler ausräumen', Priority::Normal, $sara, $leo, today: 'today');
        $dishes->categories()->attach($kueche);

        $trash = $this->task('Müll rausbringen', Priority::High, $sara, $leni, today: '-1 day'); // overdue

        // A task assigned to the guest — the only thing Opa will see.
        $this->task('Rasen mähen', Priority::Normal, $dominik, $opa, today: 'today');

        // A private task — only Dominik can see it.
        $secret = Task::create([
            'title' => 'Geschenk für Sara besorgen',
            'priority' => Priority::Normal,
            'is_private' => true,
            'default_assignee_id' => $dominik->id,
            'created_by' => $dominik->id,
        ]);
        $secret->occurrences()->create([
            'due_date' => now()->addDays(5)->toDateString(),
            'assignee_id' => $dominik->id,
        ]);

        // --- Demonstrate admin completing a kid's task on their behalf ---
        // Sara (parent) marks Leni's overdue task done, attributed to Leni.
        app(CompleteTaskAction::class)->handle(
            $trash->openOccurrence,
            actor: $sara,
            completedBy: $leni,
        );

        $this->seedMedia($dominik, $sara, $leo, $leni);
    }

    /** Movies/shows/books with entries overlapping across members, so the "shared with" indicator has something to show. */
    private function seedMedia(User $dominik, User $sara, User $leo, User $leni): void
    {
        // poster_path is left null — a real TMDb path would either need a live fetch or
        // risk pointing at a stale/wrong hash; MediaPoster already renders a placeholder
        // box for null, which is preferable to a broken image icon for demo data.
        $incredibles = MediaItem::create([
            'tmdb_id' => 900001,
            'type' => MediaType::Movie,
            'title_de' => 'Die Unglaublichen 2',
            'title_en' => 'Incredibles 2',
            'overview' => 'Die Parr-Familie kehrt zurück, um erneut die Welt zu retten.',
            'release_date' => '2018-06-14',
        ]);

        $dune = MediaItem::create([
            'tmdb_id' => 900002,
            'type' => MediaType::Movie,
            'title_de' => 'Dune',
            'title_en' => 'Dune',
            'overview' => 'Paul Atreides reist auf den gefährlichsten Planeten des Universums.',
            'release_date' => '2021-09-15',
        ]);

        $strangerThings = MediaItem::create([
            'tmdb_id' => 900003,
            'type' => MediaType::Tv,
            'title_de' => 'Stranger Things',
            'title_en' => 'Stranger Things',
            'overview' => 'Übernatürliche Ereignisse erschüttern eine Kleinstadt in den 80ern.',
            'release_date' => '2016-07-15',
            'tv_status' => 'Returning Series',
        ]);

        $kungFuPanda = MediaItem::create([
            'tmdb_id' => 900004,
            'type' => MediaType::Movie,
            'title_de' => 'Kung Fu Panda 4',
            'title_en' => 'Kung Fu Panda 4',
            'overview' => 'Po muss einen Nachfolger als Drachenkrieger ausbilden.',
            'release_date' => '2024-03-08',
        ]);

        $kleinerPrinz = BookItem::create([
            'google_books_id' => 'demo-kleiner-prinz',
            'title' => 'Der kleine Prinz',
            'authors' => 'Antoine de Saint-Exupéry',
            'overview' => 'Ein Pilot begegnet in der Wüste einem kleinen Prinzen von einem fernen Planeten.',
            'published_date' => '1943-04-06',
        ]);

        $sapiens = BookItem::create([
            'google_books_id' => 'demo-sapiens',
            'title' => 'Sapiens: Eine kurze Geschichte der Menschheit',
            'authors' => 'Yuval Noah Harari',
            'overview' => 'Eine Reise durch die Geschichte der Menschheit, von der Steinzeit bis heute.',
            'published_date' => '2011-01-01',
        ]);

        // Shared: Dominik + Sara both have Die Unglaublichen 2 and Der kleine Prinz.
        MediaEntry::create(['user_id' => $dominik->id, 'media_item_id' => $incredibles->id, 'status' => WatchStatus::Completed, 'watched_at' => now()->subDays(10)]);
        MediaEntry::create(['user_id' => $sara->id, 'media_item_id' => $incredibles->id, 'status' => WatchStatus::Watchlist]);
        BookEntry::create(['user_id' => $dominik->id, 'book_item_id' => $kleinerPrinz->id, 'status' => WatchStatus::Completed, 'read_at' => now()->subDays(30)]);
        BookEntry::create(['user_id' => $sara->id, 'book_item_id' => $kleinerPrinz->id, 'status' => WatchStatus::Completed, 'read_at' => now()->subDays(20)]);

        // Shared: Dominik + Leo both have Dune.
        MediaEntry::create(['user_id' => $dominik->id, 'media_item_id' => $dune->id, 'status' => WatchStatus::Watchlist]);
        MediaEntry::create(['user_id' => $leo->id, 'media_item_id' => $dune->id, 'status' => WatchStatus::Completed, 'watched_at' => now()->subDays(3)]);

        // Shared three ways: Dominik, Leo and Leni all have Stranger Things.
        MediaEntry::create(['user_id' => $dominik->id, 'media_item_id' => $strangerThings->id, 'status' => WatchStatus::Watching]);
        MediaEntry::create(['user_id' => $leo->id, 'media_item_id' => $strangerThings->id, 'status' => WatchStatus::Watchlist]);
        MediaEntry::create(['user_id' => $leni->id, 'media_item_id' => $strangerThings->id, 'status' => WatchStatus::Watching]);

        // Shared: Dominik + Leni both have Sapiens.
        BookEntry::create(['user_id' => $dominik->id, 'book_item_id' => $sapiens->id, 'status' => WatchStatus::Watchlist]);
        BookEntry::create(['user_id' => $leni->id, 'book_item_id' => $sapiens->id, 'status' => WatchStatus::Watchlist]);

        // Not shared with anyone — verifies the indicator correctly stays hidden too.
        MediaEntry::create(['user_id' => $leni->id, 'media_item_id' => $kungFuPanda->id, 'status' => WatchStatus::Watchlist]);
    }

    private function task(string $title, Priority $priority, User $creator, ?User $assignee, string $today): Task
    {
        $task = Task::create([
            'title' => $title,
            'priority' => $priority,
            'default_assignee_id' => $assignee?->id,
            'created_by' => $creator->id,
        ]);

        $task->occurrences()->create([
            'due_date' => $today === 'today' ? now()->toDateString() : now()->modify($today)->toDateString(),
            'assignee_id' => $assignee?->id,
        ]);

        return $task;
    }
}
