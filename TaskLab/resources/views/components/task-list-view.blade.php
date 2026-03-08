@props(['tasks'])

@php
    $groups = [
        'new'           => 'Backlog',
        'ready_for_dev' => 'Pendiente',
        'in_progress'   => 'En progreso',
        'blocked'       => 'En revisión',
        'done'          => 'Completada',
        'archived'      => 'Archivadas',
    ];
@endphp

<div class="mt-4 space-y-4">
  @php $hasAny = false; @endphp

  @foreach($groups as $status => $label)
    @php
      $groupTasks = $tasks->where('status', $status);
    @endphp
    @if($groupTasks->isNotEmpty())
      @php $hasAny = true; @endphp
      <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted shadow-card overflow-hidden">
        <div class="px-4 py-2 border-b border-slate-800 flex items-center justify-between bg-slate-900/80">
          <h3 class="text-label font-semibold text-tasklab-text">{{ $label }}</h3>
          <span class="text-meta text-tasklab-muted">{{ $groupTasks->count() }} tareas</span>
        </div>
        <table class="min-w-full text-left text-body">
          <thead class="bg-slate-900/40 text-label text-tasklab-muted">
            <tr>
              <th class="px-4 py-2">ID</th>
              <th class="px-4 py-2">Título</th>
              <th class="px-4 py-2">Tipo</th>
              <th class="px-4 py-2">Prioridad</th>
              <th class="px-4 py-2">Asignado</th>
              <th class="px-4 py-2">Creada</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 bg-tasklab-bg">
            @foreach($groupTasks as $task)
              <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-2 text-meta text-tasklab-muted">#{{ $task->id }}</td>
                <td class="px-4 py-2 text-body text-tasklab-text">
                  <a href="{{ route('tasks.show', $task) }}" class="hover:text-tasklab-accent">
                    {{ $task->title ?? 'Task #'.$task->id }}
                  </a>
                  <div class="text-meta text-tasklab-muted line-clamp-1">
                    {{ Str::limit($task->description_raw, 120) }}
                  </div>
                </td>
                <td class="px-4 py-2 text-label text-tasklab-muted">{{ ucfirst($task->type) }}</td>
                <td class="px-4 py-2 text-label text-tasklab-muted">{{ ucfirst($task->priority) }}</td>
                <td class="px-4 py-2 text-label text-tasklab-muted">{{ optional($task->assignee)->name ?? 'Sin asignar' }}</td>
                <td class="px-4 py-2 text-label text-tasklab-muted">{{ optional($task->created_at)->format('d/m/Y') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  @endforeach

  @unless($hasAny)
    <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted shadow-card px-4 py-6 text-center text-label text-tasklab-muted">
      No hay tareas para esta vista.
    </div>
  @endunless
</div>
