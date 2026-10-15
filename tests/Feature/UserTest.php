<?php

namespace Tests\Feature;

use App\FriendshipStatus;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_friends_include_both_the_requester_and_the_addressee(): void
    {
        $sam = User::factory()->create();
        $jordan = User::factory()->create();
        $alex = User::factory()->create();
        $stranger = User::factory()->create();

        Friendship::factory()->accepted()->create([
            'requester_id' => $sam->id,
            'addressee_id' => $jordan->id,
        ]);
        Friendship::factory()->accepted()->create([
            'requester_id' => $alex->id,
            'addressee_id' => $sam->id,
        ]);
        Friendship::factory()->create([
            'requester_id' => $sam->id,
            'addressee_id' => $stranger->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $friendIds = $sam->friends()->pluck('id')->sort()->values()->all();
        $expected = collect([$jordan->id, $alex->id])->sort()->values()->all();

        $this->assertSame($expected, $friendIds);
        $this->assertTrue($sam->isFriendWith($jordan));
        $this->assertTrue($jordan->isFriendWith($sam));
        $this->assertTrue($sam->isFriendWith($alex));
        $this->assertTrue($alex->isFriendWith($sam));
        $this->assertFalse($sam->isFriendWith($stranger));
        $this->assertFalse($sam->isFriendWith($sam));
    }

    public function test_pending_requests_are_split_into_incoming_and_outgoing(): void
    {
        $sam = User::factory()->create();
        $jordan = User::factory()->create();

        $request = Friendship::factory()->create([
            'requester_id' => $sam->id,
            'addressee_id' => $jordan->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->assertTrue($sam->outgoingFriendRequests()->whereKey($request->id)->exists());
        $this->assertFalse($sam->incomingFriendRequests()->whereKey($request->id)->exists());
        $this->assertTrue($jordan->incomingFriendRequests()->whereKey($request->id)->exists());
        $this->assertFalse($jordan->outgoingFriendRequests()->whereKey($request->id)->exists());
    }

    public function test_deleting_a_user_removes_their_posts_comments_likes_friendships_and_messages(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->for($member)->create();
        $otherPost = Post::factory()->for($other)->create();
        $comment = Comment::factory()->for($otherPost)->for($member)->create();
        $commentOnMembersPost = Comment::factory()->for($post)->for($other)->create();
        $member->likedPosts()->attach($otherPost);
        $friendship = Friendship::factory()->accepted()->create([
            'requester_id' => $member->id,
            'addressee_id' => $other->id,
        ]);
        $conversation = Conversation::factory()->create([
            'created_by' => $member->id,
        ]);
        $conversation->users()->attach([$member->id, $other->id]);
        $message = Message::factory()->for($conversation)->for($member, 'sender')->create();

        $member->delete();

        $this->assertModelMissing($member);
        $this->assertModelMissing($post);
        $this->assertModelMissing($comment);
        $this->assertModelMissing($commentOnMembersPost);
        $this->assertModelMissing($friendship);
        $this->assertModelMissing($message);
        $this->assertDatabaseMissing('post_likes', [
            'user_id' => $member->id,
            'post_id' => $otherPost->id,
        ]);
        $this->assertDatabaseMissing('conversation_user', [
            'user_id' => $member->id,
            'conversation_id' => $conversation->id,
        ]);
        $this->assertModelExists($other);
        $this->assertModelExists($otherPost);
        $this->assertModelExists($conversation);
        $this->assertNull($conversation->fresh()->created_by);
    }
}
