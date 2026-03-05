<?php

namespace App\Http\Controllers;

use App\Jobs\RefineTaskWithAi;
use App\Models\Task;
use App\Services\TaskAssignmentService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->get('view', 'dashboard');

        // Listado paginado para la tabla principal
        $tasks = Task::with(['reporter', 'assignee'])->latest()->paginate(15);

        // Stats para el dashboard
        $stats = [
            'total'        => Task::count(),
            'pending'      => Task::whereIn('status', ['new', 'in_refinement', 'ready_for_dev'])->count(),
            'in_progress'  => Task::where('status', 'in_progress')->count(),
            'in_review'    => Task::where('status', 'blocked')->count(),
            'done'         => Task::where('status', 'done')->count(),
        ];

        // Para vista tablero: todas las tareas agrupadas por columna Kanban
        $boardTasks = null;
        if ($view === 'board') {
            $boardTasks = Task::with(['reporter', 'assignee'])->get();
        }

        return view('tasks.index', compact('tasks', 'stats', 'view', 'boardTasks'));
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
            'reporter_id'      => auth()->id(),
            'source'           => 'web_form',
            'area'             => $validated['area'] ?? null,
            'estimated_effort' => $validated['estimated_effort'] ?? 'medium',
        ]);

        // Lanzamos la IA de refinamiento
        RefineTaskWithAi::dispatch($task);

        // Intentamos asignación automática (no falla si no encuentra dev)
        $assignmentService->assign($task);

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Task created. AI refinement in progress and auto-assignment attempted.');
    }

    public function show(Task $task)
    {
        return view('tasks.show', compact('task'));
    }

    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,in_refinement,ready_for_dev,in_progress,done,blocked'],
        ]);

        $task->update(['status' => $validated['status']]);

        return back()->with('status', 'Task status updated.');
    }
}
