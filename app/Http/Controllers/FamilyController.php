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

        $members = User::query()
            ->withCount(['assignedOccurrences as open_count' => fn ($q) => $q
                ->whereNull('completed_at')
                ->where('is_skipped', false)])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'role' => $u->role->value,
                'color' => $u->color,
                'open_count' => $u->open_count,
            ]);

        return Inertia::render('family/index', [
            'members' => $members,
            'can' => [
                'manageUsers' => $request->user()->isAdmin(),
            ],
        ]);
    }
}
