<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['conversation_id', 'user_id', 'body', 'shared_post_id'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function sharedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'shared_post_id');
    }

    /**
     * Messages from other people that this member has not opened yet.
     */
    #[Scope]
    protected function unreadFor(Builder $query, User $user): Builder
    {
        return $query
            ->where('messages.user_id', '!=', $user->id)
            ->whereRaw(
                'exists (
                    select 1 from conversation_user as read_state
                    where read_state.conversation_id = messages.conversation_id
                      and read_state.user_id = ?
                      and (
                            read_state.last_read_at is null
                            or messages.created_at > read_state.last_read_at
                      )
                )',
                [$user->id],
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toChatItem(): array
    {
        $post = $this->sharedPost;

        return [
            'id' => $this->id,
            'body' => $this->body,
            'createdAt' => ($this->created_at?->getTimestamp() ?? 0) * 1000,
            'sender' => $this->sender?->toAuthorCard(),
            'sharedPost' => $post === null ? null : [
                'id' => $post->id,
                'excerpt' => Str::limit($post->body, 140),
                'author' => $post->user?->toAuthorCard(),
            ],
        ];
    }
}
