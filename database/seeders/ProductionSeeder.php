<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Tasks\CompleteTaskAction;
use App\Enums\Priority;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->exists()) {
            $this->command?->warn('Users already exist — skipping ProductionSeeder (fresh-install only).');

            return;
        }

        // --- Family: 2 parents (admin), 2 kids (member) ---
        $dominik = User::firstOrCreate(['username' => 'dominik'], ['name' => 'Dominik', 'email' => 'dominik@home.local', 'password' => 'password', 'role' => Role::Admin]);
        $sara = User::firstOrCreate(['username' => 'sara'], ['name' => 'Sara', 'email' => 'sara@home.local', 'password' => 'password', 'role' => Role::Admin]);
        $leo = User::firstOrCreate(['username' => 'leo'], ['name' => 'Leo', 'email' => null, 'password' => 'password', 'role' => Role::Member]);
        $leni = User::firstOrCreate(['username' => 'leni'], ['name' => 'Leni', 'email' => null, 'password' => 'password', 'role' => Role::Member]);

    }
}
