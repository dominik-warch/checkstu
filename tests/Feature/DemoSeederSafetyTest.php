<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_refuses_to_run_if_users_already_exist(): void
    {
        // Simulates a real household: one real admin account, no demo data.
        User::factory()->admin()->create(['username' => 'realfamily']);
        $tasksBefore = Task::count();

        (new DemoSeeder)->run();

        // Nothing was injected — no demo users, no demo tasks.
        $this->assertSame(1, User::count());
        $this->assertSame($tasksBefore, Task::count());
        $this->assertNull(User::firstWhere('username', 'dominik'));
    }

    public function test_demo_seeder_populates_a_genuinely_fresh_database(): void
    {
        $this->assertSame(0, User::count());

        (new DemoSeeder)->run();

        $this->assertGreaterThan(0, User::count());
        $this->assertNotNull(User::firstWhere('username', 'dominik'));
    }
}
