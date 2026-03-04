<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4 space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs text-slate-500 mb-1">Task #{{ $task->id }}</p>
                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ $task->title ?? 'Untitled task' }}
                </h1>
                <p class="mt-2 text-xs text-slate-500">
                    Type: <span class="font-medium">{{ ucfirst($task->type) }}</span>
                    · Priority: <span class="font-medium">{{ ucfirst($task->priority) }}</span>
                    · Status: <span class="font-medium">{{ strtoupper($task->status) }}</span>
                </p>
            </div>

            <form method="POST" action="{{ route('tasks.updateStatus', $task) }}" class="flex items-center gap-2">
                @csrf
                @method('PATCH')
                <select name="status" class="rounded-md border border-slate-300 px-2 py-1 text-xs">
                    @foreach(['new','in_refinement','ready_for_dev','in_progress','done','blocked'] as $status)
                        <option value="{{ $status }}" {{ $task->status === $status ? 'selected' : '' }}>
                            {{ strtoupper($status) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-full bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">Update</button>
            </form>
        </div>

        @if(session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <section class="rounded-xl border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-semibold text-slate-900 mb-2">Original description</h2>
                    <pre class="whitespace-pre-wrap text-xs text-slate-700">{{ $task->description_raw }}</pre>
                </section>
            </div>

            <div class="space-y-4">
                <section class="rounded-xl border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-semibold text-slate-900 mb-2">AI summary</h2>
                    <p class="text-xs text-slate-700 whitespace-pre-wrap">
                        {{ $task->description_ai ?? 'Refinement pending or not available yet.' }}
                    </p>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-semibold text-slate-900 mb-2">Requirements</h2>
                    @if(is_array($task->requirements) && count($task->requirements))
                        <ul class="list-disc pl-4 text-xs text-slate-700 space-y-1">
                            @foreach($task->requirements as $req)
                                <li>{{ $req }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-xs text-slate-500">No requirements generated yet.</p>
                    @endif
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-semibold text-slate-900 mb-2">Behavior &amp; test cases</h2>
                    <div class="space-y-3 text-xs text-slate-700">
                        <div>
                            <p class="font-medium mb-1">Behavior</p>
                            <p class="whitespace-pre-wrap">{{ $task->behavior ?? 'No behavior description yet.' }}</p>
                        </div>
                        <div>
                            <p class="font-medium mb-1">Test cases</p>
                            @if(is_array($task->test_cases) && count($task->test_cases))
                                <ul class="list-disc pl-4 space-y-1">
                                    @foreach($task->test_cases as $case)
                                        <li>{{ $case }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-slate-500">No test cases generated yet.</p>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
