<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'body'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_likes')->withTimestamps();
    }

    /**
     * @return array{
     *     id: int,
     *     text: string,
     *     createdAt: int,
     *     likesCount: int,
     *     liked: bool,
     *     commentsCount: int,
     *     canDelete: bool,
     *     author: array<string, mixed>|null,
     *     comments: array<int, array<string, mixed>>
     * }
     */
    public function toFeedItem(User $viewer): array
    {
        return [
            'id' => $this->id,
            'text' => $this->body,
            'createdAt' => ($this->created_at?->getTimestamp() ?? 0) * 1000,
            'likesCount' => (int) $this->likers_count,
            'liked' => (bool) $this->liked,
            'commentsCount' => (int) $this->comments_count,
            'canDelete' => $this->user_id === $viewer->id,
            'author' => $this->user?->toAuthorCard(),
            'comments' => $this->comments->map(fn (Comment $comment): array => [
                'id' => $comment->id,
                'text' => $comment->body,
                'createdAt' => ($comment->created_at?->getTimestamp() ?? 0) * 1000,
                'author' => $comment->user?->toAuthorCard(),
            ])->all(),
        ];
    }
}
