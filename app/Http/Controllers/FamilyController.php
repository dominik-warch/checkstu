<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    public function index(Request $request): Response
    {
        abort_if($request->user()->isGuest(), 403);

        // No per-member counts here: side by side they'd compare siblings, and they'd reveal private tasks.
        $members = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'role' => $u->role->value,
                'color' => $u->color,
            ]);

        return Inertia::render('family/index', [
            'members' => $members,
            'can' => [
                'manageUsers' => $request->user()->isAdmin(),
            ],
        ]);
    }
}
