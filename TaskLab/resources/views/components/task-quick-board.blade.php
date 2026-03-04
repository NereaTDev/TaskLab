@props(['tasks'])

@php
  $columns = [
    'new'            => ['label' => 'Pendiente',    'color' => 'bg-amber-50 border-amber-200 text-amber-800'],
    'in_refinement'  => ['label' => 'Refinando',    'color' => 'bg-violet-50 border-violet-200 text-violet-800'],
    'ready_for_dev'  => ['label' => 'Ready for dev','color' => 'bg-sky-50 border-sky-200 text-sky-800'],
    'in_progress'    => ['label' => 'En progreso',  'color' => 'bg-blue-50 border-blue-200 text-blue-800'],
    'done'           => ['label' => 'Completada',   'color' => 'bg-emerald-50 border-emerald-200 text-emerald-800'],
  ];
@endphp

<div class="bg-slate-50/80 rounded-2xl border border-slate-200 px-3 py-3">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-xs font-semibold text-slate-900">
      Vista rápida
      <span class="ml-1 text-[11px] text-slate-400">({{ $tasks->count() }} tareas)</span>
    </h3>
    <p class="text-[11px] text-slate-500">Resumen por estado</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
    @foreach($columns as $key => $meta)
      @php $colTasks = $tasks->where('status', $key); @endphp

      <div class="rounded-xl {{ $meta['color'] }} p-2 flex flex-col min-h-[140px]">
        <p class="text-[11px] font-semibold flex items-center justify-between mb-1">
          <span>{{ $meta['label'] }}</span>
          <span class="text-[10px] text-slate-500">({{ $colTasks->count() }})</span>
        </p>

        <div class="space-y-1.5 flex-1">
          @forelse($colTasks->take(3) as $task)
            <a href="{{ route('tasks.show', $task) }}"
               class="block rounded-md bg-white/80 px-2 py-1 text-[11px] text-slate-700 hover:bg-white">
              <p class="font-medium truncate">
                {{ $task->title ?? 'Task #'.$task->id }}
              </p>
              <p class="text-[10px] text-slate-500 truncate">
                {{ Str::limit($task->description_raw, 60) }}
              </p>
            </a>
          @empty
            <p class="text-[10px] text-slate-400 italic mt-4">Sin tareas</p>
          @endforelse
        </div>
      </div>
    @endforeach
  </div>
</div>
