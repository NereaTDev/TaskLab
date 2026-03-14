<?php

namespace App\Http\Controllers;

use App\Jobs\RefineTaskWithAi;
use App\Models\Task;
use App\Models\User;
use App\Models\CategoryType;
use App\Services\TaskAssignmentService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Vista por defecto: board para admins/superadmins, dashboard para usuarios estándar
        $isPrivileged = $user && (
            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
            (method_exists($user, 'isAreaAdmin')  && $user->isAreaAdmin())
        );
        $defaultView = $isPrivileged ? 'board' : 'dashboard';
        $view = $request->get('view', $defaultView);

        // Todos los usuarios autenticados pueden usar estas vistas; cualquier otra cae a dashboard
        $allowedViews = ['dashboard', 'board', 'analysis'];
        $view = in_array($view, $allowedViews, true) ? $view : 'dashboard';

        $status = $request->get('status');
        $archived = $status === 'archived';

        $priority = $request->get('priority');
        $assigneeId = $request->get('assignee_id');

        $viewMode = $request->get('view_mode', 'board');
        if (! in_array($viewMode, ['board', 'list'], true)) {
            $viewMode = 'board';
        }

        // Filtros comunes reutilizables (prioridad y asignado)
        $applyCommonFilters = static function ($query) use ($priority, $assigneeId) {
            if ($priority && $priority !== 'all') {
                $query->where('priority', $priority);
            }

            if ($assigneeId === 'unassigned') {
                $query->whereNull('assignee_id');
            } elseif ($assigneeId && $assigneeId !== 'all') {
                $query->where('assignee_id', $assigneeId);
            }
        };

        // Stats globales para tarjetas (filtradas por los parámetros comunes)
        $statsBaseQuery = Task::query();
        if (! $archived) {
            $statsBaseQuery->whereNull('archived_at');
        } else {
            $statsBaseQuery->whereNotNull('archived_at');
        }

        if ($status && $status !== 'archived' && $status !== 'all') {
            $statsBaseQuery->where('status', $status);
        }

        $applyCommonFilters($statsBaseQuery);

        $stats = [
            'total'        => (clone $statsBaseQuery)->count(),
            'pending'      => (clone $statsBaseQuery)->whereIn('status', ['new', 'ready_for_dev'])->count(),
            'in_progress'  => (clone $statsBaseQuery)->where('status', 'in_progress')->count(),
            'in_review'    => (clone $statsBaseQuery)->where('status', 'blocked')->count(),
            'done'         => (clone $statsBaseQuery)->where('status', 'done')->count(),
        ];

        // Datos adicionales para la vista de análisis
        $analysisTypeStats = [];
        $analysisPriorityStats = [];
        $analysisDeveloperStats = [];
        $analysisTeamMembers = [];

        if ($view === 'analysis') {
            // Tareas por tipo (mapeadas a categorías de negocio)
            $typeCountsQuery = Task::selectRaw('type, count(*) as count')
                ->whereNull('archived_at');

            $applyCommonFilters($typeCountsQuery);

            $typeCounts = $typeCountsQuery
                ->groupBy('type')
                ->pluck('count', 'type');

            $analysisTypeStats = [
                'evolutiva' => [
                    'label'      => 'Evolutiva',
                    'count'      => (int) ($typeCounts['feature'] ?? 0),
                    'percentage' => 0,
                ],
                'correctiva' => [
                    'label'      => 'Correctiva',
                    'count'      => (int) ($typeCounts['bug'] ?? 0),
                    'percentage' => 0,
                ],
                'preventiva' => [
                    'label'      => 'Preventiva',
                    'count'      => (int) ($typeCounts['improvement'] ?? 0),
                    'percentage' => 0,
                ],
                'soporte' => [
                    'label'      => 'Soporte',
                    'count'      => (int) ($typeCounts['question'] ?? 0),
                    'percentage' => 0,
                ],
            ];

            $totalByType = array_sum(array_map(static fn ($item) => $item['count'], $analysisTypeStats));
            if ($totalByType > 0) {
                foreach ($analysisTypeStats as $key => $item) {
                    $analysisTypeStats[$key]['percentage'] = (int) round(($item['count'] / $totalByType) * 100);
                }
            }

            // Tareas por prioridad
            $priorityCountsQuery = Task::selectRaw('priority, count(*) as count')
                ->whereNull('archived_at');

            $applyCommonFilters($priorityCountsQuery);

            $priorityCounts = $priorityCountsQuery
                ->groupBy('priority')
                ->pluck('count', 'priority');

            $analysisPriorityStats = [
                'critica' => [
                    'label'      => 'Crítica',
                    'count'      => (int) ($priorityCounts['critical'] ?? 0),
                    'percentage' => 0,
                ],
                'alta' => [
                    'label'      => 'Alta',
                    'count'      => (int) ($priorityCounts['high'] ?? 0),
                    'percentage' => 0,
                ],
                'media' => [
                    'label'      => 'Media',
                    'count'      => (int) ($priorityCounts['medium'] ?? 0),
                    'percentage' => 0,
                ],
                'baja' => [
                    'label'      => 'Baja',
                    'count'      => (int) ($priorityCounts['low'] ?? 0),
                    'percentage' => 0,
                ],
            ];

            $totalByPriority = array_sum(array_map(static fn ($item) => $item['count'], $analysisPriorityStats));
            if ($totalByPriority > 0) {
                foreach ($analysisPriorityStats as $key => $item) {
                    $analysisPriorityStats[$key]['percentage'] = (int) round(($item['count'] / $totalByPriority) * 100);
                }
            }

            // Tareas por desarrollador (top 4 por nº de tareas asignadas)
            $developerAggregatesQuery = Task::selectRaw('assignee_id, count(*) as task_count')
                ->whereNull('archived_at')
                ->whereNotNull('assignee_id');

            $applyCommonFilters($developerAggregatesQuery);

            $developerAggregates = $developerAggregatesQuery
                ->groupBy('assignee_id')
                ->orderByDesc('task_count')
                ->take(4)
                ->get();

            if ($developerAggregates->isNotEmpty()) {
                $assignees = User::whereIn('id', $developerAggregates->pluck('assignee_id'))
                    ->get()
                    ->keyBy('id');

                $analysisDeveloperStats = $developerAggregates->map(static function ($row) use ($assignees) {
                    $user = $assignees[$row->assignee_id] ?? null;

                    return [
                        'user'       => $user,
                        'task_count' => (int) $row->task_count,
                        'percentage' => 0, // se rellenará después
                    ];
                })->values()->all();

                $maxTasks = max(array_map(static fn ($item) => $item['task_count'], $analysisDeveloperStats));
                if ($maxTasks > 0) {
                    foreach ($analysisDeveloperStats as $index => $item) {
                        $analysisDeveloperStats[$index]['percentage'] = (int) round(($item['task_count'] / $maxTasks) * 100);
                    }
                }
            }

            // Equipo de desarrollo: resumen por dev (tareas activas vs capacidad)
            $developers = User::with('developerProfile')
                ->whereHas('developerProfile')
                ->get();

            $taskStatusAggregatesQuery = Task::selectRaw('assignee_id, status, count(*) as task_count')
                ->whereNull('archived_at')
                ->whereNotNull('assignee_id');

            $applyCommonFilters($taskStatusAggregatesQuery);

            $taskStatusAggregates = $taskStatusAggregatesQuery
                ->groupBy('assignee_id', 'status')
                ->get()
                ->groupBy('assignee_id');

            $analysisTeamMembers = $developers->map(static function (User $user) use ($taskStatusAggregates) {
                $statusRows = $taskStatusAggregates->get($user->id, collect());

                $totalTasks = $statusRows->sum('task_count');
                $activeTasks = $statusRows
                    ->whereIn('status', ['backlog', 'pending', 'in_progress', 'in_review'])
                    ->sum('task_count');
                $doneTasks = $statusRows
                    ->firstWhere('status', 'done')['task_count'] ?? 0;

                $profile = $user->developerProfile;
                $capacity = $profile?->max_parallel_tasks;

                $loadPercentage = null;
                if ($capacity && $capacity > 0) {
                    $loadPercentage = (int) round(min(100, ($activeTasks / $capacity) * 100));
                }

                return [
                    'user'              => $user,
                    'profile'           => $profile,
                    'total_tasks'       => (int) $totalTasks,
                    'active_tasks'      => (int) $activeTasks,
                    'done_tasks'        => (int) $doneTasks,
                    'capacity'          => $capacity,
                    'load_percentage'   => $loadPercentage,
                    'progress_percentage' => 0, // se rellenará después
                ];
            })->all();

            if (!empty($analysisTeamMembers)) {
                $maxActive = max(array_map(static fn ($item) => $item['active_tasks'], $analysisTeamMembers));

                foreach ($analysisTeamMembers as $index => $member) {
                    if ($member['load_percentage'] !== null) {
                        $analysisTeamMembers[$index]['progress_percentage'] = $member['load_percentage'];
                    } elseif ($maxActive > 0) {
                        $analysisTeamMembers[$index]['progress_percentage'] = (int) round(($member['active_tasks'] / $maxActive) * 100);
                    } else {
                        $analysisTeamMembers[$index]['progress_percentage'] = 0;
                    }
                }

                // Ordenamos por tareas activas desc y nos quedamos con los 5 primeros
                usort($analysisTeamMembers, static fn ($a, $b) => $b['active_tasks'] <=> $a['active_tasks']);
                $analysisTeamMembers = array_slice($analysisTeamMembers, 0, 5);
            }
        }

        $boardTasks = null;     // tablero global (solo admins)
        $dashboardTasks = null; // tareas del usuario (dashboard personal)

        if ($view === 'board') {
            // Tablero: todas las tareas de la empresa (solo para admins / super admins)
            $boardTasksQuery = Task::with(['reporter', 'assignee', 'categoryValues', 'taskImages']);
            if ($archived) {
                $boardTasksQuery->whereNotNull('archived_at');
            } else {
                $boardTasksQuery->whereNull('archived_at');
            }

            if ($status && $status !== 'archived' && $status !== 'all') {
                $boardTasksQuery->where('status', $status);
            }

            $applyCommonFilters($boardTasksQuery);

            $boardTasks = $boardTasksQuery->get();
        } else {
            // Dashboard: tareas del usuario autenticado
            // - Tareas asignadas a él (assignee_id = user)
            // - Tareas web_form no asignadas creadas por él (reporter_id = user, assignee_id IS NULL)
            $dashboardTasksQuery = Task::with(['reporter', 'assignee', 'categoryValues', 'taskImages'])
                ->where(function ($q) use ($user) {
                    $q->where('assignee_id', optional($user)->id)
                      ->orWhere(function ($q2) use ($user) {
                          $q2->where('source', 'web_form')
                             ->where('reporter_id', optional($user)->id)
                             ->whereNull('assignee_id');
                      });
                });

            if ($archived) {
                $dashboardTasksQuery->whereNotNull('archived_at');
            } else {
                $dashboardTasksQuery->whereNull('archived_at');
            }

            if ($status && $status !== 'archived' && $status !== 'all') {
                $dashboardTasksQuery->where('status', $status);
            }

            // Para el dashboard personal, sólo permitimos sobreescribir el asignado
            // si el filtro explícitamente pide "sin asignar" o un usuario concreto.
            if ($assigneeId === 'unassigned') {
                $dashboardTasksQuery->whereNull('assignee_id');
            } elseif ($assigneeId && $assigneeId !== 'all') {
                $dashboardTasksQuery->where('assignee_id', $assigneeId);
            }

            if ($priority && $priority !== 'all') {
                $dashboardTasksQuery->where('priority', $priority);
            }

            $dashboardTasks = $dashboardTasksQuery->get();
        }

        // Tipos de categoría genéricos (definidos por el superadmin)
        $categoryTypes = CategoryType::with(['values.children'])
            ->orderBy('name')
            ->get();

        // Usuarios disponibles para selects de requester/owner.
        // TODO: filtrar por rol y categorías (departamentos/áreas/equipos).
        $selectableUsers = User::orderBy('name')->get();

        $openTaskId = $request->get('task');

        return view('tasks.index', compact(
            'stats',
            'view',
            'boardTasks',
            'dashboardTasks',
            'analysisTypeStats',
            'analysisPriorityStats',
            'analysisDeveloperStats',
            'analysisTeamMembers',
            'categoryTypes',
            'selectableUsers',
            'openTaskId',
            'archived',
            'status',
            'priority',
            'assigneeId',
            'viewMode',
        ));
    }


    public function store(Request $request, TaskAssignmentService $assignmentService)
    {
        $validated = $request->validate([
            'title'            => ['nullable', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'description_raw'  => ['nullable', 'string'],
            'type'             => ['required', 'in:bug,feature,improvement,question'],
            'status'           => ['nullable', 'in:new,ready_for_dev,in_progress,done,blocked,archived'],
            'priority'         => ['required', 'in:low,medium,high,critical'],
            'points'           => ['nullable', 'numeric', 'in:0.5,1,2,4,6,8,10,12,16'],
            'primary_url'      => ['nullable', 'string', 'max:2048'],
            'url'              => ['nullable', 'string', 'max:255'], // compat heredada
            'category_values'   => ['array'],
            'category_values.*' => ['integer', 'exists:category_values,id'],
            'reporter_id'       => ['nullable', 'exists:users,id'],
            'assignee_id'       => ['nullable', 'exists:users,id'],
        ]);

        $user = $request->user();

        // Elegimos la descripción "cruda" a partir de los campos disponibles
        $descriptionRaw = $validated['description_raw'] ?? $validated['description'] ?? '';

        // Compatibilidad: si viene url/primary_url y no está ya en la descripción, la añadimos abajo
        $url = $validated['primary_url'] ?? $validated['url'] ?? null;
        if (! empty($url) && ! str_contains($descriptionRaw, $url)) {
            $descriptionRaw = trim($descriptionRaw . "\n\nURL: " . $url);
        }

        $status = $validated['status'] ?? 'new';

        // Para tareas web_form: si no se elige assignee, se dejan sin asignar (assignee_id = null)
        // pero siempre se marca el requester como el usuario creador.
        $assigneeId = $validated['assignee_id'] ?? null;

        $task = Task::create([
            'title'           => $validated['title'] ?? null,
            'description_raw' => $descriptionRaw,
            'type'            => $validated['type'],
            'status'          => $status,
            'priority'        => $validated['priority'],
            'points'          => $validated['points'] ?? null,
            'reporter_id'     => $validated['reporter_id'] ?? optional($user)->id,
            'assignee_id'     => $assigneeId,
            'source'          => 'web_form',
            'primary_url'     => $url,
        ]);

        // Sincronizar categorías dinámicas (si se han enviado)
        if (! empty($validated['category_values'] ?? [])) {
            $task->categoryValues()->sync($validated['category_values']);
        }

        // IA: no refinamos automáticamente tareas web_form; solo las que vienen de canales externos.
        // El job RefineTaskWithAi seguirá ejecutándose para Discord/Teams desde sus propios flujos.
        // (Aquí no llamamos a RefineTaskWithAi::dispatch($task)).

        // Redirección según rol: usuarios estándar al dashboard, admins/SA al tablero
        $targetView = ($user && method_exists($user, 'isStandardUser') && $user->isStandardUser())
            ? 'dashboard'
            : 'board';

        return redirect()
            ->route('tasks.index', ['view' => $targetView])
            ->with('status', 'Task created. AI refinement in progress and auto-assignment attempted.');
    }

    public function show(Request $request, Task $task)
    {
        // Reutilizamos el índice abriendo el modal con ?task=, preservando view y view_mode
        $view = $request->get('view', 'board');
        $viewMode = $request->get('view_mode', 'board');

        return redirect()->route('tasks.index', [
            'view'      => $view,
            'view_mode' => $viewMode,
            'task'      => $task->id,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title'           => ['nullable', 'string', 'max:255'],
            'description_raw' => ['nullable', 'string'],
            'priority'        => ['required', 'in:low,medium,high,critical'],
            'status'          => ['required', 'in:new,ready_for_dev,in_progress,done,blocked,archived'],
            'type'            => ['required', 'in:bug,feature,improvement,question'],
            'points'          => ['nullable', 'numeric', 'in:0.5,1,2,4,6,8,10,12,16'],
            'primary_url'       => ['nullable', 'string', 'max:2048'],
            'category_values'   => ['array'],
            'category_values.*' => ['integer', 'exists:category_values,id'],
            'reporter_id'       => ['nullable', 'exists:users,id'],
            'assignee_id'       => ['nullable', 'exists:users,id'],
            'archive'           => ['nullable'],
        ]);

        $archive = $request->boolean('archive');

        $task->title           = $validated['title'] ?? $task->title;
        $task->description_raw = $validated['description_raw'] ?? $task->description_raw;
        $task->priority        = $validated['priority'];
        $task->status          = $validated['status'];
        $task->type            = $validated['type'];
        $task->primary_url     = $validated['primary_url'] ?? $task->primary_url;
        $task->reporter_id     = $validated['reporter_id'] ?? $task->reporter_id;
        $task->assignee_id     = $validated['assignee_id'] ?? $task->assignee_id;

        if (array_key_exists('points', $validated)) {
            $task->points = $validated['points'];
        }

        // Gestión de done_at: se activa al marcar done, se borra si se reabre
        if ($validated['status'] === 'done' && is_null($task->done_at)) {
            $task->done_at = now();
        } elseif ($validated['status'] !== 'done') {
            $task->done_at = null;
        }

        // Si el usuario marca la tarea como archivada (estado especial) o pulsa el botón de archivar,
        // fijamos archived_at. De momento no soportamos "desarchivar" desde aquí.
        if ($archive || $validated['status'] === 'archived') {
            $task->archived_at = now();
        }

        $task->save();

        // Sincronizar categorías dinámicas
        $task->categoryValues()->sync($validated['category_values'] ?? []);

        return redirect()
            ->route('tasks.index', ['view' => 'board'])
            ->with('status', 'Task updated.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,ready_for_dev,in_progress,done,blocked,archived'],
        ]);

        $task->update(['status' => $validated['status']]);

        if ($validated['status'] === 'archived' && is_null($task->archived_at)) {
            $task->archived_at = now();
            $task->save();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status'     => 'ok',
                'task_id'    => $task->id,
                'new_status' => $task->status,
            ]);
        }

        return back()->with('status', 'Task status updated.');
    }
}
