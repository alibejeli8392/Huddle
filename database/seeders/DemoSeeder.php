<?php

namespace Database\Seeders;

use App\Models\Comment;
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
    }
}
