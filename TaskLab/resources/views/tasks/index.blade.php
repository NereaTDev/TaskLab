<x-app-layout>
    <div class="max-w-[1600px] mx-auto py-6 px-4">
        {{-- Barra de búsqueda y filtros (estilo DevTask) --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="flex flex-1 flex-wrap items-center gap-2 min-w-0">
                <div class="flex-1 min-w-[200px] flex items-center gap-2 rounded-lg border border-slate-800 bg-tasklab-bg-muted px-3 py-2 text-sm">
                    <svg class="h-4 w-4 shrink-0 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="text"
                        placeholder="Buscar tareas..."
                        class="w-full border-none text-sm text-tasklab-text placeholder:text-tasklab-muted focus:outline-none focus:ring-0"
                    >
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Tipo
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Prioridad
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
                </div>
            </div>
            <a href="{{ route('tasks.create') }}" class="shrink-0 inline-flex items-center gap-2 rounded-full bg-tasklab-accent px-4 py-2 text-sm font-medium text-slate-950 hover:bg-tasklab-accent-soft">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Tarea
            </a>
        </div>

        @if($view === 'board')
            {{-- Stats para tablero global --}}
            <div class="mb-4 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Total tareas</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-slate-900 text-[11px] text-tasklab-text">TT</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">{{ $stats['total'] ?? 0 }}</p>
                </div>

                <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Pendientes</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-amber-900/50 text-[11px] text-amber-200">P</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">{{ $stats['pending'] ?? 0 }}</p>
                </div>

                <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>En progreso</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-sky-900/50 text-[11px] text-sky-100">EP</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">{{ $stats['in_progress'] ?? 0 }}</p>
                </div>

                <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Completadas</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-900/40 text-[11px] text-emerald-100">C</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">{{ $stats['done'] ?? 0 }}</p>
                </div>

                <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Vencidas</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-red-900/40 text-[11px] text-red-100">V</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">0</p>
                </div>

                <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Tasa de éxito</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-tasklab-accent/20 text-[11px] text-tasklab-accent">%</span>
                    </div>
                    @php
                        $total = $stats['total'] ?? 0;
                        $done = $stats['done'] ?? 0;
                        $successRate = $total > 0 ? round(($done / $total) * 100) : 0;
                    @endphp
                    <p class="text-heading font-semibold text-tasklab-text">{{ $successRate }}%</p>
                </div>
            </div>

            {{-- Tablero global de la empresa --}}
            <x-task-kanban-board :tasks="$boardTasks ?? collect()" />
        @elseif($view === 'analysis')
            {{-- Vista Análisis (placeholder) --}}
            <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-8 text-center shadow-card">
                <svg class="mx-auto h-12 w-12 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <h2 class="mt-4 text-lg font-semibold text-tasklab-text">Análisis</h2>
                <p class="mt-2 text-sm text-tasklab-muted">Gráficos y métricas de tareas (próximamente).</p>
            </div>
        @else
            {{-- Dashboard: tablero personal del usuario --}}
            <x-task-kanban-board :tasks="$dashboardTasks ?? collect()" />
        @endif
    </div>
</x-app-layout>
