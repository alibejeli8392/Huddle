<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_creates_the_demo_user_and_opens_the_feed(): void
    {
        $response = $this->post(route('guest.login'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'guest@huddle.app',
            'name' => 'Guest',
        ]);
    }

    public function test_guest_login_reuses_the_existing_demo_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Guest',
            'email' => 'guest@huddle.app',
        ]);

        $this->post(route('guest.login'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'guest@huddle.app')->count());
    }
}
