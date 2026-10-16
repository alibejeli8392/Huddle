<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class ManagerUserSeeder extends Seeder
{
    /**
     * Demo Design manager (Leslie Chen). Same department as guest@huddle.app.
     * Safe to run more than once.
     */
    public function run(): void
    {
        $guest = User::query()->where('email', 'guest@huddle.app')->first();
        $department = is_string($guest?->department) && $guest->department !== ''
            ? $guest->department
            : 'Design';

        User::query()->updateOrCreate(
            ['email' => 'manager@huddle.app'],
            [
                'name' => 'Leslie Chen',
                'password' => DemoSeeder::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'title' => 'Design Manager',
                'department' => $department,
                'bio' => 'I keep Design’s day-to-day clear so the team can ship. I protect critique time, unblock reviews, and somehow always have a spare sticky pad.',
                'avatar_path' => 'https://images.pexels.com/photos/2773977/pexels-photo-2773977.jpeg?auto=compress&cs=tinysrgb&w=400&h=400&fit=crop',
                'role' => UserRole::Manager,
            ],
        );
    }
}
