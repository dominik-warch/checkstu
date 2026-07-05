<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Arr;

class UpdateUserAction
{
    /**
     * @param  array{name: string, username: string, email?: string|null, password?: string|null, role: string}  $data
     */
    public function handle(User $user, array $data): User
    {
        // A blank password means "keep the current one".
        if (blank($data['password'] ?? null)) {
            $data = Arr::except($data, ['password']);
        }

        $user->update($data);

        return $user;
    }
}
