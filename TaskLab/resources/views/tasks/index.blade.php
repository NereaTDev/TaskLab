@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Tasks</h1>
        <a href="{{ route('tasks.create') }}" class="inline-flex items-center px-3 py-2 rounded-full bg-sky-600 text-white text-sm font-medium hover:bg-sky-700">New task</a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if($tasks->isEmpty())
        <p class="text-sm text-slate-500">No tasks yet.</p>
    @else
        <div class="space-y-3">
            @foreach($tasks as $task)
                <a href="{{ route('tasks.show', $task) }}" class="block rounded-xl border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">
                                {{ $task->title ?? 'Untitled task #' . $task->id }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500 truncate">
                                {{ Str::limit($task->description_raw, 120) }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[0.65rem] font-medium border border-slate-200 text-slate-700">
                                {{ strtoupper($task->status) }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[0.65rem] font-medium border border-slate-200 text-slate-500">
                                {{ ucfirst($task->type) }} · {{ ucfirst($task->priority) }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $tasks->links() }}
        </div>
    @endif
</div>
@endsection
