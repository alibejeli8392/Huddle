<?php

namespace Database\Seeders;

use App\FriendshipStatus;
use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use App\Support\PreviewData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    /**
     * Sample company members, posts, likes, and comments.
     * Safe to run more than once.
     */
    public function run(): void
    {
        $this->call(GuestUserSeeder::class);

        $users = [];

        foreach (PreviewData::sampleMembers() as $member) {
            $users[$member['id']] = User::query()->updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'title' => $member['title'],
                    'department' => $member['department'],
                    'bio' => $member['bio'],
                    'avatar_path' => $member['avatar'],
                ],
            );
        }

        foreach (PreviewData::samplePosts() as $sample) {
            $author = $users[$sample['authorId']];
            $createdAt = Carbon::createFromTimestampMs((int) $sample['createdAt']);

            $post = Post::query()->updateOrCreate(
                ['body' => $sample['text']],
                ['user_id' => $author->id],
            );

            $post->timestamps = false;
            $post->forceFill([
                'user_id' => $author->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();

            $likeIds = collect($sample['likes'] ?? [])
                ->map(fn (string $id): int => $users[$id]->id)
                ->all();

            $post->likers()->sync($likeIds);

            $commentBodies = [];

            foreach ($sample['comments'] ?? [] as $comment) {
                $commentBodies[] = $comment['text'];
                $commentAt = Carbon::createFromTimestampMs((int) $comment['createdAt']);

                $row = Comment::query()->updateOrCreate(
                    [
                        'post_id' => $post->id,
                        'body' => $comment['text'],
                    ],
                    [
                        'user_id' => $users[$comment['authorId']]->id,
                    ],
                );

                $row->timestamps = false;
                $row->forceFill([
                    'user_id' => $users[$comment['authorId']]->id,
                    'created_at' => $commentAt,
                    'updated_at' => $commentAt,
                ])->save();
            }

            if ($commentBodies === []) {
                $post->comments()->delete();
            } else {
                $post->comments()->whereNotIn('body', $commentBodies)->delete();
            }
        }

        $this->seedFriendships($users);
    }

    /**
     * @param  array<string, User>  $users
     */
    private function seedFriendships(array $users): void
    {
        $guest = User::query()->where('email', 'guest@huddle.app')->firstOrFail();
        $byEmail = collect($users)->keyBy(fn (User $user): string => $user->email);

        $pair = function (User $requester, User $addressee, FriendshipStatus $status): void {
            Friendship::query()->updateOrCreate(
                [
                    'requester_id' => $requester->id,
                    'addressee_id' => $addressee->id,
                ],
                ['status' => $status],
            );
        };

        $pair($byEmail['casey@northstar.io'], $guest, FriendshipStatus::Pending);
        $pair($byEmail['avery@northstar.io'], $guest, FriendshipStatus::Pending);
        $pair($byEmail['chris@northstar.io'], $guest, FriendshipStatus::Pending);
        $pair($byEmail['noah@northstar.io'], $guest, FriendshipStatus::Pending);
        $pair($guest, $byEmail['quinn@northstar.io'], FriendshipStatus::Pending);
        $pair($guest, $byEmail['jordan@northstar.io'], FriendshipStatus::Accepted);
        $pair($byEmail['sam@northstar.io'], $guest, FriendshipStatus::Accepted);
        $pair($guest, $byEmail['morgan@northstar.io'], FriendshipStatus::Accepted);

        $pair($byEmail['taylor@northstar.io'], $byEmail['jordan@northstar.io'], FriendshipStatus::Accepted);
        $pair($byEmail['jamie@northstar.io'], $byEmail['riley@northstar.io'], FriendshipStatus::Accepted);
        $pair($byEmail['elena@northstar.io'], $byEmail['devon@northstar.io'], FriendshipStatus::Pending);
    }
}
