<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class GuestUserSeeder extends Seeder
{
    /**
     * Demo account used by "Continue as Guest".
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'guest@huddle.app'],
            [
                'name' => 'Guest',
                'password' => 'password',
                'email_verified_at' => now(),
                'title' => 'Product Designer',
                'department' => 'Design',
                'bio' => 'Demo account for walking through Huddle.',
                'role' => UserRole::Employee,
            ],
        );
    }
}
