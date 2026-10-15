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

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('profile.email', $user->email)
                ->where('profile.name', $user->name)
                ->where('profile.postCount', 0)
                ->where('profile.friendCount', 0)
                ->has('messages')
            );
    }

    public function test_preview_includes_pending_requests_and_unread_messages(): void
    {
        $user = User::factory()->create();
        $viewerId = (string) $user->id;

        $this->actingAs($user)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('friendships', fn ($friendships) => $friendships
                    ->where('toId', $viewerId)
                    ->where('status', 'pending')
                    ->count() === 4)
                ->where('messages', fn ($messages) => $messages
                    ->where('unread', true)
                    ->where('toId', $viewerId)
                    ->count() === 3)
                ->where('groupMessages', fn ($messages) => $messages
                    ->where('unread', true)
                    ->where('fromId', '!=', $viewerId)
                    ->count() === 2)
            );
    }

    public function test_preview_chat_messages_are_separate_from_feed_posts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $page->component('Messages/Index');

                $props = $page->toArray()['props'];
                $postTexts = collect($props['posts'])->pluck('text');
                $chatTexts = collect($props['messages'])->pluck('text')
                    ->merge(collect($props['groupMessages'])->pluck('text'))
                    ->filter();

                $this->assertNotEmpty($postTexts->all());
                $this->assertNotEmpty($chatTexts->all());
                $this->assertSame([], $postTexts->intersect($chatTexts)->values()->all());
            });
    }
}
