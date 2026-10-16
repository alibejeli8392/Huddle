# Huddle — Progress Log

A running record of what has been built. Add a new dated entry after each major step (setup, auth, schema, each feature). Newest entries go at the top.

**Product:** Internal social network for a startup — signup, login, sessions, profiles with photo upload, member directory, friends, public/private messaging.

---

## 2026-10-16 — Demo managers, Engineering tasks, Home session fix

Walkthrough accounts and a client session bug; no schema changes.

- Root cause: Navbar `prefetch` could reuse a Home page cached under a previous account (often guest) after logging in as someone else. Fix: flush Inertia’s prefetch cache when `auth.user` changes, and clear history on login/logout. Regression: form-login as a manager, then visit `/dashboard` and `/`, still that manager.
- Seeded Engineering managers Ali Bejeli and Mahmood Ayaz (westcliff emails, initials avatars), demo-password logins for them plus Jordan, guest, and Leslie (`manager@huddle.app`). Engineering gets about six today tasks (varied “X of Y done”) and a few tomorrow, created by Ali/Mahmood with completions from eng employees including Jordan.
- Removed “Continue as Manager” (route, controller, login button, shortcut tests). Leslie and ManagerUserSeeder remain for Design tasks. Login still has “Continue as Guest”. Demo accounts use a simple demo password defined in the seeders (not shown in the UI).

---

## 2026-10-16 — Expanded demo company (photos, graph, chats)

Polished seed data only — still no app/schema/UI changes.

- About 40 people (38 `@northstar.io` plus guest and manager) with sharp Pexels portraits (400px), not randomuser.me. Original members keep their earlier Pexels photo ids. Design has 7 employees so manager task progress is meaningful.
- About 45 feed posts over 14 days; guest has ~12 friends, 4 incoming and 2 outgoing requests; ~8 direct chats and 5 groups with longer threads (~6 unread). Today’s Design tasks show varied completion counts across the team.
- Demo reset also clears demo-only friendships before rewrite. Other users stay untouched.

---

## 2026-10-16 — Daily department tasks

Members see today's work for their department. Managers create and delete those tasks. No recurring tasks, no per-person assignment, and no comments on tasks.

- `users.role` is `employee` or `manager` (default employee). `guest@huddle.app` stays an employee in Design. `manager@huddle.app` is seeded as a Design manager via `ManagerUserSeeder` (also called from `DatabaseSeeder` and `DemoSeeder`).
- `tasks` and `task_completions` store department work and who marked a task done. Completion is per person; one member's Done does not mark it done for everyone.
- `GET /tasks` shows today's list for employees (with a Done toggle and "X of Y done" for their own list) and a create form plus today/upcoming lists for managers (with department progress and delete).
- Employees cannot create or delete tasks (403). Managers only manage their own department. `APP_TIMEZONE=America/Los_Angeles` so "today" matches local time.
- The Tasks nav item badges incomplete today tasks for employees only. Managers (including Leslie Chen) sign in through the normal login form.
- `DemoSeeder` adds about six Design tasks for today (two already done by the guest), a few for tomorrow, and Engineering tasks created by the Engineering managers.

---

## 2026-10-16 — Messages, groups, and Share are stored in the database

This was the last preview piece. Chats, unread counts, and sharing a post are saved.

- `guest@huddle.app` has 3 direct conversations with accepted friends and 3 group chats (3–5 members, each started by someone else). A few threads are still unread, so the Messages badge has a count. Running `DemoSeeder` again updates those rows.
- Opening a conversation marks it read for you. Only members can view or send. A direct chat or a new group is limited to accepted friends.
- Share copies the post into a conversation you belong to, or opens a direct chat with a friend. Home and another member's profile pass the real friend and group lists to the share dialog.
- The unread badge is the shared `unreadMessageCount`. Sample members and posts for the seeder live in `database/seeders/DemoData.php`.

---

## 2026-10-15 — Profile is stored in the database

The Profile tab and another member's page read the real user. Messages, groups, and Share still use preview data.

- `/me` shows your name, email, title, department, bio, photo, post count, and friend count from the database.
- Title, department, and bio save with `PATCH /me`. Name, email, password, and delete stay on Account settings.
- A photo upload (jpg, png, or webp, up to 2 MB) is stored on the public disk. Replacing it deletes the previous local file. Remote avatar URLs are left alone. Remove photo clears the path.
- `/members/{user}` shows that person's profile and posts. Opening your own id sends you to `/me`.
- Run `php artisan storage:link` once so uploaded photos are reachable at `/storage`.

---

## 2026-10-15 — People and Friends are stored in the database

