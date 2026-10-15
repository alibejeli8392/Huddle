<?php

namespace App\Support;

use App\Models\User;

/**
 * Sample directory, posts, and messages until those tables exist.
 * The signed-in member replaces the "u1" placeholder so names and emails stay real.
 */
class PreviewData
{
    /**
     * @return array{
     *     viewer: array<string, mixed>,
     *     members: array<int, array<string, mixed>>,
     *     posts: array<int, array<string, mixed>>,
     *     friendships: array<int, array<string, mixed>>,
     *     messages: array<int, array<string, mixed>>,
     *     groups: array<int, array<string, mixed>>,
     *     groupMessages: array<int, array<string, mixed>>
     * }
     */
    public static function forUser(User $user): array
    {
        $viewerId = (string) $user->id;

        $members = array_map(function (array $member) use ($user, $viewerId): array {
            if ($member['id'] !== 'u1') {
                return $member;
            }

            return [
                ...$member,
                'id' => $viewerId,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }, self::members());

        $posts = array_map(function (array $post) use ($viewerId): array {
            $post['authorId'] = self::swap($post['authorId'], $viewerId);
            $post['likes'] = array_map(
                fn (string $id): string => self::swap($id, $viewerId),
                $post['likes'] ?? [],
            );
            $post['comments'] = array_map(function (array $comment) use ($viewerId): array {
                $comment['authorId'] = self::swap($comment['authorId'], $viewerId);

                return $comment;
            }, $post['comments'] ?? []);

            return $post;
        }, self::posts());

        $friendships = array_map(function (array $friendship) use ($viewerId): array {
            $friendship['fromId'] = self::swap($friendship['fromId'], $viewerId);
            $friendship['toId'] = self::swap($friendship['toId'], $viewerId);

            return $friendship;
        }, self::friendships());

        $messages = array_map(function (array $message) use ($viewerId): array {
            $message['fromId'] = self::swap($message['fromId'], $viewerId);
            $message['toId'] = self::swap($message['toId'], $viewerId);

            return $message;
        }, self::messages());

        $groups = array_map(function (array $group) use ($viewerId): array {
            $group['memberIds'] = array_map(
                fn (string $id): string => self::swap($id, $viewerId),
                $group['memberIds'],
            );

            return $group;
        }, self::groups());

        $groupMessages = array_map(function (array $message) use ($viewerId): array {
            $message['fromId'] = self::swap($message['fromId'], $viewerId);

            return $message;
        }, self::groupMessages());

        $viewer = $members[0];

        foreach ($members as $member) {
            if ($member['id'] === $viewerId) {
                $viewer = $member;
                break;
            }
        }

        return [
            'viewer' => $viewer,
            'members' => $members,
            'posts' => $posts,
            'friendships' => $friendships,
            'messages' => $messages,
            'groups' => $groups,
            'groupMessages' => $groupMessages,
        ];
    }

    private static function swap(string $id, string $viewerId): string
    {
        return $id === 'u1' ? $viewerId : $id;
    }

    private static function photo(int $id): string
    {
        return "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=640&w=640";
    }

    private static function ago(float $hours): int
    {
        return (int) round((microtime(true) - ($hours * 3600)) * 1000);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function members(): array
    {
        return [
            [
                'id' => 'u1',
                'name' => 'Alex Rivera',
                'email' => 'alex@northstar.io',
                'avatar' => self::photo(1181691),
                'bio' => 'Product designer. I make the confusing parts feel obvious. Always up for a desk-side critique or a coffee walk.',
                'title' => 'Product Designer',
                'department' => 'Design',
            ],
            [
                'id' => 'u2',
                'name' => 'Jordan Blake',
                'email' => 'jordan@northstar.io',
                'avatar' => self::photo(12311546),
                'bio' => 'Engineering lead. I keep the lights on and the deploys boring. Previously: too many late nights.',
                'title' => 'Engineering Lead',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u3',
                'name' => 'Sam Okonkwo',
                'email' => 'sam@northstar.io',
                'avatar' => self::photo(37079379),
                'bio' => 'People partner. I run onboarding, offsites, and the snack budget. If you need a sounding board, my calendar is open.',
                'title' => 'People Partner',
                'department' => 'People',
            ],
            [
                'id' => 'u4',
                'name' => 'Morgan Chen',
                'email' => 'morgan@northstar.io',
                'avatar' => self::photo(26953802),
                'bio' => 'Brand & campaigns. I care about the words we put in the world — and a well-kerned headline.',
                'title' => 'Brand Marketing',
                'department' => 'Marketing',
            ],
            [
                'id' => 'u5',
                'name' => 'Taylor Kim',
                'email' => 'taylor@northstar.io',
                'avatar' => self::photo(30496625),
                'bio' => 'Full-stack engineer. I like typed APIs, quiet PRs, and lunch that isn\'t at my desk.',
                'title' => 'Software Engineer',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u6',
                'name' => 'Casey Patel',
                'email' => 'casey@northstar.io',
                'avatar' => self::photo(6076115),
                'bio' => 'Data analyst. I turn messy spreadsheets into decisions. Ask me about retention, not my fantasy league.',
                'title' => 'Data Analyst',
                'department' => 'Analytics',
            ],
            [
                'id' => 'u7',
                'name' => 'Riley Johnson',
                'email' => 'riley@northstar.io',
                'avatar' => self::photo(15200412),
                'bio' => 'Customer success. I bring the outside in. If a customer is blocked, I am already in the thread.',
                'title' => 'Customer Success',
                'department' => 'Success',
            ],
            [
                'id' => 'u8',
                'name' => 'Avery Nguyen',
                'email' => 'avery@northstar.io',
                'avatar' => self::photo(32846944),
                'bio' => 'Finance. I make the numbers add up and the expense policy make sense. Yes, that dinner was in policy.',
                'title' => 'Finance Manager',
                'department' => 'Finance',
            ],
            [
                'id' => 'u9',
                'name' => 'Quinn Morales',
                'email' => 'quinn@northstar.io',
                'avatar' => self::photo(12989198),
                'bio' => 'In-house counsel. I review the fine print so you don\'t have to. Friendly, but I will redline.',
                'title' => 'Legal Counsel',
                'department' => 'Legal',
            ],
            [
                'id' => 'u10',
                'name' => 'Jamie Brooks',
                'email' => 'jamie@northstar.io',
                'avatar' => self::photo(8154425),
                'bio' => 'Account executive. I close with curiosity, not pressure. Always happy to intro you to a customer story.',
                'title' => 'Account Executive',
                'department' => 'Sales',
            ],
            [
                'id' => 'u11',
                'name' => 'Chris Adler',
                'email' => 'chris@northstar.io',
                'avatar' => self::photo(4311291),
                'bio' => 'Operations. If something is stuck between teams, that\'s my cue. I believe in checklists and good lighting.',
                'title' => 'Operations Lead',
                'department' => 'Operations',
            ],
            [
                'id' => 'u12',
                'name' => 'Priya Shah',
                'email' => 'priya@northstar.io',
                'avatar' => self::photo(30468665),
                'bio' => 'Research. I talk to users so we don\'t have to guess. Office hours Thursday afternoons.',
                'title' => 'User Researcher',
                'department' => 'Research',
            ],
            [
                'id' => 'u13',
                'name' => 'Noah Kim',
                'email' => 'noah@northstar.io',
                'avatar' => self::photo(1181686),
                'bio' => 'Support engineer. I live in the ticket queue so you don\'t have to. If it reproduced once, send me the screen recording.',
                'title' => 'Support Engineer',
                'department' => 'Support',
            ],
            [
                'id' => 'u14',
                'name' => 'Elena Vasquez',
                'email' => 'elena@northstar.io',
                'avatar' => self::photo(774909),
                'bio' => 'Recruiter. I look for people who write clearly and ask good questions. Coffee chats are not a trap.',
                'title' => 'Recruiter',
                'department' => 'People',
            ],
            [
                'id' => 'u15',
                'name' => 'Devon Brooks',
                'email' => 'devon@northstar.io',
                'avatar' => self::photo(1222271),
                'bio' => 'Office manager. Badges, plants, and the good mugs. If a room is double-booked, I already know.',
                'title' => 'Office Manager',
                'department' => 'Workplace',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function posts(): array
    {
        return [
            [
                'id' => 'p1',
                'authorId' => 'u3',
                'text' => 'Welcome to anyone starting this week — there are notebooks and a plant on your desk, and bagels in the kitchen until 11. Come say hi.',
                'createdAt' => self::ago(1.2),
                'likes' => ['u1', 'u2', 'u4', 'u7'],
                'comments' => [
                    ['id' => 'c1', 'authorId' => 'u2', 'text' => 'Bagels noted. I\'ll be the one hovering by the kitchen.', 'createdAt' => self::ago(1)],
                    ['id' => 'c2', 'authorId' => 'u5', 'text' => 'Which desk has the plant? Asking for a friend who is me.', 'createdAt' => self::ago(0.8)],
                ],
            ],
            [
                'id' => 'p2',
                'authorId' => 'u2',
                'text' => 'Deploy window is 4–4:30 today. If you have a PR you need in, ping me before 3. Otherwise we\'ll catch it in the morning train.',
                'createdAt' => self::ago(2.4),
                'likes' => ['u5', 'u1'],
                'comments' => [
                    ['id' => 'c3', 'authorId' => 'u1', 'text' => 'Mine is in. It\'s the empty-state copy, nothing risky.', 'createdAt' => self::ago(2.1)],
                ],
            ],
            [
                'id' => 'p3',
                'authorId' => 'u4',
                'text' => 'The new homepage copy is live. If you see anything that feels off-brand (or just off), drop a note. I have thick skin and a red pen.',
                'createdAt' => self::ago(5),
                'likes' => ['u1', 'u12', 'u10'],
                'comments' => [],
            ],
            [
                'id' => 'p4',
                'authorId' => 'u7',
                'text' => 'Customer shoutout: Horizon Labs said onboarding felt “unusually human.” That\'s all of you. I sent the quote to the #wins channel.',
                'createdAt' => self::ago(8),
                'likes' => ['u1', 'u2', 'u3', 'u10', 'u6'],
                'comments' => [
                    ['id' => 'c4', 'authorId' => 'u4', 'text' => 'That line is going on the wall. With permission.', 'createdAt' => self::ago(7)],
                ],
            ],
            [
                'id' => 'p5',
                'authorId' => 'u1',
                'text' => 'Desk critique hour tomorrow at 10, 4th floor nook. Bring work that\'s 60% done — the messy stuff is more useful than the polished stuff.',
                'createdAt' => self::ago(14),
                'likes' => ['u12', 'u4', 'u5'],
                'comments' => [],
            ],
            [
                'id' => 'p6',
                'authorId' => 'u6',
                'text' => 'Quick look at last week\'s activation: up 9% after the checklist change. I\'ll share the full cut in Friday\'s review. Nice work, design + eng.',
                'createdAt' => self::ago(22),
                'likes' => ['u2', 'u1'],
                'comments' => [
                    ['id' => 'c5', 'authorId' => 'u2', 'text' => 'Send the chart before Friday if you can. I want it in the review doc.', 'createdAt' => self::ago(20)],
                ],
            ],
            [
                'id' => 'p7',
                'authorId' => 'u10',
                'text' => 'Anyone in Chicago next Tuesday? I have a gap between meetings and would love a teammate to grab lunch with a prospect.',
                'createdAt' => self::ago(28),
                'likes' => ['u7'],
                'comments' => [],
            ],
            [
                'id' => 'p8',
                'authorId' => 'u11',
                'text' => 'The 3rd floor espresso machine has been revived. You\'re welcome. Please do not attempt repairs yourselves. I have a log.',
                'createdAt' => self::ago(36),
                'likes' => ['u1', 'u2', 'u3', 'u4', 'u5', 'u8'],
                'comments' => [
                    ['id' => 'c6', 'authorId' => 'u1', 'text' => 'I owe you a coffee. The previous machine had opinions.', 'createdAt' => self::ago(30)],
                ],
            ],
            [
                'id' => 'p9',
                'authorId' => 'u12',
                'text' => 'Five interviews in, a pattern: people get stuck naming a project on create. I\'ll write up findings Thursday. If you\'ve seen this too, tell me.',
                'createdAt' => self::ago(48),
                'likes' => ['u1', 'u5'],
                'comments' => [],
            ],
            [
                'id' => 'p10',
                'authorId' => 'u8',
                'text' => 'Expense reminder: Q1 close is Friday. If a receipt is living in your camera roll, now\'s the moment. I believe in you.',
                'createdAt' => self::ago(52),
                'likes' => ['u9'],
                'comments' => [
                    ['id' => 'c7', 'authorId' => 'u9', 'text' => 'Please don\'t make me hunt through Slack for the receipt policy again.', 'createdAt' => self::ago(50)],
                ],
            ],
            [
                'id' => 'p11',
                'authorId' => 'u5',
                'text' => 'Shipped the search empty state. It now says what to type instead of staring back. Small thing, but it was rude before.',
                'createdAt' => self::ago(3.4),
                'likes' => ['u1', 'u2', 'u12'],
                'comments' => [
                    ['id' => 'c8', 'authorId' => 'u12', 'text' => 'That matches what people said in interviews. Nice.', 'createdAt' => self::ago(3)],
                ],
            ],
            [
                'id' => 'p12',
                'authorId' => 'u13',
                'text' => 'If a customer says "it\'s just slow," ask which screen. I have three different slows and only one of them is ours.',
                'createdAt' => self::ago(6.5),
                'likes' => ['u2', 'u7', 'u1'],
                'comments' => [],
            ],
            [
                'id' => 'p13',
                'authorId' => 'u14',
                'text' => 'Two engineers are visiting Thursday. If you have a project you\'re proud of, I would love a 10-minute tour, not a slide deck.',
                'createdAt' => self::ago(11),
                'likes' => ['u3', 'u1', 'u5', 'u15'],
                'comments' => [
                    ['id' => 'c9', 'authorId' => 'u3', 'text' => 'I can do the tour. No slides, I promise.', 'createdAt' => self::ago(10)],
                ],
            ],
            [
                'id' => 'p14',
                'authorId' => 'u15',
                'text' => 'The 4th floor nook is reserved 10–11 tomorrow for the critique. I moved the extra chairs. Please don\'t steal them back.',
                'createdAt' => self::ago(16),
                'likes' => ['u1', 'u4'],
                'comments' => [],
            ],
            [
                'id' => 'p15',
                'authorId' => 'u9',
                'text' => 'Reminder that the vendor agreement does not cover "we\'ll figure out data residency later." I have redlines. They are friendly redlines.',
                'createdAt' => self::ago(26),
                'likes' => ['u8', 'u2'],
                'comments' => [
                    ['id' => 'c10', 'authorId' => 'u10', 'text' => 'Sending you the latest draft after lunch. Please be gentle.', 'createdAt' => self::ago(24)],
                ],
            ],
            [
                'id' => 'p16',
                'authorId' => 'u1',
                'text' => 'Posted the critique notes in the design folder. Two screens still feel like a form from 2009. I marked them.',
                'createdAt' => self::ago(33),
                'likes' => ['u4', 'u12', 'u3'],
                'comments' => [],
            ],
            [
                'id' => 'p17',
                'authorId' => 'u6',
                'text' => 'Retention dipped on day 3, not day 1. The welcome email is fine. The second task in the checklist is where people stall.',
                'createdAt' => self::ago(40),
                'likes' => ['u1', 'u2', 'u5', 'u14'],
                'comments' => [
                    ['id' => 'c11', 'authorId' => 'u5', 'text' => 'I can shorten that step this week if design is free tomorrow.', 'createdAt' => self::ago(38)],
                ],
            ],
            [
                'id' => 'p18',
                'authorId' => 'u4',
                'text' => 'The launch post is drafted. I stole one sentence from Priya\'s research notes and one from Alex\'s critique. Credit is in the doc.',
                'createdAt' => self::ago(60),
                'likes' => ['u1', 'u12', 'u3', 'u10'],
                'comments' => [],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function friendships(): array
    {
        return [
            ['fromId' => 'u1', 'toId' => 'u2', 'status' => 'accepted'],
            ['fromId' => 'u3', 'toId' => 'u1', 'status' => 'accepted'],
            ['fromId' => 'u1', 'toId' => 'u4', 'status' => 'accepted'],
            ['fromId' => 'u7', 'toId' => 'u1', 'status' => 'accepted'],
            ['fromId' => 'u6', 'toId' => 'u1', 'status' => 'pending'],
            ['fromId' => 'u8', 'toId' => 'u1', 'status' => 'pending'],
            ['fromId' => 'u1', 'toId' => 'u9', 'status' => 'pending'],
            ['fromId' => 'u2', 'toId' => 'u5', 'status' => 'accepted'],
            ['fromId' => 'u3', 'toId' => 'u4', 'status' => 'accepted'],
            ['fromId' => 'u10', 'toId' => 'u7', 'status' => 'accepted'],
            ['fromId' => 'u5', 'toId' => 'u1', 'status' => 'accepted'],
            ['fromId' => 'u1', 'toId' => 'u12', 'status' => 'accepted'],
            ['fromId' => 'u10', 'toId' => 'u1', 'status' => 'accepted'],
            ['fromId' => 'u11', 'toId' => 'u1', 'status' => 'pending'],
            ['fromId' => 'u13', 'toId' => 'u1', 'status' => 'pending'],
            ['fromId' => 'u1', 'toId' => 'u14', 'status' => 'pending'],
            ['fromId' => 'u1', 'toId' => 'u15', 'status' => 'pending'],
            ['fromId' => 'u14', 'toId' => 'u3', 'status' => 'accepted'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function messages(): array
    {
        return [
            [
                'id' => 'm1',
                'fromId' => 'u2',
                'toId' => 'u1',
                'text' => 'Hey — can you glance at the empty state for search before I ship it?',
                'createdAt' => self::ago(3.1),
                'unread' => false,
            ],
            [
                'id' => 'm2',
                'fromId' => 'u1',
                'toId' => 'u2',
                'text' => 'Yes. Send the build and I\'ll look after standup.',
                'createdAt' => self::ago(3),
                'unread' => false,
            ],
            [
                'id' => 'm3',
                'fromId' => 'u2',
                'toId' => 'u1',
                'text' => 'Just dropped it in the PR. No rush if standup runs long.',
                'createdAt' => self::ago(2.6),
                'unread' => true,
            ],
            [
                'id' => 'm4',
                'fromId' => 'u3',
                'toId' => 'u1',
                'text' => 'Would you be open to a 20-min chat with two new designers next week? Totally informal.',
                'createdAt' => self::ago(6),
                'unread' => false,
            ],
            [
                'id' => 'm5',
                'fromId' => 'u1',
                'toId' => 'u3',
                'text' => 'Absolutely. Tuesday or Wednesday afternoon works best.',
                'createdAt' => self::ago(5.5),
                'unread' => false,
            ],
            [
                'id' => 'm6',
                'fromId' => 'u4',
                'toId' => 'u1',
                'text' => 'Loved the critique notes. Mind if I pull two lines into the launch post?',
                'createdAt' => self::ago(10),
                'unread' => true,
            ],
            [
                'id' => 'm7',
                'fromId' => 'u5',
                'toId' => 'u1',
                'text' => 'Empty state is up. The hint text is still a guess — tell me if it sounds like a manual.',
                'createdAt' => self::ago(4),
                'unread' => false,
            ],
            [
                'id' => 'm8',
                'fromId' => 'u1',
                'toId' => 'u5',
                'text' => 'It sounds like a person. Ship it.',
                'createdAt' => self::ago(3.6),
                'unread' => false,
            ],
            [
                'id' => 'm9',
                'fromId' => 'u7',
                'toId' => 'u1',
                'text' => 'Horizon wants to quote us again. Can I use your critique line about messy work?',
                'createdAt' => self::ago(9),
                'unread' => true,
            ],
            [
                'id' => 'm10',
                'fromId' => 'u12',
                'toId' => 'u1',
                'text' => 'Thursday office hours are full, but I saved you the 2pm slot if the naming problem is still bothering you.',
                'createdAt' => self::ago(15),
                'unread' => false,
            ],
            [
                'id' => 'm11',
                'fromId' => 'u1',
                'toId' => 'u12',
                'text' => '2pm is perfect. I\'ll bring the three worst project names we\'ve shipped.',
                'createdAt' => self::ago(14),
                'unread' => false,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function groups(): array
    {
        return [
            [
                'id' => 'g1',
                'name' => 'Launch crew',
                'memberIds' => ['u1', 'u2', 'u4', 'u5'],
            ],
            [
                'id' => 'g2',
                'name' => 'People & welcome',
                'memberIds' => ['u1', 'u3', 'u7', 'u14'],
            ],
            [
                'id' => 'g3',
                'name' => 'Friday lunch',
                'memberIds' => ['u1', 'u10', 'u12', 'u15'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function groupMessages(): array
    {
        return [
            [
                'id' => 'gm1',
                'groupId' => 'g1',
                'fromId' => 'u2',
                'text' => 'Deploy is 4. If your PR is not green by 3, it waits.',
                'createdAt' => self::ago(5),
                'sharedPost' => null,
                'unread' => false,
            ],
            [
                'id' => 'gm2',
                'groupId' => 'g1',
                'fromId' => 'u4',
                'text' => 'Copy is locked except the headline. I can still change that one.',
                'createdAt' => self::ago(4.5),
                'sharedPost' => null,
                'unread' => false,
            ],
            [
                'id' => 'gm3',
                'groupId' => 'g1',
                'fromId' => 'u5',
                'text' => 'Empty state is in the release branch.',
                'createdAt' => self::ago(4.2),
                'sharedPost' => null,
                'unread' => true,
            ],
            [
                'id' => 'gm4',
                'groupId' => 'g2',
                'fromId' => 'u3',
                'text' => 'New folks start Monday. Notebooks are on the desks.',
                'createdAt' => self::ago(8),
                'sharedPost' => null,
                'unread' => false,
            ],
            [
                'id' => 'gm5',
                'groupId' => 'g2',
                'fromId' => 'u7',
                'text' => 'I\'ll walk them through a customer call, not a slide.',
                'createdAt' => self::ago(7.4),
                'sharedPost' => null,
                'unread' => true,
            ],
            [
                'id' => 'gm6',
                'groupId' => 'g3',
                'fromId' => 'u10',
                'text' => 'Thursday, not Friday — I fly out at 2. Noodles on 2nd?',
                'createdAt' => self::ago(20),
                'sharedPost' => null,
                'unread' => false,
            ],
            [
                'id' => 'gm7',
                'groupId' => 'g3',
                'fromId' => 'u15',
                'text' => 'I can hold the corner table if you tell me a time.',
                'createdAt' => self::ago(19),
                'sharedPost' => null,
                'unread' => false,
            ],
        ];
    }
}
