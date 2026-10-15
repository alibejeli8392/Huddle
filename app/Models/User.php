<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\FriendshipStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Photo address for the screen: a remote URL, or a file on the public disk.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $path = $this->avatar_path;

            if (! is_string($path) || $path === '') {
                return null;
            }

            if (str_starts_with($path, 'http')) {
                return $path;
            }

            return Storage::disk('public')->url($path);
        });
    }

    public function toAuthorCard(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'department' => $this->department,
            'avatar' => $this->avatar_url,
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

    public function friendshipWith(User $other): ?Friendship
    {
        return Friendship::query()
            ->where(function (Builder $query) use ($other): void {
                $query->where('requester_id', $this->getKey())
                    ->where('addressee_id', $other->getKey());
            })
            ->orWhere(function (Builder $query) use ($other): void {
                $query->where('requester_id', $other->getKey())
                    ->where('addressee_id', $this->getKey());
            })
            ->first();
    }

    /**
     * Send a request, or accept one that is already waiting from this person.
     */
    public function requestFriendshipWith(User $other): Friendship
    {
        $existing = $this->friendshipWith($other);

        if ($existing === null) {
            return Friendship::query()->create([
                'requester_id' => $this->getKey(),
                'addressee_id' => $other->getKey(),
                'status' => FriendshipStatus::Pending,
            ]);
        }

        if ($existing->status === FriendshipStatus::Pending && (int) $existing->requester_id === (int) $other->getKey()) {
            $existing->update(['status' => FriendshipStatus::Accepted]);

            return $existing;
        }

        if ($existing->status === FriendshipStatus::Declined) {
            $existing->update([
                'requester_id' => $this->getKey(),
                'addressee_id' => $other->getKey(),
                'status' => FriendshipStatus::Pending,
            ]);

            return $existing->refresh();
        }

        return $existing;
    }

    /**
     * Everyone except this member, with how they are connected.
     *
     * @return array<int, array<string, mixed>>
     */
    public function directoryCards(): array
    {
        $friendships = $this->friendshipsByOtherUser();

        return static::query()
            ->whereKeyNot($this->getKey())
            ->orderBy('name')
            ->get()
            ->map(function (User $member) use ($friendships): array {
                $friendship = $friendships->get((int) $member->getKey());

                return $this->memberCard($member, $friendship);
            })
            ->all();
    }

    /**
     * @return array{
     *     incomingRequests: array<int, array<string, mixed>>,
     *     outgoingRequests: array<int, array<string, mixed>>,
     *     friends: array<int, array<string, mixed>>
     * }
     */
    public function friendLists(): array
    {
        $incoming = $this->incomingFriendRequests()->with('requester')->oldest()->get();
        $outgoing = $this->outgoingFriendRequests()->with('addressee')->oldest()->get();
        $accepted = Friendship::query()
            ->with(['requester', 'addressee'])
            ->where('status', FriendshipStatus::Accepted)
            ->where(function (Builder $query): void {
                $query->where('requester_id', $this->getKey())
                    ->orWhere('addressee_id', $this->getKey());
            })
            ->oldest()
            ->get();

        return [
            'incomingRequests' => $incoming->map(fn (Friendship $friendship): array => $this->memberCard($friendship->requester, $friendship))->all(),
            'outgoingRequests' => $outgoing->map(fn (Friendship $friendship): array => $this->memberCard($friendship->addressee, $friendship))->all(),
            'friends' => $accepted->map(function (Friendship $friendship): array {
                $other = (int) $friendship->requester_id === (int) $this->getKey()
                    ? $friendship->addressee
                    : $friendship->requester;

                return $this->memberCard($other, $friendship);
            })->all(),
        ];
    }

    /**
     * This member's posts, newest first, in the shape the feed cards use.
     *
     * @return array<int, array<string, mixed>>
     */
    public function feedItemsFor(User $viewer): array
    {
        return $this->posts()
            ->with([
                'user',
                'comments' => fn ($query) => $query->oldest(),
                'comments.user',
            ])
            ->withCount(['likers', 'comments'])
            ->withExists([
                'likers as liked' => fn ($query) => $query->whereKey($viewer->id),
            ])
            ->latest()
            ->get()
            ->map(fn (Post $post): array => $post->toFeedItem($viewer))
            ->all();
    }

    /**
     * How this member appears to someone else, including the friend button state.
     *
     * @return array<string, mixed>
     */
    public function presentMember(User $member): array
    {
        return $this->memberCard($member, $this->friendshipsByOtherUser()->get((int) $member->getKey()));
    }

    /**
     * @return Collection<int, Friendship>
     */
    private function friendshipsByOtherUser(): Collection
    {
        return Friendship::query()
            ->where('requester_id', $this->getKey())
            ->orWhere('addressee_id', $this->getKey())
            ->get()
            ->keyBy(function (Friendship $friendship): int {
                return (int) $friendship->requester_id === (int) $this->getKey()
                    ? (int) $friendship->addressee_id
                    : (int) $friendship->requester_id;
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function memberCard(User $member, ?Friendship $friendship): array
    {
        $relationship = 'none';

        if ($friendship !== null && $friendship->status === FriendshipStatus::Accepted) {
            $relationship = 'friends';
        } elseif ($friendship !== null && $friendship->status === FriendshipStatus::Pending) {
            $relationship = (int) $friendship->requester_id === (int) $this->getKey()
                ? 'request_sent'
                : 'request_received';
        }

        return [
            ...$member->toAuthorCard(),
            'bio' => $member->bio,
            'relationship' => $relationship,
            'friendshipId' => $relationship === 'none' ? null : $friendship?->id,
        ];
    }

    public function clearAvatar(): void
    {
        $this->deleteStoredAvatar($this->avatar_path);
        $this->avatar_path = null;
        $this->save();
    }

    public function storeAvatar(string $path): void
    {
        $previous = $this->avatar_path;
        $this->avatar_path = $path;
        $this->save();

        if ($previous !== $path) {
            $this->deleteStoredAvatar($previous);
        }
    }

    public function deleteStoredAvatar(?string $path): void
    {
        if (! is_string($path) || $path === '' || str_starts_with($path, 'http')) {
            return;
        }

        Storage::disk('public')->delete($path);
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
