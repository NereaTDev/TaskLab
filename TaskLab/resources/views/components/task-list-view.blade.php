@props(['tasks', 'categoryTypes' => collect(), 'users' => collect(), 'openTaskId' => null])

@php
    $statusFilter = request()->get('status');

    $groups = [
        'new' => [
            'label'  => 'Backlog',
            'icon'   => 'inbox',
            'header' => 'bg-slate-800/60 border-slate-700',
            'badge'  => 'bg-slate-800 text-tasklab-muted border border-slate-600',
            'bar'    => 'bg-slate-600',
        ],
        'ready_for_dev' => [
            'label'  => 'Pendiente',
            'icon'   => 'clock',
            'header' => 'bg-slate-800/60 border-slate-700',
            'badge'  => 'bg-tasklab-accent/10 text-tasklab-accent border border-tasklab-accent/40',
            'bar'    => 'bg-tasklab-accent',
        ],
        'in_progress' => [
            'label'  => 'En Progreso',
            'icon'   => 'bolt',
            'header' => 'bg-tasklab-primary/10 border-tasklab-primary/30',
            'badge'  => 'bg-tasklab-primary/20 text-tasklab-primary border border-tasklab-primary/60',
            'bar'    => 'bg-tasklab-primary',
        ],
        'blocked' => [
            'label'  => 'En Revisión',
            'icon'   => 'eye',
            'header' => 'bg-violet-900/30 border-violet-600/50',
            'badge'  => 'bg-violet-900/40 text-violet-100 border border-violet-700/70',
            'bar'    => 'bg-violet-500',
        ],
        'done' => [
            'label'  => 'Completada',
            'icon'   => 'check',
            'header' => 'bg-tasklab-success/10 border-tasklab-success/30',
            'badge'  => 'bg-tasklab-success/20 text-tasklab-success border border-tasklab-success/60',
            'bar'    => 'bg-tasklab-success',
        ],
    ];

    $moveTargets = [
        'new'           => 'Backlog',
        'ready_for_dev' => 'Pendiente',
        'in_progress'   => 'En Progreso',
        'blocked'       => 'En Revisión',
        'done'          => 'Completada',
    ];
@endphp

<div
  x-data="taskBoard('{{ route('tasks.updateStatus', ['task' => 'TASK_ID_PLACEHOLDER'], false) }}', @js($tasks->values()))"
  @if($openTaskId)
    x-init="(() => { const id = {{ (int) $openTaskId }}; const t = tasks.find(task => Number(task.id) === id); if (t) { openTaskModal(t); } })()"
  @endif
