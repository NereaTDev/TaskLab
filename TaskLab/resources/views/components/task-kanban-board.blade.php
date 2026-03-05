@props(['tasks'])

@php
  $columnConfig = [
    'pending' => [
      'label'   => 'Pendiente',
      'statuses' => ['new', 'in_refinement', 'ready_for_dev'],
      'icon'    => 'clock',
      'bg'      => 'bg-slate-900',
      'header'  => 'bg-slate-800 border-slate-700',
      'badge'   => 'bg-slate-700 text-slate-100',
    ],
    'in_progress' => [
      'label'    => 'En Progreso',
      'statuses' => ['in_progress'],
      'icon'     => 'bolt',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-sky-900/50 border-sky-700',
      'badge'    => 'bg-sky-800 text-sky-100',
    ],
    'in_review' => [
      'label'    => 'En Revisión',
      'statuses' => ['blocked'],
      'icon'     => 'eye',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-violet-900/40 border-violet-700',
      'badge'    => 'bg-violet-800 text-violet-100',
    ],
    'done' => [
      'label'    => 'Completada',
      'statuses' => ['done'],
      'icon'     => 'check',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-emerald-900/40 border-emerald-700',
      'badge'    => 'bg-emerald-800 text-emerald-100',
    ],
  ];

  $priorityColors = [
    'critical' => 'bg-red-500 text-white',
    'high'     => 'bg-orange-100 text-orange-800',
    'medium'   => 'bg-slate-100 text-slate-700',
    'low'      => 'bg-slate-100 text-slate-500',
  ];

  $typeLabels = [
    'bug'         => 'Bug',
    'feature'     => 'Evolutiva',
    'improvement' => 'Mejora',
    'question'    => 'Consulta',
  ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  @foreach($columnConfig as $key => $col)
    @php
      $colTasks = $tasks->whereIn('status', $col['statuses']);
      $count = $colTasks->count();
    @endphp
    <div class="rounded-xl border border-slate-800 {{ $col['bg'] }} flex flex-col min-h-[400px]">
      {{-- Encabezado de columna --}}
      <div class="flex items-center justify-between px-3 py-2.5 rounded-t-xl border-b {{ $col['header'] }}">
        <div class="flex items-center gap-2">
          @if($col['icon'] === 'clock')
            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          @elseif($col['icon'] === 'bolt')
            <svg class="h-4 w-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          @elseif($col['icon'] === 'eye')
            <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          @else
            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          @endif
          <span class="text-sm font-semibold text-tasklab-text">{{ $col['label'] }}</span>
        </div>
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $col['badge'] }}">{{ $count }}</span>
        @if(in_array($key, ['pending', 'in_progress']))
          <a href="{{ route('tasks.create') }}" class="p-1 rounded-md text-slate-500 hover:bg-white/60 hover:text-slate-700" title="Añadir tarea">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          </a>
        @endif
      </div>

      <div class="flex-1 p-2 space-y-2 overflow-y-auto">
        @forelse($colTasks as $task)
          <a href="{{ route('tasks.show', $task) }}" class="block rounded-lg border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card hover:border-tasklab-accent transition-shadow">
            <h3 class="text-sm font-medium text-tasklab-text line-clamp-2">{{ $task->title ?? 'Sin título #' . $task->id }}</h3>
            <div class="mt-2 flex flex-wrap gap-1">
              <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium {{ $priorityColors[$task->priority] ?? $priorityColors['medium'] }}">
                {{ $task->priority === 'critical' ? 'Crítica' : ucfirst($task->priority) }}
              </span>
              <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium bg-sky-100 text-sky-800">
                {{ $typeLabels[$task->type] ?? ucfirst($task->type) }}
              </span>
            </div>
            <div class="mt-2 flex items-center gap-3 text-[11px] text-tasklab-muted">
              <span class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                —
              </span>
              <span class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ $task->created_at->format('d M') }}
              </span>
            </div>
            <div class="mt-1.5 flex items-center gap-1 text-[11px] text-tasklab-muted">
              <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              {{ $task->assignee ? $task->assignee->name : 'Sin asignar' }}
            </div>
            <p class="mt-1 text-[10px] text-tasklab-muted">Admin Panel</p>
            <div class="mt-2 flex flex-wrap gap-1">
              <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] text-slate-500">#frontend</span>
              <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] text-slate-500">#backend</span>
              <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] text-slate-500">#database</span>
            </div>
          </a>
        @empty
          <div class="flex flex-col items-center justify-center py-12 text-slate-400">
            @if($col['icon'] === 'clock')
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($col['icon'] === 'bolt')
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            @elseif($col['icon'] === 'eye')
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            @else
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
            @endif
            <p class="text-xs font-medium">Sin tareas</p>
          </div>
        @endforelse
      </div>
    </div>
  @endforeach
</div>
