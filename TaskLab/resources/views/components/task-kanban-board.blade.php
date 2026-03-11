@props(['tasks', 'categoryTypes' => collect(), 'users' => collect(), 'openTaskId' => null, 'archivedView' => false, 'activeStatus' => 'all'])

@php
  if ($archivedView) {
    $columnConfig = [
      'archived' => [
        'label'    => 'Archivadas',
        'statuses' => ['new', 'ready_for_dev', 'in_progress', 'blocked', 'done', 'archived'],
        'target'   => 'archived',
        'icon'     => 'archive',
        'bg'       => 'bg-slate-900',
        'header'   => 'bg-slate-800 border-slate-700',
        'badge'    => 'bg-slate-800 text-tasklab-muted border border-slate-600',
      ],
    ];
  } else {
    $columnConfig = [
    'backlog' => [
      'label'    => 'Backlog',
      'statuses' => ['new'],
      'target'   => 'new',
      'icon'     => 'inbox',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-slate-800 border-slate-700',
      'badge'    => 'bg-slate-800 text-tasklab-muted border border-slate-600',
    ],
    'pending' => [
      'label'    => 'Pendiente',
      'statuses' => ['ready_for_dev'],
      'target'   => 'ready_for_dev',
      'icon'     => 'clock',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-slate-800 border-slate-700',
      'badge'    => 'bg-tasklab-accent/10 text-tasklab-accent border border-tasklab-accent/40',
    ],
    'in_progress' => [
      'label'    => 'En Progreso',
      'statuses' => ['in_progress'],
      'target'   => 'in_progress',
      'icon'     => 'bolt',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-tasklab-primary/10 border-tasklab-primary/40',
      'badge'    => 'bg-tasklab-primary/20 text-tasklab-primary border border-tasklab-primary/60',
    ],
    'in_review' => [
      'label'    => 'En Revisión',
      'statuses' => ['blocked'],
      'target'   => 'blocked',
      'icon'     => 'eye',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-violet-900/30 border-violet-600/70',
      'badge'    => 'bg-violet-900/40 text-violet-100 border border-violet-700/70',
    ],
    'done' => [
      'label'    => 'Completada',
      'statuses' => ['done'],
      'target'   => 'done',
      'icon'     => 'check',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-tasklab-success/15 border-tasklab-success/40',
      'badge'    => 'bg-tasklab-success/20 text-tasklab-success border border-tasklab-success/60',
    ],
  ];
  }

  // Si hay un estado activo concreto (y no estamos en archivadas), reducimos a la columna correspondiente
  $filteredColumnKey = null;
  if (! $archivedView && $activeStatus && $activeStatus !== 'all') {
      $statusToColumn = [
          'new'           => 'backlog',
          'ready_for_dev' => 'pending',
          'in_progress'   => 'in_progress',
          'blocked'       => 'in_review',
          'done'          => 'done',
      ];

      if (isset($statusToColumn[$activeStatus])) {
          $filteredColumnKey = $statusToColumn[$activeStatus];
          $columnConfig = array_intersect_key($columnConfig, [$filteredColumnKey => true]);
      }
  }

  $priorityColors = [
    'critical' => 'bg-tasklab-danger/20 text-tasklab-danger border border-tasklab-danger/60',
    'high'     => 'bg-tasklab-accent/10 text-tasklab-accent border border-tasklab-accent/40',
    'medium'   => 'bg-tasklab-primary/10 text-tasklab-primary border border-tasklab-primary/40',
    'low'      => 'bg-tasklab-bg-muted text-tasklab-muted border border-slate-800',
  ];

  $typeLabels = [
    'bug'         => 'Bug',
    'feature'     => 'Evolutiva',
    'improvement' => 'Mejora',
    'question'    => 'Consulta',
  ];
@endphp

<div
  class="grid grid-cols-1 {{ ($archivedView || $filteredColumnKey) ? '' : 'md:grid-cols-3 lg:grid-cols-5' }} gap-4"
  x-data="taskBoard(
    '{{ route('tasks.updateStatus', ['task' => 'TASK_ID_PLACEHOLDER'], false) }}',
    @js($tasks->values()),
    @js($categoryTypes->map(fn($t) => [
        'id'     => $t->id,
        'name'   => $t->name,
        'slug'   => $t->slug,
        'values' => $t->values->map(fn($v) => [
            'id'               => $v->id,
            'name'             => $v->name,
            'parent_id'        => $v->parent_id,
            'category_type_id' => $v->category_type_id,
        ]),
    ]))
  )"
  @if($openTaskId)
    x-init="(() => { const id = {{ (int) $openTaskId }}; const t = tasks.find(task => Number(task.id) === id); if (t) { openTaskModal(t); } })()"
  @endif
