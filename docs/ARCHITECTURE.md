# Huddle — Architecture

One Laravel app. The server and the screens live in the same repository. There is no separate API or SPA.

**Last updated:** 2026-10-01

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
| `app/Models/` | Eloquent models. `User` only, so far. | One model per table. |
| `app/Http/Controllers/` | Page controllers (`FeedController`, `MemberController`, …) and Breeze auth controllers in `Auth/`. | One controller per screen or auth action. |
| `app/Http/Controllers/Auth/` | Login, register, password reset, email verify. | Breeze owns this. Leave the logic alone when restyling screens. |
| `app/Http/Requests/` | Form validation (`LoginRequest`, `ProfileUpdateRequest`). | Validation stays out of the controller. |
| `app/Http/Middleware/` | `HandleInertiaRequests` shares the signed-in user with every page. | Shared props, not page-specific data. |
| `app/Support/PreviewData.php` | Sample members, posts, friendships, and messages. | Stand-in until those tables exist. The signed-in user's name and email replace the placeholder viewer. |
| `routes/web.php` | App URLs: `/`, `/dashboard`, `/members`, `/friends`, `/messages`, `/me`, `/profile`. | Screens a member opens. |
| `routes/auth.php` | Login, register, logout, password reset. | Breeze auth URLs. |
| `database/migrations/` | Tables. Users, sessions, cache, and jobs so far. | Schema changes only. |
| `database/factories/` | `UserFactory` for tests. | Test data. |
| `resources/js/Pages/` | One Inertia screen per file. The path must match `Inertia::render('…')`. | A page is a route, not a reusable widget. |
| `resources/js/Layouts/` | Chrome around a page. See the two systems below. | Nav and page frame, not the page body. |
| `resources/js/Components/ui/` | New design primitives: `Button`, `Avatar`, `Icons`, `Logo`, `AuthFrame`. | Shared look, no feed or friends logic. |
| `resources/js/Components/social/` | New design pieces for the product: `Navbar`, `PostCard`, `MemberCard`, `FriendButton`. | Used by the social screens only. |
| `resources/js/Components/breeze/` | Original Breeze widgets: inputs, modal, dropdown, `PrimaryButton`, `ApplicationLogo`. | Still used by account settings and the older auth screens. |
| `resources/js/context/` | `HuddleContext` holds preview likes, posts, and messages in the browser. | Not saved to the database yet. |
| `resources/js/utils/` | `cn` (class names) and `timeAgo`. | Tiny helpers, not components. |
| `resources/views/app.blade.php` | The single HTML shell. Loads fonts and mounts React. | Laravel's one Blade view for the app. |
| `tests/Feature/` | PHPUnit. Auth, profile, and the social page routes. | Proves a URL still returns the right page. |

`config/`, `bootstrap/`, `storage/`, `vendor/`, `node_modules/`, and `public/` are framework and build output. Day-to-day work does not start there.

## Two UI systems, on purpose

The new black-and-white screens and the original Breeze screens both exist. They are not duplicates to delete yet.

| | New design | Breeze UI |
|---|---|---|
| Layout | `Layouts/AppLayout.jsx` | `Layouts/AuthenticatedLayout.jsx` (account settings), `Layouts/GuestLayout.jsx` (older auth screens) |
| Logo | `Components/ui/Logo.jsx` (script wordmark) | `Components/breeze/ApplicationLogo.jsx` (Laravel mark) |
| Buttons | `Components/ui/Button.jsx` | `Components/breeze/PrimaryButton.jsx`, `SecondaryButton.jsx`, `DangerButton.jsx` |
| Used on | Home, Members, Friends, Messages, Profile (`/me`), Login, Register | `/profile` account forms, forgot/reset password, verify email, confirm password |

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
| Home (feed) | `Pages/Feed/Index.jsx` | `GET /` redirects here when signed in; the page itself is `GET /dashboard` |
| Members | `Pages/Members/Index.jsx` | `GET /members` |
| Friends | `Pages/Friends/Index.jsx` | `GET /friends` |
| Messages | `Pages/Messages/Index.jsx` | `GET /messages` |
| Profile | `Pages/Profile/Show.jsx` | `GET /me` |
| Account settings | `Pages/Profile/Edit.jsx` | `GET /profile` |
| Login / Register / password | `Pages/Auth/*.jsx` | `routes/auth.php` |

Posts, friends, and messages on the social screens are still preview data from `PreviewData`. They are not database tables yet.

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
```

MySQL must be running. In this local setup the root user has no password.
