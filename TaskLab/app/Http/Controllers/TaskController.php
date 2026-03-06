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

        // Vista solicitada: dashboard | board | analysis (por defecto dashboard)
        $view = $request->get('view', 'dashboard');

        // Usuarios estándar: siempre dashboard personal
        if ($user && method_exists($user, 'isStandardUser') && $user->isStandardUser()) {
            $view = 'dashboard';
        } else {
            // Admin / Super Admin: solo vistas conocidas
            $view = in_array($view, ['dashboard', 'board', 'analysis'], true) ? $view : 'dashboard';
        }

        // Stats globales para tarjetas (por ahora no filtramos por usuario)
        $stats = [
            'total'        => Task::count(),
            'pending'      => Task::whereIn('status', ['new', 'in_refinement', 'ready_for_dev'])->count(),
            'in_progress'  => Task::where('status', 'in_progress')->count(),
            'in_review'    => Task::where('status', 'blocked')->count(),
            'done'         => Task::where('status', 'done')->count(),
        ];

        // Datos adicionales para la vista de análisis
        $analysisTypeStats = [];
        $analysisPriorityStats = [];
        $analysisDeveloperStats = [];
        $analysisTeamMembers = [];

        if ($view === 'analysis') {
            // Tareas por tipo (mapeadas a categorías de negocio)
            $typeCounts = Task::selectRaw('type, count(*) as count')
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
            $priorityCounts = Task::selectRaw('priority, count(*) as count')
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
            $developerAggregates = Task::selectRaw('assignee_id, count(*) as task_count')
                ->whereNotNull('assignee_id')
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

            $taskStatusAggregates = Task::selectRaw('assignee_id, status, count(*) as task_count')
                ->whereNotNull('assignee_id')
                ->groupBy('assignee_id', 'status')
                ->get()
                ->groupBy('assignee_id');

            $analysisTeamMembers = $developers->map(static function (User $user) use ($taskStatusAggregates) {
                $statusRows = $taskStatusAggregates->get($user->id, collect());

                $totalTasks = $statusRows->sum('task_count');
                $activeTasks = $statusRows
                    ->whereIn('status', ['new', 'in_refinement', 'ready_for_dev', 'in_progress'])
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
            $boardTasks = Task::with(['reporter', 'assignee', 'categoryValues'])->get();
        } else {
            // Dashboard: tareas del usuario autenticado
            $dashboardTasks = Task::with(['reporter', 'assignee', 'categoryValues'])
                ->where('assignee_id', optional($user)->id)
                ->get();
        }

        // Tipos de categoría genéricos (definidos por el superadmin)
        $categoryTypes = CategoryType::with(['values.children'])
            ->orderBy('name')
            ->get();

        // Usuarios disponibles para selects de requester/owner.
        // TODO: filtrar por rol y categorías (departamentos/áreas/equipos).
        $selectableUsers = User::orderBy('name')->get();

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
        ));
    }

    public function create()
    {
        return view('tasks.create');
    }

    public function store(Request $request, TaskAssignmentService $assignmentService)
    {
        $validated = $request->validate([
            'type'            => ['required', 'in:bug,feature,improvement,question'],
            'url'             => ['nullable', 'string', 'max:255'],
            'priority'        => ['required', 'in:low,medium,high,critical'],
            'description'     => ['required', 'string'],
            'area'            => ['nullable', 'in:web,plataforma,frontierz,dashboard_empresas'],
            'estimated_effort'=> ['nullable', 'in:low,medium,high'],
        ]);

        $user = $request->user();

        $descriptionRaw = $validated['description'];
        if (!empty($validated['url'])) {
            $descriptionRaw .= "\n\nURL: " . $validated['url'];
        }

        $task = Task::create([
            'title'            => null,
            'description_raw'  => $descriptionRaw,
            'type'             => $validated['type'],
            'status'           => 'new',
            'priority'         => $validated['priority'],
            'reporter_id'      => optional($user)->id,
            'source'           => 'web_form',
            'area'             => $validated['area'] ?? null,
            'estimated_effort' => $validated['estimated_effort'] ?? 'medium',
        ]);

        // Lanzamos la IA de refinamiento
        RefineTaskWithAi::dispatch($task);

        // Intentamos asignación automática (no falla si no encuentra dev)
        $assignmentService->assign($task);

        // Redirección según rol: usuarios estándar al dashboard, admins/SA al tablero
        $targetView = ($user && method_exists($user, 'isStandardUser') && $user->isStandardUser())
            ? 'dashboard'
            : 'board';

        return redirect()
            ->route('tasks.index', ['view' => $targetView])
            ->with('status', 'Task created. AI refinement in progress and auto-assignment attempted.');
    }

    public function show(Task $task)
    {
        return view('tasks.show', compact('task'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title'           => ['nullable', 'string', 'max:255'],
            'description_raw' => ['nullable', 'string'],
            'priority'        => ['required', 'in:low,medium,high,critical'],
            'area'            => ['nullable', 'in:web,plataforma,frontierz,dashboard_empresas'],
            'status'          => ['required', 'in:new,in_refinement,ready_for_dev,in_progress,done,blocked'],
            'type'            => ['required', 'in:bug,feature,improvement,question'],
            'estimated_effort'=> ['nullable', 'in:low,medium,high'],
            'points'          => ['nullable', 'integer', 'min:0'],
            'category_values'   => ['array'],
            'category_values.*' => ['integer', 'exists:category_values,id'],
            'reporter_id'       => ['nullable', 'exists:users,id'],
            'assignee_id'       => ['nullable', 'exists:users,id'],
        ]);

        $task->title           = $validated['title'] ?? $task->title;
        $task->description_raw = $validated['description_raw'] ?? $task->description_raw;
        $task->priority        = $validated['priority'];
        $task->area            = $validated['area'] ?? null;
        $task->status          = $validated['status'];
        $task->type            = $validated['type'];
        $task->estimated_effort = $validated['estimated_effort'] ?? $task->estimated_effort;
        $task->reporter_id     = $validated['reporter_id'] ?? $task->reporter_id;
        $task->assignee_id     = $validated['assignee_id'] ?? $task->assignee_id;

        // Campo opcional 'points': requiere columna en la tabla tasks.
        // De momento lo ignoramos para no romper hasta crear la migración.
        // if (array_key_exists('points', $validated)) {
        //     $task->points = $validated['points'];
        // }

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
            'status' => ['required', 'in:new,in_refinement,ready_for_dev,in_progress,done,blocked'],
        ]);

        $task->update(['status' => $validated['status']]);

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
