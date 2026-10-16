<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Today's tasks for employees; create form plus today/upcoming for managers.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $department = is_string($user->department) ? $user->department : '';
        $today = now()->toDateString();
        $employeeCount = $user->departmentEmployeeCount();

        $mapTasks = function ($tasks) use ($user, $employeeCount): array {
            return $tasks
                ->map(fn (Task $task): array => $task->toListItem($user, $employeeCount))
                ->all();
        };

        if ($user->isManager()) {
            $base = Task::query()
                ->forDepartment($department)
                ->withCount('completions')
                ->withExists([
                    'completions as completed_by_viewer' => fn ($query) => $query->where('user_id', $user->id),
                ]);

            $todayTasks = (clone $base)
                ->dueOn($today)
                ->orderByDueTime()
                ->get();

            $upcomingTasks = (clone $base)
                ->whereDate('due_date', '>', $today)
                ->orderBy('due_date')
                ->orderByDueTime()
                ->get();

            return Inertia::render('Tasks/Index', [
                'role' => $user->role?->value ?? $user->role,
                'department' => $department,
                'today' => $today,
                'todayTasks' => $mapTasks($todayTasks),
                'upcomingTasks' => $mapTasks($upcomingTasks),
                'employeeCount' => $employeeCount,
            ]);
        }

        $todayTasks = Task::query()
            ->forDepartment($department)
            ->dueOn($today)
            ->withCount('completions')
            ->withExists([
                'completions as completed_by_viewer' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->orderByDueTime()
            ->get();

        $completedCount = $todayTasks->filter(fn (Task $task): bool => $task->isCompletedBy($user))->count();

        return Inertia::render('Tasks/Index', [
            'role' => $user->role?->value ?? $user->role,
            'department' => $department,
            'today' => $today,
            'todayTasks' => $mapTasks($todayTasks),
            'upcomingTasks' => [],
            'employeeCount' => $employeeCount,
            'progress' => [
                'done' => $completedCount,
                'total' => $todayTasks->count(),
            ],
        ]);
    }

    /**
     * Managers create a task for their own department.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        Task::query()->create([
            'created_by' => $user->id,
            'department' => $user->department,
            'title' => $data['title'],
            'details' => $data['details'] ?? null,
            'due_date' => $data['due_date'],
            'due_time' => $data['due_time'] ?? null,
        ]);

        return redirect()->route('tasks.index');
    }

    /**
     * Managers delete a task from their department.
     */
    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index');
    }

    /**
     * Toggle the current user's completion of a department task.
     */
    public function toggleComplete(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('complete', $task);

        $user = $request->user();
        $existing = $task->completions()->where('user_id', $user->id)->first();

        if ($existing !== null) {
            $existing->delete();
        } else {
            $task->completions()->create([
                'user_id' => $user->id,
                'completed_at' => now(),
            ]);
        }

        return back();
    }
}
