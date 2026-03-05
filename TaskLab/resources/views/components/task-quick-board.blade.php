@props(['tasks'])

@php
  $columns = [
    'new'            => ['label' => 'Pendiente',     'color' => 'border-tasklab-warning/40 bg-tasklab-warning/10'],
    'in_refinement'  => ['label' => 'Refinando',     'color' => 'border-tasklab-primary-soft/40 bg-tasklab-primary-soft/10'],
    'ready_for_dev'  => ['label' => 'Ready for dev', 'color' => 'border-tasklab-primary/40 bg-tasklab-primary/10'],
    'in_progress'    => ['label' => 'En progreso',   'color' => 'border-tasklab-primary/40 bg-tasklab-primary/10'],
    'done'           => ['label' => 'Completada',    'color' => 'border-tasklab-success/40 bg-tasklab-success/10'],
  ];
@endphp

<div class="bg-tasklab-bg-muted rounded-2xl border border-slate-800 px-3 py-3 shadow-card">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-label font-semibold text-tasklab-text">
      Vista rápida
      <span class="ml-1 text-meta text-tasklab-muted">({{ $tasks->count() }} tareas)</span>
    </h3>
    <p class="text-meta text-tasklab-muted">Resumen por estado</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
    @foreach($columns as $key => $meta)
      @php $colTasks = $tasks->where('status', $key); @endphp

      <div class="rounded-xl border {{ $meta['color'] }} p-2 flex flex-col min-h-[140px] bg-tasklab-bg">
        <p class="text-label font-semibold flex items-center justify-between mb-1 text-tasklab-text">
          <span>{{ $meta['label'] }}</span>
          <span class="text-meta text-tasklab-muted">({{ $colTasks->count() }})</span>
        </p>

        <div class="space-y-1.5 flex-1">
          @forelse($colTasks->take(3) as $task)
            <a href="{{ route('tasks.show', $task) }}"
               class="block rounded-md border border-slate-800 bg-tasklab-bg-muted px-2 py-1 text-meta text-tasklab-text hover:bg-tasklab-bg">
              <p class="font-medium truncate">
                {{ $task->title ?? 'Task #'.$task->id }}
              </p>
              <p class="text-[10px] text-tasklab-muted truncate">
                {{ Str::limit($task->description_raw, 60) }}
              </p>
            </a>
          @empty
            <p class="text-meta text-tasklab-muted italic mt-4">Sin tareas</p>
          @endforelse
        </div>
      </div>
    @endforeach
  </div>
</div>
