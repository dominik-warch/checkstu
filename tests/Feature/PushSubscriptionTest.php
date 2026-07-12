<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\PushSubscription;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_a_push_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('push-subscriptions.store'), [
            'endpoint' => 'https://push.example.test/abc123',
            'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
        ])->assertRedirect();

        $this->assertSame(1, $user->pushSubscriptions()->count());
        $this->assertSame('https://push.example.test/abc123', PushSubscription::first()->endpoint);
    }

    public function test_a_user_can_remove_their_push_subscription(): void
    {
        $user = User::factory()->create();
        $user->updatePushSubscription('https://push.example.test/abc123', 'public-key', 'auth-token');

        $this->actingAs($user)->delete(route('push-subscriptions.destroy'), [
            'endpoint' => 'https://push.example.test/abc123',
        ])->assertRedirect();

        $this->assertSame(0, $user->pushSubscriptions()->count());
    }
}
