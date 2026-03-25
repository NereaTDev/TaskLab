<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class TaskAnalyticsService
{
    public function globalStats(): array
    {
        return [
            'total'       => Task::count(),
            'pending'     => Task::pending()->count(),
            'in_progress' => Task::byStatus(TaskStatus::InProgress->value)->count(),
            'in_review'   => Task::byStatus(TaskStatus::Blocked->value)->count(),
            'done'        => Task::byStatus(TaskStatus::Done->value)->count(),
        ];
    }

    public function typeStats(): array
    {
        $typeCounts = Task::selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        $stats = [
            'evolutiva'  => ['label' => 'Evolutiva',  'count' => (int) ($typeCounts['feature'] ?? 0),     'percentage' => 0],
            'correctiva' => ['label' => 'Correctiva', 'count' => (int) ($typeCounts['bug'] ?? 0),         'percentage' => 0],
            'preventiva' => ['label' => 'Preventiva', 'count' => (int) ($typeCounts['improvement'] ?? 0), 'percentage' => 0],
            'soporte'    => ['label' => 'Soporte',    'count' => (int) ($typeCounts['question'] ?? 0),    'percentage' => 0],
        ];

        $total = array_sum(array_column($stats, 'count'));
        if ($total > 0) {
            foreach ($stats as $key => $item) {
                $stats[$key]['percentage'] = (int) round(($item['count'] / $total) * 100);
            }
        }

        return $stats;
    }

    public function priorityStats(): array
    {
        $priorityCounts = Task::selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        $stats = [
            'critica' => ['label' => 'Crítica', 'count' => (int) ($priorityCounts['critical'] ?? 0), 'percentage' => 0],
            'alta'    => ['label' => 'Alta',    'count' => (int) ($priorityCounts['high'] ?? 0),     'percentage' => 0],
            'media'   => ['label' => 'Media',   'count' => (int) ($priorityCounts['medium'] ?? 0),   'percentage' => 0],
            'baja'    => ['label' => 'Baja',    'count' => (int) ($priorityCounts['low'] ?? 0),      'percentage' => 0],
        ];

        $total = array_sum(array_column($stats, 'count'));
        if ($total > 0) {
            foreach ($stats as $key => $item) {
                $stats[$key]['percentage'] = (int) round(($item['count'] / $total) * 100);
            }
        }

        return $stats;
    }

    public function developerStats(): array
    {
        $aggregates = Task::selectRaw('assignee_id, count(*) as task_count')
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->orderByDesc('task_count')
            ->take(4)
            ->get();

        if ($aggregates->isEmpty()) {
            return [];
        }

        $assignees = User::whereIn('id', $aggregates->pluck('assignee_id'))
            ->get()
            ->keyBy('id');

        $stats = $aggregates->map(static function ($row) use ($assignees) {
            return [
                'user'       => $assignees[$row->assignee_id] ?? null,
                'task_count' => (int) $row->task_count,
                'percentage' => 0,
            ];
        })->values()->all();

        $max = max(array_column($stats, 'task_count'));
        if ($max > 0) {
            foreach ($stats as $i => $item) {
                $stats[$i]['percentage'] = (int) round(($item['task_count'] / $max) * 100);
            }
        }

        return $stats;
    }

    public function teamMembersStats(): array
    {
        $developers = User::with('developerProfile')
            ->whereHas('developerProfile')
            ->get();

        $taskStatusAggregates = Task::selectRaw('assignee_id, status, count(*) as task_count')
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id', 'status')
            ->get()
            ->groupBy('assignee_id');

        $members = $developers->map(static function (User $user) use ($taskStatusAggregates) {
            $statusRows  = $taskStatusAggregates->get($user->id, collect());
            $totalTasks  = $statusRows->sum('task_count');
            $activeTasks = $statusRows->whereIn('status', TaskStatus::activeStatuses())->sum('task_count');
            $doneTasks   = $statusRows->firstWhere('status', TaskStatus::Done->value)['task_count'] ?? 0;

            $profile     = $user->developerProfile;
            $capacity    = $profile?->max_parallel_tasks;

            $loadPercentage = ($capacity && $capacity > 0)
                ? (int) round(min(100, ($activeTasks / $capacity) * 100))
                : null;

            return [
                'user'                => $user,
                'profile'             => $profile,
                'total_tasks'         => (int) $totalTasks,
                'active_tasks'        => (int) $activeTasks,
                'done_tasks'          => (int) $doneTasks,
                'capacity'            => $capacity,
                'load_percentage'     => $loadPercentage,
                'progress_percentage' => 0,
            ];
        })->all();

        if (!empty($members)) {
            $maxActive = max(array_column($members, 'active_tasks'));

            foreach ($members as $i => $member) {
                $members[$i]['progress_percentage'] = $member['load_percentage'] !== null
                    ? $member['load_percentage']
                    : ($maxActive > 0 ? (int) round(($member['active_tasks'] / $maxActive) * 100) : 0);
            }

            usort($members, static fn ($a, $b) => $b['active_tasks'] <=> $a['active_tasks']);
            $members = array_slice($members, 0, 5);
        }

        return $members;
    }
}
