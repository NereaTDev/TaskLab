<?php

namespace App\Services;

use App\Models\CategoryType;
use App\Models\DeveloperProfile;
use App\Models\Task;
use App\Models\User;
use App\Models\UserCategoryAssignment;
use App\Notifications\TaskAssigned;
use App\Services\DiscordNotificationService;
use Illuminate\Support\Str;

class TaskAssignmentService
{
    /**
     * Asigna la tarea al mejor candidato disponible.
     *
     * Estrategia (por orden de prioridad):
     * 1. Usuarios del equipo (CategoryType) sugerido por la IA con posición coincidente.
     * 2. Cualquier usuario del equipo sugerido (sin filtrar por posición).
     * 3. Fallback: DeveloperProfile activo con tipo compatible (sistema legacy).
     * 4. Último recurso: superadmin.
     */
    public function assign(Task $task, string $teamName = '', string $requiredPosition = ''): ?Task
    {
        // ── 1 & 2. Asignación basada en equipo (CategoryType) ─────────────────
        if ($teamName !== '') {
            $type = CategoryType::get()->first(function ($t) use ($teamName) {
                similar_text(Str::lower($t->name), Str::lower($teamName), $pct);
                return $pct >= 70;
            });

            if ($type) {
                $userIds = UserCategoryAssignment::join(
                    'category_values',
                    'category_values.id', '=', 'user_category_assignments.category_value_id'
                )
                    ->where('category_values.category_type_id', $type->id)
                    ->distinct()
                    ->pluck('user_category_assignments.user_id');

                $candidates = User::whereIn('id', $userIds)
                    ->where('is_super_admin', false)
                    ->get();

                // Intento 1: filtrar por posición
                if ($requiredPosition !== '' && $candidates->count() > 1) {
                    $needle = Str::lower($requiredPosition);
                    $byPosition = $candidates->filter(fn ($u) =>
                        $u->position && str_contains(Str::lower($u->position), $needle)
                    );
                    if ($byPosition->isNotEmpty()) {
                        $candidates = $byPosition;
                    }
                }

                $best = $this->pickLeastLoaded($candidates);
                if ($best) {
                    return $this->doAssign($task, $best->id);
                }
            }
        }

        // ── 3. Fallback: DeveloperProfile activo con tipo compatible ──────────
        if ($task->type) {
            $devs = DeveloperProfile::where('active', true)
                ->where(fn ($q) => $q->where('type', $task->type)->orWhere('type', 'fullstack'))
                ->with(['user:id,name,email'])
                ->get();

            if ($devs->isNotEmpty()) {
                $best = $this->pickLeastLoadedFromProfiles($devs);
                if ($best) {
                    return $this->doAssign($task, $best);
                }
            }
        }

        // ── 4. Último recurso: superadmin ─────────────────────────────────────
        $this->assignToSuperAdmin($task);
        return $task;
    }

    /** Devuelve el User con menos tareas activas de la colección. */
    private function pickLeastLoaded(\Illuminate\Support\Collection $users): ?User
    {
        if ($users->isEmpty()) {
            return null;
        }

        return $users->map(function (User $u) {
            return [
                'user'  => $u,
                'load'  => Task::where('assignee_id', $u->id)
                    ->whereIn('status', ['new', 'ready_for_dev', 'in_progress'])
                    ->count(),
            ];
        })->sortBy('load')->first()['user'];
    }

    /** Versión para DeveloperProfile que respeta max_parallel_tasks. */
    private function pickLeastLoadedFromProfiles(\Illuminate\Support\Collection $profiles): ?int
    {
        $mapped = $profiles->mapWithKeys(function (DeveloperProfile $profile) {
            $user = $profile->user;
            $count = Task::where('assignee_id', $user->id)
                ->whereIn('status', ['new', 'ready_for_dev', 'in_progress'])
                ->count();
            if (! is_null($profile->max_parallel_tasks) && $count >= $profile->max_parallel_tasks) {
                $count = PHP_INT_MAX;
            }
            return [$user->id => $count];
        })->filter(fn ($c) => $c < PHP_INT_MAX);

        return $mapped->isEmpty() ? null : $mapped->sortBy(fn ($v) => $v)->keys()->first();
    }

    /** Hace el update, save y notificaciones con idempotencia. */
    private function doAssign(Task $task, int $userId): Task
    {
        $alreadyAssigned = ($task->assignee_id === $userId);

        $task->assignee_id = $userId;
        $task->status      = $task->status === 'new' ? 'ready_for_dev' : $task->status;
        $task->save();

        if (! $alreadyAssigned) {
            $task->assignee->notify(new TaskAssigned($task->fresh()));
            app(DiscordNotificationService::class)->notifyTaskAssigned($task);
        }

        return $task;
    }

    protected function assignToSuperAdmin(Task $task): void
    {
        $email      = env('TASKLAB_SUPERADMIN_EMAIL');
        $superAdmin = $email
            ? User::where('email', $email)->first()
            : null;

        $superAdmin ??= User::where('is_super_admin', true)->first();

        if ($superAdmin) {
            $this->doAssign($task, $superAdmin->id);
        }
    }
}