The directory and friend requests are real. Messages, groups, and the profile preview still come from `PreviewData`.

- The guest account has 4 incoming requests, 1 outgoing request, and 3 friends. Running `DemoSeeder` again updates those rows instead of duplicating them.
- People shows every other member and whether you are friends, waiting on them, or they are waiting on you. Search still filters name, title, and department in the browser.
- Sending, accepting, declining, canceling, and unfriending go through the server. The Friends badge is the real incoming count on every page.
- Share and the new-group picker still use the preview friend list, because those chats are keyed by the preview member ids.

---

## 2026-10-15 — Home feed is stored in the database

Posts, likes, and comments on Home are real. Members, friends, messages, groups, and the profile preview still come from `PreviewData`.

- `DemoSeeder` creates the 15 sample members, the guest account, and the sample posts with likes and comments. Running it again updates those rows instead of duplicating them.
- Home loads posts from the database. Creating a post, liking, commenting, and deleting your own post go through the server.
- Sharing a post still drops it into a preview conversation.

---

## 2026-10-15 — Database tables for posts, friends, and messages

Schema only. Screens still read `PreviewData`.

- Users can store a title, department, bio, and avatar path.
- Posts, likes, comments, friendships, conversations, membership, and messages are real tables. Status and conversation type are strings backed by PHP enums, so MySQL and the test database stay the same.
- Deleting a user removes their posts, comments, likes, friendships, and messages. A conversation stays if its creator leaves, and a shared post can be deleted without deleting the message.

---

## 2026-10-15 — Home posts drop dislike, composer sits tighter

- Post cards no longer have a dislike button or count. Like still toggles on and off. Comment and Share stay.
- The Home composer starts at about two lines and grows with the text, so "Public · Everyone at work" sits just under the field. Post stays disabled until there is text.

---

## 2026-10-15 — Share opens on screen and confirms the send

The share dialog was a fixed layer inside the feed's entrance animation. That animation leaves a transform on the page, so the dialog was centered in the full feed instead of the window. The friend list sat below the fold, and a click on the dimmed page closed the dialog.

- The dialog now mounts on the document, so it stays in view. Choosing a friend or group still inserts the post into that conversation.
- After sending, a short "Shared with <name>" note appears and then goes away.
- The share control is a curved arrow with the word Share, at the right end of the like, dislike, and comment row.

---

## 2026-10-15 — Feed posts stay off the Messages page

The Messages "Company" channel was rendering the same post list as Home, and sending there created a feed post.

- Messages now lists only direct conversations and group chats. The Company row and the Public/Private switch are gone.
- A new Home post is stored with the feed only. It does not show up in a conversation.
- Share still copies one post into the friend or group chat the member picks.

---

## 2026-10-15 — Labeled navigation and one profile page

Structure only. Colors, fonts, and the black-and-white look are unchanged.

- Sidebar and mobile tab bar now read Home, People, Friends, Messages, Profile. The active item is filled black on desktop and underlined on mobile.
- Friends shows a count of pending incoming requests. Messages shows a count of unread preview messages (direct and group). The badge hides at zero. Accepting or declining a request, or opening a conversation, updates the count.
- `/me` is the only profile screen, with Profile and Account settings tabs. Account settings is the existing name, email, password, and delete forms inside the app shell. `GET /profile` redirects to `/me?tab=account`. Patch and delete still post to `/profile`.

---

## 2026-10-01 — Guest login and a fuller preview

Auth for the demo account is real. Posts, friends, messages, comments, and groups are still preview data in the browser.

- Login has **Continue as Guest**. `POST /guest-login` finds or creates `guest@huddle.app` and signs that user in, then opens the feed. `GuestUserSeeder` creates the same account.
- Sample data is larger: 15 members, 18 posts with likes, dislikes, and comments, more friendship states, more direct threads, and 3 group chats.
- Posts can be liked or disliked (one or the other), commented on, and shared into a direct message or a group. Messages can start a new simulated group from the friend list.

---

## 2026-10-01 — Home URL opens login or the feed

`GET /` no longer renders the Laravel welcome page. Guests are sent to login. Signed-in members are sent to `/dashboard`. `Pages/Welcome.jsx` was removed.

---

## 2026-10-01 — Frontend folders grouped for a first read

No behavior changed. Files moved, imports updated, and short comments added so the new design and the Breeze UI are obvious next to each other.

- `docs/ARCHITECTURE.md` now opens with the backend half (`app/`, `routes/`, `database/`) and the frontend half (`resources/js/`).
- React components are grouped: `Components/ui/` (button, avatar, icons, logo), `Components/social/` (navbar, post, member, friend button), `Components/breeze/` (the original inputs, modal, and buttons).
- Layouts, both logos, and both button sets have a header comment saying which system they belong to and why the other one is still here.

