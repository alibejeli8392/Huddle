<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class GuestUserSeeder extends Seeder
{
    /**
     * Demo account used by "Continue as Guest".
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'guest@huddle.app'],
            [
                'name' => 'Guest',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
    }
}
