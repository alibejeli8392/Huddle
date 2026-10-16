<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class ManagerUserSeeder extends Seeder
{
    /**
     * Demo manager account used by "Continue as Manager".
     * Same department as guest@huddle.app. Safe to run more than once.
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
                'password' => 'password',
                'email_verified_at' => now(),
                'title' => 'Design Manager',
                'department' => $department,
                'bio' => 'I keep the day-to-day work clear so the team can ship.',
                'avatar_path' => 'https://images.pexels.com/photos/1181686/pexels-photo-1181686.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=640&w=640',
                'role' => UserRole::Manager,
            ],
        );
    }
}
