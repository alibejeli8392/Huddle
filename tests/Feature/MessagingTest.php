<?php

namespace Tests\Feature;

use App\ConversationType;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_message_actions(): void
    {
        $conversation = Conversation::factory()->create();
        $post = Post::factory()->create();

        $this->post(route('conversations.store'), [
            'type' => 'direct',
            'friend_id' => 1,
        ])->assertRedirect(route('login'));
        $this->post(route('conversations.messages.store', $conversation), [
            'body' => 'Hi',
        ])->assertRedirect(route('login'));
        $this->get(route('messages.show', $conversation))->assertRedirect(route('login'));
        $this->get(route('messages.index', ['c' => $conversation->id]))->assertRedirect(route('login'));
        $this->post(route('posts.share', $post), [
            'targets' => [['type' => 'friend', 'id' => 1]],
        ])->assertRedirect(route('login'));
    }

    public function test_accepted_friends_can_start_a_direct_conversation(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $this->acceptFriendship($user, $friend);

        $this->actingAs($user)
            ->from(route('messages.index'))
            ->post(route('conversations.store'), [
                'type' => 'direct',
                'friend_id' => $friend->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('messages.show', Conversation::query()->first()));

        $conversation = Conversation::query()->first();

        $this->assertSame(ConversationType::Direct, $conversation->type);
        $this->assertSame($user->id, $conversation->created_by);
        $this->assertEqualsCanonicalizing(
            [$user->id, $friend->id],
            $conversation->users()->pluck('users.id')->all(),
        );
    }

    public function test_starting_a_direct_conversation_reuses_the_existing_one(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $this->acceptFriendship($user, $friend);
        $existing = Conversation::factory()->create([
            'type' => ConversationType::Direct,
            'created_by' => $friend->id,
        ]);
        $existing->users()->attach([$user->id, $friend->id]);

        $this->actingAs($user)
            ->post(route('conversations.store'), [
                'type' => 'direct',
                'friend_id' => $friend->id,
            ])
            ->assertRedirect(route('messages.show', $existing));

        $this->assertSame(1, Conversation::query()->count());
    }

    public function test_pending_friend_cannot_start_a_direct_conversation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Friendship::factory()->create([
            'requester_id' => $user->id,
            'addressee_id' => $other->id,
        ]);

        $this->actingAs($user)
            ->from(route('messages.index'))
            ->post(route('conversations.store'), [
                'type' => 'direct',
                'friend_id' => $other->id,
            ])
            ->assertInvalid(['friend_id' => 'You can only message accepted friends.'])
            ->assertRedirect(route('messages.index'));

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_stranger_cannot_start_a_direct_conversation(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($user)
            ->from(route('messages.index'))
            ->post(route('conversations.store'), [
                'type' => 'direct',
                'friend_id' => $stranger->id,
            ])
            ->assertInvalid(['friend_id' => 'You can only message accepted friends.']);

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_member_can_create_a_group_with_friends(): void
    {
        $user = User::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->acceptFriendship($user, $first);
        $this->acceptFriendship($user, $second);

        $this->actingAs($user)
            ->post(route('conversations.store'), [
                'type' => 'group',
                'name' => 'Launch crew',
                'member_ids' => [$first->id, $second->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('messages.show', Conversation::query()->first()));

        $conversation = Conversation::query()->first();

        $this->assertSame(ConversationType::Group, $conversation->type);
        $this->assertSame('Launch crew', $conversation->name);
        $this->assertSame($user->id, $conversation->created_by);
        $this->assertEqualsCanonicalizing(
            [$user->id, $first->id, $second->id],
            $conversation->users()->pluck('users.id')->all(),
        );
    }

    public function test_group_name_is_required_and_limited_to_60_characters(): void
    {
        $user = User::factory()->create();
        [$first, $second] = $this->twoFriendsOf($user);

        $this->actingAs($user)
            ->from(route('messages.index'))
            ->post(route('conversations.store'), [
                'type' => 'group',
                'name' => '   ',
                'member_ids' => [$first->id, $second->id],
            ])
            ->assertInvalid(['name' => 'Give the group a name.']);

        $this->actingAs($user)
            ->from(route('messages.index'))
            ->post(route('conversations.store'), [
                'type' => 'group',
                'name' => str_repeat('a', 61),
                'member_ids' => [$first->id, $second->id],
            ])
            ->assertInvalid(['name' => 'The group name must be 60 characters or fewer.']);

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_group_requires_at_least_two_friends(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $this->acceptFriendship($user, $friend);

        $this->actingAs($user)
            ->from(route('messages.index'))
            ->post(route('conversations.store'), [
                'type' => 'group',
                'name' => 'Launch crew',
                'member_ids' => [$friend->id],
            ])
            ->assertInvalid(['member_ids' => 'Choose at least two friends.']);

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_group_members_must_be_accepted_friends(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();
        $this->acceptFriendship($user, $friend);

        $this->actingAs($user)
            ->from(route('messages.index'))
            ->post(route('conversations.store'), [
                'type' => 'group',
                'name' => 'Launch crew',
                'member_ids' => [$friend->id, $stranger->id],
            ])
            ->assertInvalid(['member_ids' => 'Every member must be an accepted friend.'])
            ->assertRedirect(route('messages.index'));

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_member_can_send_a_message(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $conversation = $this->directConversation($user, $friend);

        $this->actingAs($user)
            ->post(route('conversations.messages.store', $conversation), [
                'body' => 'See you at standup.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('messages.show', $conversation));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => 'See you at standup.',
        ]);
    }

    public function test_message_body_is_required(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $conversation = $this->directConversation($user, $friend);

        $this->actingAs($user)
            ->from(route('messages.show', $conversation))
            ->post(route('conversations.messages.store', $conversation), [
                'body' => '   ',
            ])
            ->assertInvalid(['body' => 'Write a message before sending.'])
            ->assertRedirect(route('messages.show', $conversation));

        $this->assertSame(0, Message::query()->count());
    }

    public function test_message_body_cannot_exceed_1000_characters(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $conversation = $this->directConversation($user, $friend);

        $this->actingAs($user)
            ->from(route('messages.show', $conversation))
            ->post(route('conversations.messages.store', $conversation), [
                'body' => str_repeat('a', 1001),
            ])
            ->assertInvalid(['body' => 'A message can be at most 1000 characters.']);

        $this->assertSame(0, Message::query()->count());
    }

    public function test_only_members_can_send_a_message(): void
    {
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->users()->attach($member->id);

        $this->actingAs($outsider)
            ->post(route('conversations.messages.store', $conversation), [
                'body' => 'Hello',
            ])
            ->assertForbidden();

        $this->assertSame(0, Message::query()->count());
    }

    public function test_only_members_can_view_a_conversation(): void
    {
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->users()->attach($member->id);

        $this->actingAs($outsider)
            ->get(route('messages.show', $conversation))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('messages.index', ['c' => $conversation->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'last_read_at' => null,
        ]);
    }

    public function test_opening_a_conversation_marks_it_read_and_lowers_the_badge(): void
    {
        $this->travelTo('2026-10-16 15:00:00');

        $reader = User::factory()->create();
        $author = User::factory()->create(['name' => 'Jordan Blake']);
        $otherAuthor = User::factory()->create();
        $open = $this->directConversation($reader, $author);
        $stillUnread = $this->directConversation($reader, $otherAuthor);
        $open->users()->updateExistingPivot($author->id, ['last_read_at' => '2026-10-16 09:00:00']);
        Message::factory()->for($open)->for($author, 'sender')->create([
            'body' => 'The build is up.',
            'created_at' => '2026-10-16 14:00:00',
        ]);
        Message::factory()->for($stillUnread)->for($otherAuthor, 'sender')->create([
            'body' => 'Still waiting.',
            'created_at' => '2026-10-16 13:00:00',
        ]);

        $this->actingAs($reader)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessageCount', 2));

        $this->actingAs($reader)
            ->get(route('messages.show', $open))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Messages/Index')
                ->where('unreadMessageCount', 1)
                ->where('activeConversation.id', $open->id)
                ->where('activeConversation.messages.0.body', 'The build is up.')
                ->where('activeConversation.messages.0.sender.name', 'Jordan Blake')
                ->where('conversations', function ($conversations) use ($open, $stillUnread): bool {
                    $rows = collect($conversations)->keyBy('id');

                    return (int) $rows[$open->id]['unreadCount'] === 0
                        && (int) $rows[$stillUnread->id]['unreadCount'] === 1;
                })
            );

        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $open->id,
            'user_id' => $reader->id,
            'last_read_at' => '2026-10-16 15:00:00',
        ]);
        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $open->id,
            'user_id' => $author->id,
            'last_read_at' => '2026-10-16 09:00:00',
        ]);

        $this->actingAs($reader)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('unreadMessageCount', 1));
    }

    public function test_conversation_query_marks_it_read(): void
    {
        $this->travelTo('2026-10-16 15:00:00');

        $reader = User::factory()->create();
        $author = User::factory()->create();
        $conversation = $this->directConversation($reader, $author);
        Message::factory()->for($conversation)->for($author, 'sender')->create([
            'created_at' => '2026-10-16 14:00:00',
        ]);

        $this->actingAs($reader)
            ->get(route('messages.index', ['c' => $conversation->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeConversation.id', $conversation->id)
                ->where('unreadMessageCount', 0)
            );

        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $conversation->id,
            'user_id' => $reader->id,
            'last_read_at' => '2026-10-16 15:00:00',
        ]);
    }

    public function test_messages_page_lists_conversations_by_latest_message(): void
    {
        $reader = User::factory()->create();
        $olderFriend = User::factory()->create(['name' => 'Sam Okonkwo']);
        $newerFriend = User::factory()->create(['name' => 'Morgan Chen']);
        $older = $this->directConversation($reader, $olderFriend);
        $newer = $this->directConversation($reader, $newerFriend);
        Message::factory()->for($older)->for($olderFriend, 'sender')->create([
            'body' => 'Tuesday works.',
            'created_at' => '2026-10-14 12:00:00',
        ]);
        Message::factory()->for($newer)->for($newerFriend, 'sender')->create([
            'body' => 'The draft is ready.',
            'created_at' => '2026-10-16 12:00:00',
        ]);

        $this->actingAs($reader)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeConversation', null)
                ->where('conversations.0.id', $newer->id)
                ->where('conversations.0.name', 'Morgan Chen')
                ->where('conversations.0.preview', 'The draft is ready.')
                ->where('conversations.1.id', $older->id)
                ->where('conversations.1.name', 'Sam Okonkwo')
            );
    }

    public function test_conversation_shows_a_shared_post_card(): void
    {
        $reader = User::factory()->create();
        $author = User::factory()->create(['name' => 'Jordan Blake']);
        $conversation = $this->directConversation($reader, $author);
        $post = Post::factory()->for($author)->create([
            'body' => 'Notebooks are on the desks.',
        ]);
        Message::factory()->for($conversation)->for($author, 'sender')->create([
            'body' => 'Look at this',
            'shared_post_id' => $post->id,
        ]);

        $this->actingAs($reader)
            ->get(route('messages.show', $conversation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeConversation.messages.0.body', 'Look at this')
                ->where('activeConversation.messages.0.sender.name', 'Jordan Blake')
                ->where('activeConversation.messages.0.sharedPost.excerpt', 'Notebooks are on the desks.')
                ->where('activeConversation.messages.0.sharedPost.author.id', $author->id)
            );
    }

    public function test_member_can_share_a_post_with_a_friend_and_a_group(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $groupMate = User::factory()->create();
        $this->acceptFriendship($user, $friend);
        $this->acceptFriendship($user, $groupMate);
        $post = Post::factory()->for($friend)->create([
            'body' => 'Bagels in the kitchen until 11.',
        ]);
        $direct = Conversation::factory()->create([
            'type' => ConversationType::Direct,
            'created_by' => $user->id,
        ]);
        $direct->users()->attach([$user->id, $friend->id]);
        $group = Conversation::factory()->group()->create([
            'name' => 'Launch crew',
            'created_by' => $user->id,
        ]);
        $group->users()->attach([$user->id, $friend->id, $groupMate->id]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.share', $post), [
                'targets' => [
                    ['type' => 'friend', 'id' => $friend->id],
                    ['type' => 'conversation', 'id' => $group->id],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame(1, Conversation::query()->where('type', ConversationType::Direct)->count());
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $direct->id,
            'user_id' => $user->id,
            'body' => '',
            'shared_post_id' => $post->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $group->id,
            'user_id' => $user->id,
            'body' => '',
            'shared_post_id' => $post->id,
        ]);
    }

    public function test_share_rejects_a_friend_who_is_not_accepted(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();
        $this->acceptFriendship($user, $friend);
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.share', $post), [
                'targets' => [
                    ['type' => 'friend', 'id' => $friend->id],
                    ['type' => 'friend', 'id' => $stranger->id],
                ],
            ])
            ->assertInvalid(['targets.1.id' => 'You can only share with accepted friends.'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Message::query()->count());
        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_share_rejects_a_conversation_the_member_is_not_in(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $foreign = Conversation::factory()->group()->create(['name' => 'Other room']);
        $foreign->users()->attach(User::factory()->create()->id);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.share', $post), [
                'targets' => [
                    ['type' => 'conversation', 'id' => $foreign->id],
                ],
            ])
            ->assertInvalid(['targets.0.id' => 'Choose a conversation you belong to.']);

        $this->assertSame(0, Message::query()->count());
    }

    public function test_share_requires_a_target(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.share', $post), [])
            ->assertInvalid(['targets' => 'Choose someone to share with.']);

        $this->assertSame(0, Message::query()->count());
    }

    public function test_feed_and_member_profile_include_share_targets(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create(['name' => 'Jordan Blake']);
        $groupMate = User::factory()->create();
        $profile = User::factory()->create();
        $this->acceptFriendship($user, $friend);
        $this->acceptFriendship($user, $groupMate);
        $direct = $this->directConversation($user, $friend);
        $group = Conversation::factory()->group()->create([
            'name' => 'Friday lunch',
            'created_by' => $user->id,
        ]);
        $group->users()->attach([$user->id, $friend->id, $groupMate->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('shareTargets.friends', fn ($friends): bool => collect($friends)->pluck('name')->contains('Jordan Blake'))
                ->where('shareTargets.groups', function ($groups) use ($group, $direct): bool {
                    $ids = collect($groups)->pluck('id');

                    return $ids->contains($group->id) && $ids->doesntContain($direct->id);
                })
            );

        $this->actingAs($user)
            ->get(route('members.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Members/Show')
                ->where('shareTargets.friends', fn ($friends): bool => collect($friends)->pluck('name')->contains('Jordan Blake'))
                ->where('shareTargets.groups', fn ($groups): bool => collect($groups)->pluck('name')->contains('Friday lunch'))
            );
    }

    private function acceptFriendship(User $a, User $b): void
    {
        Friendship::factory()->accepted()->create([
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
        ]);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function twoFriendsOf(User $user): array
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->acceptFriendship($user, $first);
        $this->acceptFriendship($user, $second);

        return [$first, $second];
    }

    private function directConversation(User $a, User $b): Conversation
    {
        $conversation = Conversation::factory()->create([
            'type' => ConversationType::Direct,
            'created_by' => $a->id,
        ]);
        $conversation->users()->attach([$a->id, $b->id]);

        return $conversation;
    }
}
