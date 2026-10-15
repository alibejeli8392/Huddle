# Huddle — Progress Log

A running record of what has been built. Add a new dated entry after each major step (setup, auth, schema, each feature). Newest entries go at the top.

**Product:** Internal social network for a startup — signup, login, sessions, profiles with photo upload, member directory, friends, public/private messaging.

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
