# Huddle — Progress Log

A running record of what has been built. Add a new dated entry after each major step (setup, auth, schema, each feature). Newest entries go at the top.

**Product:** Internal social network for a startup — signup, login, sessions, profiles with photo upload, member directory, friends, public/private messaging.

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
