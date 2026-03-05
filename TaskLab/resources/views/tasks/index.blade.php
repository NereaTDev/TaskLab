<x-app-layout>
    <div class="max-w-[1600px] mx-auto py-6 px-4">
        {{-- Barra de búsqueda y filtros (estilo DevTask) --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="flex flex-1 flex-wrap items-center gap-2 min-w-0">
                <div class="flex-1 min-w-[200px] flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="text"
                        placeholder="Buscar tareas..."
                        class="w-full border-none text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                    >
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-700">
                        Crítica
                        <button type="button" class="rounded p-0.5 hover:bg-slate-200 text-slate-500" aria-label="Quitar filtro">×</button>
                    </span>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Tipo
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Prioridad
                        <span class="rounded-full bg-slate-200 px-1.5 py-0.5 text-[10px]">1</span>
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Estado
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Asignado
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Fecha
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Limpiar (1)
                    </button>
                </div>
            </div>
            <a href="{{ route('tasks.create') }}" class="shrink-0 inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Tarea
            </a>
        </div>

        @if(session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if($view === 'board')
            {{-- Vista Tablero Kanban --}}
            <x-task-kanban-board :tasks="$boardTasks ?? collect()" />
        @elseif($view === 'analysis')
            {{-- Vista Análisis (placeholder) --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">Análisis</h2>
                <p class="mt-2 text-sm text-slate-500">Gráficos y métricas de tareas (próximamente).</p>
            </div>
        @else
            {{-- Vista Dashboard: stats + quick board + tabla --}}
            <x-task-stats :stats="$stats ?? []" />

            @if(!$tasks->isEmpty())
                <div class="mt-6">
                    <x-task-quick-board :tasks="$tasks->getCollection()" />
                </div>
            @endif

            @if($tasks->isEmpty())
                <p class="mt-6 text-sm text-slate-500">No hay tareas todavía.</p>
            @else
                <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-medium">Tarea</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium">Tipo / Prioridad</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium">Estado</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium">Responsable</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($tasks as $task)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 align-top">
                                        <a href="{{ route('tasks.show', $task) }}" class="block">
                                            <p class="font-medium text-slate-900 truncate">
                                                {{ $task->title ?? 'Sin título #' . $task->id }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-slate-500 line-clamp-2">
                                                {{ Str::limit($task->description_raw, 140) }}
                                            </p>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex flex-wrap gap-1 text-xs text-slate-600">
                                            <span class="inline-flex items-center rounded-full border border-slate-200 px-2 py-0.5">{{ ucfirst($task->type) }}</span>
                                            <span class="inline-flex items-center rounded-full border border-slate-200 px-2 py-0.5">{{ ucfirst($task->priority) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700">
                                            {{ strtoupper($task->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-top text-xs text-slate-500">
                                        {{ optional($task->assignee)->name ?? optional($task->reporter)->name ?? '—' }}
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
        @endif
    </div>
</x-app-layout>
