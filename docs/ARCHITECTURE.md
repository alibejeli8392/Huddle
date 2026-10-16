# Huddle — Architecture

One Laravel app. The server and the screens live in the same repository. There is no separate API or SPA.

**Last updated:** 2026-10-16

```
Browser
  │
  ▼
routes/          which URL
  │
  ▼
app/             PHP: auth, validation, data
  │
  ▼
Inertia          page name + props (not a JSON API)
  │
  ▼
resources/js/    React: the screen you see
```

## Two halves

| Half | Folders | What it is |
|---|---|---|
| Backend | `app/`, `routes/`, `database/` | All PHP. Models, controllers, migrations, and URLs. |
| Frontend | `resources/js/` | All React. Pages, components, and layouts. Tailwind lives in `resources/css/app.css` and `tailwind.config.js`. |

A request walks straight through both:

1. The browser hits a route in `routes/web.php` or `routes/auth.php`.
2. A controller calls `Inertia::render('PageName', $props)`.
3. Inertia loads `resources/js/Pages/PageName.jsx`.
4. That page renders in the browser. Later clicks swap the page without a full reload.

Named URLs from PHP are available in React through Ziggy (`route('dashboard')`).

## Folder by folder

| Folder | What lives here | Why it is separate |
|---|---|---|
| `app/Models/` | Eloquent models: `User`, `Post`, `Comment`, `Friendship`, `Conversation`, `Message`, `Task`, `TaskCompletion`. | One model per table. `post_likes` and `conversation_user` are pivots, not models. |
| `app/Http/Controllers/` | Page controllers (`FeedController`, `MemberController`, …) and Breeze auth controllers in `Auth/`. | One controller per screen or auth action. |
| `app/Http/Controllers/Auth/` | Login, register, password reset, email verify. | Breeze owns this. Leave the logic alone when restyling screens. |
| `app/Http/Requests/` | Form validation (`LoginRequest`, `StoreMessageRequest`, `SharePostRequest`). | Validation stays out of the controller. |
| `app/Policies/` | Who can delete a post, respond to a friend request, or open a conversation. | Authorization stays out of the controller. |
| `app/Http/Middleware/` | `HandleInertiaRequests` shares the signed-in user, the friend-request count, the unread message count, and incomplete today-task count. | Shared props, not page-specific data. |
| `routes/web.php` | App URLs: `/`, `/dashboard`, `/members`, `/friends`, `/messages`, `/tasks`, `/me`, `/profile`. | Screens a member opens. |
| `routes/auth.php` | Login, register, logout, password reset. | Breeze auth URLs. |
| `database/migrations/` | Tables. Users, posts, likes, comments, friendships, conversations, and messages, plus sessions, cache, and jobs. | Schema changes only. |
| `database/factories/` | Factories for users and the social models. | Test data. |
| `database/seeders/DemoData.php` | Sample members and posts that `DemoSeeder` writes. | Demo catalog, not a screen. |
| `resources/js/Pages/` | One Inertia screen per file. The path must match `Inertia::render('…')`. | A page is a route, not a reusable widget. |
| `resources/js/Layouts/` | Chrome around a page. See the two systems below. | Nav and page frame, not the page body. |
| `resources/js/Components/ui/` | New design primitives: `Button`, `Avatar`, `Icons`, `Logo`, `AuthFrame`. | Shared look, no feed or friends logic. |
| `resources/js/Components/social/` | New design pieces for the product: `Navbar`, `PostCard`, `MemberCard`, `FriendButton`. | Used by the social screens only. |
| `resources/js/Components/breeze/` | Original Breeze widgets: inputs, modal, dropdown, `PrimaryButton`, `ApplicationLogo`. | Still used by account settings and the older auth screens. |
| `resources/js/utils/` | `cn` (class names) and `timeAgo`. | Tiny helpers, not components. |
| `resources/views/app.blade.php` | The single HTML shell. Loads fonts and mounts React. | Laravel's one Blade view for the app. |
| `tests/Feature/` | PHPUnit. Auth, profile, and the social page routes. | Proves a URL still returns the right page. |

`config/`, `bootstrap/`, `storage/`, `vendor/`, `node_modules/`, and `public/` are framework and build output. Day-to-day work does not start there.

## Two UI systems, on purpose

The new black-and-white screens and the original Breeze screens both exist. They are not duplicates to delete yet.

| | New design | Breeze UI |
|---|---|---|
| Layout | `Layouts/AppLayout.jsx` | `Layouts/GuestLayout.jsx` (older auth screens). `AuthenticatedLayout.jsx` is the old Breeze shell and is no longer mounted. |
| Logo | `Components/ui/Logo.jsx` (script wordmark) | `Components/breeze/ApplicationLogo.jsx` (Laravel mark) |
| Buttons | `Components/ui/Button.jsx` | `Components/breeze/PrimaryButton.jsx`, `SecondaryButton.jsx`, `DangerButton.jsx` |
| Used on | Home, People, Friends, Messages, Tasks, Profile (`/me`, including account settings), Login, Register | Forgot/reset password, verify email, confirm password |

