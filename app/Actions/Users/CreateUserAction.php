<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;

class CreateUserAction
{
    /**
     * @param  array{name: string, username: string, email?: string|null, password: string, role: string, color?: string|null}  $data
     */
    public function handle(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'], // hashed via cast
            'role' => $data['role'],
            'color' => $data['color'] ?? null,
        ]);
    }
}
