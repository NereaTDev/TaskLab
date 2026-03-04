<x-app-layout>
    <div class="max-w-6xl mx-auto py-8 px-4 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Tasks</h1>
                <p class="mt-1 text-xs text-slate-500">Overview of all tasks captured in TaskLab.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('tasks.create') }}" class="inline-flex items-center rounded-full bg-sky-600 px-4 py-2 text-xs font-medium text-white hover:bg-sky-700">New task</a>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-2 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Stats cards estilo DevTask --}}
        <x-task-stats :stats="$stats ?? []" />

        @if(!$tasks->isEmpty())
            {{-- Quick board estilo mini-Kanban --}}
            <div class="mt-4">
                <x-task-quick-board :tasks="$tasks->getCollection()" />
            </div>
        @endif

        @if($tasks->isEmpty())
            <p class="text-sm text-slate-500">No tasks yet.</p>
        @else
            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Task</th>
                            <th class="px-4 py-2 text-left font-medium">Meta</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Owner</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($tasks as $task)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ route('tasks.show', $task) }}" class="block">
                                        <p class="text-sm font-medium text-slate-900 truncate">
                                            {{ $task->title ?? 'Untitled task #' . $task->id }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-500 line-clamp-2">
                                            {{ Str::limit($task->description_raw, 140) }}
                                        </p>
                                    </a>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap gap-1 text-[10px] text-slate-600">
                                        <span class="inline-flex items-center rounded-full border border-slate-200 px-2 py-0.5">{{ ucfirst($task->type) }}</span>
                                        <span class="inline-flex items-center rounded-full border border-slate-200 px-2 py-0.5">{{ ucfirst($task->priority) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-medium text-slate-700">
                                        {{ strtoupper($task->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-[11px] text-slate-500">
                                    {{ optional($task->reporter)->name ?? '—' }}
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
    </div>
</x-app-layout>
