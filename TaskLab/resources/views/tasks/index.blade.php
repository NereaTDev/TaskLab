<x-app-layout>
    <div class="max-w-6xl mx-auto py-8 px-4 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">TaskLab Dashboard</h1>
                <p class="mt-1 text-xs text-slate-500">Gestiona las tareas de tu equipo desde un único panel.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('tasks.create') }}" class="inline-flex items-center rounded-full bg-sky-600 px-4 py-2 text-xs font-medium text-white hover:bg-sky-700">
                    + Nueva tarea
                </a>
            </div>
        </div>

        {{-- Tabs visuales --}}
        <div class="border-b border-slate-200">
            <div class="inline-flex rounded-full bg-slate-100 p-1 text-xs">
                <button class="px-3 py-1 rounded-full bg-white shadow-sm text-slate-900 font-medium">
                    Dashboard
                </button>
                <button class="px-3 py-1 rounded-full text-slate-500 hover:text-slate-900">
                    Tablero
                </button>
                <button class="px-3 py-1 rounded-full text-slate-500 hover:text-slate-900">
                    Análisis
                </button>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-2 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Stats estilo DevTask --}}
        <x-task-stats :stats="$stats ?? []" />

        {{-- Filtros / búsqueda --}}
        <div class="mt-4 rounded-full border border-slate-200 bg-white px-3 py-2 flex flex-wrap items-center gap-2 text-[11px]">
            <div class="flex-1 min-w-[160px] flex items-center gap-2">
                <span class="text-slate-400">🔍</span>
                <input
                    type="text"
                    placeholder="Buscar tareas..."
                    class="w-full border-none text-xs text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                >
            </div>
            <div class="flex flex-wrap gap-1">
                <button class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600 hover:bg-slate-50">Tipo</button>
                <button class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600 hover:bg-slate-50">Prioridad</button>
                <button class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600 hover:bg-slate-50">Estado</button>
                <button class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600 hover:bg-slate-50">Asignado</button>
                <button class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600 hover:bg-slate-50">Fecha</button>
            </div>
        </div>

        @if(!$tasks->isEmpty())
            <div class="mt-4">
                <x-task-quick-board :tasks="$tasks->getCollection()" />
            </div>
        @endif

        {{-- Tabla de tareas --}}
        @if($tasks->isEmpty())
            <p class="text-sm text-slate-500 mt-4">No tasks yet.</p>
        @else
            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Task</th>
                            <th class="px-4 py-2 text-left font-medium">Meta</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Owner</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($tasks as $task)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ route('tasks.show', $task) }}" class="block">
                                        <p class="text-sm font-medium text-slate-900 truncate">
                                            {{ $task->title ?? 'Untitled task #' . $task->id }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-500 line-clamp-2">
                                            {{ Str::limit($task->description_raw, 140) }}
                                        </p>
                                    </a>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap gap-1 text-[10px] text-slate-600">
                                        <span class="inline-flex items-center rounded-full border border-slate-200 px-2 py-0.5">{{ ucfirst($task->type) }}</span>
                                        <span class="inline-flex items-center rounded-full border border-slate-200 px-2 py-0.5">{{ ucfirst($task->priority) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-medium text-slate-700">
                                        {{ strtoupper($task->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-[11px] text-slate-500">
                                    {{ optional($task->reporter)->name ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
