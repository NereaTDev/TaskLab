<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4 space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-meta text-tasklab-muted mb-1">Task #{{ $task->id }}</p>
                <h1 class="text-2xl font-semibold text-tasklab-text">
                    {{ $task->title ?? 'Untitled task' }}
                </h1>
                <p class="mt-2 text-label text-tasklab-muted">
                    Type: <span class="font-medium text-tasklab-text">{{ ucfirst($task->type) }}</span>
                    · Priority: <span class="font-medium text-tasklab-text">{{ ucfirst($task->priority) }}</span>
                    · Status: <span class="font-medium text-tasklab-text">{{ strtoupper($task->status) }}</span>
                    @if(!is_null($task->points))
                        · Estimación: <span class="font-medium text-tasklab-text">{{ $task->points }} h</span>
                    @endif
                </p>
            </div>

            <form method="POST" action="{{ route('tasks.updateStatus', $task) }}" class="flex items-center gap-2">
                @csrf
                @method('PATCH')
                <select
                    name="status"
                    class="rounded-full border border-slate-700 bg-tasklab-bg-muted px-3 py-1.5 text-label text-tasklab-text focus:border-tasklab-primary focus:border-tasklab-accent focus:ring-tasklab-accent"
                >
                    @foreach(['new','in_refinement','ready_for_dev','in_progress','done','blocked'] as $status)
                        <option value="{{ $status }}" {{ $task->status === $status ? 'selected' : '' }}>
                            {{ strtoupper($status) }}
                        </option>
                    @endforeach
                </select>
                <button
                    type="submit"
                    class="inline-flex items-center px-3 py-1.5 rounded-full bg-tasklab-primary text-tasklab-text text-body font-medium hover:bg-tasklab-primary-soft transition"
                >
                    Update
                </button>
            </form>
        </div>

        @if(session('status'))
            <div class="rounded-lg bg-tasklab-success/10 border border-tasklab-success/40 px-3 py-2 text-body text-tasklab-text">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
                    <h2 class="text-title font-semibold text-tasklab-text mb-2">Original description</h2>
                    <pre class="whitespace-pre-wrap text-body text-tasklab-muted">{{ $task->description_raw }}</pre>
                </section>

                @if($task->primary_url || (is_array($task->additional_urls) && count($task->additional_urls)))
                    <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card space-y-2">
                        <h2 class="text-title font-semibold text-tasklab-text mb-2">URLs</h2>
                        @if($task->primary_url)
                            <p class="text-body text-tasklab-muted">
                                <span class="font-medium">Principal:</span>
                                <a href="{{ $task->primary_url }}" class="text-tasklab-accent underline" target="_blank" rel="noopener noreferrer">{{ $task->primary_url }}</a>
                            </p>
                        @endif
                        @if(is_array($task->additional_urls) && count($task->additional_urls))
                            <div class="text-body text-tasklab-muted space-y-1">
                                <p class="font-medium">Adicionales:</p>
                                <ul class="list-disc pl-4">
                                    @foreach($task->additional_urls as $url)
                                        <li>
                                            <a href="{{ $url }}" class="text-tasklab-accent underline" target="_blank" rel="noopener noreferrer">{{ $url }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>
                @endif

                @if($task->attachments && is_array($task->attachments) && count($task->attachments))
                    <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card space-y-2">
                        <h2 class="text-title font-semibold text-tasklab-text mb-2">Adjuntos</h2>
                        <ul class="list-disc pl-4 text-body text-tasklab-muted space-y-1">
                            @foreach($task->attachments as $attachment)
                                <li>
                                    @if(is_array($attachment) && isset($attachment['url']))
                                        <a href="{{ $attachment['url'] }}" class="text-tasklab-accent underline" target="_blank" rel="noopener noreferrer">
                                            {{ $attachment['label'] ?? $attachment['url'] }}
                                        </a>
                                    @else
                                        <span>{{ is_string($attachment) ? $attachment : json_encode($attachment) }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <div class="space-y-4">
                <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
                    <h2 class="text-title font-semibold text-tasklab-text mb-2">AI summary</h2>
                    <p class="text-body text-tasklab-muted whitespace-pre-wrap">
                        {{ $task->description_ai ?? 'Refinement pending or not available yet.' }}
                    </p>
                    @if($task->impact)
                        <p class="mt-3 text-body text-tasklab-text">
                            <span class="font-semibold">Impacto:</span>
                            <span class="text-tasklab-muted">{{ $task->impact }}</span>
                        </p>
                    @endif
                </section>

                <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
                    <h2 class="text-title font-semibold text-tasklab-text mb-2">Requirements</h2>
                    @if(is_array($task->requirements) && count($task->requirements))
                        <ul class="list-disc pl-4 text-body text-tasklab-muted space-y-1">
                            @foreach($task->requirements as $req)
                                <li>{{ $req }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-label text-tasklab-muted">No requirements generated yet.</p>
                    @endif
                </section>

                <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
                    <h2 class="text-title font-semibold text-tasklab-text mb-2">Behavior &amp; test cases</h2>
                    <div class="space-y-3 text-body text-tasklab-muted">
                        <div>
                            <p class="font-medium mb-1 text-tasklab-text">Behavior</p>
                            <p class="whitespace-pre-wrap">{{ $task->behavior ?? 'No behavior description yet.' }}</p>
                        </div>
                        <div>
                            <p class="font-medium mb-1 text-tasklab-text">Test cases</p>
                            @if(is_array($task->test_cases) && count($task->test_cases))
                                <ul class="list-disc pl-4 space-y-1">
                                    @foreach($task->test_cases as $case)
                                        <li>{{ $case }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-label text-tasklab-muted">No test cases generated yet.</p>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
