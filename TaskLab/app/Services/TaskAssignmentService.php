<?php

namespace App\Services;

use App\Models\DeveloperProfile;
use App\Models\Task;

class TaskAssignmentService
{
    /**
     * Attempt to auto-assign a task to the best available developer.
     *
     * Rules (MVP):
     * - Filter devs by active=true
     * - Filter devs whose areas include the task area (if set)
     * - Filter devs whose type matches the task type, or are fullstack
     * - Optionally respect max_parallel_tasks if set
     * - Choose the dev with the fewest active tasks (status in [new, in_refinement, ready_for_dev, in_progress])
     */
    public function assign(Task $task): ?Task
    {
        if (! $task->area || ! $task->type) {
            // Without area or type we can't make a smart decision.
            return $task;
        }

        $devs = DeveloperProfile::query()
            ->where('active', true)
            ->where(function ($q) use ($task) {
                // Type compatibility: same type or fullstack
                $q->where('type', $task->type)
                  ->orWhere('type', 'fullstack');
            })
            ->where(function ($q) use ($task) {
                // Areas contains task area (areas is json array)
                $q->whereJsonContains('areas', $task->area)
                  ->orWhereNull('areas'); // if areas is null, assume can work anywhere
            })
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'email');
            }])
            ->get();

        if ($devs->isEmpty()) {
            return $task;
        }

        // For each dev, compute current load (number of active tasks).
        $devWithLoad = $devs->mapWithKeys(function (DeveloperProfile $profile) {
            $user = $profile->user;

            $activeCount = Task::query()
                ->where('assignee_id', $user->id)
                ->whereIn('status', ['new', 'in_refinement', 'ready_for_dev', 'in_progress'])
                ->count();

            // Respect max_parallel_tasks if set
            if (! is_null($profile->max_parallel_tasks) && $activeCount >= $profile->max_parallel_tasks) {
                $activeCount = PHP_INT_MAX; // treat as "unavailable" for now
            }

            return [$user->id => [
                'profile'      => $profile,
                'active_count' => $activeCount,
            ]];
        });

        // Filter out devs that are effectively unavailable
        $availableDevs = $devWithLoad->filter(fn ($data) => $data['active_count'] < PHP_INT_MAX);

        if ($availableDevs->isEmpty()) {
            return $task;
        }

        // Choose dev with lowest active_count
        $bestDevId = $availableDevs->sortBy('active_count')->keys()->first();

        $task->assignee_id = $bestDevId;
        $task->status = $task->status === 'new' ? 'ready_for_dev' : $task->status;
        $task->save();

        return $task;
    }
}
