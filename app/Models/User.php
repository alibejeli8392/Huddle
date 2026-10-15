<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\FriendshipStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'title', 'department', 'bio', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function toAuthorCard(): array
    {
        $avatar = $this->avatar_path;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'department' => $this->department,
            'avatar' => is_string($avatar) && str_starts_with($avatar, 'http') ? $avatar : null,
        ];
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function likedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_likes')->withTimestamps();
    }

    /**
     * Users connected by an accepted friendship, whichever person sent the request.
     *
     * @return Builder<User>
     */
    public function friends(): Builder
    {
        $friendIds = Friendship::query()
            ->where('status', FriendshipStatus::Accepted)
            ->where(function (Builder $query): void {
                $query->where('requester_id', $this->getKey())
                    ->orWhere('addressee_id', $this->getKey());
            })
            ->get(['requester_id', 'addressee_id'])
            ->map(function (Friendship $friendship): int {
                $requesterId = (int) $friendship->requester_id;

                return $requesterId === (int) $this->getKey()
                    ? (int) $friendship->addressee_id
                    : $requesterId;
            });

        return static::query()->whereKey($friendIds);
    }

    /**
     * @return HasMany<Friendship, $this>
     */
    public function incomingFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id')
            ->where('status', FriendshipStatus::Pending);
    }

    /**
     * @return HasMany<Friendship, $this>
     */
    public function outgoingFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id')
            ->where('status', FriendshipStatus::Pending);
    }

    /**
     * @return BelongsToMany<Conversation, $this>
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function isFriendWith(User $user): bool
    {
        if ($this->is($user)) {
            return false;
        }

        return Friendship::query()
            ->where('status', FriendshipStatus::Accepted)
            ->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $pair) use ($user): void {
                    $pair->where('requester_id', $this->getKey())
                        ->where('addressee_id', $user->getKey());
                })->orWhere(function (Builder $pair) use ($user): void {
                    $pair->where('requester_id', $user->getKey())
                        ->where('addressee_id', $this->getKey());
                });
            })
            ->exists();
    }
}
