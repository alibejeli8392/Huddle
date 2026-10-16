<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\UserRole;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ali_mahmood_and_jordan_can_log_in_with_the_demo_password(): void
    {
        $accounts = [
            ['name' => 'Ali Bejeli', 'email' => 'a.bejeli.8392@westcliff.edu', 'role' => UserRole::Manager],
            ['name' => 'Mahmood Ayaz', 'email' => 'm.ayaz.9754@westcliff.edu', 'role' => UserRole::Manager],
            ['name' => 'Jordan Blake', 'email' => 'jordan@northstar.io', 'role' => UserRole::Employee],
        ];

        foreach ($accounts as $account) {
            $user = User::factory()->create([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => DemoSeeder::DEMO_PASSWORD,
                'role' => $account['role'],
                'department' => 'Engineering',
                'email_verified_at' => now(),
            ]);

            $this->post('/login', [
                'email' => $user->email,
                'password' => DemoSeeder::DEMO_PASSWORD,
            ])->assertRedirect(route('dashboard', absolute: false));

            $this->assertAuthenticatedAs($user);
            $this->post('/logout')->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_engineering_managers_can_create_tasks_and_jordan_cannot(): void
    {
        $ali = User::factory()->manager()->create([
            'email' => 'a.bejeli.8392@westcliff.edu',
            'department' => 'Engineering',
            'password' => DemoSeeder::DEMO_PASSWORD,
        ]);
        $mahmood = User::factory()->manager()->create([
            'email' => 'm.ayaz.9754@westcliff.edu',
            'department' => 'Engineering',
            'password' => DemoSeeder::DEMO_PASSWORD,
        ]);
        $jordan = User::factory()->employee()->create([
            'email' => 'jordan@northstar.io',
            'department' => 'Engineering',
            'password' => DemoSeeder::DEMO_PASSWORD,
        ]);

        $this->actingAs($ali)
            ->post(route('tasks.store'), [
                'title' => 'Ali eng task',
                'due_date' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tasks.index'));

        $this->actingAs($mahmood)
            ->post(route('tasks.store'), [
                'title' => 'Mahmood eng task',
                'due_date' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tasks.index'));

        $this->actingAs($jordan)
            ->post(route('tasks.store'), [
                'title' => 'Jordan should fail',
                'due_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Ali eng task',
            'department' => 'Engineering',
            'created_by' => $ali->id,
        ]);
        $this->assertDatabaseHas('tasks', [
            'title' => 'Mahmood eng task',
            'department' => 'Engineering',
            'created_by' => $mahmood->id,
        ]);
        $this->assertDatabaseMissing('tasks', [
            'title' => 'Jordan should fail',
        ]);
        $this->assertSame(2, Task::query()->where('department', 'Engineering')->count());
    }
}
