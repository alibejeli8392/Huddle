# Huddle — Architecture

Short orientation for someone unfamiliar with this codebase. Huddle is a **monolith**: Laravel owns routes, auth, and data; React pages render through Inertia.js (no separate API/SPA).

**Last updated:** 2026-09-12

---

## Tech stack

| Layer | Choice | Role |
|---|---|---|
| Backend | Laravel 13 (PHP 8.3+) | Routing, auth, Eloquent, validation, file storage |
| Frontend bridge | Inertia.js 2 (`inertiajs/inertia-laravel` + `@inertiajs/react`) | Server returns a page name + props; React renders it without a JSON API |
| UI | React 18 (JSX) + Tailwind CSS | Page components, layouts, and reusable UI |
| Bundler | Vite 8 (`laravel-vite-plugin`) | Compiles `resources/js` and `resources/css` |
| Auth scaffolding | Laravel Breeze (Inertia + React) | Login, register, password reset, email verify, basic profile |
| Database | MySQL (`huddle` on `127.0.0.1:3306`) | Persistent data; sessions and cache also use DB tables |
| HTTP client | Axios (`resources/js/bootstrap.js`) | Available as `window.axios` for non-Inertia requests |

How a request works:

1. Browser hits a Laravel route in `routes/web.php` (or `routes/auth.php`).
2. The controller (or a closure) calls `Inertia::render('PageName', $props)`.
3. Inertia looks up `resources/js/Pages/PageName.jsx`.
4. That page (and its layout/components) hydrates in the browser. Later visits are XHR + client-side swaps, not full page reloads.

Ziggy (`tightenco/ziggy`) is installed so named Laravel routes can be used from JS.

---

## High-level folder structure

Only the directories that matter day-to-day. Laravel also has `bootstrap/`, `storage/`, `vendor/`, `node_modules/`, and `public/` — leave those alone unless you are changing config, assets, or compiled output.

```
Huddle/
├── app/                          PHP application code
│   ├── Http/
│   │   ├── Controllers/          Route handlers (Auth/* + ProfileController)
│   │   ├── Middleware/           HandleInertiaRequests shares auth user to every page
│   │   └── Requests/             Form Request validation (Login, ProfileUpdate)
│   ├── Models/                   Eloquent models (User only, so far)
│   └── Providers/                AppServiceProvider
├── config/                       Laravel config (app, auth, database, filesystems, …)
├── database/
│   ├── migrations/               Schema changes (users, cache, jobs so far)
│   ├── factories/                Model factories for tests/seeders
│   └── seeders/                  DatabaseSeeder (empty besides User factory comment)
├── docs/                         Project docs (this file + PROGRESS.md)
├── resources/
│   ├── css/app.css               Global Tailwind styles
│   ├── js/
│   │   ├── app.jsx               Inertia + Vite entry
│   │   ├── bootstrap.js          Axios setup
│   │   ├── Pages/                One file (or folder) per Inertia page
│   │   ├── Components/           Reusable UI (buttons, inputs, modal, …)
│   │   └── Layouts/              AuthenticatedLayout, GuestLayout
│   └── views/app.blade.php       Single Blade shell that mounts the React app
├── routes/
│   ├── web.php                   App pages (/, /dashboard, /profile)
│   ├── auth.php                  Breeze auth routes
│   └── console.php               Artisan console routes
├── tests/                        PHPUnit feature/unit tests (Breeze auth + profile)
├── .env                          Local config (not committed)
└── vite.config.js
```

### Where new work should go

| If you are adding… | Put it here |
|---|---|
| A new screen (directory, thread, friend list) | `resources/js/Pages/…` + a route that `Inertia::render`s it |
| A reusable button, card, avatar | `resources/js/Components/` |
| Shared chrome (nav, guest wrapper) | `resources/js/Layouts/` |
| HTTP endpoint / form submit | `app/Http/Controllers/` + a Form Request under `app/Http/Requests/` |
| A table or column | `database/migrations/` + an Eloquent model in `app/Models/` |
| A named URL | `routes/web.php` (or `routes/auth.php` for auth-only Breeze routes) |

### Current pages (Breeze defaults)

| Inertia page | File | Route |
|---|---|---|
| Welcome | `Pages/Welcome.jsx` | `GET /` |
| Dashboard | `Pages/Dashboard.jsx` | `GET /dashboard` (auth + verified) |
| Login / Register / … | `Pages/Auth/*.jsx` | `routes/auth.php` |
| Profile edit | `Pages/Profile/Edit.jsx` | `GET /profile` |

Planned Huddle features (profiles with photos, member directory, friends, messaging) do not have models, migrations, or pages yet.

---

## Local run

Two processes:

```bash
php artisan serve          # Laravel → http://localhost:8000
npm run dev                # Vite → http://localhost:5173
```

MySQL must be running. Root has no password in this local setup. The leftover `database/database.sqlite` file is from the default Laravel skeleton and is not used.
