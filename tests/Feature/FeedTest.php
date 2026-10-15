<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_feed_actions(): void
    {
        $post = Post::factory()->create();

        $this->post(route('posts.store'), ['body' => 'Hello'])->assertRedirect(route('login'));
        $this->post(route('posts.like', $post))->assertRedirect(route('login'));
        $this->post(route('posts.comments.store', $post), ['body' => 'Hi'])->assertRedirect(route('login'));
        $this->delete(route('posts.destroy', $post))->assertRedirect(route('login'));
    }

    public function test_feed_page_lists_database_posts_newest_first(): void
    {
        $author = User::factory()->create(['name' => 'Sam Okonkwo']);
        $commenter = User::factory()->create(['name' => 'Jordan Blake']);
        $older = Post::factory()->for($author)->create([
            'body' => 'Older note',
            'created_at' => '2026-10-14 12:00:00',
        ]);
        $newer = Post::factory()->for($author)->create([
            'body' => 'Newer note',
            'created_at' => '2026-10-15 12:00:00',
        ]);
        $newer->likers()->attach($author);
        Comment::factory()->for($newer)->for($commenter)->create([
            'body' => 'A comment',
        ]);

        $this->actingAs($author)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Feed/Index')
                ->where('posts.0.text', 'Newer note')
                ->where('posts.0.likesCount', 1)
                ->where('posts.0.liked', true)
                ->where('posts.0.commentsCount', 1)
                ->where('posts.0.comments.0.text', 'A comment')
                ->where('posts.0.comments.0.author.name', 'Jordan Blake')
                ->where('posts.0.author.name', 'Sam Okonkwo')
                ->where('posts.1.text', 'Older note')
                ->where('posts.1.id', $older->id)
                ->has('friendships')
                ->has('messages')
            );
    }

    public function test_member_can_create_a_post(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.store'), ['body' => 'Hello team'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $post = Post::query()->where('body', 'Hello team')->first();

        $this->assertNotNull($post);
        $this->assertSame($user->id, $post->user_id);
        $this->assertModelExists($post);
    }

    public function test_post_body_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.store'), ['body' => ''])
            ->assertSessionHasErrors([
                'body' => 'The body field is required.',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Post::query()->count());
    }

    public function test_post_body_cannot_exceed_1000_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.store'), ['body' => str_repeat('a', 1001)])
            ->assertSessionHasErrors([
                'body' => 'The body field must not be greater than 1000 characters.',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Post::query()->count());
    }

    public function test_liking_a_post_toggles_the_current_members_like(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.like', $post))
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($post->likers()->whereKey($user->id)->exists());

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.like', $post))
            ->assertRedirect(route('dashboard'));

        $this->assertFalse($post->likers()->whereKey($user->id)->exists());
    }

    public function test_member_can_comment_on_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.comments.store', $post), ['body' => 'Nice work'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $comment = $post->comments()->first();

        $this->assertNotNull($comment);
        $this->assertSame('Nice work', $comment->body);
        $this->assertSame($user->id, $comment->user_id);
        $this->assertModelExists($comment);
    }

    public function test_comment_body_is_required(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.comments.store', $post), ['body' => ''])
            ->assertSessionHasErrors([
                'body' => 'The body field is required.',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Comment::query()->count());
    }

    public function test_comment_body_cannot_exceed_500_characters(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.comments.store', $post), ['body' => str_repeat('a', 501)])
            ->assertSessionHasErrors([
                'body' => 'The body field must not be greater than 500 characters.',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Comment::query()->count());
    }

    public function test_author_can_delete_their_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($author)
            ->from(route('dashboard'))
            ->delete(route('posts.destroy', $post))
            ->assertRedirect(route('dashboard'));

        $this->assertModelMissing($post);
    }

    public function test_another_member_cannot_delete_a_post(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($other)
            ->from(route('dashboard'))
            ->delete(route('posts.destroy', $post))
            ->assertForbidden();

        $this->assertModelExists($post);
    }
}