>

  @if($statusFilter === 'archived')
    {{-- ── Vista archivadas ──────────────────────────────────────────────────── --}}
    <div class="mt-4 rounded-2xl border border-slate-800 bg-tasklab-bg-muted shadow-card overflow-hidden">
      <div class="px-4 py-2.5 border-b border-slate-800 flex items-center justify-between bg-slate-900/80">
        <h3 class="text-label font-semibold text-tasklab-text">Archivadas</h3>
        <span class="text-meta text-tasklab-muted">{{ $tasks->count() }} tareas</span>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-body">
          <thead class="bg-slate-900/40 text-label text-tasklab-muted">
            <tr>
              <th class="px-4 py-2 whitespace-nowrap">ID</th>
              <th class="px-4 py-2">Título</th>
              <th class="px-4 py-2 whitespace-nowrap hidden sm:table-cell">Tipo</th>
              <th class="px-4 py-2 whitespace-nowrap hidden sm:table-cell">Prioridad</th>
              <th class="px-4 py-2 whitespace-nowrap hidden md:table-cell">Asignado</th>
              <th class="px-4 py-2 whitespace-nowrap hidden md:table-cell">Creada</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 bg-tasklab-bg">
            @forelse($tasks as $task)
              <tr
                class="hover:bg-slate-900/60 cursor-pointer"
                @click.stop="openTaskModal(tasks.find(t => Number(t.id) === {{ $task->id }}))"
              >
                <td class="px-4 py-2 text-meta text-tasklab-muted whitespace-nowrap">#{{ $task->id }}</td>
                <td class="px-4 py-2 text-body text-tasklab-text">
                  <span class="hover:text-tasklab-accent">{{ $task->title ?? 'Task #'.$task->id }}</span>
                  <div class="text-meta text-tasklab-muted line-clamp-1">{{ Str::limit($task->description_raw, 120) }}</div>
                </td>
                <td class="px-4 py-2 text-label text-tasklab-muted hidden sm:table-cell">{{ ucfirst($task->type) }}</td>
                <td class="px-4 py-2 text-label text-tasklab-muted hidden sm:table-cell">{{ ucfirst($task->priority) }}</td>
                <td class="px-4 py-2 text-label text-tasklab-muted hidden md:table-cell whitespace-nowrap">{{ optional($task->assignee)->name ?? 'Sin asignar' }}</td>
                <td class="px-4 py-2 text-label text-tasklab-muted hidden md:table-cell whitespace-nowrap">{{ optional($task->created_at)->format('d/m/Y') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-4 py-6 text-center text-label text-tasklab-muted">No hay tareas archivadas.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  @else
    {{-- ── Vista lista estilo kanban: grupos colapsables por estado ───────────── --}}
    <div class="mt-4 space-y-2">
      @foreach($groups as $status => $col)
        <div
          x-data="{ open: localStorage.getItem('listgroup_{{ $status }}') !== 'false' }"
          x-init="$watch('open', v => localStorage.setItem('listgroup_{{ $status }}', String(v)))"
          class="rounded-xl border border-slate-800 bg-tasklab-bg-muted overflow-hidden"
        >
          {{-- ── Cabecera del grupo ────────────────────────────────────────── --}}
          <button
            type="button"
            class="w-full flex items-center gap-3 px-4 py-2.5 border-b {{ $col['header'] }} hover:opacity-90 transition-opacity"
            @click="open = !open"
          >
            {{-- Icono --}}
            @if($col['icon'] === 'clock')
              <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($col['icon'] === 'bolt')
              <svg class="h-4 w-4 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            @elseif($col['icon'] === 'eye')
              <svg class="h-4 w-4 text-violet-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            @elseif($col['icon'] === 'check')
              <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            @else
              <svg class="h-4 w-4 text-tasklab-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            @endif

            <span class="text-sm font-semibold text-tasklab-text">{{ $col['label'] }}</span>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $col['badge'] }}" x-text="columnTasks(['{{ $status }}']).length"></span>

            {{-- Chevron --}}
            <svg class="h-4 w-4 text-tasklab-muted ml-auto transition-transform duration-150" :class="open ? '' : '-rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          {{-- ── Filas de tareas ───────────────────────────────────────────── --}}
          <div x-show="open">
            <template x-for="task in columnTasks(['{{ $status }}'])" :key="task.id">
              <div
                class="group flex items-center gap-3 px-4 py-2.5 border-b border-slate-800/50 last:border-b-0 hover:bg-slate-900/40 cursor-pointer transition-colors"
                @click.stop="openTaskModal(task)"
              >
                {{-- Barra de prioridad --}}
                <div
                  class="w-0.5 self-stretch rounded-full shrink-0 min-h-[1.5rem]"
                  :class="{
                    'bg-tasklab-danger': task.priority === 'critical',
                    'bg-tasklab-accent': task.priority === 'high',
                    'bg-tasklab-primary': task.priority === 'medium',
                    'bg-slate-700': !task.priority || task.priority === 'low',
                  }"
                ></div>

                {{-- Título + descripción --}}
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="text-sm text-tasklab-text truncate" x-text="task.title || ('Sin título #' + task.id)"></span>
                    <div x-data="updatedBadge(task.id, task.ai_refined_at)" x-show="show" x-transition.opacity class="shrink-0">
                      <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold bg-orange-500/20 text-orange-400 border border-orange-500/40">updated</span>
                    </div>
                  </div>
                  <p
                    class="hidden sm:block text-[11px] text-tasklab-muted truncate"
                    x-show="task.description_ai || task.description_raw"
                    x-text="task.description_ai || task.description_raw || ''"
                  ></p>
                </div>

                {{-- Badges: prioridad + tipo + puntos --}}
                <div class="hidden sm:flex items-center gap-1.5 shrink-0">
                  <span
                    class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                    :class="{
                      'bg-tasklab-danger/20 text-tasklab-danger border border-tasklab-danger/60': task.priority === 'critical',
                      'bg-tasklab-accent/10 text-tasklab-accent border border-tasklab-accent/40': task.priority === 'high',
                      'bg-tasklab-primary/10 text-tasklab-primary border border-tasklab-primary/40': task.priority === 'medium',
                      'bg-tasklab-bg text-tasklab-muted border border-slate-800': !['critical','high','medium'].includes(task.priority),
                    }"
                    x-text="({'critical':'Crítica','high':'Alta','medium':'Media','low':'Baja'})[task.priority] || 'Baja'"
                  ></span>
                  <span
                    class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-tasklab-bg text-tasklab-muted border border-slate-800"
                    x-text="({'bug':'Bug','feature':'Evolutiva','improvement':'Mejora','question':'Consulta'})[task.type] || task.type"
                  ></span>
                  <span
                    x-show="task.points"
                    class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-slate-900 text-tasklab-muted border border-slate-700"
                  >⏱ <span class="ml-0.5" x-text="task.points + ' h'"></span></span>
                </div>

                {{-- Asignado --}}
                <span
                  class="hidden md:block text-[11px] text-tasklab-muted shrink-0 w-24 truncate text-right"
                  x-text="task.assignee?.name || 'Sin asignar'"
                ></span>

                {{-- Fecha --}}
                <span
                  class="hidden lg:block text-[11px] text-tasklab-muted shrink-0 whitespace-nowrap"
                  x-text="task.created_at ? new Date(task.created_at).toLocaleDateString('es-ES') : ''"
                ></span>

                {{-- Dropdown: mover a otro estado --}}
                <div class="shrink-0 relative" x-data="{ open: false }" @click.stop @click.outside="open = false">
                  <button
                    type="button"
                    class="p-1.5 rounded-lg text-tasklab-muted opacity-0 group-hover:opacity-100 hover:bg-slate-800 hover:text-tasklab-text transition-all"
                    title="Mover a..."
                    @click="open = !open"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                  </button>
                  <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 bottom-full mb-1 z-20 w-44 rounded-xl border border-slate-700 bg-tasklab-bg shadow-xl py-1"
                    style="display:none"
                  >
                    <p class="px-3 pt-1.5 pb-0.5 text-[10px] text-tasklab-muted uppercase tracking-wide">Mover a...</p>
                    @foreach($moveTargets as $s => $label)
                      <button
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-1.5 text-xs text-tasklab-text hover:bg-slate-800 transition-colors"
                        :class="task.status === '{{ $s }}' ? 'opacity-40 pointer-events-none' : ''"
                        @click="setTaskStatus(task.id, '{{ $s }}'); open = false"
                      >
                        <span class="h-1.5 w-1.5 rounded-full shrink-0
                          {{ $s === 'new' ? 'bg-slate-500' : '' }}
                          {{ $s === 'ready_for_dev' ? 'bg-tasklab-accent' : '' }}
                          {{ $s === 'in_progress' ? 'bg-tasklab-primary' : '' }}
                          {{ $s === 'blocked' ? 'bg-violet-500' : '' }}
                          {{ $s === 'done' ? 'bg-tasklab-success' : '' }}
                        "></span>
                        {{ $label }}
                      </button>
                    @endforeach
                  </div>
                </div>
              </div>
            </template>

            {{-- Estado vacío --}}
            <template x-if="columnTasks(['{{ $status }}']).length === 0">
              <div class="px-4 py-5 text-center text-[11px] text-tasklab-muted">Sin tareas en este estado</div>
            </template>
          </div>
        </div>
      @endforeach
    </div>
  @endif

</div>
