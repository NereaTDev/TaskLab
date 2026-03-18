<?php

namespace App\Jobs;

use App\Jobs\DownloadTaskAttachments;
use App\Models\CategoryType;
use App\Models\CategoryValue;
use App\Models\Task;
use App\Models\User;
use App\Models\UserCategoryAssignment;
use App\Services\AiTaskRefiner;
use App\Services\DiscordNotificationService;
use App\Services\GithubContextService;
use App\Services\TaskAssignmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RefineTaskWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public Task  $task,
        public array $imageUrls = [],
    ) {}

    public function failed(\Throwable $exception): void
    {
        Log::error("RefineTaskWithAi: job fallido definitivamente para tarea #{$this->task->id}", [
            'error' => $exception->getMessage(),
        ]);

        // Notificar al requester antes de borrar
        try {
            $discord = app(DiscordNotificationService::class);
            $discord->notifyTaskProcessingFailed($this->task);
        } catch (\Throwable) {}

        // Borrar la tarea — no tiene contenido útil sin refinar
        // El requester recibirá un DM para que reenvíe su petición
        $this->task->forceDelete();
    }

    public function handle(AiTaskRefiner $refiner, DiscordNotificationService $discord, TaskAssignmentService $assignment, GithubContextService $github): void
    {
        // 1. Construir contexto: árbol de categorías, equipos, tareas similares y código fuente
        $categoryTree = $this->buildCategoryTree();
        $teamContext  = $this->buildTeamContext();
        $similarTasks = $this->findSimilarTasks();
        try {
            $t0 = microtime(true);
            $codeContext = $github->buildCodeContext(
                $this->task->description_raw . ' ' . ($this->task->title ?? '')
            );
            $elapsed = round(microtime(true) - $t0, 2);
            Log::info("RefineTaskWithAi: code context built in {$elapsed}s for task #{$this->task->id}", [
                'has_context' => $codeContext !== '',
            ]);
        } catch (\Throwable $e) {
            Log::warning("RefineTaskWithAi: error building code context for task #{$this->task->id}: " . $e->getMessage());
            $codeContext = '';
        }

        // 2. Llamar a la IA con contexto completo
        $result = $refiner->refine(
            $this->task->description_raw,
            $this->imageUrls,
            $categoryTree,
            $similarTasks,
            $teamContext,
            $codeContext,
        );

        // 3. Gate de aceptación — ¿la tarea tiene suficiente calidad?
        $acceptance = $result['acceptance_check'] ?? ['status' => 'approved', 'issues' => []];

        if (($acceptance['status'] ?? 'approved') === 'needs_review') {
            $this->task->update([
                'title'             => $result['title'] ?? $this->task->title,
                'status'            => 'needs_review',
                'rejection_reasons' => $acceptance['issues'] ?? [],
            ]);

            $discord->notifyTaskNeedsReview($this->task, $acceptance['issues'] ?? []);

            Log::info("RefineTaskWithAi: tarea #{$this->task->id} bloqueada por calidad", [
                'score'  => $acceptance['score'] ?? null,
                'issues' => $acceptance['issues'] ?? [],
            ]);
            return; // No asignar ni procesar más
        }

        // 4. Detección de duplicados/conflictos
        $duplicate = $result['duplicate_check'] ?? ['status' => 'unique'];

        if (($duplicate['status'] ?? 'unique') === 'conflict') {
            $conflictingTask = Task::find($duplicate['related_task_id']);
            if ($conflictingTask) {
                $discord->notifyTaskConflict($this->task, $conflictingTask);
                Log::info("RefineTaskWithAi: tarea #{$this->task->id} en conflicto con #{$conflictingTask->id}");
            }
            // La tarea sigue adelante (el usuario puede querer revertir el cambio)
        }

        if (($duplicate['status'] ?? 'unique') === 'related' && ! empty($duplicate['related_task_id'])) {
            $relatedTask = Task::find($duplicate['related_task_id']);
            if ($relatedTask && in_array($relatedTask->status, ['processing', 'new', 'ready_for_dev', 'in_progress'])) {
                $this->mergeIntoExisting($relatedTask, $discord);
                return; // La nueva tarea ha sido fusionada, no continuar
            }
        }

        // 5. Aplicar refinamiento completo
        $this->applyRefinement($result);

        // 6. Asignar la tarea (pasando el equipo y posición sugeridos por la IA)
        $assignment->assign(
            $this->task->fresh(),
            $result['team']              ?? '',
            $result['required_position'] ?? '',
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function buildCategoryTree(): string
    {
        $types = CategoryType::with(['values' => function ($q) {
            $q->orderBy('sort_order')->with(['children' => function ($q) {
                $q->orderBy('sort_order');
            }]);
        }])->get();

        if ($types->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($types as $type) {
            $lines[] = "- {$type->name}";
            foreach ($type->values->whereNull('parent_id') as $value) {
                $lines[] = "  - {$value->name}";
                foreach ($value->children as $child) {
                    $lines[] = "    - {$child->name}";
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Construye el contexto de equipos para la IA: por cada CategoryType lista
     * los perfiles (posición) de los usuarios asignados a él.
     */
    private function buildTeamContext(): string
    {
        $types = CategoryType::orderBy('name')->get();

        if ($types->isEmpty()) {
            return '';
        }

        $lines = [];

        foreach ($types as $type) {
            // IDs de usuarios con asignaciones en este CategoryType
            $userIds = UserCategoryAssignment::join(
                'category_values',
                'category_values.id', '=', 'user_category_assignments.category_value_id'
            )
                ->where('category_values.category_type_id', $type->id)
                ->distinct()
                ->pluck('user_category_assignments.user_id');

            if ($userIds->isEmpty()) {
                continue;
            }

            $users = User::whereIn('id', $userIds)
                ->where('is_super_admin', false)
                ->get(['id', 'name', 'position']);

            if ($users->isEmpty()) {
                continue;
            }

            $lines[] = "Equipo: {$type->name}";

            $positions = $users->pluck('position')->filter()->unique()->values();
            if ($positions->isNotEmpty()) {
                $lines[] = "  Perfiles disponibles: " . $positions->implode(', ');
            }

            $lines[] = "  Miembros: {$users->count()}";
        }

        return implode("\n", $lines);
    }

    private function findSimilarTasks(): array
    {
        // Buscar tareas activas de los últimos 6 meses
        $candidates = Task::whereNull('archived_at')
            ->whereNotIn('status', ['archived', 'done'])
            ->where('id', '!=', $this->task->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->with(['reporter', 'categoryValues'])
            ->get();

        if ($candidates->isEmpty()) {
            return [];
        }

        // Pre-filtrar por similitud de texto (evitar pasar cientos de tareas a la IA)
        $needle = Str::lower($this->task->description_raw . ' ' . ($this->task->title ?? ''));

        $scored = $candidates->map(function (Task $t) use ($needle) {
            $haystack = Str::lower(($t->title ?? '') . ' ' . ($t->description_raw ?? ''));
            similar_text($needle, $haystack, $pct);
            return ['task' => $t, 'score' => $pct];
        })->sortByDesc('score')->take(8);

        return $scored->map(function ($item) {
            $t          = $item['task'];
            $categories = $t->categoryValues->map(fn ($cv) => $cv->name)->implode(' > ');
            return [
                'id'         => $t->id,
                'title'      => $t->title ?? Str::limit($t->description_raw, 60),
                'status'     => $t->status,
                'categories' => $categories ? [$categories] : [],
            ];
        })->values()->all();
    }

    private function mergeIntoExisting(Task $existingTask, DiscordNotificationService $discord): void
    {
        // Añadir el reporter de la tarea nueva como co-requester de la existente
        $coRequesterIds   = $existingTask->co_requester_ids ?? [];
        $newReporterId    = $this->task->reporter_id;

        if ($newReporterId && ! in_array($newReporterId, $coRequesterIds)) {
            $coRequesterIds[] = $newReporterId;
        }

        // Añadir contexto adicional al description_raw de la tarea existente
        $additionalContext = "\n\n---\n**Contexto adicional (solicitante: {$this->task->reporter?->name}):**\n{$this->task->description_raw}";

        $existingTask->update([
            'co_requester_ids' => $coRequesterIds,
            'description_raw'  => $existingTask->description_raw . $additionalContext,
        ]);

        // Transferir imágenes del mensaje fusionado a la tarea existente
        if (! empty($this->imageUrls)) {
            DownloadTaskAttachments::dispatch($existingTask, $this->imageUrls);
        }

        // Archivar la tarea nueva (ya fusionada)
        $this->task->update([
            'archived_at' => now(),
            'status'      => 'archived',
        ]);

        $discord->notifyTaskMerged($this->task, $existingTask);

        Log::info("RefineTaskWithAi: tarea #{$this->task->id} fusionada en #{$existingTask->id}");
    }

    private function applyRefinement(array $result): void
    {
        $points = $result['points'] ?? null;
        if (is_numeric($points)) {
            $points  = (float) $points;
            $allowed = [0.5, 1, 2, 4, 6, 8, 10, 12, 16];
            $closest = null;
            $minDiff = null;
            foreach ($allowed as $v) {
                $diff = abs($v - $points);
                if ($minDiff === null || $diff < $minDiff) {
                    $minDiff = $diff;
                    $closest = $v;
                }
            }
            $points = $closest;
        } else {
            $points = null;
        }

        $update = [
            'title'          => $result['title']        ?? $this->task->title,
            'description_ai' => $result['summary']      ?? $this->task->description_ai,
            'requirements'   => $result['requirements'] ?? [],
            'behavior'       => $result['behavior']     ?? null,
            'test_cases'     => $result['test_cases']   ?? [],
            'status'         => 'ready_for_dev',
        ];

        if (! empty($result['type']))            $update['type']           = $result['type'];
        if (! empty($result['priority']))        $update['priority']       = $result['priority'];
        if ($points !== null)                    $update['points']         = $points;
        if (! empty($result['primary_url']))     $update['primary_url']    = $result['primary_url'];
        if (! empty($result['additional_urls'])) $update['additional_urls'] = $result['additional_urls'];
        if (! empty($result['impact']))          $update['impact']         = $result['impact'];

        $update['ai_refined_at'] = now();

        $this->task->update($update);

        // Mapear categorías propuestas por la IA contra los valores reales de la BD
        $categoryValueIds = $this->mapCategoriesToValues($result['categories'] ?? []);
        if (! empty($categoryValueIds)) {
            $this->task->categoryValues()->sync($categoryValueIds);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mapeo fuzzy de categorías (sin cambios respecto a la versión anterior)
    // ─────────────────────────────────────────────────────────────────────────

    protected function mapCategoriesToValues(array $categories): array
    {
        if (empty($categories)) {
            return [];
        }

        $types = CategoryType::with(['values.children'])->get();
        if ($types->isEmpty()) {
            return [];
        }

        $matchedIds = [];

        foreach ($categories as $cat) {
            $path = $cat['path'] ?? null;
            if (! is_array($path) || empty($path)) {
                continue;
            }

            $path = array_map(fn ($s) => trim(mb_strtolower($s)), $path);

            $type = $this->bestMatchType($types, $path[0] ?? '');
            if (! $type) {
                continue;
            }

            $currentLevel = $type->values->whereNull('parent_id');
            $parent       = null;

            for ($i = 1; $i < count($path); $i++) {
                if ($path[$i] === '') {
                    continue;
                }
                $value = $this->bestMatchValue($currentLevel, $path[$i]);
                if (! $value) {
                    $parent = null;
                    break;
                }
                $parent       = $value;
                $currentLevel = $value->children;
            }

            if ($parent) {
                $matchedIds[] = $parent->id;
            }
        }

        return array_values(array_unique($matchedIds));
    }

    protected function bestMatchType($types, string $needle): ?CategoryType
    {
        $needleNorm = Str::slug($needle);
        $best       = null;
        $bestScore  = 0;

        foreach ($types as $type) {
            similar_text($needleNorm, Str::slug($type->name), $p1);
            similar_text($needleNorm, Str::slug($type->slug ?? ''), $p2);
            $score = max($p1, $p2);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $type;
            }
        }

        return $bestScore >= 60 ? $best : null;
    }

    protected function bestMatchValue($values, string $needle): ?CategoryValue
    {
        $needleNorm = Str::slug($needle);
        $best       = null;
        $bestScore  = 0;

        foreach ($values as $value) {
            similar_text($needleNorm, Str::slug($value->name), $p1);
            similar_text($needleNorm, Str::slug($value->slug ?? ''), $p2);
            $score = max($p1, $p2);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $value;
            }
        }

        return $bestScore >= 60 ? $best : null;
    }
}
