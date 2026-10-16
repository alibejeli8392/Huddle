<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Managers can create tasks for their own department.
     */
    public function create(User $user): bool
    {
        return $user->isManager()
            && is_string($user->department)
            && $user->department !== '';
    }

    /**
     * Anyone signed in can view tasks for their own department.
     */
    public function view(User $user, Task $task): bool
    {
        return is_string($user->department)
            && $user->department !== ''
            && $user->department === $task->department;
    }

    /**
     * Managers can delete tasks that belong to their department.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->isManager()
            && is_string($user->department)
            && $user->department !== ''
            && $user->department === $task->department;
    }

    /**
     * Employees (and managers) in the task's department can toggle their own completion.
     */
    public function complete(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }
}
