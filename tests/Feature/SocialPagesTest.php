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
        $this->get(route('tasks.index'))->assertRedirect(route('login'));
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_authenticated_member_can_open_each_social_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Feed/Index')
                ->has('posts')
                ->has('shareTargets.friends')
                ->has('shareTargets.groups')
                ->where('unreadMessageCount', 0)
            );

        $this->actingAs($user)
            ->get(route('members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Members/Index')
                ->has('directory')
                ->missing('messages')
            );

        $this->actingAs($user)
            ->get(route('friends.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Friends/Index')
                ->has('incomingRequests')
                ->has('outgoingRequests')
                ->has('friends')
                ->missing('messages')
            );

        $this->actingAs($user)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Messages/Index')
                ->has('conversations')
                ->has('friends')
                ->where('activeConversation', null)
                ->where('unreadMessageCount', 0)
                ->missing('posts')
            );

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks/Index')
                ->has('todayTasks')
                ->where('incompleteTaskCount', 0)
            );

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('profile.email', $user->email)
                ->where('profile.name', $user->name)
                ->where('profile.postCount', 0)
                ->where('profile.friendCount', 0)
                ->missing('messages')
                ->missing('viewer')
            );
    }
}
