<?php

namespace App\Http\Controllers;

use App\Jobs\RefineTaskWithAi;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        // Listado paginado para la tabla principal
        $tasks = Task::latest()->paginate(15);

        // Stats sencillas para el dashboard (inspirado en DevTask)
        $stats = [
            'total'       => Task::count(),
            'pending'     => Task::where('status', 'new')->count(),
            'in_progress' => Task::whereIn('status', ['in_refinement', 'ready_for_dev', 'in_progress'])->count(),
            'done'        => Task::where('status', 'done')->count(),
        ];

        return view('tasks.index', compact('tasks', 'stats'));
    }

    public function create()
    {
        return view('tasks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => ['required', 'in:bug,feature,improvement,question'],
            'url'         => ['nullable', 'string', 'max:255'],
            'priority'    => ['required', 'in:low,medium,high,critical'],
            'description' => ['required', 'string'],
        ]);

        $descriptionRaw = $validated['description'];
        if (!empty($validated['url'])) {
            $descriptionRaw .= "\n\nURL: " . $validated['url'];
        }

        $task = Task::create([
            'title'           => null,
            'description_raw' => $descriptionRaw,
            'type'            => $validated['type'],
            'status'          => 'new',
            'priority'        => $validated['priority'],
            'reporter_id'     => auth()->id(),
            'source'          => 'web_form',
        ]);

        RefineTaskWithAi::dispatch($task);

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Task created. AI refinement in progress.');
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
