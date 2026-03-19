<?php

namespace App\Services;

use App\Models\CategoryType;
use App\Models\CategoryValue;
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
     * 1a. Usuarios del equipo (CategoryType) sugerido por la IA con categoría coincidente (fuzzy).
     * 1b. Usuarios del equipo con posición coincidente (string match en user->position).
     * 1c. Cualquier usuario del equipo (sin filtrar).
     * 2.  Fallback: DeveloperProfile activo con tipo compatible (sistema legacy).
     * 3.  Último recurso: superadmin.
     *
     * La carga se mide como suma de horas (points) de tareas activas, no por conteo.
     *
     * @param  int[]  $categoryValueIds  IDs de CategoryValue sugeridos por la IA para esta tarea.
     */
    public function assign(Task $task, string $teamName = '', string $requiredPosition = '', array $categoryValueIds = []): ?Task
    {
        // ── 1. Asignación basada en equipo (CategoryType) ──────────────────────
        if ($teamName !== '') {
            $type = CategoryType::get()->first(function ($t) use ($teamName) {
                similar_text(Str::lower($t->name), Str::lower($teamName), $pct);
                return $pct >= 70;
            });

            if ($type) {
                // Todos los IDs de CategoryValue que pertenecen a este equipo
                $teamValueIds = CategoryValue::where('category_type_id', $type->id)
                    ->pluck('id');

                // Todos los usuarios del equipo
                $userIds = UserCategoryAssignment::whereIn('category_value_id', $teamValueIds)
                    ->distinct()
                    ->pluck('user_id');

                $candidates = User::whereIn('id', $userIds)
                    ->where('is_super_admin', false)
                    ->get();

                if ($candidates->isNotEmpty()) {
                    // ── Intento 1a: filtrar por category values específicos de la IA ──
                    // Solo los value IDs que pertenecen a este equipo
                    $teamSpecificValueIds = array_values(array_filter(
                        $categoryValueIds,
                        fn ($id) => $teamValueIds->contains($id)
                    ));

                    if (!empty($teamSpecificValueIds) && $candidates->count() > 1) {
                        $byCategory = $candidates->filter(fn (User $u) =>
                            UserCategoryAssignment::where('user_id', $u->id)
                                ->whereIn('category_value_id', $teamSpecificValueIds)
                                ->exists()
                        );
                        if ($byCategory->isNotEmpty()) {
                            $candidates = $byCategory;
                        }
                    }

                    // ── Intento 1b: filtrar por posición (string en user->position) ──
                    if ($requiredPosition !== '' && $candidates->count() > 1) {
                        $needle = Str::lower($requiredPosition);
                        $byPosition = $candidates->filter(fn (User $u) =>
                            $u->position && str_contains(Str::lower($u->position), $needle)
                        );
                        if ($byPosition->isNotEmpty()) {
                            $candidates = $byPosition;
                        }
                    }

                    $best = $this->pickLeastLoaded($candidates);
                    if ($best) {
                        return $this->doAssign($task, $best->id, $type->id, $categoryValueIds);
                    }
                }
            }
        }

        // ── 2. Fallback: DeveloperProfile activo con tipo compatible ──────────
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

        // ── 3. Último recurso: superadmin ─────────────────────────────────────
        $this->assignToSuperAdmin($task);
        return $task;
    }

    /**
     * Devuelve el User con menor suma de horas activas (points) de la colección.
     * Usar horas en lugar de conteo refleja mejor la carga real de trabajo.
     */
    private function pickLeastLoaded(\Illuminate\Support\Collection $users): ?User
    {
        if ($users->isEmpty()) {
            return null;
        }

        return $users->map(function (User $u) {
            return [
                'user' => $u,
                'load' => (float) Task::where('assignee_id', $u->id)
                    ->whereIn('status', ['new', 'ready_for_dev', 'in_progress'])
                    ->sum('points'),
            ];
        })->sortBy('load')->first()['user'];
    }

    /** Versión para DeveloperProfile que respeta max_parallel_tasks. */
    private function pickLeastLoadedFromProfiles(\Illuminate\Support\Collection $profiles): ?int
    {
        $mapped = $profiles->mapWithKeys(function (DeveloperProfile $profile) {
            $user  = $profile->user;
            $hours = (float) Task::where('assignee_id', $user->id)
                ->whereIn('status', ['new', 'ready_for_dev', 'in_progress'])
                ->sum('points');

            // Respetar límite de tareas paralelas (convertimos a "infinito" si excede)
            if (! is_null($profile->max_parallel_tasks)) {
                $count = Task::where('assignee_id', $user->id)
                    ->whereIn('status', ['new', 'ready_for_dev', 'in_progress'])
                    ->count();
                if ($count >= $profile->max_parallel_tasks) {
                    $hours = PHP_INT_MAX;
                }
            }

            return [$user->id => $hours];
        })->filter(fn ($h) => $h < PHP_INT_MAX);

        return $mapped->isEmpty() ? null : $mapped->sortBy(fn ($v) => $v)->keys()->first();
    }

    /**
     * Hace el update, save, sincroniza category values y envía notificaciones.
     *
     * @param  int       $userId
     * @param  int|null  $categoryTypeId        Equipo (CategoryType) donde se encontró al usuario.
     * @param  int[]     $suggestedValueIds     Category values sugeridos por la IA.
     */
    private function doAssign(Task $task, int $userId, ?int $categoryTypeId = null, array $suggestedValueIds = []): Task
    {
        $alreadyAssigned = ($task->assignee_id === $userId);

        $task->assignee_id = $userId;
        $task->status      = $task->status === 'new' ? 'ready_for_dev' : $task->status;
        $task->save();

        // ── Sincronizar category values ────────────────────────────────────────
        // Mergeamos: los que ya tiene la tarea + los sugeridos por la IA
        // + los del equipo del asignado (para que la tarea refleje su team/categoría)
        $existingIds = $task->categoryValues()->pluck('category_values.id')->toArray();

        $assigneeTeamIds = [];
        if ($categoryTypeId) {
            $assigneeTeamIds = UserCategoryAssignment::where('user_id', $userId)
                ->join('category_values', 'category_values.id', '=', 'user_category_assignments.category_value_id')
                ->where('category_values.category_type_id', $categoryTypeId)
                ->pluck('user_category_assignments.category_value_id')
                ->toArray();
        }

        $mergedIds = array_unique(array_merge($existingIds, $suggestedValueIds, $assigneeTeamIds));
        if (! empty($mergedIds)) {
            $task->categoryValues()->sync($mergedIds);
        }

        // ── Notificaciones ─────────────────────────────────────────────────────
        if (! $alreadyAssigned) {
            $fresh = $task->fresh();
            $fresh->assignee->notify(new TaskAssigned($fresh));
            app(DiscordNotificationService::class)->notifyTaskAssigned($fresh);
        }

        return $task->fresh();
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
