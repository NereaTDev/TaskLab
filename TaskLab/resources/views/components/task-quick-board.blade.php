@props(['tasks'])

@php
  $columns = [
    'new'            => 'New',
    'in_refinement'  => 'Refinement',
    'ready_for_dev'  => 'Ready for dev',
    'in_progress'    => 'In progress',
    'done'           => 'Done',
  ];
  $grouped = $tasks->groupBy('status');
@endphp

<div class="bg-slate-50/80 rounded-xl border border-slate-200 px-3 py-3">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-xs font-semibold text-slate-900">Quick board</h3>
    <p class="text-[11px] text-slate-500">Preview of statuses</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
    @foreach($columns as $key => $label)
      <div class="rounded-lg bg-white/70 border border-slate-200 p-2 flex flex-col min-h-[120px]">
        <p class="text-[11px] font-semibold text-slate-700 mb-1">
          {{ $label }}
          <span class="text-[10px] text-slate-400">({{ ($grouped[$key] ?? collect())->count() }})</span>
        </p>
        <div class="space-y-1.5 overflow-hidden">
          @foreach(($grouped[$key] ?? collect())->take(4) as $task)
            <a href="{{ route('tasks.show', $task) }}" class="block rounded-md border border-slate-200 bg-slate-50/80 px-2 py-1 text-[11px] text-slate-700 hover:bg-slate-100">
              <p class="font-medium truncate">{{ $task->title ?? 'Task #'.$task->id }}</p>
              <p class="text-[10px] text-slate-500 truncate">{{ Str::limit($task->description_raw, 60) }}</p>
            </a>
          @endforeach
          @if(($grouped[$key] ?? collect())->count() === 0)
            <p class="text-[10px] text-slate-400 italic">No tasks</p>
          @endif
        </div>
      </div>
    @endforeach
  </div>
</div>
