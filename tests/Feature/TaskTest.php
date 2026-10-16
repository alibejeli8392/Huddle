<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_task_routes(): void
    {
        $task = Task::factory()->create();

        $this->get(route('tasks.index'))->assertRedirect(route('login'));
        $this->post(route('tasks.store'), [
            'title' => 'Do the thing',
            'due_date' => now()->toDateString(),
        ])->assertRedirect(route('login'));
        $this->delete(route('tasks.destroy', $task))->assertRedirect(route('login'));
        $this->post(route('tasks.complete', $task))->assertRedirect(route('login'));
    }

    public function test_manager_can_create_a_task_for_their_department(): void
    {
        $manager = User::factory()->manager()->create([
            'department' => 'Design',
        ]);

        $this->actingAs($manager)
            ->from(route('tasks.index'))
            ->post(route('tasks.store'), [
                'title' => 'Ship the mock',
                'details' => 'Keep it quiet.',
                'due_date' => now()->toDateString(),
                'due_time' => '14:30',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tasks.index'));

        $task = Task::query()->where('title', 'Ship the mock')->first();

        $this->assertNotNull($task);
        $this->assertSame($manager->id, $task->created_by);
        $this->assertSame('Design', $task->department);
        $this->assertSame('Keep it quiet.', $task->details);
        $this->assertTrue($task->due_date->isSameDay(now()));
        $this->assertSame('14:30', $task->formattedDueTime());
    }

    public function test_employee_cannot_create_or_delete_tasks(): void
    {
        $employee = User::factory()->employee()->create([
            'department' => 'Design',
        ]);
        $task = Task::factory()->create([
            'department' => 'Design',
            'created_by' => $employee->id,
        ]);

        $this->actingAs($employee)
            ->post(route('tasks.store'), [
                'title' => 'Nope',
                'due_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($employee)
            ->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_manager_can_delete_tasks_in_their_department(): void
    {
        $manager = User::factory()->manager()->create([
            'department' => 'Design',
        ]);
        $task = Task::factory()->create([
            'department' => 'Design',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_manager_cannot_manage_another_department_task(): void
    {
        $manager = User::factory()->manager()->create([
            'department' => 'Design',
        ]);
        $other = Task::factory()->create([
            'department' => 'Engineering',
        ]);

        $this->actingAs($manager)
            ->delete(route('tasks.destroy', $other))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('tasks.complete', $other))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $other->id]);
    }

    public function test_creating_a_task_requires_a_title_and_date(): void
    {
        $manager = User::factory()->manager()->create([
            'department' => 'Design',
        ]);

        $this->actingAs($manager)
            ->from(route('tasks.index'))
            ->post(route('tasks.store'), [
                'title' => '',
                'due_date' => '',
                'due_time' => '25:00',
            ])
            ->assertSessionHasErrors(['title', 'due_date', 'due_time']);
    }

    public function test_employee_sees_only_todays_tasks_for_their_department(): void
    {
        $employee = User::factory()->employee()->create([
            'department' => 'Design',
        ]);
        $todayMine = Task::factory()->create([
            'department' => 'Design',
            'title' => 'Design today',
            'due_date' => now()->toDateString(),
            'due_time' => '09:00:00',
        ]);
        Task::factory()->create([
            'department' => 'Design',
            'title' => 'Design tomorrow',
            'due_date' => now()->addDay()->toDateString(),
        ]);
        Task::factory()->create([
            'department' => 'Engineering',
            'title' => 'Eng today',
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($employee)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks/Index')
                ->where('role', UserRole::Employee->value)
                ->has('todayTasks', 1)
                ->where('todayTasks.0.id', $todayMine->id)
                ->where('todayTasks.0.title', 'Design today')
                ->has('upcomingTasks', 0)
                ->where('progress.total', 1)
                ->where('progress.done', 0));
    }

    public function test_completion_toggles_per_user(): void
    {
        $first = User::factory()->employee()->create(['department' => 'Design']);
        $second = User::factory()->employee()->create(['department' => 'Design']);
        $task = Task::factory()->create([
            'department' => 'Design',
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($first)
            ->post(route('tasks.complete', $task))
            ->assertRedirect();

        $this->assertDatabaseHas('task_completions', [
            'task_id' => $task->id,
            'user_id' => $first->id,
        ]);
        $this->assertDatabaseMissing('task_completions', [
            'task_id' => $task->id,
            'user_id' => $second->id,
        ]);

        $this->actingAs($second)
            ->get(route('tasks.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('todayTasks.0.completed', false)
                ->where('progress.done', 0));

        $this->actingAs($first)
            ->post(route('tasks.complete', $task))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_completions', [
            'task_id' => $task->id,
            'user_id' => $first->id,
        ]);
    }

    public function test_manager_sees_progress_counts_for_department_employees(): void
    {
        $manager = User::factory()->manager()->create(['department' => 'Design']);
        $alice = User::factory()->employee()->create(['department' => 'Design']);
        $bob = User::factory()->employee()->create(['department' => 'Design']);
        User::factory()->employee()->create(['department' => 'Engineering']);

        $task = Task::factory()->create([
            'department' => 'Design',
            'created_by' => $manager->id,
            'due_date' => now()->toDateString(),
        ]);

        TaskCompletion::factory()->create([
            'task_id' => $task->id,
            'user_id' => $alice->id,
        ]);

        $this->actingAs($manager)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks/Index')
                ->where('role', UserRole::Manager->value)
                ->where('employeeCount', 2)
                ->where('todayTasks.0.completedCount', 1)
                ->where('todayTasks.0.employeeCount', 2)
                ->where('todayTasks.0.canDelete', true));

        $this->assertTrue($bob->isEmployee());
    }

    public function test_incomplete_task_badge_count_is_shared_for_employees_only(): void
    {
        $employee = User::factory()->employee()->create(['department' => 'Design']);
        $manager = User::factory()->manager()->create(['department' => 'Design']);

        $done = Task::factory()->create([
            'department' => 'Design',
            'due_date' => now()->toDateString(),
            'due_time' => '08:00:00',
        ]);
        Task::factory()->create([
            'department' => 'Design',
            'due_date' => now()->toDateString(),
            'due_time' => '10:00:00',
        ]);
        Task::factory()->create([
            'department' => 'Design',
            'due_date' => now()->toDateString(),
            'due_time' => null,
        ]);
        Task::factory()->create([
            'department' => 'Design',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        TaskCompletion::factory()->create([
            'task_id' => $done->id,
            'user_id' => $employee->id,
        ]);

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('incompleteTaskCount', 2));

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('incompleteTaskCount', 0));
    }

    public function test_tasks_without_time_sort_last(): void
    {
        $employee = User::factory()->employee()->create(['department' => 'Design']);
        $late = Task::factory()->create([
            'department' => 'Design',
            'title' => 'Afternoon',
            'due_date' => now()->toDateString(),
            'due_time' => '15:00:00',
        ]);
        $untimed = Task::factory()->create([
            'department' => 'Design',
            'title' => 'Whenever',
            'due_date' => now()->toDateString(),
            'due_time' => null,
        ]);
        $early = Task::factory()->create([
            'department' => 'Design',
            'title' => 'Morning',
            'due_date' => now()->toDateString(),
            'due_time' => '09:00:00',
        ]);

        $this->actingAs($employee)
            ->get(route('tasks.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('todayTasks.0.id', $early->id)
                ->where('todayTasks.1.id', $late->id)
                ->where('todayTasks.2.id', $untimed->id));
    }
}