Each of those files has a short comment at the top saying which system it belongs to.

## Tech stack

| Layer | Choice | Role |
|---|---|---|
| Backend | Laravel 13 (PHP 8.3+) | Routing, auth, Eloquent, validation |
| Bridge | Inertia.js 2 | Server sends a page name and props |
| UI | React (JSX) + Tailwind CSS 3 | Pages and components |
| Bundler | Vite | Compiles `resources/js` and `resources/css` |
| Auth | Laravel Breeze | Login, register, password reset, account delete |
| Database | MySQL (`huddle` on `127.0.0.1:3306`) | Users, sessions, and cache |

## Current pages

| Screen | File | Route |
|---|---|---|
| Home (feed) | `Pages/Feed/Index.jsx` | `GET /dashboard` loads posts, likes, and comments from the database, plus friends and groups for Share. `POST /posts`, `POST /posts/{post}/like`, `POST /posts/{post}/comments`, `POST /posts/{post}/share`, `DELETE /posts/{post}`. |
| People | `Pages/Members/Index.jsx` | `GET /members` lists real users except you. `POST /friends/{user}` sends or accepts a request. |
| Friends | `Pages/Friends/Index.jsx` | `GET /friends` lists real requests and friends. Accept, decline, cancel, and unfriend use `/friendships/{friendship}`. |
| Messages | `Pages/Messages/Index.jsx` | `GET /messages` and `GET /messages/{conversation}` (or `?c=`). Opening one marks it read. `POST /conversations` starts a direct chat or a group. `POST /conversations/{conversation}/messages` sends a message. |
| Profile | `Pages/Profile/Show.jsx` | `GET /me` loads the signed-in member from the database. `PATCH /me` saves title, department, and bio. `POST /me/avatar` and `DELETE /me/avatar` change the photo. Account name, email, password, and delete stay on `PATCH /profile`. |
| Member | `Pages/Members/Show.jsx` | `GET /members/{user}` is a read-only profile and that person's posts. Share on those posts uses the same friend and group list as Home. Your own id redirects to `/me`. |
| Tasks | `Pages/Tasks/Index.jsx` | `GET /tasks` lists today's department tasks. Employees toggle `POST /tasks/{task}/complete`. Managers create with `POST /tasks` and delete with `DELETE /tasks/{task}`. |
| Login / Register / password | `Pages/Auth/*.jsx` | `routes/auth.php` (includes demo guest and manager login) |

Posts on Home come from the `posts`, `post_likes`, and `comments` tables. People, friends, and profiles come from `users` and `friendships`. Messages and groups come from `conversations`, `conversation_user`, and `messages`. Tasks come from `tasks` and `task_completions`. The Friends badge is the shared `friendRequestCount`. The Messages badge is the shared `unreadMessageCount`. The Tasks badge is the shared `incompleteTaskCount` (employees only).

## Schema

| Table | What it stores |
|---|---|
| `users` | Account, plus nullable `title`, `department`, `bio`, `avatar_path`, and `role` (`employee` / `manager`, default `employee`). |
| `posts` | A member's post (`user_id`, `body`). |
| `post_likes` | One like per member per post. |
| `comments` | A comment on a post. |
| `friendships` | One row per pair (`requester_id`, `addressee_id`) with status `pending`, `accepted`, or `declined`. |
| `conversations` | A direct chat or a named group. `created_by` is cleared if that member deletes their account. |
| `conversation_user` | Who is in a conversation, and when they last read it. |
| `messages` | A message in a conversation. `shared_post_id` points at a post and becomes null if that post is deleted. |
| `tasks` | A department task (`created_by`, `department`, `title`, `details`, `due_date`, optional `due_time`). |
| `task_completions` | One completion per member per task (`task_id`, `user_id`, `completed_at`). |

Status, conversation type, and role are string columns. The allowed values live in `App\FriendshipStatus`, `App\ConversationType`, and `App\UserRole` (with matching `User::ROLE_*` constants). Deleting a user cascades to the rows they own, including tasks they created and their completions.

## Where to put something new

| If you are adding… | Put it here |
|---|---|
| A new screen | `resources/js/Pages/…` and a route that `Inertia::render`s it |
| A generic control (button, avatar, icon) | `resources/js/Components/ui/` |
| A feed, member, or message widget | `resources/js/Components/social/` |
| A change to the old account or password forms | `resources/js/Components/breeze/` or `Pages/Profile/Partials/` |
| Shared chrome | `resources/js/Layouts/` |
| An HTTP action | `app/Http/Controllers/` plus a Form Request in `app/Http/Requests/` |
| A table | `database/migrations/` and a model in `app/Models/` |
| A URL | `routes/web.php`, or `routes/auth.php` for Breeze auth |

## Local run

```bash
php artisan serve          # Laravel → http://localhost:8000
npm run dev                # Vite → http://localhost:5173
php artisan storage:link   # once, so uploaded photos in storage/app/public are visible at /storage
```

MySQL must be running. In this local setup the root user has no password.