>
  @foreach($columnConfig as $key => $col)
    @php
      $count = $tasks->whereIn('status', $col['statuses'])->count();
    @endphp
    <div
      class="rounded-xl border border-slate-800 {{ $col['bg'] }} flex flex-col min-h-[400px]"
      @dragover.prevent
      @drop.prevent="moveTaskToStatus('{{ $col['target'] }}')"
    >
      {{-- Encabezado de columna --}}
      <div class="flex items-center justify-between px-3 py-2.5 rounded-t-xl border-b {{ $col['header'] }}">
        <div class="flex items-center gap-2">
          @if($col['icon'] === 'clock')
            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          @elseif($col['icon'] === 'bolt')
            <svg class="h-4 w-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          @elseif($col['icon'] === 'eye')
            <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          @elseif($col['icon'] === 'archive')
            <svg class="h-4 w-4 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 7V5a2 2 0 012-2h10a2 2 0 012 2v2M5 7v10a2 2 0 002 2h10a2 2 0 002-2V7"/></svg>
          @else
            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          @endif
          <span class="text-sm font-semibold text-tasklab-text">{{ $col['label'] }}</span>
        </div>
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $col['badge'] }}">{{ $count }}</span>
        @if(in_array($key, ['pending', 'in_progress']))
          <a href="{{ route('tasks.create') }}" class="p-1 rounded-md text-tasklab-accent hover:bg-tasklab-accent/10 hover:text-tasklab-accent" title="Añadir tarea">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          </a>
        @endif
      </div>

      <div class="flex-1 p-2 space-y-2 overflow-y-auto" data-column-body="{{ $col['target'] }}">
        <template x-for="task in columnTasks(@js($col['statuses']))" :key="task.id">
          <div
            class="block rounded-lg border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card hover:border-tasklab-accent transition-shadow cursor-move"
            draggable="true"
            :data-task-id="task.id"
            @dragstart="draggedTaskId = task.id"
            @dragend="draggedTaskId = null"
            @click.stop="openTaskModal(task)"
          >
            <h3 class="text-sm font-medium text-tasklab-text line-clamp-2" x-text="task.title || ('Sin título #' + task.id)"></h3>
            <div class="mt-2 flex flex-wrap gap-1">
              <span
                class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                :class="{
                  'bg-tasklab-danger/20 text-tasklab-danger border border-tasklab-danger/60': task.priority === 'critical',
                  'bg-tasklab-accent/10 text-tasklab-accent border border-tasklab-accent/40': task.priority === 'high',
                  'bg-tasklab-primary/10 text-tasklab-primary border border-tasklab-primary/40': task.priority === 'medium',
                  'bg-tasklab-bg-muted text-tasklab-muted border border-slate-800': !['critical','high','medium'].includes(task.priority),
                }"
              >
                <span x-text="task.priority === 'critical' ? 'Crítica' : (task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Media')"></span>
              </span>
              <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-tasklab-bg text-tasklab-muted border border-slate-800">
                <span x-text="
                  task.type === 'bug' ? 'Bug' :
                  task.type === 'feature' ? 'Evolutiva' :
                  task.type === 'improvement' ? 'Mejora' :
                  task.type === 'question' ? 'Consulta' :
                  (task.type ? task.type : '')
                "></span>
              </span>
              <span
                x-show="task.points"
                class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-slate-900 text-tasklab-muted border border-slate-700"
              >
                ⏱ <span class="ml-0.5" x-text="task.points + ' h'"></span>
              </span>
            </div>
            <div class="mt-2 flex items-center gap-3 text-[11px] text-tasklab-muted">
              <span class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-text="task.created_at ? new Date(task.created_at).toLocaleDateString('es-ES') : ''"></span>
              </span>
            </div>
            <div class="mt-1.5 flex items-center gap-1 text-[11px] text-tasklab-muted">
              <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <span x-text="task.assignee && task.assignee.name ? task.assignee.name : 'Sin asignar'"></span>
            </div>
          </div>
        </template>

        <template x-if="columnTasks(@js($col['statuses'])).length === 0">
          <div class="flex flex-col items-center justify-center py-12 text-tasklab-muted">
            @if($col['icon'] === 'clock')
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($col['icon'] === 'bolt')
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            @elseif($col['icon'] === 'eye')
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            @else
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
            @endif
            <p class="text-label font-medium text-tasklab-muted">Sin tareas</p>
          </div>
        </template>
      </div>
    </div>
  @endforeach

  <x-task-detail-modal :categoryTypes="$categoryTypes" :users="$users" />
</div>
