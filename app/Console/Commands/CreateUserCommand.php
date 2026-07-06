<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateUserCommand extends Command
{
    protected $signature = 'checkstu:create-user';

    protected $description = 'Create a checkstu user account (e.g. the first admin after deployment)';

    public function handle(): int
    {
        $name = text('Name', required: true);
        $username = Str::lower(text('Username (login)', required: true));
        $role = select('Role', [
            Role::Admin->value => 'Elternteil (Admin)',
            Role::Member->value => 'Kind (Mitglied)',
            Role::Guest->value => 'Gast',
        ], default: Role::Admin->value);
        $password = password('Password', required: true);

        $validator = Validator::make(
            ['name' => $name, 'username' => $username, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'username' => $username,
            'email' => null,
            'password' => $password,
            'role' => $role,
        ]);

        $this->info("✅ Created {$role} account '{$username}'.");

        return self::SUCCESS;
    }
}
