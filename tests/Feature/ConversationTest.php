<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_count_includes_other_members_messages_after_last_read(): void
    {
        $reader = User::factory()->create();
        $author = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'created_by' => $author->id,
        ]);
        $conversation->users()->attach($reader->id, ['last_read_at' => '2026-10-15 12:30:00']);
        $conversation->users()->attach($author->id, ['last_read_at' => '2026-10-15 12:30:00']);
        Message::factory()->for($conversation)->for($author, 'sender')->create([
            'created_at' => '2026-10-15 12:00:00',
        ]);
        Message::factory()->for($conversation)->for($author, 'sender')->create([
            'created_at' => '2026-10-15 13:00:00',
        ]);
        Message::factory()->for($conversation)->for($reader, 'sender')->create([
            'created_at' => '2026-10-15 14:00:00',
        ]);

        $this->assertSame(1, $conversation->unreadCountFor($reader));
    }

    public function test_unread_count_is_zero_when_the_member_has_read_every_message(): void
    {
        $reader = User::factory()->create();
        $author = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'created_by' => $author->id,
        ]);
        $conversation->users()->attach($reader->id, ['last_read_at' => '2026-10-15 15:00:00']);
        $conversation->users()->attach($author->id);
        Message::factory()->for($conversation)->for($author, 'sender')->create([
            'created_at' => '2026-10-15 13:00:00',
        ]);

        $this->assertSame(0, $conversation->unreadCountFor($reader));
    }

    public function test_unread_count_is_zero_for_someone_outside_the_conversation(): void
    {
        $outsider = User::factory()->create();
        $author = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'created_by' => $author->id,
        ]);
        $conversation->users()->attach($author->id);
        Message::factory()->for($conversation)->for($author, 'sender')->create();

        $this->assertSame(0, $conversation->unreadCountFor($outsider));
    }
}
