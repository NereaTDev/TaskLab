<x-app-layout>
    <div class="max-w-[1600px] mx-auto py-6 px-4">
        {{-- Barra de búsqueda y filtros (estilo DevTask) --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="flex flex-1 flex-wrap items-center gap-2 min-w-0">
                <div class="flex-1 min-w-[200px] flex items-center gap-2 rounded-xl border border-slate-800 bg-tasklab-bg-muted px-3 py-1 text-body">
                    <svg class="h-4 w-4 shrink-0 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="text"
                        placeholder="Buscar tareas..."
                        class="w-full bg-transparent border-none text-body text-tasklab-text placeholder:text-tasklab-muted focus:outline-none focus:ring-0 p-1"
                    >
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-2 text-body font-medium text-tasklab-muted hover:border-tasklab-accent hover:text-tasklab-accent transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Tipo
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-2 text-body font-medium text-tasklab-muted hover:border-tasklab-accent hover:text-tasklab-accent transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Prioridad
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-2 text-body font-medium text-tasklab-muted hover:border-tasklab-accent hover:text-tasklab-accent transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Estado
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-2 text-body font-medium text-tasklab-muted hover:border-tasklab-accent hover:text-tasklab-accent transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Asignado
                    </button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-2 text-body font-medium text-tasklab-muted hover:border-tasklab-accent hover:text-tasklab-accent transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Fecha
                    </button>
                </div>
            </div>
            <a href="{{ route('tasks.create') }}" class="shrink-0 inline-flex items-center gap-2 rounded-full bg-tasklab-accent px-4 py-2 text-body font-medium text-slate-950 hover:bg-tasklab-accent-soft">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Tarea
            </a>
        </div>

        @php
            $total = $stats['total'] ?? 0;
            $done = $stats['done'] ?? 0;
            $pending = $stats['pending'] ?? 0;
            $inProgress = $stats['in_progress'] ?? 0;
            $successRate = $total > 0 ? round(($done / $total) * 100) : 0;
        @endphp

        @if($view === 'analysis')
            {{-- Stats globales (solo en Análisis) --}}
            <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Total tareas</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-slate-900 text-[11px] text-tasklab-text">TT</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">{{ $total }}</p>
                </div>

                <div class="rounded-xl border border-tasklab-warning/40 bg-tasklab-warning/10 p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Pendientes</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-tasklab-bg text-[11px] text-tasklab-warning">P</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">{{ $pending }}</p>
                </div>

                <div class="rounded-xl border border-tasklab-primary/40 bg-tasklab-primary/10 p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>En progreso</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-tasklab-bg text-[11px] text-tasklab-primary">EP</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">{{ $inProgress }}</p>
                </div>

                <div class="rounded-xl border border-tasklab-success/40 bg-tasklab-success/10 p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Completadas</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-tasklab-bg text-[11px] text-tasklab-success">C</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">{{ $done }}</p>
                </div>

                <div class="rounded-xl border border-tasklab-danger/40 bg-tasklab-danger/10 p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Vencidas</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-tasklab-bg text-[11px] text-tasklab-danger">V</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">0</p>
                </div>

                <div class="rounded-xl border border-tasklab-accent/40 bg-tasklab-accent/10 p-3 shadow-card flex flex-col gap-2">
                    <div class="flex items-center justify-between text-label text-tasklab-muted">
                        <span>Tasa de éxito</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-tasklab-bg text-[11px] text-tasklab-accent">%</span>
                    </div>
                    <p class="text-heading font-semibold text-tasklab-text">{{ $successRate }}%</p>
                </div>
            </div>
        @endif

        @if($view === 'board')
            {{-- Tablero global de la empresa --}}
            <x-task-kanban-board :tasks="$boardTasks ?? collect()" :categoryTypes="$categoryTypes ?? collect()" :users="$selectableUsers ?? collect()" />
        @elseif($view === 'analysis')
            {{-- Vista Análisis: layout inspirado en DevTask, adaptado a TaskLab (dark) --}}
            <div class="space-y-6">
                {{-- Fila de gráficos --}}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    {{-- Tareas por tipo --}}
                    <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-title font-semibold text-tasklab-text">Tareas por tipo</h2>
                                <p class="text-label text-tasklab-muted">Distribución por naturaleza de la tarea</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-meta text-tasklab-muted">
                                Total: <span class="ml-1 text-tasklab-text font-semibold">{{ $total }}</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-6">
                            <div class="relative mx-auto h-32 w-32">
                                <div class="absolute inset-0 rounded-full bg-[conic-gradient(var(--tw-gradient-stops))] from-tasklab-primary via-tasklab-accent to-violet-500/80 opacity-80"></div>
                                <div class="absolute inset-4 rounded-full bg-tasklab-bg"></div>
                            </div>
                            <div class="flex-1 space-y-2 text-body">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-tasklab-primary"></span>
                                        <span class="text-tasklab-text">Evolutiva</span>
                                    </div>
                                    <span class="text-label text-tasklab-muted">
                                        {{ $analysisTypeStats['evolutiva']['count'] ?? 0 }} tareas · {{ $analysisTypeStats['evolutiva']['percentage'] ?? 0 }}%
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                                        <span class="text-tasklab-text">Correctiva</span>
                                    </div>
                                    <span class="text-label text-tasklab-muted">
                                        {{ $analysisTypeStats['correctiva']['count'] ?? 0 }} tareas · {{ $analysisTypeStats['correctiva']['percentage'] ?? 0 }}%
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-tasklab-success"></span>
                                        <span class="text-tasklab-text">Preventiva</span>
                                    </div>
                                    <span class="text-label text-tasklab-muted">
                                        {{ $analysisTypeStats['preventiva']['count'] ?? 0 }} tareas · {{ $analysisTypeStats['preventiva']['percentage'] ?? 0 }}%
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-tasklab-muted"></span>
                                        <span class="text-tasklab-text">Soporte</span>
                                    </div>
                                    <span class="text-label text-tasklab-muted">
                                        {{ $analysisTypeStats['soporte']['count'] ?? 0 }} tareas · {{ $analysisTypeStats['soporte']['percentage'] ?? 0 }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tareas por prioridad --}}
                    <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-title font-semibold text-tasklab-text">Tareas por prioridad</h2>
                                <p class="text-label text-tasklab-muted">Equilibrio entre crítica, alta, media y baja</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <div class="relative mx-auto h-32 w-32">
                                <div class="absolute inset-0 rounded-full bg-[conic-gradient(var(--tw-gradient-stops))] from-tasklab-danger via-tasklab-accent via-60% via-tasklab-primary to-tasklab-success opacity-80"></div>
                                <div class="absolute inset-4 rounded-full bg-tasklab-bg"></div>
                            </div>
                            <div class="flex-1 space-y-2 text-body">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-tasklab-danger"></span>
                                        <span class="text-tasklab-text">Crítica</span>
                                    </div>
                                    <span class="text-label text-tasklab-muted">
                                        {{ $analysisPriorityStats['critica']['count'] ?? 0 }} tareas · {{ $analysisPriorityStats['critica']['percentage'] ?? 0 }}%
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-tasklab-accent"></span>
                                        <span class="text-tasklab-text">Alta</span>
                                    </div>
                                    <span class="text-label text-tasklab-muted">
                                        {{ $analysisPriorityStats['alta']['count'] ?? 0 }} tareas · {{ $analysisPriorityStats['alta']['percentage'] ?? 0 }}%
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-tasklab-primary"></span>
                                        <span class="text-tasklab-text">Media</span>
                                    </div>
                                    <span class="text-label text-tasklab-muted">
                                        {{ $analysisPriorityStats['media']['count'] ?? 0 }} tareas · {{ $analysisPriorityStats['media']['percentage'] ?? 0 }}%
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-tasklab-muted"></span>
                                        <span class="text-tasklab-text">Baja</span>
                                    </div>
                                    <span class="text-label text-tasklab-muted">
                                        {{ $analysisPriorityStats['baja']['count'] ?? 0 }} tareas · {{ $analysisPriorityStats['baja']['percentage'] ?? 0 }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tareas por desarrollador (barras horizontales) --}}
                    <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-title font-semibold text-tasklab-text">Tareas por desarrollador</h2>
                                <p class="text-label text-tasklab-muted">Top de desarrolladores por nº de tareas asignadas.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @if(!empty($analysisDeveloperStats))
                                @foreach($analysisDeveloperStats as $index => $dev)
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 text-right text-label text-tasklab-muted">{{ $index + 1 }}.</span>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-body text-tasklab-text">{{ optional($dev['user'])->name ?? 'Sin asignar' }}</span>
                                                <span class="text-label text-tasklab-muted">{{ $dev['task_count'] }} tareas</span>
                                            </div>
                                            <div class="mt-1 h-1.5 rounded-full bg-slate-900">
                                                <div
                                                    class="h-1.5 rounded-full bg-tasklab-primary"
                                                    style="width: {{ max($dev['percentage'], 8) }}%;"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @elseif(!empty($analysisTeamMembers))
                                @foreach($analysisTeamMembers as $index => $member)
                                    @php
                                        /** @var \App\Models\User $devUser */
                                        $devUser = $member['user'];
                                    @endphp
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 text-right text-label text-tasklab-muted">{{ $index + 1 }}.</span>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-body text-tasklab-text">{{ $devUser->name }}</span>
                                                <span class="text-label text-tasklab-muted">0 tareas</span>
                                            </div>
                                            <div class="mt-1 h-1.5 rounded-full bg-slate-900">
                                                <div class="h-1.5 rounded-full bg-tasklab-primary w-0"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-meta text-tasklab-muted">Aún no hay desarrolladores con tareas asignadas.</p>
                            @endif
                            <p class="text-meta text-tasklab-muted mt-1">Basado en tareas actualmente asignadas.</p>
                        </div>
                    </div>
                </div>

                {{-- Fila inferior: equipo + resumen de actividad --}}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    {{-- Equipo de desarrollo --}}
                    <div class="xl:col-span-2 rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-title font-semibold text-tasklab-text">Equipo de desarrollo</h2>
                                <p class="text-label text-tasklab-muted">Carga de trabajo por desarrollador (basado en tareas activas).</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @if(!empty($analysisTeamMembers))
                                @foreach($analysisTeamMembers as $member)
                                    @php
                                        /** @var \App\Models\User $devUser */
                                        $devUser = $member['user'];
                                        $profile = $member['profile'];
                                    @endphp
                                    <div class="flex items-center gap-3 rounded-xl border border-slate-800 bg-tasklab-bg p-3">
                                        <div class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-meta font-semibold text-tasklab-text">
                                            {{ strtoupper(substr($devUser->name ?? 'D', 0, 2)) }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-body font-medium text-tasklab-text">{{ $devUser->name }}</p>
                                                    <p class="text-meta text-tasklab-muted">
                                                        {{ $profile?->type ? ucfirst($profile->type) : 'Sin tipo definido' }}
                                                        @if($profile && $profile->active)
                                                            · Disponible
                                                        @elseif($profile)
                                                            · No disponible
                                                        @endif
                                                    </p>
                                                </div>
                                                <span class="inline-flex items-center rounded-full border border-tasklab-danger/50 bg-tasklab-danger/10 px-2 py-0.5 text-meta text-tasklab-danger">
                                                    {{ $member['active_tasks'] }}/{{ $member['capacity'] ?? '∞' }}
                                                </span>
                                            </div>
                                            <div class="mt-2 h-1.5 rounded-full bg-slate-900">
                                                <div
                                                    class="h-1.5 rounded-full bg-tasklab-danger"
                                                    style="width: {{ max($member['progress_percentage'], 5) }}%;"
                                                ></div>
                                            </div>
                                            <p class="mt-1 text-meta text-tasklab-muted">
                                                {{ $member['active_tasks'] }} tareas activas · {{ $member['done_tasks'] }} completadas (total {{ $member['total_tasks'] }})
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-meta text-tasklab-muted">Aún no hay desarrolladores con perfil configurado.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Resumen de actividad --}}
                    <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
                        <h2 class="text-title font-semibold text-tasklab-text">Resumen de actividad</h2>
                        <p class="mt-1 text-label text-tasklab-muted">Métricas simples a partir de los estados actuales.</p>

                        <dl class="mt-4 space-y-3 text-body">
                            <div class="flex items-center justify-between">
                                <dt class="text-tasklab-muted">Tareas totales</dt>
                                <dd class="text-tasklab-text font-semibold">{{ $total }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-tasklab-muted">Tareas pendientes</dt>
                                <dd class="text-tasklab-text font-semibold">{{ $pending }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-tasklab-muted">Tareas en progreso</dt>
                                <dd class="text-tasklab-text font-semibold">{{ $inProgress }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-tasklab-muted">Tareas completadas</dt>
                                <dd class="text-tasklab-text font-semibold">{{ $done }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-tasklab-muted">Tasa de éxito</dt>
                                <dd class="text-tasklab-text font-semibold">{{ $successRate }}%</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-tasklab-muted">Promedio tareas/dev</dt>
                                <dd class="text-tasklab-text font-semibold">—</dd>
                            </div>
                        </dl>

                        <p class="mt-4 text-meta text-tasklab-muted">Puedes ampliar esta tarjeta con métricas diarias (creadas hoy, completadas hoy, etc.) cuando añadamos lógica en el backend.</p>
                    </div>
                </div>
            </div>
        @else
            {{-- Dashboard: tablero personal del usuario --}}
            <x-task-kanban-board :tasks="$dashboardTasks ?? collect()" />
        @endif
    </div>
</x-app-layout>
