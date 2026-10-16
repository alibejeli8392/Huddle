<?php

namespace Database\Seeders;

use App\ConversationType;
use App\FriendshipStatus;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\Post;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    /**
     * Sample company members, posts, likes, comments, chats, and department tasks.
     * Safe to run more than once.
     */
    public function run(): void
    {
        $this->call(GuestUserSeeder::class);
        $this->call(ManagerUserSeeder::class);

        $users = [];

        foreach (DemoData::sampleMembers() as $member) {
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
                    'role' => UserRole::Employee,
                ],
            );
        }

        foreach (DemoData::samplePosts() as $sample) {
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
        $this->seedConversations($users);
        $this->seedTasks($users);
    }

    /**
     * Department tasks for the guest's team, plus a couple elsewhere.
     *
     * @param  array<string, User>  $users
     */
    private function seedTasks(array $users): void
    {
        $guest = User::query()->where('email', 'guest@huddle.app')->firstOrFail();
        $manager = User::query()->where('email', 'manager@huddle.app')->firstOrFail();
        $department = $guest->department ?? 'Design';
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $jordan = collect($users)->first(fn (User $user): bool => $user->email === 'jordan@northstar.io');
        $creatorId = $manager->id;

        $designToday = [
            [
                'title' => 'Standup notes in the design channel',
                'details' => 'Capture blockers before 10.',
                'due_time' => '09:15:00',
                'completed_by_guest' => true,
            ],
            [
                'title' => 'Critique the empty-state mock',
                'details' => 'Leave two concrete notes on the Figma file.',
                'due_time' => '11:00:00',
                'completed_by_guest' => true,
            ],
            [
                'title' => 'Ship the profile photo crop',
                'details' => 'Pair with engineering if the crop feels off on mobile.',
                'due_time' => '14:30:00',
                'completed_by_guest' => false,
            ],
            [
                'title' => 'Review the welcome walkthrough copy',
                'details' => null,
                'due_time' => '16:00:00',
                'completed_by_guest' => false,
            ],
            [
                'title' => 'Tidy the shared component library',
                'details' => 'Archive unused variants; no new components today.',
                'due_time' => null,
                'completed_by_guest' => false,
            ],
            [
                'title' => 'Sketch one idea for the tasks badge',
                'details' => 'Keep it quiet — count only, no color.',
                'due_time' => null,
                'completed_by_guest' => false,
            ],
        ];

        foreach ($designToday as $sample) {
            $task = Task::query()->updateOrCreate(
                [
                    'department' => $department,
                    'title' => $sample['title'],
                    'due_date' => $today,
                ],
                [
                    'created_by' => $creatorId,
                    'details' => $sample['details'],
                    'due_time' => $sample['due_time'],
                ],
            );

            if ($sample['completed_by_guest']) {
                TaskCompletion::query()->updateOrCreate(
                    [
                        'task_id' => $task->id,
                        'user_id' => $guest->id,
                    ],
                    ['completed_at' => now()->subHours(2)],
                );
            } else {
                TaskCompletion::query()
                    ->where('task_id', $task->id)
                    ->where('user_id', $guest->id)
                    ->delete();
            }
        }

        $tomorrowSamples = [
            [
                'title' => 'Prep slides for Friday critique',
                'details' => 'Three screens max.',
                'due_time' => '10:00:00',
            ],
            [
                'title' => 'Sync with People on new-hire kit',
                'details' => null,
                'due_time' => '15:00:00',
            ],
            [
                'title' => 'Audit icon sizes on mobile nav',
                'details' => 'Make sure six tabs still fit at 375px.',
                'due_time' => null,
            ],
        ];

        foreach ($tomorrowSamples as $sample) {
            Task::query()->updateOrCreate(
                [
                    'department' => $department,
                    'title' => $sample['title'],
                    'due_date' => $tomorrow,
                ],
                [
                    'created_by' => $creatorId,
                    'details' => $sample['details'],
                    'due_time' => $sample['due_time'],
                ],
            );
        }

        $otherDepartment = 'Engineering';
        $otherCreator = $jordan instanceof User ? $jordan : $manager;

        $otherSamples = [
            [
                'title' => 'Green the release branch',
                'details' => 'No flaky tests in the merge queue.',
                'due_date' => $today,
                'due_time' => '13:00:00',
            ],
            [
                'title' => 'Rotate staging secrets checklist',
                'details' => null,
                'due_date' => $tomorrow,
                'due_time' => null,
            ],
        ];

        foreach ($otherSamples as $sample) {
            Task::query()->updateOrCreate(
                [
                    'department' => $otherDepartment,
                    'title' => $sample['title'],
                    'due_date' => $sample['due_date'],
                ],
                [
                    'created_by' => $otherCreator->id,
                    'details' => $sample['details'],
                    'due_time' => $sample['due_time'],
                ],
            );
        }

        $todayCount = Task::query()->forDepartment($department)->dueOn($today)->count();
        $guestDone = TaskCompletion::query()
            ->where('user_id', $guest->id)
            ->whereHas('task', fn ($query) => $query->forDepartment($department)->dueOn($today))
            ->count();

        $this->command?->info("Seeded tasks for {$department}: {$guestDone} of {$todayCount} done today for {$guest->email}.");
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

    /**
     * Direct chats with guest's three friends, and three groups guest belongs to.
     * A few threads stay unread so the Messages badge has a count.
     *
     * @param  array<string, User>  $users
     */
    private function seedConversations(array $users): void
    {
        $guest = User::query()->where('email', 'guest@huddle.app')->firstOrFail();
        $byEmail = collect($users)->keyBy(fn (User $user): string => $user->email);

        $person = function (string $email) use ($byEmail): User {
            $user = $byEmail->get($email);

            if (! $user instanceof User) {
                throw new \RuntimeException("Demo member {$email} is missing.");
            }

            return $user;
        };

        $jordan = $person('jordan@northstar.io');
        $sam = $person('sam@northstar.io');
        $morgan = $person('morgan@northstar.io');
        $taylor = $person('taylor@northstar.io');
        $riley = $person('riley@northstar.io');
        $elena = $person('elena@northstar.io');
        $devon = $person('devon@northstar.io');
        $jamie = $person('jamie@northstar.io');
        $priya = $person('priya@northstar.io');

        $this->seedDirect($guest, $jordan, now()->subDay(), [
            [$jordan, 'Hey — can you glance at the empty state for search before I ship it?', now()->subDays(2)],
            [$guest, 'Yes. Send the build and I\'ll look after standup.', now()->subDays(2)->addMinutes(10)],
            [$jordan, 'Just dropped it in the PR. No rush if standup runs long.', now()->subHours(3)],
        ]);

        $this->seedDirect($guest, $sam, now(), [
            [$sam, 'Would you be open to a 20-min chat with two new designers next week? Totally informal.', now()->subDay()],
            [$guest, 'Absolutely. Tuesday or Wednesday afternoon works best.', now()->subHours(20)],
        ]);

        $this->seedDirect($guest, $morgan, now()->subHours(4), [
            [$morgan, 'Loved the critique notes. Mind if I pull two lines into the launch post?', now()->subHours(6)],
            [$guest, 'Take both lines. Credit the critique doc if you quote them.', now()->subHours(5)],
            [$morgan, 'Will do. I\'ll send you the draft before it goes up.', now()->subMinutes(30)],
        ]);

        $this->seedGroup('Launch crew', $jordan, [$guest, $jordan, $morgan, $taylor, $riley], now()->subDay(), [
            [$jordan, 'Deploy is 4. If your PR is not green by 3, it waits.', now()->subDays(3)],
            [$morgan, 'Copy is locked except the headline. I can still change that one.', now()->subDays(3)->addHour()],
            [$taylor, 'Empty state is in the release branch.', now()->subHours(2)],
        ]);

        $this->seedGroup('People & welcome', $sam, [$guest, $sam, $elena, $devon], now(), [
            [$sam, 'New folks start Monday. Notebooks are on the desks.', now()->subDays(4)],
            [$elena, 'I\'ll walk them through a customer call, not a slide.', now()->subDays(4)->addHours(2)],
        ]);

        $this->seedGroup('Friday lunch', $jamie, [$guest, $jamie, $priya], now()->subDay(), [
            [$jamie, 'Thursday, not Friday — I fly out at 2. Noodles on 2nd?', now()->subDays(2)],
            [$priya, 'I can hold the corner table if you tell me a time.', now()->subDays(2)->addMinutes(30)],
            [$jamie, '12:15 works. I\'ll be the one with the laptop closed.', now()->subMinutes(45)],
        ]);

        $this->command?->info("Seeded chats for {$guest->email}. Unread messages: {$guest->unreadMessageCount()}.");
    }

    /**
     * @param  array<int, array{0: User, 1: string, 2: Carbon}>  $lines
     */
    private function seedDirect(User $guest, User $friend, ?Carbon $guestReadAt, array $lines): void
    {
        $conversation = Conversation::directBetween($guest, $friend);

        if ($conversation === null) {
            $conversation = Conversation::query()->create([
                'type' => ConversationType::Direct,
                'created_by' => $friend->id,
            ]);
        }

        $conversation->users()->sync([
            $guest->id => ['last_read_at' => $guestReadAt],
            $friend->id => ['last_read_at' => now()],
        ]);

        $this->seedLines($conversation, $lines);
    }

    /**
     * @param  array<int, User>  $members
     * @param  array<int, array{0: User, 1: string, 2: Carbon}>  $lines
     */
    private function seedGroup(string $name, User $creator, array $members, ?Carbon $guestReadAt, array $lines): void
    {
        $conversation = Conversation::query()->updateOrCreate(
            [
                'type' => ConversationType::Group,
                'name' => $name,
            ],
            [
                'created_by' => $creator->id,
            ],
        );

        $pivot = [];

        foreach ($members as $member) {
            $pivot[$member->id] = [
                'last_read_at' => $member->email === 'guest@huddle.app' ? $guestReadAt : now(),
            ];
        }

        $conversation->users()->sync($pivot);
        $this->seedLines($conversation, $lines);
    }

    /**
     * @param  array<int, array{0: User, 1: string, 2: Carbon}>  $lines
     */
    private function seedLines(Conversation $conversation, array $lines): void
    {
        $bodies = [];

        foreach ($lines as [$author, $body, $at]) {
            $bodies[] = $body;

            $message = Message::query()->updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'body' => $body,
                ],
                [
                    'user_id' => $author->id,
                    'shared_post_id' => null,
                ],
            );

            $message->timestamps = false;
            $message->forceFill([
                'user_id' => $author->id,
                'created_at' => $at,
                'updated_at' => $at,
            ])->save();
        }

        $conversation->messages()
            ->whereNull('shared_post_id')
            ->whereNotIn('body', $bodies)
            ->delete();
    }
}
