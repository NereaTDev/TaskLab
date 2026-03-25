<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Anyone authenticated can see the task list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can view a task.
     */
    public function view(User $user, Task $task): bool
    {
        return true;
    }

    /**
     * Any authenticated user can create tasks.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only admins/super-admins or the task reporter can update a task.
     */
    public function update(User $user, Task $task): bool
    {
        return $user->isSuperAdmin()
            || $user->isAreaAdmin()
            || $task->reporter_id === $user->id;
    }

    /**
     * Any authenticated team member can update task status (Kanban drag & drop).
     */
    public function updateStatus(User $user, Task $task): bool
    {
        return true;
    }

    /**
     * Only super admins can delete tasks.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->isSuperAdmin();
    }
}
