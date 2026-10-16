<?php

namespace Database\Seeders;

/**
 * Sample company members and posts for DemoSeeder.
 */
class DemoData
{
    private static function photo(int $id): string
    {
        return "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w=400&h=400&fit=crop";
    }

    /**
     * Milliseconds since epoch for a post created this many days ago at a wall-clock time.
     */
    private static function at(float $daysAgo, int $hour = 10, int $minute = 0): int
    {
        return (int) now()
            ->subDays((int) floor($daysAgo))
            ->subHours((int) round(($daysAgo - floor($daysAgo)) * 24))
            ->setTime($hour, $minute)
            ->getTimestampMs();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function sampleMembers(): array
    {
        return self::members();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function samplePosts(): array
    {
        return self::posts();
    }

    /**
     * Emails owned by the fictional demo company (plus guest/manager accounts).
     *
     * @return list<string>
     */
    public static function demoEmails(): array
    {
        $emails = array_map(
            fn (array $member): string => $member['email'],
            self::members(),
        );

        return array_values(array_unique([
            ...$emails,
            'guest@huddle.app',
            'manager@huddle.app',
        ]));
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
                'bio' => 'I design the flows people stumble through first. Outside work I climb indoors and keep a ridiculous plant collection on my monitor shelf.',
                'title' => 'Product Designer',
                'department' => 'Design',
            ],
            [
                'id' => 'u2',
                'name' => 'Jordan Blake',
                'email' => 'jordan@northstar.io',
                'avatar' => self::photo(12311546),
                'bio' => 'I lead the product engineering group and care a lot about boring deploys. Weekends are for trail runs and very opinionated coffee.',
                'title' => 'Engineering Lead',
                'department' => 'Engineering',
                'use_demo_password' => true,
            ],
            [
                'id' => 'u3',
                'name' => 'Sam Okonkwo',
                'email' => 'sam@northstar.io',
                'avatar' => self::photo(37079379),
                'bio' => 'I run onboarding and the rhythms that make a new week feel human. If you need a sounding board, bring tea — I almost always say yes.',
                'title' => 'People Partner',
                'department' => 'People',
            ],
            [
                'id' => 'u4',
                'name' => 'Morgan Chen',
                'email' => 'morgan@northstar.io',
                'avatar' => self::photo(26953802),
                'bio' => 'Brand and campaigns. I obsess over headlines and the one sentence a customer will remember. I also bake for every launch, whether anyone asked.',
                'title' => 'Brand Marketing',
                'department' => 'Marketing',
            ],
            [
                'id' => 'u5',
                'name' => 'Taylor Kim',
                'email' => 'taylor@northstar.io',
                'avatar' => self::photo(30496625),
                'bio' => 'Full-stack engineer. I like typed APIs, quiet pull requests, and lunch that is not at my desk. I will notice if your empty state is rude.',
                'title' => 'Software Engineer',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u6',
                'name' => 'Casey Patel',
                'email' => 'casey@northstar.io',
                'avatar' => self::photo(6076115),
                'bio' => 'I turn campaign data into decisions marketing can act on the same week. Ask me about retention curves, not my fantasy football roster.',
                'title' => 'Growth Analyst',
                'department' => 'Marketing',
            ],
            [
                'id' => 'u7',
                'name' => 'Riley Johnson',
                'email' => 'riley@northstar.io',
                'avatar' => self::photo(15200412),
                'bio' => 'Customer success. I bring the outside voice into our rooms. If a customer is blocked, I am already in the thread with a next step.',
                'title' => 'Customer Success',
                'department' => 'Support',
            ],
            [
                'id' => 'u8',
                'name' => 'Avery Nguyen',
                'email' => 'avery@northstar.io',
                'avatar' => self::photo(32846944),
                'bio' => 'Enterprise sales. I close with curiosity, not pressure, and I keep a running list of customer stories worth sharing. Always down for a walk-and-talk.',
                'title' => 'Account Executive',
                'department' => 'Sales',
            ],
            [
                'id' => 'u9',
                'name' => 'Quinn Morales',
                'email' => 'quinn@northstar.io',
                'avatar' => self::photo(12989198),
                'bio' => 'I partner with managers on hiring loops and leveling. I write clearly and expect the same. Side project: a terrible but sincere office playlist.',
                'title' => 'People Operations',
                'department' => 'People',
            ],
            [
                'id' => 'u10',
                'name' => 'Jamie Brooks',
                'email' => 'jamie@northstar.io',
                'avatar' => self::photo(8154425),
                'bio' => 'Mid-market sales. I like demos that feel like a conversation. If you have a customer quote that is not polished yet, send it — I will protect the voice.',
                'title' => 'Account Executive',
                'department' => 'Sales',
            ],
            [
                'id' => 'u11',
                'name' => 'Chris Adler',
                'email' => 'chris@northstar.io',
                'avatar' => self::photo(4311291),
                'bio' => 'Support lead. I live between the ticket queue and eng triage so customers do not have to. I keep a log of which "slow" means what.',
                'title' => 'Support Lead',
                'department' => 'Support',
            ],
            [
                'id' => 'u12',
                'name' => 'Priya Shah',
                'email' => 'priya@northstar.io',
                'avatar' => self::photo(30468665),
                'bio' => 'I talk to users so we do not have to guess. Office hours Thursday afternoons; bring a half-baked question and I will help sharpen it.',
                'title' => 'Design Researcher',
                'department' => 'Design',
            ],
            [
                'id' => 'u13',
                'name' => 'Noah Kim',
                'email' => 'noah@northstar.io',
                'avatar' => self::photo(1181686),
                'bio' => 'Support engineer. If it reproduced once, send the screen recording. I also maintain the internal FAQ nobody admits they read.',
                'title' => 'Support Engineer',
                'department' => 'Support',
            ],
            [
                'id' => 'u14',
                'name' => 'Elena Vasquez',
                'email' => 'elena@northstar.io',
                'avatar' => self::photo(774909),
                'bio' => 'Recruiter. I look for people who write clearly and ask good questions. Coffee chats are not a trap — I mean that.',
                'title' => 'Recruiter',
                'department' => 'People',
            ],
            [
                'id' => 'u15',
                'name' => 'Devon Brooks',
                'email' => 'devon@northstar.io',
                'avatar' => self::photo(1222271),
                'bio' => 'Platform engineer. I keep CI honest and the staging environment less haunted. I will notice if your flaky test is actually a real bug.',
                'title' => 'Platform Engineer',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u16',
                'name' => 'Harper Diaz',
                'email' => 'harper@northstar.io',
                'avatar' => self::photo(3184338),
                'bio' => 'Content and lifecycle. I write the emails people actually finish. Quirk: I name every draft after a street near the office.',
                'title' => 'Content Marketer',
                'department' => 'Marketing',
            ],
            [
                'id' => 'u17',
                'name' => 'Marcus Okeke',
                'email' => 'marcus@northstar.io',
                'avatar' => self::photo(614810),
                'bio' => 'Sales engineer. I turn product quirks into demo strengths and never pretend a feature exists. Lunch preference: wherever has outdoor seating.',
                'title' => 'Sales Engineer',
                'department' => 'Sales',
            ],
            [
                'id' => 'u18',
                'name' => 'Sofia Berg',
                'email' => 'sofia@northstar.io',
                'avatar' => self::photo(1043471),
                'bio' => 'I build the frontend pieces designers sketch on sticky notes. I keep a notebook of microcopy fails and celebrate when we retire one.',
                'title' => 'Frontend Engineer',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u19',
                'name' => 'Mia Alvarez',
                'email' => 'mia@northstar.io',
                'avatar' => self::photo(415829),
                'bio' => 'Visual designer focused on illustration and empty states that feel kind. I sketch on the bus and somehow never lose my pen.',
                'title' => 'Visual Designer',
                'department' => 'Design',
            ],
            [
                'id' => 'u20',
                'name' => 'Owen Park',
                'email' => 'owen@northstar.io',
                'avatar' => self::photo(220453),
                'bio' => 'Interaction designer. I care about the awkward middle of a flow, not just the happy path. Board games are serious business to me.',
                'title' => 'Interaction Designer',
                'department' => 'Design',
            ],
            [
                'id' => 'u21',
                'name' => 'Jade Nguyen',
                'email' => 'jade@northstar.io',
                'avatar' => self::photo(733872),
                'bio' => 'I tend the design system so buttons do not invent themselves. Weekends: ceramics and very neat spreadsheets.',
                'title' => 'Design Systems',
                'department' => 'Design',
            ],
            [
                'id' => 'u22',
                'name' => 'Nina Kowalski',
                'email' => 'nina@northstar.io',
                'avatar' => self::photo(91227),
                'bio' => 'Product designer for onboarding. I rewrite one confusing sentence a day and call it a win.',
                'title' => 'Product Designer',
                'department' => 'Design',
            ],
            [
                'id' => 'u23',
                'name' => 'Luke Hartmann',
                'email' => 'luke@northstar.io',
                'avatar' => self::photo(2102415),
                'bio' => 'Backend engineer. I like clear contracts between services and PRs that explain the why. Trail running when the weather cooperates.',
                'title' => 'Backend Engineer',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u24',
                'name' => 'Amira Hassan',
                'email' => 'amira@northstar.io',
                'avatar' => self::photo(1516680),
                'bio' => 'Mobile engineer. I chase the last 5% of polish on small screens. I also keep the office plant watering schedule honest.',
                'title' => 'Mobile Engineer',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u25',
                'name' => 'Ben Ortiz',
                'email' => 'ben@northstar.io',
                'avatar' => self::photo(2128807),
                'bio' => 'QA engineer. I break things politely and write reproduction steps that actually reproduce. Crossword puzzles at lunch.',
                'title' => 'QA Engineer',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u26',
                'name' => 'Kenji Sato',
                'email' => 'kenji@northstar.io',
                'avatar' => self::photo(2182970),
                'bio' => 'Infra engineer. I make deploys dull on purpose. Hot take: staging should feel boring.',
                'title' => 'Infrastructure Engineer',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u27',
                'name' => 'Tara Singh',
                'email' => 'tara@northstar.io',
                'avatar' => self::photo(1681010),
                'bio' => 'L&D partner. I turn messy tribal knowledge into short guides people finish. Always carrying sticky notes.',
                'title' => 'Learning Partner',
                'department' => 'People',
            ],
            [
                'id' => 'u28',
                'name' => 'Iris Chen',
                'email' => 'iris@northstar.io',
                'avatar' => self::photo(1845534),
                'bio' => 'People analytics. I translate survey noise into one useful chart. I will defend a good coffee break.',
                'title' => 'People Analyst',
                'department' => 'People',
            ],
            [
                'id' => 'u29',
                'name' => 'Gabe Romano',
                'email' => 'gabe@northstar.io',
                'avatar' => self::photo(2379004),
                'bio' => 'Product marketing. I write launch notes that sound like humans. Vinyl collecting is getting out of hand.',
                'title' => 'Product Marketing',
                'department' => 'Marketing',
            ],
            [
                'id' => 'u30',
                'name' => 'Zoe Okonkwo',
                'email' => 'zoe@northstar.io',
                'avatar' => self::photo(1858175),
                'bio' => 'Social and community. I notice when a customer post deserves an internal shout-out. Runner, early mornings only.',
                'title' => 'Community Manager',
                'department' => 'Marketing',
            ],
            [
                'id' => 'u31',
                'name' => 'Nadia Rahman',
                'email' => 'nadia@northstar.io',
                'avatar' => self::photo(1987301),
                'bio' => 'Account manager. I keep renewals calm and surprises rare. I remember how everyone takes their tea.',
                'title' => 'Account Manager',
                'department' => 'Sales',
            ],
            [
                'id' => 'u32',
                'name' => 'Cole Bennett',
                'email' => 'cole@northstar.io',
                'avatar' => self::photo(2379005),
                'bio' => 'SDR. I book meetings without being annoying — that is the goal. Climbing gym after work most Tuesdays.',
                'title' => 'Sales Development',
                'department' => 'Sales',
            ],
            [
                'id' => 'u33',
                'name' => 'Felix Moreau',
                'email' => 'felix@northstar.io',
                'avatar' => self::photo(2709388),
                'bio' => 'Support specialist. I write replies that solve the thing and leave the person less stressed. Bread baking on Sundays.',
                'title' => 'Support Specialist',
                'department' => 'Support',
            ],
            [
                'id' => 'u34',
                'name' => 'Rupa Iyer',
                'email' => 'rupa@northstar.io',
                'avatar' => self::photo(2269872),
                'bio' => 'Support specialist focused on billing puzzles. I keep a glossary of invoice words nobody should need.',
                'title' => 'Support Specialist',
                'department' => 'Support',
            ],
            [
                'id' => 'u35',
                'name' => 'Otto Berg',
                'email' => 'otto@northstar.io',
                'avatar' => self::photo(2897883),
                'bio' => 'Operations. If something is stuck between teams, that is my cue. I believe in checklists and good lighting.',
                'title' => 'Operations Lead',
                'department' => 'Operations',
            ],
            [
                'id' => 'u36',
                'name' => 'Lena Petrov',
                'email' => 'lena@northstar.io',
                'avatar' => self::photo(2380794),
                'bio' => 'Workplace ops. Badges, rooms, and the snack order. If a room is double-booked, I already know.',
                'title' => 'Workplace Coordinator',
                'department' => 'Operations',
            ],
            [
                'id' => 'u37',
                'name' => 'Will Torres',
                'email' => 'will@northstar.io',
                'avatar' => self::photo(3184292),
                'bio' => 'IT and devices. I make laptops boringly reliable. Ask me before you buy a mystery USB hub.',
                'title' => 'IT Specialist',
                'department' => 'Operations',
            ],
            [
                'id' => 'u38',
                'name' => 'Anika Bose',
                'email' => 'anika@northstar.io',
                'avatar' => self::photo(2613260),
                'bio' => 'Data engineer. I clean pipelines so analysts do not invent numbers. Chess at lunch if anyone is brave.',
                'title' => 'Data Engineer',
                'department' => 'Engineering',
            ],
            [
                'id' => 'u39',
                'name' => 'Ali Bejeli',
                'email' => 'a.bejeli.8392@westcliff.edu',
                'avatar' => null,
                'bio' => 'I ship frontend pieces that feel quiet on a small screen. Ask me about empty states, not my backlog of half-finished side projects.',
                'title' => 'Frontend Developer',
                'department' => 'Engineering',
                'role' => 'manager',
                'use_demo_password' => true,
            ],
            [
                'id' => 'u40',
                'name' => 'Mahmood Ayaz',
                'email' => 'm.ayaz.9754@westcliff.edu',
                'avatar' => null,
                'bio' => 'I keep APIs boring and deploy windows short. Outside work I cook for friends and pretend I will finish that book.',
                'title' => 'Backend Developer',
                'department' => 'Engineering',
                'role' => 'manager',
                'use_demo_password' => true,
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
                'text' => 'Welcome to anyone starting this week — notebooks and a plant are on your desk, and bagels are in the kitchen until 11. Come say hi; I will introduce you to whoever is free.',
                'createdAt' => self::at(0.2, 8, 40),
                'likes' => ['u1', 'u2', 'u4', 'u7', 'u14', 'u18', 'u19', 'u36'],
                'comments' => [
                    [
                        'id' => 'c1',
                        'authorId' => 'u14',
                        'text' => 'I put spare chargers in the welcome tote too. Grab one if your laptop is always at 12%.',
                        'createdAt' => self::at(0.15, 9, 5),
                    ],
                    [
                        'id' => 'c2',
                        'authorId' => 'u5',
                        'text' => 'Which desk has the fern? Asking for a friend who is me.',
                        'createdAt' => self::at(0.1, 9, 22),
                    ],
                    [
                        'id' => 'c3',
                        'authorId' => 'u3',
                        'text' => 'Fourth row by the window. Fern answers to Fern.',
                        'createdAt' => self::at(0.08, 9, 40),
                    ],
                ],
            ],
            [
                'id' => 'p2',
                'authorId' => 'u2',
                'text' => 'Deploy window is 4–4:30 today. If you have a PR you need in, ping me before 3. Otherwise we catch it on the morning train.',
                'createdAt' => self::at(0.4, 11, 10),
                'likes' => ['u5', 'u1', 'u15', 'u18', 'u23', 'u26'],
                'comments' => [
                    [
                        'id' => 'c4',
                        'authorId' => 'u1',
                        'text' => 'Mine is in — checklist microcopy only. Nothing that should scare staging.',
                        'createdAt' => self::at(0.35, 11, 35),
                    ],
                    [
                        'id' => 'c5',
                        'authorId' => 'u15',
                        'text' => 'CI is green on main. I will watch the canary for the first ten minutes.',
                        'createdAt' => self::at(0.3, 12, 0),
                    ],
                ],
            ],
            [
                'id' => 'p3',
                'authorId' => 'u5',
                'text' => 'Shipped the search empty state. It now suggests what to type instead of staring back blank. Small thing, but it was rude before.',
                'createdAt' => self::at(0.6, 15, 20),
                'likes' => ['u1', 'u2', 'u12', 'u18', 'u4', 'u7', 'u16', 'u22'],
                'comments' => [
                    [
                        'id' => 'c6',
                        'authorId' => 'u12',
                        'text' => 'That matches what people said in last week’s interviews. Nice catch.',
                        'createdAt' => self::at(0.55, 15, 45),
                    ],
                    [
                        'id' => 'c7',
                        'authorId' => 'u18',
                        'text' => 'I stole your suggestion chips pattern for the filters panel. Credit is in the PR.',
                        'createdAt' => self::at(0.5, 16, 10),
                    ],
                ],
            ],
            [
                'id' => 'p4',
                'authorId' => 'u7',
                'text' => 'Customer shout-out: Horizon Labs said onboarding felt “unusually human.” That is all of you. Full quote is in #wins.',
                'createdAt' => self::at(1.1, 9, 15),
                'likes' => ['u1', 'u2', 'u3', 'u4', 'u10', 'u8', 'u16', 'u17', 'u14', 'u31'],
                'comments' => [
                    [
                        'id' => 'c8',
                        'authorId' => 'u4',
                        'text' => 'Asking for permission to put that line on the wall. Literally.',
                        'createdAt' => self::at(1.0, 10, 5),
                    ],
                    [
                        'id' => 'c9',
                        'authorId' => 'u10',
                        'text' => 'Horizon’s AE is Jamie — I’ll loop them on the thank-you note.',
                        'createdAt' => self::at(0.95, 10, 40),
                    ],
                ],
            ],
            [
                'id' => 'p5',
                'authorId' => 'u4',
                'text' => 'Homepage copy is live. If something feels off-brand (or just off), drop a note in the doc — I have thick skin and a red pen.',
                'createdAt' => self::at(1.4, 14, 0),
                'likes' => ['u1', 'u12', 'u10', 'u16', 'u29'],
                'comments' => [
                    [
                        'id' => 'c10',
                        'authorId' => 'u16',
                        'text' => 'The second paragraph still uses “leverage.” I left a quieter alternative.',
                        'createdAt' => self::at(1.35, 14, 30),
                    ],
                ],
            ],
            [
                'id' => 'p6',
                'authorId' => 'u12',
                'text' => 'Quick poll for anyone who has sat with a new user: when they create a project, do they (A) name it immediately, (B) skip and rename later, or (C) freeze for a while? Drop a letter — I am tallying for Thursday’s write-up.',
                'createdAt' => self::at(1.8, 11, 20),
                'likes' => ['u1', 'u5', 'u3', 'u18', 'u6', 'u20', 'u22'],
                'comments' => [
                    [
                        'id' => 'c11',
                        'authorId' => 'u1',
                        'text' => 'Mostly C in my sessions. They stare at the field like it asked for their password.',
                        'createdAt' => self::at(1.7, 11, 50),
                    ],
                    [
                        'id' => 'c12',
                        'authorId' => 'u5',
                        'text' => 'B for power users, C for everyone else. We should show examples.',
                        'createdAt' => self::at(1.65, 12, 15),
                    ],
                    [
                        'id' => 'c13',
                        'authorId' => 'u7',
                        'text' => 'Customers tell me A only after the third project. First one is always C.',
                        'createdAt' => self::at(1.6, 13, 0),
                    ],
                    [
                        'id' => 'c14',
                        'authorId' => 'u18',
                        'text' => 'I vote we seed three example names and let them edit. Happy to spike it.',
                        'createdAt' => self::at(1.55, 13, 40),
                    ],
                ],
            ],
            [
                'id' => 'p7',
                'authorId' => 'u11',
                'text' => 'Reminder: if a customer says “it’s just slow,” ask which screen. We have three different slows and only one of them is ours this week.',
                'createdAt' => self::at(2.2, 8, 50),
                'likes' => ['u2', 'u7', 'u13', 'u1', 'u15', 'u33'],
                'comments' => [
                    [
                        'id' => 'c15',
                        'authorId' => 'u13',
                        'text' => 'Export page is the real one right now. I have a loom if you need it.',
                        'createdAt' => self::at(2.1, 9, 20),
                    ],
                ],
            ],
            [
                'id' => 'p8',
                'authorId' => 'u1',
                'text' => 'Desk critique tomorrow at 10 in the 4th floor nook. Bring work that is about 60% done — the messy stuff helps more than the polished stuff.',
                'createdAt' => self::at(2.5, 16, 10),
                'likes' => ['u12', 'u4', 'u5', 'u18', 'u3', 'u19', 'u20', 'u21'],
                'comments' => [
                    [
                        'id' => 'c16',
                        'authorId' => 'u12',
                        'text' => 'I will bring the create-project sketches. Warning: they are ugly on purpose.',
                        'createdAt' => self::at(2.4, 16, 45),
                    ],
                    [
                        'id' => 'c16b',
                        'authorId' => 'u20',
                        'text' => 'Saving a seat. Bringing the checklist step that still feels cold.',
                        'createdAt' => self::at(2.35, 17, 5),
                    ],
                ],
            ],
            [
                'id' => 'p9',
                'authorId' => 'u6',
                'text' => 'Activation was up 9% last week after the checklist change. I will share the full cut in Friday’s review — design and eng, this one is yours.',
                'createdAt' => self::at(3.0, 10, 0),
                'likes' => ['u2', 'u1', 'u5', 'u4', 'u16', 'u18', 'u3', 'u12', 'u22'],
                'comments' => [
                    [
                        'id' => 'c17',
                        'authorId' => 'u2',
                        'text' => 'Send the chart before Friday if you can. I want it in the review doc.',
                        'createdAt' => self::at(2.9, 10, 35),
                    ],
                    [
                        'id' => 'c18',
                        'authorId' => 'u1',
                        'text' => 'Happy to walk through what we changed in the second step if that helps the story.',
                        'createdAt' => self::at(2.85, 11, 0),
                    ],
                ],
            ],
            [
                'id' => 'p10',
                'authorId' => 'u14',
                'text' => 'Two engineering candidates are visiting Thursday. If you have a project you are proud of, I would love a 10-minute tour — not a slide deck.',
                'createdAt' => self::at(3.3, 13, 30),
                'likes' => ['u3', 'u1', 'u5', 'u15', 'u2', 'u9', 'u23'],
                'comments' => [
                    [
                        'id' => 'c19',
                        'authorId' => 'u15',
                        'text' => 'I can show the CI dashboard and why we care about flake budgets. No slides.',
                        'createdAt' => self::at(3.2, 14, 0),
                    ],
                    [
                        'id' => 'c20',
                        'authorId' => 'u3',
                        'text' => 'I will own the hallway intros so you do not get ambushed by five people at once.',
                        'createdAt' => self::at(3.15, 14, 25),
                    ],
                ],
            ],
            [
                'id' => 'p11',
                'authorId' => 'u10',
                'text' => 'Anyone in Chicago next Tuesday? I have a gap between meetings and would love a teammate along for lunch with a prospect.',
                'createdAt' => self::at(3.7, 9, 5),
                'likes' => ['u7', 'u8', 'u17', 'u32'],
                'comments' => [
                    [
                        'id' => 'c21',
                        'authorId' => 'u17',
                        'text' => 'I land at 10:40. If lunch is after 12:30 I can join and bring the demo laptop.',
                        'createdAt' => self::at(3.6, 9, 40),
                    ],
                ],
            ],
            [
                'id' => 'p12',
                'authorId' => 'u16',
                'text' => 'Lifecycle email #3 is in review. Subject line options are in the doc — vote with a 🔥 on the one you would open. No wrong answers, only quieter ones.',
                'createdAt' => self::at(4.1, 15, 0),
                'likes' => ['u4', 'u6', 'u3', 'u1', 'u29'],
                'comments' => [
                    [
                        'id' => 'c22',
                        'authorId' => 'u4',
                        'text' => 'Option B. A tries too hard. C sounds like a password reset.',
                        'createdAt' => self::at(4.0, 15, 30),
                    ],
                    [
                        'id' => 'c23',
                        'authorId' => 'u6',
                        'text' => 'B also won our tiny sample last month. Boring is fine.',
                        'createdAt' => self::at(3.95, 16, 0),
                    ],
                ],
            ],
            [
                'id' => 'p13',
                'authorId' => 'u15',
                'text' => 'Staging was haunted again this morning. Root cause: a leftover feature flag from last Thursday. I cleared it and added a check so we do not re-haunt ourselves.',
                'createdAt' => self::at(4.5, 8, 20),
                'likes' => ['u2', 'u5', 'u18', 'u13', 'u1', 'u11', 'u26'],
                'comments' => [
                    [
                        'id' => 'c24',
                        'authorId' => 'u2',
                        'text' => 'Thank you. Also thank you for not naming the flag after a meme this time.',
                        'createdAt' => self::at(4.4, 8, 55),
                    ],
                    [
                        'id' => 'c25',
                        'authorId' => 'u18',
                        'text' => 'Was that why filters looked like 2019 for ten minutes? Asking for science.',
                        'createdAt' => self::at(4.35, 9, 15),
                    ],
                    [
                        'id' => 'c26',
                        'authorId' => 'u15',
                        'text' => 'Yes. Science regrets its choices.',
                        'createdAt' => self::at(4.3, 9, 30),
                    ],
                ],
            ],
            [
                'id' => 'p14',
                'authorId' => 'u8',
                'text' => 'Closed Meridian after a six-week cycle. Biggest unlock was Marcus’s demo of the audit log — they stopped asking for a custom report mid-call.',
                'createdAt' => self::at(5.0, 17, 10),
                'likes' => ['u10', 'u17', 'u4', 'u7', 'u2', 'u3', 'u16', 'u1', 'u14', 'u6', 'u11', 'u31'],
                'comments' => [
                    [
                        'id' => 'c27',
                        'authorId' => 'u17',
                        'text' => 'Glad it landed. I cut three slides so we had time for their actual questions.',
                        'createdAt' => self::at(4.9, 17, 40),
                    ],
                    [
                        'id' => 'c28',
                        'authorId' => 'u7',
                        'text' => 'I will schedule the kickoff onboarding call for next Wednesday.',
                        'createdAt' => self::at(4.85, 18, 0),
                    ],
                ],
            ],
            [
                'id' => 'p15',
                'authorId' => 'u9',
                'text' => 'Leveling rubric v2 is in the People folder. Managers: use it for the next cycle. ICs: it is there so the conversation is less mysterious. Questions welcome.',
                'createdAt' => self::at(5.4, 11, 0),
                'likes' => ['u3', 'u2', 'u14', 'u5', 'u27'],
                'comments' => [],
            ],
            [
                'id' => 'p16',
                'authorId' => 'u18',
                'text' => 'Does anyone remember why the mobile nav still has a leftover “More” slot? I want to delete it before it grows a personality.',
                'createdAt' => self::at(6.0, 10, 25),
                'likes' => ['u1', 'u5', 'u2', 'u21'],
                'comments' => [
                    [
                        'id' => 'c29',
                        'authorId' => 'u1',
                        'text' => 'It was a placeholder from the Tasks spike. Safe to remove — I will update the spec.',
                        'createdAt' => self::at(5.9, 10, 50),
                    ],
                    [
                        'id' => 'c30',
                        'authorId' => 'u5',
                        'text' => 'I can take the PR this afternoon if you leave a one-line note in Figma.',
                        'createdAt' => self::at(5.85, 11, 10),
                    ],
                ],
            ],
            [
                'id' => 'p17',
                'authorId' => 'u13',
                'text' => 'Wrote up three macros for the “password reset that is not a password reset” ticket. Support folks: they live under Templates → Auth. Please tweak the tone if they sound like robots.',
                'createdAt' => self::at(6.5, 14, 40),
                'likes' => ['u11', 'u7', 'u3', 'u33'],
                'comments' => [
                    [
                        'id' => 'c31',
                        'authorId' => 'u11',
                        'text' => 'Already used macro #2 twice today. Tone is good — slightly warmer than our old one.',
                        'createdAt' => self::at(6.4, 15, 5),
                    ],
                ],
            ],
            [
                'id' => 'p18',
                'authorId' => 'u4',
                'text' => 'Launch post is drafted. I borrowed one sentence from Priya’s research notes and one from Alex’s critique. Credits are in the doc; yell if I misquoted you.',
                'createdAt' => self::at(7.0, 9, 30),
                'likes' => ['u1', 'u12', 'u3', 'u10', 'u16', 'u8', 'u29'],
                'comments' => [
                    [
                        'id' => 'c32',
                        'authorId' => 'u12',
                        'text' => 'You got the quote right. Thanks for linking the source.',
                        'createdAt' => self::at(6.9, 10, 0),
                    ],
                ],
            ],
            [
                'id' => 'p19',
                'authorId' => 'u2',
                'text' => 'We are pausing the “nice to have” queue until Friday so we can clear the merge backlog. If something is actually blocking a customer, say so in the thread — silence means it waits.',
                'createdAt' => self::at(7.5, 8, 15),
                'likes' => ['u5', 'u15', 'u18', 'u1', 'u11', 'u7', 'u23'],
                'comments' => [],
            ],
            [
                'id' => 'p20',
                'authorId' => 'u3',
                'text' => 'Friday lunch is moving to Thursday this week — Jamie flies out at 2. Noodles on 2nd Street, 12:15. Reply here if you are in so we can hold the corner table.',
                'createdAt' => self::at(8.0, 12, 0),
                'likes' => ['u10', 'u12', 'u1', 'u14', 'u16', 'u36'],
                'comments' => [
                    [
                        'id' => 'c33',
                        'authorId' => 'u12',
                        'text' => 'In. I can grab the table at noon if that helps.',
                        'createdAt' => self::at(7.9, 12, 20),
                    ],
                    [
                        'id' => 'c34',
                        'authorId' => 'u10',
                        'text' => 'In, and I will be the one with the laptop closed. Promise.',
                        'createdAt' => self::at(7.85, 12, 45),
                    ],
                    [
                        'id' => 'c35',
                        'authorId' => 'u1',
                        'text' => 'In if critique wraps on time. Save me a seat near the wall.',
                        'createdAt' => self::at(7.8, 13, 10),
                    ],
                ],
            ],
            [
                'id' => 'p21',
                'authorId' => 'u6',
                'text' => 'Day-3 retention dipped again; day-1 is fine. The welcome email is not the problem — people stall on the second checklist task. Chart is in the analytics folder.',
                'createdAt' => self::at(9.0, 10, 10),
                'likes' => ['u1', 'u2', 'u5', 'u12', 'u16', 'u4', 'u22', 'u20'],
                'comments' => [
                    [
                        'id' => 'c36',
                        'authorId' => 'u5',
                        'text' => 'I can shorten that step this week if design is free tomorrow.',
                        'createdAt' => self::at(8.9, 10, 40),
                    ],
                    [
                        'id' => 'c37',
                        'authorId' => 'u1',
                        'text' => 'I am free 2–3 tomorrow. Let’s pair on the microcopy first.',
                        'createdAt' => self::at(8.85, 11, 5),
                    ],
                    [
                        'id' => 'c38',
                        'authorId' => 'u12',
                        'text' => 'Happy to sit in — I have two quotes that match this exact stall.',
                        'createdAt' => self::at(8.8, 11, 30),
                    ],
                ],
            ],
            [
                'id' => 'p22',
                'authorId' => 'u17',
                'text' => 'Building a short internal tip sheet for “when the prospect asks for SSO on the first call.” Sales: glance before Thursday’s enablement. Eng: tell me if I oversimplified the setup time.',
                'createdAt' => self::at(10.0, 15, 20),
                'likes' => ['u8', 'u10', 'u2', 'u13', 'u32'],
                'comments' => [
                    [
                        'id' => 'c39',
                        'authorId' => 'u2',
                        'text' => 'Setup is closer to two days than “same afternoon” unless they already have Okta. I left a comment.',
                        'createdAt' => self::at(9.9, 15, 50),
                    ],
                ],
            ],
            [
                'id' => 'p23',
                'authorId' => 'u1',
                'text' => 'Critique notes are in the design folder. Two screens still feel like a form from 2009 — I marked them with sticky notes and suggested a next pass.',
                'createdAt' => self::at(11.0, 16, 45),
                'likes' => ['u4', 'u12', 'u3', 'u18', 'u5', 'u19'],
                'comments' => [
                    [
                        'id' => 'c40',
                        'authorId' => 'u18',
                        'text' => 'I can take the denser one on Wednesday if the spacing rules are locked.',
                        'createdAt' => self::at(10.9, 17, 10),
                    ],
                ],
            ],
            [
                'id' => 'p24',
                'authorId' => 'u11',
                'text' => 'Third-floor espresso machine is alive again. Please do not attempt repairs yourselves. I have a log, and the log has feelings.',
                'createdAt' => self::at(12.0, 9, 0),
                'likes' => ['u1', 'u2', 'u3', 'u4', 'u5', 'u8', 'u14', 'u16', 'u18', 'u9', 'u10', 'u6', 'u17', 'u15', 'u35', 'u36'],
                'comments' => [
                    [
                        'id' => 'c41',
                        'authorId' => 'u1',
                        'text' => 'I owe you a coffee. The previous machine had opinions.',
                        'createdAt' => self::at(11.9, 9, 25),
                    ],
                    [
                        'id' => 'c42',
                        'authorId' => 'u9',
                        'text' => 'Putting “do not fix the espresso machine” in the new-hire FAQ.',
                        'createdAt' => self::at(11.85, 9, 50),
                    ],
                ],
            ],
            [
                'id' => 'p25',
                'authorId' => 'u14',
                'text' => 'We filled the senior designer role. Offer accepted this morning. More soon on start date — thank you to everyone who did a panel and wrote real feedback instead of “seemed fine.”',
                'createdAt' => self::at(13.2, 11, 30),
                'likes' => ['u3', 'u1', 'u2', 'u12', 'u4', 'u9', 'u5', 'u18', 'u19', 'u21'],
                'comments' => [
                    [
                        'id' => 'c43',
                        'authorId' => 'u3',
                        'text' => 'Excited. I will start the onboarding checklist today so week one is not a scavenger hunt.',
                        'createdAt' => self::at(13.1, 12, 0),
                    ],
                    [
                        'id' => 'c44',
                        'authorId' => 'u1',
                        'text' => 'Happy to buddy for the first design critique when they start.',
                        'createdAt' => self::at(13.05, 12, 20),
                    ],
                    [
                        'id' => 'c45',
                        'authorId' => 'u12',
                        'text' => 'Same — I can share the research office-hours invite.',
                        'createdAt' => self::at(13.0, 12, 40),
                    ],
                ],
            ],
            [
                'id' => 'p26',
                'authorId' => 'u19',
                'text' => 'Posted three empty-state illustration options in Figma. Looking for a vibe check — warm vs minimal. No wrong answer, just preferences.',
                'createdAt' => self::at(0.8, 13, 15),
                'likes' => ['u1', 'u20', 'u21', 'u12', 'u4'],
                'comments' => [
                    [
                        'id' => 'c46',
                        'authorId' => 'u20',
                        'text' => 'Warm. Minimal reads as “we forgot the art.”',
                        'createdAt' => self::at(0.75, 13, 40),
                    ],
                    [
                        'id' => 'c47',
                        'authorId' => 'u21',
                        'text' => 'Agree on warm, but keep the line weight matching the icon set.',
                        'createdAt' => self::at(0.7, 14, 5),
                    ],
                ],
            ],
            [
                'id' => 'p27',
                'authorId' => 'u21',
                'text' => 'Design tokens PR is up: spacing scale cleaned, old “almost 16” values removed. Please yell before merging if you were relying on the mess.',
                'createdAt' => self::at(1.2, 10, 0),
                'likes' => ['u1', 'u18', 'u19', 'u5', 'u22'],
                'comments' => [
                    [
                        'id' => 'c48',
                        'authorId' => 'u18',
                        'text' => 'Bless. I will update the nav padding after this lands.',
                        'createdAt' => self::at(1.15, 10, 25),
                    ],
                ],
            ],
            [
                'id' => 'p28',
                'authorId' => 'u22',
                'text' => 'Working on the checklist step that Casey flagged. Draft microcopy in the doc — tell me if it still sounds like homework.',
                'createdAt' => self::at(1.6, 16, 30),
                'likes' => ['u1', 'u6', 'u12', 'u20', 'u5'],
                'comments' => [
                    [
                        'id' => 'c49',
                        'authorId' => 'u6',
                        'text' => 'Much clearer. The old one made people think they needed a strategy deck.',
                        'createdAt' => self::at(1.55, 17, 0),
                    ],
                    [
                        'id' => 'c50',
                        'authorId' => 'u1',
                        'text' => 'Second sentence still hedges. Try cutting “you may want to.”',
                        'createdAt' => self::at(1.5, 17, 20),
                    ],
                ],
            ],
            [
                'id' => 'p29',
                'authorId' => 'u23',
                'text' => 'API deprecation notice for the old /v1/projects filter params — sunset in three weeks. Migration notes in the eng wiki. Ping me if something still depends on them.',
                'createdAt' => self::at(2.0, 9, 0),
                'likes' => ['u2', 'u5', 'u15', 'u18', 'u38'],
                'comments' => [
                    [
                        'id' => 'c51',
                        'authorId' => 'u5',
                        'text' => 'We still use `q` in one mobile path. I will switch it this week.',
                        'createdAt' => self::at(1.95, 9, 30),
                    ],
                ],
            ],
            [
                'id' => 'p30',
                'authorId' => 'u24',
                'text' => 'iOS build 214 is on TestFlight. Push notification badges should finally clear when you open Messages. If not, send me device + OS.',
                'createdAt' => self::at(2.7, 15, 45),
                'likes' => ['u2', 'u7', 'u11', 'u18', 'u1'],
                'comments' => [
                    [
                        'id' => 'c52',
                        'authorId' => 'u7',
                        'text' => 'Testing with a customer tomorrow morning. Will report back.',
                        'createdAt' => self::at(2.65, 16, 10),
                    ],
                ],
            ],
            [
                'id' => 'p31',
                'authorId' => 'u25',
                'text' => 'Found a flake in the share-dialog test — it was timing, not a real bug. Quarantined and opened a ticket to slow the animation in test mode.',
                'createdAt' => self::at(3.2, 11, 20),
                'likes' => ['u15', 'u2', 'u18', 'u5'],
                'comments' => [
                    [
                        'id' => 'c53',
                        'authorId' => 'u15',
                        'text' => 'Thank you for not “fixing” it by deleting the assertion.',
                        'createdAt' => self::at(3.15, 11, 45),
                    ],
                ],
            ],
            [
                'id' => 'p32',
                'authorId' => 'u26',
                'text' => 'We are moving staging deploys to a single window (noon–1 Pacific) so flags stop colliding. Exceptions need a note in #eng-deploys.',
                'createdAt' => self::at(3.8, 8, 30),
                'likes' => ['u2', 'u15', 'u23', 'u5', 'u35'],
                'comments' => [],
            ],
            [
                'id' => 'p33',
                'authorId' => 'u27',
                'text' => 'New short guide: “How we run a first-week buddy week.” Four pages, no jargon. Feedback welcome before we hand it to managers.',
                'createdAt' => self::at(4.2, 14, 0),
                'likes' => ['u3', 'u9', 'u14', 'u1', 'u36'],
                'comments' => [
                    [
                        'id' => 'c54',
                        'authorId' => 'u3',
                        'text' => 'This is the one I wished we had last quarter. Minor tweak on page 2 in the doc.',
                        'createdAt' => self::at(4.15, 14, 30),
                    ],
                ],
            ],
            [
                'id' => 'p34',
                'authorId' => 'u28',
                'text' => 'Engagement survey theme: people want clearer owners on cross-team work. Not a blame chart — a “who decides” chart. Happy to workshop examples.',
                'createdAt' => self::at(4.8, 10, 15),
                'likes' => ['u3', 'u9', 'u2', 'u35', 'u4'],
                'comments' => [
                    [
                        'id' => 'c55',
                        'authorId' => 'u35',
                        'text' => 'I can bring the ops version of that chart. We already sketched one.',
                        'createdAt' => self::at(4.75, 10, 45),
                    ],
                ],
            ],
            [
                'id' => 'p35',
                'authorId' => 'u29',
                'text' => 'Competitive one-pager for Meridian-like deals is in Marketing → Battlecards. It is factual, not spicy. Sales: tell me what is missing.',
                'createdAt' => self::at(5.2, 16, 0),
                'likes' => ['u8', 'u10', 'u17', 'u4', 'u32'],
                'comments' => [
                    [
                        'id' => 'c56',
                        'authorId' => 'u8',
                        'text' => 'Missing the audit-log angle Marcus used. I left a comment.',
                        'createdAt' => self::at(5.15, 16, 25),
                    ],
                ],
            ],
            [
                'id' => 'p36',
                'authorId' => 'u30',
                'text' => 'Someone on LinkedIn wrote a kind thread about our onboarding. I dropped the link in #wins (no pile-on, just awareness).',
                'createdAt' => self::at(5.6, 12, 30),
                'likes' => ['u4', 'u7', 'u3', 'u16', 'u1', 'u14'],
                'comments' => [
                    [
                        'id' => 'c57',
                        'authorId' => 'u7',
                        'text' => 'Already sent a thank-you note to the author. They are a customer admin.',
                        'createdAt' => self::at(5.55, 13, 0),
                    ],
                ],
            ],
            [
                'id' => 'p37',
                'authorId' => 'u31',
                'text' => 'Renewal with Brightline moved to next month — they need SSO first. Eng estimate would help me set expectations without inventing timelines.',
                'createdAt' => self::at(6.2, 9, 40),
                'likes' => ['u8', 'u2', 'u17', 'u7'],
                'comments' => [
                    [
                        'id' => 'c58',
                        'authorId' => 'u2',
                        'text' => 'If they have Okta already, about two days. I will confirm with Luke.',
                        'createdAt' => self::at(6.15, 10, 5),
                    ],
                ],
            ],
            [
                'id' => 'p38',
                'authorId' => 'u32',
                'text' => 'Booked seven demos this week. Shout-out to Harper for the one-pager that makes the first email less awkward.',
                'createdAt' => self::at(6.8, 17, 20),
                'likes' => ['u16', 'u10', 'u8', 'u29', 'u3'],
                'comments' => [
                    [
                        'id' => 'c59',
                        'authorId' => 'u16',
                        'text' => 'Glad it helped. Steal freely; just keep the tone.',
                        'createdAt' => self::at(6.75, 17, 45),
                    ],
                ],
            ],
            [
                'id' => 'p39',
                'authorId' => 'u33',
                'text' => 'Heads up: a burst of “can’t upload avatar” tickets was a file-type mix-up (HEIC). Macro added. Product: worth a friendlier error?',
                'createdAt' => self::at(7.2, 11, 10),
                'likes' => ['u11', 'u1', 'u18', 'u24', 'u7'],
                'comments' => [
                    [
                        'id' => 'c60',
                        'authorId' => 'u1',
                        'text' => 'Yes — we should say “try JPG or PNG” instead of a generic fail. I’ll file it.',
                        'createdAt' => self::at(7.15, 11, 35),
                    ],
                ],
            ],
            [
                'id' => 'p40',
                'authorId' => 'u34',
                'text' => 'Billing FAQ refresh is live. If a customer asks why an invoice looks different after a seat change, send them article B-14.',
                'createdAt' => self::at(7.8, 15, 0),
                'likes' => ['u11', 'u7', 'u31', 'u8'],
                'comments' => [],
            ],
            [
                'id' => 'p41',
                'authorId' => 'u35',
                'text' => 'Facilities: 4th floor nook reserved Tue/Thu 10–11 for design critique through month end. Other bookings will bounce — by design.',
                'createdAt' => self::at(8.3, 8, 50),
                'likes' => ['u1', 'u12', 'u19', 'u20', 'u36', 'u3'],
                'comments' => [
                    [
                        'id' => 'c61',
                        'authorId' => 'u36',
                        'text' => 'Calendar updated. I also left the extra chairs where Alex left them.',
                        'createdAt' => self::at(8.25, 9, 15),
                    ],
                ],
            ],
            [
                'id' => 'p42',
                'authorId' => 'u36',
                'text' => 'Snack order lands Wednesday. If you have a strong opinion about sparkling water brands, you have until Tuesday noon.',
                'createdAt' => self::at(8.7, 13, 20),
                'likes' => ['u3', 'u9', 'u14', 'u1', 'u5', 'u16', 'u33'],
                'comments' => [
                    [
                        'id' => 'c62',
                        'authorId' => 'u9',
                        'text' => 'The quiet one, not the loud citrus one. Thank you for your service.',
                        'createdAt' => self::at(8.65, 13, 45),
                    ],
                ],
            ],
            [
                'id' => 'p43',
                'authorId' => 'u37',
                'text' => 'Laptop refresh wave starts Monday for anyone still on the 2019 machines. Sign up on the IT sheet — no shame, only faster compiles.',
                'createdAt' => self::at(9.5, 10, 0),
                'likes' => ['u5', 'u18', 'u23', 'u15', 'u2', 'u24'],
                'comments' => [
                    [
                        'id' => 'c63',
                        'authorId' => 'u5',
                        'text' => 'Signing up. My fan has opinions during TypeScript builds.',
                        'createdAt' => self::at(9.45, 10, 25),
                    ],
                ],
            ],
            [
                'id' => 'p44',
                'authorId' => 'u38',
                'text' => 'Warehouse job for the events table finished overnight — dashboards should stop timing out. If something still crawls, tell me which chart.',
                'createdAt' => self::at(10.5, 9, 10),
                'likes' => ['u6', 'u28', 'u2', 'u15'],
                'comments' => [
                    [
                        'id' => 'c64',
                        'authorId' => 'u6',
                        'text' => 'Activation dashboard is snappy again. Thank you.',
                        'createdAt' => self::at(10.45, 9, 40),
                    ],
                ],
            ],
            [
                'id' => 'p45',
                'authorId' => 'u20',
                'text' => 'Poll: for the checklist microcopy, do we say “Add a teammate” or “Invite someone”? One feels friendlier; one feels clearer. Drop a vote.',
                'createdAt' => self::at(11.5, 14, 30),
                'likes' => ['u1', 'u12', 'u22', 'u5', 'u16', 'u4'],
                'comments' => [
                    [
                        'id' => 'c65',
                        'authorId' => 'u12',
                        'text' => '“Invite someone” won in two interviews last month.',
                        'createdAt' => self::at(11.45, 14, 55),
                    ],
                    [
                        'id' => 'c66',
                        'authorId' => 'u22',
                        'text' => 'Using Invite. I’ll update the draft tonight.',
                        'createdAt' => self::at(11.4, 15, 15),
                    ],
                    [
                        'id' => 'c67',
                        'authorId' => 'u1',
                        'text' => 'Agree. “Add a teammate” sounded like a permission setting.',
                        'createdAt' => self::at(11.35, 15, 35),
                    ],
                ],
            ],
            [
                'id' => 'p46',
                'authorId' => 'u39',
                'text' => 'Shipped a quieter empty state for the tasks list — no illustration, just one sentence and a single action. Critique notes welcome before Friday.',
                'createdAt' => self::at(0.6, 10, 15),
                'likes' => ['u2', 'u5', 'u18', 'u40', 'u1', 'u22'],
                'comments' => [
                    [
                        'id' => 'c68',
                        'authorId' => 'u18',
                        'text' => 'The sentence reads well at 375. I’d drop the second clause.',
                        'createdAt' => self::at(0.55, 10, 40),
                    ],
                    [
                        'id' => 'c69',
                        'authorId' => 'u40',
                        'text' => 'API side is ready whenever you wire the CTA.',
                        'createdAt' => self::at(0.5, 11, 5),
                    ],
                ],
            ],
            [
                'id' => 'p47',
                'authorId' => 'u39',
                'text' => 'Pairing slots open this afternoon if anyone wants eyes on a stubborn layout. Bring the phone frame, not just desktop.',
                'createdAt' => self::at(1.3, 13, 20),
                'likes' => ['u5', 'u24', 'u22', 'u2'],
                'comments' => [
                    [
                        'id' => 'c70',
                        'authorId' => 'u5',
                        'text' => 'Grabbing 2:30. Checklist badges still fight the nav on small widths.',
                        'createdAt' => self::at(1.25, 13, 45),
                    ],
                ],
            ],
            [
                'id' => 'p48',
                'authorId' => 'u40',
                'text' => 'Release train notes are in #eng. If your PR is still red at 3, it waits — no hero merges into the afternoon window.',
                'createdAt' => self::at(0.8, 9, 5),
                'likes' => ['u2', 'u15', 'u23', 'u39', 'u25', 'u26'],
                'comments' => [
                    [
                        'id' => 'c71',
                        'authorId' => 'u2',
                        'text' => 'Agreed. I’ll hold the canary until CI is green.',
                        'createdAt' => self::at(0.75, 9, 25),
                    ],
                    [
                        'id' => 'c72',
                        'authorId' => 'u15',
                        'text' => 'Staging secrets checklist is done — nothing leftover from last week.',
                        'createdAt' => self::at(0.7, 9, 50),
                    ],
                ],
            ],
            [
                'id' => 'p49',
                'authorId' => 'u40',
                'text' => 'Wrote a short note on how we name feature flags. Goal: a new hire can guess what `invite_cta_v2` does without asking Slack.',
                'createdAt' => self::at(2.4, 16, 10),
                'likes' => ['u39', 'u23', 'u5', 'u28'],
                'comments' => [
                    [
                        'id' => 'c73',
                        'authorId' => 'u39',
                        'text' => 'Love this. I’ll rename the two frontend flags that still say “tmp”.',
                        'createdAt' => self::at(2.35, 16, 35),
                    ],
                ],
            ],
        ];
    }
}
