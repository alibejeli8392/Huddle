<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class GuestUserSeeder extends Seeder
{
    /**
     * Demo account used by "Continue as Guest".
     * Always resets profile fields so a reseed restores the demo persona.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'guest@huddle.app'],
            [
                'name' => 'Guest',
                'password' => DemoSeeder::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'title' => 'Product Designer',
                'department' => 'Design',
                'bio' => 'I am walking through Huddle as a designer on the Design team. I leave sticky notes on half-finished mocks and I am usually first to the coffee machine after standup.',
                'avatar_path' => 'https://images.pexels.com/photos/2726111/pexels-photo-2726111.jpeg?auto=compress&cs=tinysrgb&w=400&h=400&fit=crop',
                'role' => UserRole::Employee,
            ],
        );
    }
}