---

## 2026-10-01 — Login and register match the social UI

Visual-only pass on the two auth screens, plus two unused style leftovers removed. Breeze routes, controllers, validation, and session handling were not changed.

- `Login` and `Register` now use the script logotype, black button, and the same field styling as the rest of the app. Remember me, forgot password, validation errors, and the existing `useForm` posts are unchanged.
- Removed unused `ghost` and `danger` variants from `Components/Button.jsx`.
- Removed unused `fontFamily.logo` from `tailwind.config.js`. The wordmark still uses `.logo-script`.

---

## 2026-10-01 — Design screens migrated into Inertia

The Amera/Figma export in `design-reference/` is now the logged-in UI. Login, register, password reset, and the Breeze account page (`/profile`) were left as they were.

- Home (`/dashboard`), Members, Friends, Messages, and Profile (`/me`) use the black-and-white layout: script logotype, sidebar, and mobile tab bar.
- Shared pieces live in `resources/js/Components/` (navbar, avatar, button, post card, member card, friend button, icons, logo).
- Sample posts, members, friendships, and messages are passed as Inertia props from `App\Support\PreviewData`. The signed-in user's name and email replace the placeholder viewer. Likes, posts, friend actions, and messages update in the browser only — they are not saved yet.
- Tailwind 3 theme colors (`ink`, `muted`, `line`, `wash`, `hover`, `heart`) and the Grand Hotel logotype were added so the export's class names resolve. The export itself used Tailwind 4 and React Router.
- `design-reference/` was removed after the screens were ported.

---

## 2026-09-12 — Safe boilerplate cleanup

Removed leftover Laravel skeleton files that Huddle does not use. Welcome/Dashboard and Breeze auth were left untouched.

- Deleted `database/database.sqlite` (app uses MySQL `huddle`)
- Deleted `tests/Unit/ExampleTest.php` and `tests/Feature/ExampleTest.php`
- Cleared the `inspire` demo command from `routes/console.php` (file kept)
- Replaced stock Laravel `README.md` with a short Huddle setup guide

---

## 2026-09-12 — Conventions established

Added Cursor rules so future work (and a presenter) can follow one set of names and file locations. No application code changed.

- `.cursor/rules/conventions.mdc` — project identity, solo-developer working style, git commit prefixes
- `.cursor/rules/laravel.mdc` — Eloquent / migration / controller / Form Request / Service conventions
- `.cursor/rules/react.mdc` — `Pages` vs `Components` vs `Layouts`, JSX naming, Inertia props

Rules optimize for a single builder plus a course collaborator/presenter, not a multi-engineer split.

---

## 2026-09-12 — Project setup

Initial Laravel + Breeze + Inertia + React app is running locally and talking to MySQL. No Huddle-specific features yet beyond Breeze’s default auth/profile scaffolding.

### Laravel install

- Fresh Laravel 13 project in this directory (`laravel/framework` ^13.17, PHP 8.4 via Herd).
- App name set to `Huddle` in `.env` (`APP_NAME=Huddle`).
- Local app URL: `http://localhost:8000`.

### Breeze + Inertia + React

- Laravel Breeze installed with the Inertia.js + React stack (JSX, not TypeScript).
- Vite 8 + `@inertiajs/react` + Tailwind are wired through `resources/js/app.jsx` and `vite.config.js`.
- Default Breeze pages are present: Welcome, Dashboard, Auth (login/register/password reset/verify email), and a basic Profile edit screen (name, email, password, delete account).
- Recreated missing `resources/js/bootstrap.js` (Axios setup) so Vite could resolve `import './bootstrap'` from `app.jsx`. Installed `axios` as a dev dependency.

### MySQL connection

- Switched `.env` from SQLite to MySQL:
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_PORT=3306`
  - `DB_DATABASE=huddle`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=` (empty; Homebrew MySQL root has no password)
- Created the `huddle` database: `mysql -u root -e "CREATE DATABASE huddle;"`

### Initial migrations

Ran `php artisan migrate`. Created:

- `migrations` (Laravel’s migration tracker)
- `users`, `password_reset_tokens`, `sessions` (from `0001_01_01_000000_create_users_table`)
- `cache`, `cache_locks` (from `0001_01_01_000001_create_cache_table`)
- `jobs`, `job_batches`, `failed_jobs` (from `0001_01_01_000002_create_jobs_table`)

Session and cache drivers are still set to `database` in `.env`.

### Not built yet

Profiles with photo upload, member directory, friends, and public/private messaging. Auth/profile UI is still stock Breeze.
