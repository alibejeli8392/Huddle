<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ManagerHomeSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_stays_authenticated_when_visiting_home_after_form_login(): void
    {
        $manager = User::factory()->manager()->create([
            'email' => 'leslie.chen@example.com',
            'password' => 'password',
            'department' => 'Design',
        ]);

        $this->post('/login', [
            'email' => $manager->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($manager);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Feed/Index')
                ->where('auth.user.id', $manager->id)
                ->where('auth.user.email', $manager->email)
            );

        $this->get('/')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($manager);
        $this->assertSame($manager->email, auth()->user()->email);
    }
}
