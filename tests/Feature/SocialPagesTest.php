<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SocialPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('members.index'))->assertRedirect(route('login'));
        $this->get(route('friends.index'))->assertRedirect(route('login'));
        $this->get(route('messages.index'))->assertRedirect(route('login'));
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_authenticated_member_can_open_each_social_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));

        $pages = [
            'dashboard' => 'Feed/Index',
            'members.index' => 'Members/Index',
            'friends.index' => 'Friends/Index',
            'messages.index' => 'Messages/Index',
            'profile.show' => 'Profile/Show',
        ];

        foreach ($pages as $name => $component) {
            $this->actingAs($user)
                ->get(route($name))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->where('viewer.email', $user->email)
                    ->where('viewer.name', $user->name)
                    ->has('members')
                    ->has('posts')
                    ->has('friendships')
                    ->has('messages')
                );
        }
    }
}
