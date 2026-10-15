<?php

namespace Tests\Feature;

use App\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_friend_actions(): void
    {
        $user = User::factory()->create();
        $friendship = Friendship::factory()->create();

        $this->post(route('friends.store', $user))->assertRedirect(route('login'));
        $this->post(route('friendships.accept', $friendship))->assertRedirect(route('login'));
        $this->post(route('friendships.decline', $friendship))->assertRedirect(route('login'));
        $this->delete(route('friendships.destroy', $friendship))->assertRedirect(route('login'));
    }

    public function test_member_can_send_a_friend_request(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($sender)
            ->from(route('members.index'))
            ->post(route('friends.store', $recipient))
            ->assertRedirect(route('members.index'));

        $friendship = Friendship::query()->first();

        $this->assertNotNull($friendship);
        $this->assertSame($sender->id, $friendship->requester_id);
        $this->assertSame($recipient->id, $friendship->addressee_id);
        $this->assertSame(FriendshipStatus::Pending, $friendship->status);
        $this->assertModelExists($friendship);
    }

    public function test_sending_a_request_twice_does_not_create_a_duplicate(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($sender)->post(route('friends.store', $recipient));
        $this->actingAs($sender)->post(route('friends.store', $recipient))->assertRedirect();

        $this->assertSame(1, Friendship::query()->count());
        $this->assertSame(FriendshipStatus::Pending, Friendship::query()->first()->status);
    }

    public function test_member_cannot_send_a_friend_request_to_themselves(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('friends.store', $user))
            ->assertForbidden();

        $this->assertSame(0, Friendship::query()->count());
    }

    public function test_sending_a_request_accepts_a_pending_request_from_that_person(): void
    {
        $sam = User::factory()->create();
        $jordan = User::factory()->create();
        $request = Friendship::factory()->create([
            'requester_id' => $jordan->id,
            'addressee_id' => $sam->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->actingAs($sam)->post(route('friends.store', $jordan))->assertRedirect();

        $this->assertSame(1, Friendship::query()->count());
        $this->assertSame(FriendshipStatus::Accepted, $request->refresh()->status);
        $this->assertTrue($sam->isFriendWith($jordan));
    }

    public function test_addressee_can_accept_or_decline_a_request(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $request = Friendship::factory()->create([
            'requester_id' => $sender->id,
            'addressee_id' => $recipient->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->actingAs($recipient)
            ->from(route('friends.index'))
            ->post(route('friendships.accept', $request))
            ->assertRedirect(route('friends.index'));

        $this->assertSame(FriendshipStatus::Accepted, $request->refresh()->status);
        $this->assertSame(0, $recipient->incomingFriendRequests()->count());

        $second = Friendship::factory()->create([
            'requester_id' => $sender->id,
            'addressee_id' => User::factory()->create()->id,
        ]);
        $otherRecipient = User::query()->find($second->addressee_id);

        $this->actingAs($otherRecipient)
            ->post(route('friendships.decline', $second))
            ->assertRedirect();

        $this->assertSame(FriendshipStatus::Declined, $second->refresh()->status);
        $this->assertModelExists($second);
    }

    public function test_requester_cannot_accept_or_decline_their_own_request(): void
    {
        $sender = User::factory()->create();
        $request = Friendship::factory()->create([
            'requester_id' => $sender->id,
            'addressee_id' => User::factory()->create()->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->actingAs($sender)->post(route('friendships.accept', $request))->assertForbidden();
        $this->actingAs($sender)->post(route('friendships.decline', $request))->assertForbidden();

        $this->assertSame(FriendshipStatus::Pending, $request->refresh()->status);
    }

    public function test_requester_can_cancel_an_outgoing_request(): void
    {
        $sender = User::factory()->create();
        $request = Friendship::factory()->create([
            'requester_id' => $sender->id,
            'addressee_id' => User::factory()->create()->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->actingAs($sender)
            ->from(route('friends.index'))
            ->delete(route('friendships.destroy', $request))
            ->assertRedirect(route('friends.index'));

        $this->assertModelMissing($request);
    }

    public function test_addressee_cannot_cancel_a_request_they_received(): void
    {
        $recipient = User::factory()->create();
        $request = Friendship::factory()->create([
            'requester_id' => User::factory()->create()->id,
            'addressee_id' => $recipient->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->actingAs($recipient)
            ->delete(route('friendships.destroy', $request))
            ->assertForbidden();

        $this->assertModelExists($request);
    }

    public function test_either_friend_can_unfriend(): void
    {
        $sam = User::factory()->create();
        $jordan = User::factory()->create();
        $friendship = Friendship::factory()->accepted()->create([
            'requester_id' => $sam->id,
            'addressee_id' => $jordan->id,
        ]);

        $this->actingAs($jordan)
            ->delete(route('friendships.destroy', $friendship))
            ->assertRedirect();

        $this->assertModelMissing($friendship);
        $this->assertFalse($sam->isFriendWith($jordan));
    }

    public function test_stranger_cannot_end_a_friendship(): void
    {
        $friendship = Friendship::factory()->accepted()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->delete(route('friendships.destroy', $friendship))
            ->assertForbidden();

        $this->assertModelExists($friendship);
    }

    public function test_people_page_shows_each_relationship(): void
    {
        $sam = User::factory()->create(['name' => 'Sam Okonkwo']);
        $friend = User::factory()->create(['name' => 'Jordan Blake', 'title' => 'Engineering Lead', 'department' => 'Engineering']);
        $sent = User::factory()->create(['name' => 'Quinn Morales']);
        $received = User::factory()->create(['name' => 'Casey Patel']);

        Friendship::factory()->accepted()->create([
            'requester_id' => $sam->id,
            'addressee_id' => $friend->id,
        ]);
        Friendship::factory()->create([
            'requester_id' => $sam->id,
            'addressee_id' => $sent->id,
            'status' => FriendshipStatus::Pending,
        ]);
        Friendship::factory()->create([
            'requester_id' => $received->id,
            'addressee_id' => $sam->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->actingAs($sam)
            ->get(route('members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Members/Index')
                ->where('directory', function ($directory) use ($sam, $friend, $sent, $received) {
                    $status = fn (int $id): ?string => $directory->firstWhere('id', $id)['relationship'] ?? null;

                    return $directory->where('id', $sam->id)->isEmpty()
                        && $status($friend->id) === 'friends'
                        && $status($sent->id) === 'request_sent'
                        && $status($received->id) === 'request_received';
                })
                ->where('friendRequestCount', 1)
            );
    }

    public function test_friends_page_lists_requests_and_friends(): void
    {
        $sam = User::factory()->create();
        $friend = User::factory()->create(['name' => 'Jordan Blake']);
        $sent = User::factory()->create(['name' => 'Quinn Morales']);
        $received = User::factory()->create(['name' => 'Casey Patel']);

        Friendship::factory()->accepted()->create([
            'requester_id' => $friend->id,
            'addressee_id' => $sam->id,
        ]);
        Friendship::factory()->create([
            'requester_id' => $sam->id,
            'addressee_id' => $sent->id,
            'status' => FriendshipStatus::Pending,
        ]);
        Friendship::factory()->create([
            'requester_id' => $received->id,
            'addressee_id' => $sam->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->actingAs($sam)
            ->get(route('friends.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Friends/Index')
                ->where('friends.0.name', 'Jordan Blake')
                ->where('friends.0.relationship', 'friends')
                ->where('outgoingRequests.0.name', 'Quinn Morales')
                ->where('outgoingRequests.0.relationship', 'request_sent')
                ->where('incomingRequests.0.name', 'Casey Patel')
                ->where('incomingRequests.0.relationship', 'request_received')
                ->where('friendRequestCount', 1)
            );
    }

    public function test_friend_request_badge_is_shared_on_every_page(): void
    {
        $sam = User::factory()->create();
        Friendship::factory()->create([
            'requester_id' => User::factory()->create()->id,
            'addressee_id' => $sam->id,
            'status' => FriendshipStatus::Pending,
        ]);
        Friendship::factory()->create([
            'requester_id' => User::factory()->create()->id,
            'addressee_id' => $sam->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->actingAs($sam)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('friendRequestCount', 2));

        $this->actingAs($sam)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('friendRequestCount', 2));
    }
}
