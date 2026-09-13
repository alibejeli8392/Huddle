# Huddle

Internal social networking app for a startup, built with Laravel + Inertia.js + React + MySQL.

## Local setup

MySQL should be running, with a `huddle` database (local root user, no password). Copy `.env.example` to `.env` if needed, then:

```bash
composer install
npm install
php artisan migrate
php artisan serve
```

In a second terminal:

```bash
npm run dev
```

App: [http://localhost:8000](http://localhost:8000)  
Vite: [http://localhost:5173](http://localhost:5173)
