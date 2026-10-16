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
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    /**
     * Simple demo password for selected walkthrough accounts (guest, Leslie, Jordan, Ali, Mahmood).
     * Other seeded members keep the default password string "password".
     */
    public const DEMO_PASSWORD = '1234';

    /**
     * Sample company members, posts, likes, comments, chats, and department tasks.
     * Safe to run more than once. Resets demo-owned content first; never deletes other users.
     */
    public function run(): void
    {
        $this->resetDemoContent();

        $this->call(GuestUserSeeder::class);
        $this->call(ManagerUserSeeder::class);

        $users = [];

        foreach (DemoData::sampleMembers() as $member) {
            $role = isset($member['role'])
                ? UserRole::from($member['role'])
                : UserRole::Employee;

            $users[$member['id']] = User::query()->updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => ($member['use_demo_password'] ?? false)
                        ? self::DEMO_PASSWORD
                        : 'password',
                    'email_verified_at' => now(),
                    'title' => $member['title'],
                    'department' => $member['department'],
                    'bio' => $member['bio'],
                    'avatar_path' => $member['avatar'] ?? null,
                    'role' => $role,
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

        $guest = User::query()->where('email', 'guest@huddle.app')->firstOrFail();
        $demoIds = User::query()->whereIn('email', DemoData::demoEmails())->pluck('id');
        $friendships = Friendship::query()
            ->whereIn('requester_id', $demoIds)
            ->whereIn('addressee_id', $demoIds)
            ->count();
        $conversations = Conversation::query()
            ->whereHas('users', fn ($q) => $q->whereKey($guest->id))
            ->count();
        $messages = Message::query()
            ->whereIn('conversation_id', Conversation::query()->whereHas('users', fn ($q) => $q->whereKey($guest->id))->select('conversations.id'))
            ->count();

        $this->command?->info(sprintf(
            'Demo company refreshed: %d members, %d posts, %d demo friendships, %d guest conversations, %d messages in those chats. Unread for guest: %d.',
            count($users),
            count(DemoData::samplePosts()),
            $friendships,
            $conversations,
            $messages,
            $guest->unreadMessageCount(),
        ));
    }

    /**
     * Remove posts, social content, chats, friendships, and tasks owned by demo accounts only.
     */
    private function resetDemoContent(): void
    {
        $demoIds = User::query()
            ->whereIn('email', DemoData::demoEmails())
            ->pluck('id');

        if ($demoIds->isEmpty()) {
            return;
        }

        Friendship::query()
            ->whereIn('requester_id', $demoIds)
            ->whereIn('addressee_id', $demoIds)
            ->delete();

        $taskIds = Task::query()->whereIn('created_by', $demoIds)->pluck('id');

        if ($taskIds->isNotEmpty()) {
            TaskCompletion::query()->whereIn('task_id', $taskIds)->delete();
            Task::query()->whereIn('id', $taskIds)->delete();
        }

        TaskCompletion::query()->whereIn('user_id', $demoIds)->delete();

        $postIds = Post::query()->whereIn('user_id', $demoIds)->pluck('id');

        if ($postIds->isNotEmpty()) {
            Comment::query()->whereIn('post_id', $postIds)->delete();
            DB::table('post_likes')->whereIn('post_id', $postIds)->delete();
            Post::query()->whereIn('id', $postIds)->delete();
        }

        Comment::query()->whereIn('user_id', $demoIds)->delete();
        DB::table('post_likes')->whereIn('user_id', $demoIds)->delete();

        $conversationIds = Conversation::query()
            ->where(function ($query) use ($demoIds): void {
                $query->whereIn('created_by', $demoIds)
                    ->orWhereHas('users', fn ($inner) => $inner->whereIn('users.id', $demoIds));
            })
            ->pluck('id')
            ->unique();

        $safeConversationIds = $conversationIds->filter(function (int $id) use ($demoIds): bool {
            $memberIds = DB::table('conversation_user')
                ->where('conversation_id', $id)
                ->pluck('user_id');

            if ($memberIds->isEmpty()) {
                return Conversation::query()->whereKey($id)->whereIn('created_by', $demoIds)->exists();
            }

            return $memberIds->diff($demoIds)->isEmpty();
        })->values();

        if ($safeConversationIds->isNotEmpty()) {
            Message::query()->whereIn('conversation_id', $safeConversationIds)->delete();
            DB::table('conversation_user')->whereIn('conversation_id', $safeConversationIds)->delete();
            Conversation::query()->whereIn('id', $safeConversationIds)->delete();
        }

        Message::query()->whereIn('user_id', $demoIds)->delete();
    }

    /**
     * @param  array<string, User>  $users
     */
    private function seedFriendships(array $users): void
    {
        $guest = User::query()->where('email', 'guest@huddle.app')->firstOrFail();
        $byEmail = collect($users)->keyBy(fn (User $user): string => $user->email);
        $e = fn (string $email) => $byEmail[$email];

        $pair = function (User $requester, User $addressee, FriendshipStatus $status): void {
            Friendship::query()->updateOrCreate(
                [
                    'requester_id' => $requester->id,
                    'addressee_id' => $addressee->id,
                ],
                ['status' => $status],
            );
        };

        // Guest: 4 incoming (keep)
        $pair($e('casey@northstar.io'), $guest, FriendshipStatus::Pending);
        $pair($e('avery@northstar.io'), $guest, FriendshipStatus::Pending);
        $pair($e('chris@northstar.io'), $guest, FriendshipStatus::Pending);
        $pair($e('noah@northstar.io'), $guest, FriendshipStatus::Pending);

        // Guest: 2 outgoing
        $pair($guest, $e('quinn@northstar.io'), FriendshipStatus::Pending);
        $pair($guest, $e('otto@northstar.io'), FriendshipStatus::Pending);

        // Guest: 12 accepted
        foreach ([
            'jordan@northstar.io',
            'sam@northstar.io',
            'morgan@northstar.io',
            'taylor@northstar.io',
            'riley@northstar.io',
            'jamie@northstar.io',
            'priya@northstar.io',
            'harper@northstar.io',
            'sofia@northstar.io',
            'marcus@northstar.io',
            'elena@northstar.io',
            'devon@northstar.io',
        ] as $i => $email) {
            if ($i % 2 === 0) {
                $pair($guest, $e($email), FriendshipStatus::Accepted);
            } else {
                $pair($e($email), $guest, FriendshipStatus::Accepted);
            }
        }

        // Mix among other members
        $pair($e('taylor@northstar.io'), $e('jordan@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('jamie@northstar.io'), $e('riley@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('elena@northstar.io'), $e('devon@northstar.io'), FriendshipStatus::Pending);
        $pair($e('harper@northstar.io'), $e('morgan@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('sofia@northstar.io'), $e('taylor@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('marcus@northstar.io'), $e('jamie@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('mia@northstar.io'), $e('alex@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('owen@northstar.io'), $e('priya@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('jade@northstar.io'), $e('nina@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('luke@northstar.io'), $e('jordan@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('amira@northstar.io'), $e('sofia@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('ben@northstar.io'), $e('devon@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('kenji@northstar.io'), $e('luke@northstar.io'), FriendshipStatus::Pending);
        $pair($e('tara@northstar.io'), $e('sam@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('iris@northstar.io'), $e('quinn@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('gabe@northstar.io'), $e('morgan@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('zoe@northstar.io'), $e('harper@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('nadia@northstar.io'), $e('avery@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('cole@northstar.io'), $e('jamie@northstar.io'), FriendshipStatus::Pending);
        $pair($e('felix@northstar.io'), $e('chris@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('rupa@northstar.io'), $e('riley@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('lena@northstar.io'), $e('otto@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('will@northstar.io'), $e('lena@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('anika@northstar.io'), $e('casey@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('alex@northstar.io'), $e('jade@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('nina@northstar.io'), $e('owen@northstar.io'), FriendshipStatus::Pending);

        // Engineering managers: several accepted friendships each
        $ali = $e('a.bejeli.8392@westcliff.edu');
        $mahmood = $e('m.ayaz.9754@westcliff.edu');
        $pair($ali, $mahmood, FriendshipStatus::Accepted);
        $pair($ali, $e('jordan@northstar.io'), FriendshipStatus::Accepted);
        $pair($ali, $e('taylor@northstar.io'), FriendshipStatus::Accepted);
        $pair($ali, $e('sofia@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('luke@northstar.io'), $ali, FriendshipStatus::Accepted);
        $pair($e('devon@northstar.io'), $ali, FriendshipStatus::Accepted);
        $pair($ali, $guest, FriendshipStatus::Accepted);
        $pair($mahmood, $e('jordan@northstar.io'), FriendshipStatus::Accepted);
        $pair($mahmood, $e('ben@northstar.io'), FriendshipStatus::Accepted);
        $pair($mahmood, $e('kenji@northstar.io'), FriendshipStatus::Accepted);
        $pair($e('amira@northstar.io'), $mahmood, FriendshipStatus::Accepted);
        $pair($e('anika@northstar.io'), $mahmood, FriendshipStatus::Accepted);
        $pair($mahmood, $guest, FriendshipStatus::Accepted);
    }

    /**
     * @param  array<string, User>  $users
     */
    private function seedTasks(array $users): void
    {
        $guest = User::query()->where('email', 'guest@huddle.app')->firstOrFail();
        $manager = User::query()->where('email', 'manager@huddle.app')->firstOrFail();
        $department = $guest->department ?? 'Design';
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $designEmployees = User::query()
            ->where('department', $department)
            ->where('role', UserRole::Employee)
            ->orderBy('email')
            ->get();

        $morgan = collect($users)->first(fn (User $user): bool => $user->email === 'morgan@northstar.io');
        $sam = collect($users)->first(fn (User $user): bool => $user->email === 'sam@northstar.io');
        $ali = collect($users)->first(fn (User $user): bool => $user->email === 'a.bejeli.8392@westcliff.edu');
        $mahmood = collect($users)->first(fn (User $user): bool => $user->email === 'm.ayaz.9754@westcliff.edu');
        $jordan = collect($users)->first(fn (User $user): bool => $user->email === 'jordan@northstar.io');
        $creatorId = $manager->id;

        $designToday = [
            [
                'title' => 'Post standup notes in #design',
                'details' => 'Capture blockers and who owns each follow-up before 10.',
                'due_time' => '09:15:00',
                'completed_by_guest' => true,
                'extra_completers' => 4,
            ],
            [
                'title' => 'Leave notes on the empty-state mock',
                'details' => 'Two concrete comments in Figma — what to keep, what still feels cold.',
                'due_time' => '11:00:00',
                'completed_by_guest' => true,
                'extra_completers' => 1,
            ],
            [
                'title' => 'Pair on the profile photo crop',
                'details' => 'Check the crop on a phone-width frame with eng before it ships.',
                'due_time' => '14:30:00',
                'completed_by_guest' => false,
                'extra_completers' => 5,
            ],
            [
                'title' => 'Review welcome walkthrough copy',
                'details' => 'Mark any line that sounds like a brochure. Suggest quieter wording.',
                'due_time' => '16:00:00',
                'completed_by_guest' => false,
                'extra_completers' => 2,
            ],
            [
                'title' => 'Archive unused library variants',
                'details' => 'No new components today — just retire what nobody has touched in a month.',
                'due_time' => null,
                'completed_by_guest' => false,
                'extra_completers' => 0,
            ],
            [
                'title' => 'Sketch one quieter tasks badge',
                'details' => 'Count only, no color. Should still read at 375px next to Messages.',
                'due_time' => null,
                'completed_by_guest' => false,
                'extra_completers' => 3,
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

            TaskCompletion::query()->where('task_id', $task->id)->delete();

            $completerIds = [];

            if ($sample['completed_by_guest']) {
                $completerIds[] = $guest->id;
            }

            $others = $designEmployees
                ->reject(fn (User $user): bool => $user->is($guest))
                ->values();

            foreach ($others->take($sample['extra_completers']) as $employee) {
                $completerIds[] = $employee->id;
            }

            foreach (array_unique($completerIds) as $userId) {
                TaskCompletion::query()->create([
                    'task_id' => $task->id,
                    'user_id' => $userId,
                    'completed_at' => now()->subHours(random_int(1, 5)),
                ]);
            }
        }

        $tomorrowSamples = [
            [
                'title' => 'Prep three screens for Friday critique',
                'details' => 'Hard cap at three. Label what is still a question vs a decision.',
                'due_time' => '10:00:00',
            ],
            [
                'title' => 'Sync with People on the new-hire kit',
                'details' => 'Confirm desk setup photos and the first-week buddy note.',
                'due_time' => '15:00:00',
            ],
            [
                'title' => 'Audit icon sizes on mobile nav',
                'details' => 'Six tabs must still fit at 375px without truncating labels into noise.',
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

        $otherSamples = [
            [
                'department' => 'Marketing',
                'created_by' => $morgan instanceof User ? $morgan->id : $creatorId,
                'title' => 'Lock launch headline options',
                'details' => 'Pick two finalists before the afternoon review with Design.',
                'due_date' => $today,
                'due_time' => '15:30:00',
            ],
            [
                'department' => 'People',
                'created_by' => $sam instanceof User ? $sam->id : $creatorId,
                'title' => 'Confirm Monday desk assignments',
                'details' => 'Plants, notebooks, and badges for the two new starters.',
                'due_date' => $tomorrow,
                'due_time' => '11:00:00',
            ],
        ];

        foreach ($otherSamples as $sample) {
            Task::query()->updateOrCreate(
                [
                    'department' => $sample['department'],
                    'title' => $sample['title'],
                    'due_date' => $sample['due_date'],
                ],
                [
                    'created_by' => $sample['created_by'],
                    'details' => $sample['details'],
                    'due_time' => $sample['due_time'],
                ],
            );
        }

        $this->seedEngineeringTasks($ali, $mahmood, $jordan, $today, $tomorrow);

        $todayCount = Task::query()->forDepartment($department)->dueOn($today)->count();
        $guestDone = TaskCompletion::query()
            ->where('user_id', $guest->id)
            ->whereHas('task', fn ($query) => $query->forDepartment($department)->dueOn($today))
            ->count();

        $this->command?->info("Seeded tasks for {$department}: {$guestDone} of {$todayCount} done today for {$guest->email}. Design employees: {$designEmployees->count()}.");
    }

    /**
     * About six Engineering tasks for today (varied completions) and a few for tomorrow.
     */
    private function seedEngineeringTasks(?User $ali, ?User $mahmood, ?User $jordan, string $today, string $tomorrow): void
    {
        if (! $ali instanceof User || ! $mahmood instanceof User) {
            return;
        }

        $employees = User::query()
            ->where('department', 'Engineering')
            ->where('role', UserRole::Employee)
            ->orderBy('email')
            ->get();

        $jordanId = $jordan instanceof User ? $jordan->id : null;

        $todaySamples = [
            [
                'title' => 'Green the release branch before 3',
                'details' => 'No flaky tests in the merge queue; ping Devon if CI lies.',
                'due_time' => '09:30:00',
                'created_by' => $ali->id,
                'completer_count' => 1,
                'include_jordan' => true,
            ],
            [
                'title' => 'Review invite CTA flag naming',
                'details' => 'Rename anything still called tmp_* before it ships to prod.',
                'due_time' => '11:00:00',
                'created_by' => $mahmood->id,
                'completer_count' => 3,
                'include_jordan' => true,
            ],
            [
                'title' => 'Pair on checklist empty-state copy',
                'details' => 'One sentence, one action. Keep it quiet on a phone-width frame.',
                'due_time' => '13:00:00',
                'created_by' => $ali->id,
                'completer_count' => 5,
                'include_jordan' => false,
            ],
            [
                'title' => 'Rotate staging secrets checklist',
                'details' => 'Confirm last week’s feature-flag cleanup is still gone.',
                'due_time' => '15:30:00',
                'created_by' => $mahmood->id,
                'completer_count' => 2,
                'include_jordan' => true,
            ],
            [
                'title' => 'Write the afternoon deploy note',
                'details' => 'Who owns the canary, what rolls back, and when Slack goes quiet.',
                'due_time' => null,
                'created_by' => $ali->id,
                'completer_count' => 0,
                'include_jordan' => false,
            ],
            [
                'title' => 'Triage the two flaky platform specs',
                'details' => 'Reproduce twice or quarantine with a link to the issue.',
                'due_time' => null,
                'created_by' => $mahmood->id,
                'completer_count' => 7,
                'include_jordan' => false,
            ],
        ];

        foreach ($todaySamples as $sample) {
            $task = Task::query()->updateOrCreate(
                [
                    'department' => 'Engineering',
                    'title' => $sample['title'],
                    'due_date' => $today,
                ],
                [
                    'created_by' => $sample['created_by'],
                    'details' => $sample['details'],
                    'due_time' => $sample['due_time'],
                ],
            );

            TaskCompletion::query()->where('task_id', $task->id)->delete();

            $completerIds = [];

            if ($sample['include_jordan'] && $jordanId !== null) {
                $completerIds[] = $jordanId;
            }

            $others = $employees
                ->reject(fn (User $user): bool => $jordanId !== null && $user->id === $jordanId)
                ->values();

            $needed = max(0, $sample['completer_count'] - count($completerIds));

            foreach ($others->take($needed) as $employee) {
                $completerIds[] = $employee->id;
            }

            foreach (array_unique($completerIds) as $userId) {
                TaskCompletion::query()->create([
                    'task_id' => $task->id,
                    'user_id' => $userId,
                    'completed_at' => now()->subHours(random_int(1, 5)),
                ]);
            }
        }

        $tomorrowSamples = [
            [
                'title' => 'Sketch the mobile nav badge spacing',
                'details' => 'Six tabs must still fit at 375px without truncating labels.',
                'due_time' => '10:00:00',
                'created_by' => $ali->id,
            ],
            [
                'title' => 'Document the staging deploy window',
                'details' => 'Noon–1 Pacific only unless someone notes an exception in #eng.',
                'due_time' => '14:00:00',
                'created_by' => $mahmood->id,
            ],
            [
                'title' => 'Retire unused invite experiment flags',
                'details' => 'No new experiments today — just delete what nobody has touched.',
                'due_time' => null,
                'created_by' => $mahmood->id,
            ],
        ];

        foreach ($tomorrowSamples as $sample) {
            Task::query()->updateOrCreate(
                [
                    'department' => 'Engineering',
                    'title' => $sample['title'],
                    'due_date' => $tomorrow,
                ],
                [
                    'created_by' => $sample['created_by'],
                    'details' => $sample['details'],
                    'due_time' => $sample['due_time'],
                ],
            );
        }

        $engToday = Task::query()->forDepartment('Engineering')->dueOn($today)->count();
        $jordanDone = $jordan instanceof User
            ? TaskCompletion::query()
                ->where('user_id', $jordan->id)
                ->whereHas('task', fn ($query) => $query->forDepartment('Engineering')->dueOn($today))
                ->count()
            : 0;

        $this->command?->info(sprintf(
            'Seeded Engineering tasks: %d today, %d tomorrow. Jordan done today: %d of %d. Employees: %d.',
            $engToday,
            count($tomorrowSamples),
            $jordanDone,
            $engToday,
            $employees->count(),
        ));
    }

    /**
     * Eight direct chats with accepted friends and five groups.
     * About six unread messages across a few threads.
     *
     * @param  array<string, User>  $users
     */
    private function seedConversations(array $users): void
    {
        $guest = User::query()->where('email', 'guest@huddle.app')->firstOrFail();
        $byEmail = collect($users)->keyBy(fn (User $user): string => $user->email);
        $p = function (string $email) use ($byEmail): User {
            $user = $byEmail->get($email);

            if (! $user instanceof User) {
                throw new \RuntimeException("Demo member {$email} is missing.");
            }

            return $user;
        };

        $jordan = $p('jordan@northstar.io');
        $sam = $p('sam@northstar.io');
        $morgan = $p('morgan@northstar.io');
        $taylor = $p('taylor@northstar.io');
        $riley = $p('riley@northstar.io');
        $jamie = $p('jamie@northstar.io');
        $priya = $p('priya@northstar.io');
        $harper = $p('harper@northstar.io');
        $elena = $p('elena@northstar.io');
        $devon = $p('devon@northstar.io');
        $alex = $p('alex@northstar.io');
        $mia = $p('mia@northstar.io');
        $owen = $p('owen@northstar.io');
        $jade = $p('jade@northstar.io');
        $nina = $p('nina@northstar.io');
        $sofia = $p('sofia@northstar.io');
        $luke = $p('luke@northstar.io');
        $ben = $p('ben@northstar.io');
        $tara = $p('tara@northstar.io');

        // ~6 unread across Jordan (2), Morgan (1), Launch crew (2), Design crit (1)
        $this->seedDirect($guest, $jordan, 2, $this->thread([
            [$jordan, 'Can you glance at the checklist microcopy before I merge the shorten step?', 5, 14, 10],
            [$guest, 'Yes — send the PR. I’ll look after standup.', 5, 14, 22],
            [$jordan, 'Link is in #eng. Second sentence still feels homework-y to me.', 5, 15, 5],
            [$guest, 'Agreed. I’ll suggest cutting “you may want to.”', 5, 15, 40],
            [$jordan, 'Perfect. Also: does “Invite someone” still win over “Add a teammate”?', 4, 9, 15],
            [$guest, 'Invite someone. Priya’s interviews lined up with that.', 4, 9, 40],
            [$jordan, 'Shipped a draft with Invite. CI is green.', 3, 16, 20],
            [$guest, 'Nice. I’ll skim the diff tonight.', 3, 17, 5],
            [$jordan, 'No rush. If standup runs long tomorrow, it can wait.', 0, 11, 20],
            [$jordan, 'One more: canary looks clean on the checklist step.', 0, 14, 5],
        ]));

        $this->seedDirect($guest, $sam, 0, $this->thread([
            [$sam, 'Would you do a 20-min chat with two new designers next week? Informal, no slides.', 6, 11, 5],
            [$guest, 'Absolutely. Tuesday or Wednesday afternoon works best.', 6, 11, 30],
            [$sam, 'They asked about critique culture — anything you wish someone had told you?', 5, 10, 0],
            [$guest, 'Bring messy work. Polished decks hide the useful questions.', 5, 10, 25],
            [$sam, 'Writing that down. Calendar hold coming once they pick a day.', 4, 15, 10],
            [$guest, 'I can also walk them past the design system board if that helps.', 4, 15, 40],
            [$sam, 'Yes please. Jade said she can join for ten minutes.', 3, 9, 20],
            [$guest, 'Great — ping me the invite and I’ll block it.', 3, 9, 45],
            [$sam, 'Invite sent. Subject is “Design hello,” not “interview.”', 1, 16, 0],
            [$guest, 'Got it. Looking forward to it.', 1, 16, 20],
        ]));

        $this->seedDirect($guest, $morgan, 1, $this->thread([
            [$morgan, 'Loved the critique notes. Okay if I pull two lines into the launch post?', 4, 10, 0],
            [$guest, 'Take both. Credit the critique doc if you quote them.', 4, 10, 20],
            [$morgan, 'Draft is in Marketing → Launch. Headline still wiggly.', 3, 14, 15],
            [$guest, 'Option B felt quieter to me. A tries too hard.', 3, 14, 40],
            [$morgan, 'B it is. Harper already voted the same.', 2, 9, 5],
            [$guest, 'Good company. Send me the final before it goes up?', 2, 9, 25],
            [$morgan, 'Will do. Also stealing one Priya sentence — already credited.', 1, 11, 10],
            [$guest, 'She’ll appreciate that.', 1, 11, 30],
            [$morgan, 'Draft’s in your inbox. No rush if you’re in critique.', 0, 15, 40],
        ]));

        $this->seedDirect($guest, $taylor, 0, $this->thread([
            [$taylor, 'Empty-state chips are in. Want a look on mobile width?', 7, 13, 0],
            [$guest, 'Yes — especially the suggestion that still feels cold.', 7, 13, 20],
            [$taylor, 'Dropped screenshots in the PR. Sofia already poked the spacing.', 6, 10, 15],
            [$guest, 'Spacing looks better. Copy on chip 2 still sounds like an error.', 6, 10, 45],
            [$taylor, 'Rewrote chip 2. “Try a person or a project” instead of “No results.”', 5, 16, 0],
            [$guest, 'That’s the one. Ship it.', 5, 16, 20],
            [$taylor, 'Merged. Thanks for the pass.', 4, 9, 10],
            [$guest, 'Anytime. Yell if the Tasks badge fights the layout again.', 4, 9, 30],
            [$taylor, 'Will do. Nav still fits at 375 on my simulator.', 2, 14, 0],
        ]));

        $this->seedDirect($guest, $riley, 0, $this->thread([
            [$riley, 'Customer asked why the checklist felt like homework. Sharing in case it helps design.', 8, 11, 0],
            [$guest, 'That’s exactly the day-3 stall Casey flagged. Thank you.', 8, 11, 25],
            [$riley, 'They liked the invite step once we walked them through it live.', 7, 15, 10],
            [$guest, 'Good signal. We’re shortening the second step this week.', 7, 15, 35],
            [$riley, 'Want me to join the microcopy pair tomorrow?', 6, 9, 0],
            [$guest, 'Yes — 2pm if you can. Bring the quote if it’s shareable.', 6, 9, 20],
            [$riley, 'Calendar held. I’ll scrub the company name.', 5, 10, 5],
            [$guest, 'Perfect. See you then.', 5, 10, 25],
            [$riley, 'Notes from the call are in the CS folder. Short version: invite language landed.', 1, 17, 0],
        ]));

        $this->seedDirect($guest, $jamie, 0, $this->thread([
            [$jamie, 'Thursday lunch still on? Flying out at 2.', 5, 12, 0],
            [$guest, 'On my side. Noodles on 2nd at 12:15?', 5, 12, 15],
            [$jamie, 'Yes. Priya’s holding the corner table.', 5, 12, 30],
            [$guest, 'I’ll be the one coming from critique — might be 12:20.', 4, 9, 0],
            [$jamie, 'We’ll guard a seat. Laptop stays closed. Promise.', 4, 9, 20],
            [$guest, 'Holding you to that.', 4, 9, 35],
            [$jamie, 'Also: thanks for the Meridian wall quote permission thread.', 3, 16, 0],
            [$guest, 'Morgan’s fighting for it. I just nodded.', 3, 16, 20],
            [$jamie, 'See you Thursday. I’ll text if the flight moves.', 2, 11, 0],
        ]));

        $this->seedDirect($guest, $priya, 0, $this->thread([
            [$priya, 'Office hours Thursday — want me to bring the freeze-on-name clips?', 6, 10, 0],
            [$guest, 'Please. They’re the best argument for example names.', 6, 10, 20],
            [$priya, 'Also sitting in on the checklist microcopy pair if that’s still open.', 5, 14, 0],
            [$guest, 'Open. Riley’s joining with a customer quote too.', 5, 14, 20],
            [$priya, 'I’ll keep my notes short. Two quotes max.', 4, 9, 10],
            [$guest, 'Appreciated. Long decks put everyone to sleep.', 4, 9, 30],
            [$priya, 'Wrote a tiny digest in Research → Checklist. No slides.', 2, 15, 0],
            [$guest, 'Read it. The “stare at the field” line is going in the critique.', 2, 15, 25],
            [$priya, 'Steal away. Credit optional.', 1, 10, 0],
        ]));

        $this->seedDirect($guest, $harper, 0, $this->thread([
            [$harper, 'Lifecycle email #3 — mind a design eye on the button label?', 7, 11, 0],
            [$guest, 'Send it. I’ll look after lunch.', 7, 11, 20],
            [$harper, 'Doc is in Marketing → Lifecycle. Subject options too.', 7, 13, 0],
            [$guest, 'Button: “Continue setup” feels clearer than “Get started.”', 6, 16, 10],
            [$harper, 'Changed. Morgan liked B for the subject as well.', 6, 16, 40],
            [$guest, 'B is the quiet one. Good.', 5, 9, 0],
            [$harper, 'Shipped to review. Thanks for the fast pass.', 4, 14, 0],
            [$guest, 'Anytime. Name the next draft after a street I can pronounce.', 4, 14, 20],
            [$harper, 'Deal. Working title is Pine.', 3, 10, 0],
        ]));

        $this->seedGroup('Launch crew', $jordan, [$guest, $jordan, $morgan, $taylor, $riley, $sofia], 2, $this->thread([
            [$jordan, 'Deploy is at 4. If your PR isn’t green by 3, it waits.', 6, 9, 15],
            [$morgan, 'Copy is locked except the headline — I can still change that one.', 6, 10, 40],
            [$taylor, 'Empty state is on the release branch.', 5, 11, 0],
            [$riley, 'Customer success is standing by for the first tickets after deploy.', 5, 11, 30],
            [$guest, 'Checklist microcopy PR is ready for a look.', 4, 14, 0],
            [$jordan, 'I’ll review after standup. Keep the invite wording.', 4, 14, 25],
            [$sofia, 'Frontend padding matches Jade’s tokens now.', 3, 10, 0],
            [$morgan, 'Headline B is in the launch doc.', 3, 15, 0],
            [$taylor, 'Canary looks clean on search.', 2, 16, 0],
            [$jordan, 'Reminder: merge freeze soft-starts at 3.', 1, 9, 0],
            [$sofia, 'Nav badge spacing checked at 375. Still fits.', 0, 12, 10],
            [$taylor, 'Empty-state chips are in the release branch. Watching CI.', 0, 15, 40],
        ]));

        $this->seedGroup('People & welcome', $sam, [$guest, $sam, $elena, $devon, $tara], 0, $this->thread([
            [$sam, 'New folks start Monday. Notebooks and plants are already on the desks.', 7, 10, 0],
            [$elena, 'I’ll walk them through a real customer call, not a slide deck.', 7, 12, 15],
            [$devon, 'Badges are printed. I’ll leave them with Sam by end of day.', 6, 16, 30],
            [$guest, 'Happy to buddy for the first design critique when they’re ready.', 6, 17, 5],
            [$tara, 'Buddy guide v1 is in the People folder if you want a checklist.', 5, 9, 20],
            [$sam, 'Using it. Page 2 tweak landed from Elena.', 5, 10, 0],
            [$elena, 'Coffee chats are optional — say that out loud so it feels true.', 4, 14, 0],
            [$guest, 'I’ll keep the first critique small: one screen, two questions.', 4, 14, 30],
            [$devon, 'IT sheet is up for laptop refresh if anyone’s still on 2019 machines.', 3, 11, 0],
            [$sam, 'Welcome tote has chargers again. Tell me if we run out.', 2, 15, 0],
            [$tara, 'I can sit in on the hallway intros so nobody gets ambushed.', 1, 9, 30],
            [$guest, 'Appreciate that. I’ll ping when the start date is firm.', 1, 10, 0],
        ]));

        $this->seedGroup('Friday lunch', $jamie, [$guest, $jamie, $priya, $harper], 0, $this->thread([
            [$jamie, 'Thursday, not Friday — I fly out at 2. Noodles on 2nd?', 5, 13, 10],
            [$priya, 'I can hold the corner table if you give me a time.', 5, 13, 40],
            [$jamie, '12:15 works. I’ll be the one with the laptop closed.', 5, 14, 0],
            [$guest, 'Coming from critique — might be five minutes late.', 4, 9, 0],
            [$harper, 'In. Saving a seat near the wall.', 4, 9, 20],
            [$priya, 'Table is held under Priya. They know us.', 3, 16, 0],
            [$jamie, 'Perfect. If the flight moves I’ll say so here.', 3, 16, 30],
            [$guest, 'Bring appetite, leave status updates.', 2, 11, 0],
            [$harper, 'Deal. No roadmap talk before noodles arrive.', 2, 11, 20],
            [$priya, 'I’ll text the group if the corner is taken — unlikely.', 1, 10, 0],
            [$jamie, 'See you Thursday. Looking forward to a normal lunch.', 1, 12, 0],
        ]));

        $this->seedGroup('Design crit', $alex, [$guest, $alex, $mia, $owen, $jade, $nina, $priya], 1, $this->thread([
            [$alex, 'Tomorrow’s critique: three screens max. Label questions vs decisions.', 4, 16, 0],
            [$mia, 'I’ll bring the warm empty-state options.', 4, 16, 25],
            [$owen, 'Bringing the checklist step that still feels cold.', 4, 16, 50],
            [$jade, 'Tokens PR is merged — use the new spacing on anything new.', 3, 9, 10],
            [$nina, 'Onboarding draft is in the doc. Need eyes on step two.', 3, 10, 0],
            [$guest, 'I can lead the invite-language bit. Keep it short.', 3, 10, 30],
            [$priya, 'Two interview clips ready if we need evidence.', 2, 14, 0],
            [$alex, 'Nook is reserved 10–11. Extra chairs already there.', 2, 15, 0],
            [$mia, 'Please don’t steal the chairs afterward. Looking at past us.', 2, 15, 20],
            [$owen, 'Microcopy alternatives are on the sticky notes in Figma.', 1, 11, 0],
            [$nina, 'I’ll print one board so we’re not squinting at laptops.', 1, 11, 30],
            [$jade, 'Systems note: archive unused variants after critique if we agree.', 0, 9, 0],
            [$alex, 'Agenda locked. See you in the nook.', 0, 16, 10],
        ]));

        $this->seedGroup('Eng deploys', $jordan, [$guest, $jordan, $taylor, $sofia, $luke, $ben, $devon], 0, $this->thread([
            [$jordan, 'Staging deploys: noon–1 Pacific only unless you note an exception here.', 5, 8, 30],
            [$devon, 'Flag cleanup from last week is still gone. Good.', 5, 9, 0],
            [$luke, 'Deprecation notes for /v1 filters are in the wiki.', 4, 10, 0],
            [$taylor, 'Mobile path still used `q` — switching this week.', 4, 10, 30],
            [$ben, 'Share-dialog flake was timing. Quarantined with a ticket.', 3, 11, 20],
            [$sofia, 'I’ll slow the animation in test mode so we can unflake it properly.', 3, 11, 50],
            [$jordan, 'Checklist shorten is in the afternoon train if green.', 2, 9, 0],
            [$guest, 'Design signed off on Invite wording.', 2, 9, 25],
            [$luke, 'Canary dashboard is quiet. Watching error rate.', 1, 14, 0],
            [$devon, 'CI budget: no new flakes without an owner.', 1, 14, 30],
            [$ben, 'I’ll own the share-dialog follow-up.', 1, 15, 0],
            [$taylor, 'Release branch is tidy. Thanks everyone.', 0, 16, 0],
            [$jordan, 'Nice work. See you at the 4 o’clock window.', 0, 16, 20],
        ]));

        $this->seedManagerChats($users);

        $this->command?->info("Seeded chats for {$guest->email}. Unread messages: {$guest->unreadMessageCount()}.");
    }

    /**
     * Direct and group chats so Ali and Mahmood’s Messages pages are not empty.
     *
     * @param  array<string, User>  $users
     */
    private function seedManagerChats(array $users): void
    {
        $byEmail = collect($users)->keyBy(fn (User $user): string => $user->email);
        $p = function (string $email) use ($byEmail): User {
            $user = $byEmail->get($email);

            if (! $user instanceof User) {
                throw new \RuntimeException("Demo member {$email} is missing.");
            }

            return $user;
        };

        $ali = $p('a.bejeli.8392@westcliff.edu');
        $mahmood = $p('m.ayaz.9754@westcliff.edu');
        $jordan = $p('jordan@northstar.io');
        $taylor = $p('taylor@northstar.io');
        $sofia = $p('sofia@northstar.io');
        $luke = $p('luke@northstar.io');
        $devon = $p('devon@northstar.io');
        $ben = $p('ben@northstar.io');
        $kenji = $p('kenji@northstar.io');
        $amira = $p('amira@northstar.io');

        $this->seedPeerDirect($ali, $jordan, $this->thread([
            [$ali, 'Empty state PR is up — can you glance before standup?', 2, 9, 10],
            [$jordan, 'Looking now. Keep the CTA as Invite someone.', 2, 9, 35],
            [$ali, 'Done. Also trimmed the second sentence.', 2, 10, 5],
            [$jordan, 'Ship it with the afternoon train if CI stays green.', 1, 14, 20],
            [$ali, 'Will do. I’ll ping #eng when it’s in.', 1, 14, 45],
            [$jordan, 'Thanks — badge spacing can wait until tomorrow.', 0, 11, 15],
        ]));

        $this->seedPeerDirect($ali, $sofia, $this->thread([
            [$sofia, 'Can we pair on the mobile nav tonight?', 3, 16, 0],
            [$ali, 'Yes — bring the 375 frame. I’ll bring the badge mock.', 3, 16, 20],
            [$sofia, 'Labels still truncate on Tasks when the count is double digits.', 2, 10, 5],
            [$ali, 'I’ll try a thinner weight before we shrink the icons.', 2, 10, 30],
            [$sofia, 'Sounds good. See you at 5 by the design monitors.', 1, 15, 10],
        ]));

        $this->seedPeerGroup('Frontend polish', $ali, [$ali, $sofia, $taylor, $jordan], $this->thread([
            [$ali, 'Today’s focus: empty states and badge spacing. Drop blockers here.', 4, 9, 0],
            [$sofia, 'I’m on the nav badges after lunch.', 3, 11, 20],
            [$taylor, 'API already returns incomplete counts — no schema change.', 2, 13, 5],
            [$jordan, 'Keep changes behind the invite flag until Friday critique.', 1, 15, 40],
            [$ali, 'Flag stays on. Critique deck link in the thread tomorrow.', 0, 10, 15],
        ]));

        $this->seedPeerDirect($mahmood, $jordan, $this->thread([
            [$mahmood, 'Staging secrets checklist is ready for a second pair of eyes.', 3, 10, 0],
            [$jordan, 'Looks clean. Any leftover tmp flags?', 3, 10, 25],
            [$mahmood, 'Two renamed this morning. Doc is in #eng.', 2, 14, 10],
            [$jordan, 'Perfect. Hold the rotate until tomorrow’s window.', 1, 9, 30],
            [$mahmood, 'Will do. I’ll note it on the deploy card.', 1, 9, 55],
            [$jordan, 'Thanks — see you at the 4 o’clock window.', 0, 15, 20],
        ]));

        $this->seedPeerDirect($mahmood, $luke, $this->thread([
            [$luke, 'Contract for the invite endpoint still says teammate in one place.', 4, 11, 5],
            [$mahmood, 'I’ll fix the OpenAPI blurb before lunch.', 4, 11, 25],
            [$luke, 'Also: pagination default feels high for the people picker.', 3, 15, 0],
            [$mahmood, 'Dropping it to 20. Thanks for catching that.', 3, 15, 20],
            [$luke, 'Green on my side after the bump.', 1, 10, 40],
        ]));

        $this->seedPeerGroup('Backend train', $mahmood, [$mahmood, $luke, $devon, $ben, $kenji, $amira, $jordan], $this->thread([
            [$mahmood, 'Afternoon train owners: Luke on API, Devon on CI, Ben on sign-off.', 5, 8, 45],
            [$devon, 'Platform job flake is quarantined with a link in the issue.', 4, 10, 10],
            [$ben, 'QA will re-run checklist after the empty-state merge.', 3, 12, 0],
            [$luke, 'Invite endpoint blurb updated — no teammate wording left.', 2, 14, 15],
            [$kenji, 'Staging deploy window stays noon–1 unless someone notes here.', 1, 9, 5],
            [$mahmood, 'Thanks all. Soft freeze at 3 as usual.', 0, 11, 30],
        ]));
    }

    /**
     * @param  array<int, array{0: User, 1: string, 2: Carbon}>  $lines
     */
    private function seedPeerDirect(User $a, User $b, array $lines): void
    {
        $conversation = Conversation::directBetween($a, $b);

        if ($conversation === null) {
            $conversation = Conversation::query()->create([
                'type' => ConversationType::Direct,
                'created_by' => $a->id,
            ]);
        }

        $conversation->users()->sync([
            $a->id => ['last_read_at' => now()],
            $b->id => ['last_read_at' => now()],
        ]);

        $this->seedLines($conversation, $lines);
    }

    /**
     * @param  array<int, User>  $members
     * @param  array<int, array{0: User, 1: string, 2: Carbon}>  $lines
     */
    private function seedPeerGroup(string $name, User $creator, array $members, array $lines): void
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
            $pivot[$member->id] = ['last_read_at' => now()];
        }

        $conversation->users()->sync($pivot);
        $this->seedLines($conversation, $lines);
    }

    /**
     * @param  array<int, array{0: User, 1: string, 2: int, 3: int, 4: int}>  $rows
     * @return array<int, array{0: User, 1: string, 2: Carbon}>
     */
    private function thread(array $rows): array
    {
        $lines = array_map(
            fn (array $row): array => [$row[0], $row[1], now()->subDays($row[2])->setTime($row[3], $row[4])],
            $rows,
        );

        // Wall-clock times later today would still look unread after markReadBy(now()).
        $latest = collect($lines)->max(fn (array $line): int => $line[2]->getTimestamp());
        $ceiling = now()->getTimestamp();

        if ($latest > $ceiling) {
            $shiftSeconds = $latest - $ceiling + 60;
            $lines = array_map(
                fn (array $line): array => [$line[0], $line[1], $line[2]->copy()->subSeconds($shiftSeconds)],
                $lines,
            );
        }

        return $lines;
    }

    /**
     * @param  array<int, array{0: User, 1: string, 2: Carbon}>  $lines
     */
    private function seedDirect(User $guest, User $friend, int $unreadFromOthers, array $lines): void
    {
        $conversation = Conversation::directBetween($guest, $friend);

        if ($conversation === null) {
            $conversation = Conversation::query()->create([
                'type' => ConversationType::Direct,
                'created_by' => $friend->id,
            ]);
        }

        $conversation->users()->sync([
            $guest->id => ['last_read_at' => $this->guestReadAt($guest, $lines, $unreadFromOthers)],
            $friend->id => ['last_read_at' => now()],
        ]);

        $this->seedLines($conversation, $lines);
    }

    /**
     * @param  array<int, User>  $members
     * @param  array<int, array{0: User, 1: string, 2: Carbon}>  $lines
     */
    private function seedGroup(string $name, User $creator, array $members, int $unreadFromOthers, array $lines): void
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

        $guest = collect($members)->first(fn (User $user): bool => $user->email === 'guest@huddle.app');
        $guestReadAt = $guest instanceof User
            ? $this->guestReadAt($guest, $lines, $unreadFromOthers)
            : now();

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
     * Place the guest's read cursor just before the last N messages from other people.
     *
     * @param  array<int, array{0: User, 1: string, 2: Carbon}>  $lines
     */
    private function guestReadAt(User $guest, array $lines, int $unreadFromOthers): Carbon
    {
        // Message times can be later today than "now" during early-morning seeds.
        $latest = collect($lines)
            ->map(fn (array $line): Carbon => $line[2])
            ->sortBy(fn (Carbon $at): int => $at->getTimestamp())
            ->last();

        if ($unreadFromOthers <= 0) {
            return $latest instanceof Carbon ? $latest->copy()->addSecond() : now()->addDay();
        }

        $fromOthers = collect($lines)
            ->filter(fn (array $line): bool => $line[0]->isNot($guest))
            ->values();

        if ($fromOthers->count() < $unreadFromOthers) {
            return now()->subDay();
        }

        /** @var Carbon $cutoff */
        $cutoff = $fromOthers->slice(-$unreadFromOthers)->first()[2];

        return $cutoff->copy()->subSecond();
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
