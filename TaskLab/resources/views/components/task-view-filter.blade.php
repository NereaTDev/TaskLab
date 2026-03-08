@php
    $viewMode = request()->get('view_mode', 'board');
    $baseQuery = request()->except('view_mode', 'page');

    $linkFor = function (string $mode) use ($baseQuery) {
        return route('tasks.index', array_merge($baseQuery, ['view_mode' => $mode]));
    };
@endphp

<div class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-2 py-1">
    {{-- Botón Kanban --}}
    <a
        href="{{ $linkFor('board') }}"
        class="inline-flex items-center justify-center h-7 w-7 rounded-full {{ $viewMode === 'board'
            ? 'bg-tasklab-accent text-slate-950'
            : 'bg-tasklab-bg text-tasklab-muted hover:bg-tasklab-bg-muted hover:text-tasklab-text' }} transition-colors"
        title="Vista Kanban"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 5h4v14H4zM10 5h4v9h-4zM16 5h4v6h-4z" />
        </svg>
    </a>

    {{-- Botón Lista --}}
    <a
        href="{{ $linkFor('list') }}"
        class="inline-flex items-center justify-center h-7 w-7 rounded-full {{ $viewMode === 'list'
            ? 'bg-tasklab-accent text-slate-950'
            : 'bg-tasklab-bg text-tasklab-muted hover:bg-tasklab-bg-muted hover:text-tasklab-text' }} transition-colors"
        title="Vista Lista"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10" />
        </svg>
    </a>
</div>
