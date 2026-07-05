<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_family_member(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Leo',
                'username' => 'leo',
                'email' => null,
                'password' => 'password123',
                'role' => 'member',
            ])
            ->assertRedirect();

        $leo = User::firstWhere('username', 'leo');
        $this->assertNotNull($leo);
        $this->assertSame(Role::Member, $leo->role);
        $this->assertNull($leo->email);
    }

    public function test_member_cannot_create_users(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->post(route('users.store'), [
                'name' => 'X',
                'username' => 'x',
                'password' => 'password123',
                'role' => 'member',
            ])
            ->assertForbidden();

        $this->assertNull(User::firstWhere('username', 'x'));
    }

    public function test_admin_can_update_a_user_without_changing_the_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Old']);
        $originalHash = $user->password;

        $this->actingAs($admin)
            ->patch(route('users.update', $user), [
                'name' => 'New',
                'username' => $user->username,
                'email' => $user->email,
                'password' => '', // keep current
                'role' => 'member',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('New', $user->name);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $other))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_username_is_normalised_to_lowercase(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mixed',
            'username' => 'MixedCase',
            'password' => 'password123',
            'role' => 'member',
        ]);

        $this->assertNotNull(User::firstWhere('username', 'mixedcase'));
    }

    public function test_family_page_is_visible_to_members(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('family'))
            ->assertOk();
    }
}
