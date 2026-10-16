<?php

namespace App\Models;

use App\ConversationType;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['type', 'name', 'created_by'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ConversationType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * The direct chat between two members, if one already exists.
     */
    public static function directBetween(User $a, User $b): ?self
    {
        return self::query()
            ->where('type', ConversationType::Direct)
            ->whereHas('users', fn (Builder $query) => $query->whereKey($a->id))
            ->whereHas('users', fn (Builder $query) => $query->whereKey($b->id))
            ->first();
    }

    /**
     * Reuse the direct chat between two members, or open one.
     */
    public static function findOrCreateDirect(User $a, User $b): self
    {
        $existing = self::directBetween($a, $b);

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($a, $b): self {
            $existing = self::directBetween($a, $b);

            if ($existing !== null) {
                return $existing;
            }

            $conversation = self::query()->create([
                'type' => ConversationType::Direct,
                'created_by' => $a->id,
            ]);

            $conversation->users()->attach([$a->id, $b->id]);

            return $conversation;
        });
    }

    /**
     * Open a named group. The creator is a member along with the given people.
     *
     * @param  array<int, int>  $memberIds
     */
    public static function startGroup(User $creator, string $name, array $memberIds): self
    {
        return DB::transaction(function () use ($creator, $name, $memberIds): self {
            $conversation = self::query()->create([
                'type' => ConversationType::Group,
                'name' => $name,
                'created_by' => $creator->id,
            ]);

            $ids = collect($memberIds)
                ->push($creator->id)
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $conversation->users()->attach($ids);

            return $conversation;
        });
    }

    public function markReadBy(User $user): void
    {
        $this->users()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);
    }

    /**
     * Messages from other members that arrived after this member last opened the conversation.
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()->unreadFor($user)->count();
    }

    public function titleFor(User $viewer): string
    {
        if ($this->type === ConversationType::Group) {
            return (string) $this->name;
        }

        $other = $this->users->first(fn (User $user): bool => $user->isNot($viewer));

        return $other?->name ?? 'Direct message';
    }

    /**
     * @return array<string, mixed>
     */
    public function toInboxItem(User $viewer): array
    {
        $this->loadMissing(['users', 'latestMessage']);

        $latest = $this->latestMessage;
        $preview = null;

        if ($latest !== null) {
            $preview = $latest->body !== '' ? Str::limit($latest->body, 80) : 'Shared a post';
        }

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'name' => $this->titleFor($viewer),
            'members' => $this->memberCards(),
            'preview' => $preview,
            'previewAt' => $latest?->created_at === null ? null : $latest->created_at->getTimestamp() * 1000,
            'unreadCount' => (int) ($this->unread_count ?? $this->unreadCountFor($viewer)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toThread(User $viewer): array
    {
        $this->loadMissing('users');

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'name' => $this->titleFor($viewer),
            'members' => $this->memberCards(),
            'messages' => $this->messages()
                ->with(['sender', 'sharedPost.user'])
                ->oldest()
                ->orderBy('id')
                ->get()
                ->map(fn (Message $message): array => $message->toChatItem())
                ->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function memberCards(): array
    {
        return $this->users
            ->map(fn (User $user): array => $user->toAuthorCard())
            ->values()
            ->all();
    }
}
