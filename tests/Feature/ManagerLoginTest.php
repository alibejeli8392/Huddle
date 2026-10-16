<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_login_creates_the_demo_manager_and_opens_tasks(): void
    {
        User::factory()->create([
            'email' => 'guest@huddle.app',
            'department' => 'Design',
            'role' => UserRole::Employee,
        ]);

        $response = $this->post(route('manager.login'));

        $response->assertRedirect(route('tasks.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'manager@huddle.app',
            'role' => UserRole::Manager->value,
            'department' => 'Design',
        ]);
    }

    public function test_manager_login_reuses_the_existing_demo_manager(): void
    {
        $user = User::factory()->manager()->create([
            'name' => 'Leslie Chen',
            'email' => 'manager@huddle.app',
            'department' => 'Design',
        ]);

        $this->post(route('manager.login'))->assertRedirect(route('tasks.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'manager@huddle.app')->count());
    }
}
