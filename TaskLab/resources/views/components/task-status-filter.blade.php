@props(['current' => 'all', 'view' => 'dashboard'])

@php
    $options = [
        'all'       => 'Todos los estados',
        'new'       => 'Backlog',
        'ready_for_dev' => 'Pendiente',
        'in_progress'   => 'En progreso',
        'blocked'       => 'En revisión',
        'done'          => 'Completada',
        'archived'      => 'Archivadas',
    ];

    $currentValue = $current ?? 'all';
    if (! array_key_exists($currentValue, $options)) {
        $currentValue = 'all';
    }

    // Construimos el query preservando el resto de filtros pero sobreescribiendo `status` y `view`.
    $baseQuery = request()->except('status', 'page');
    $baseQuery['view'] = $view;
@endphp

<form
    method="GET"
    action="{{ route('tasks.index') }}"
    class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3"
>
    @foreach($baseQuery as $name => $value)
        @if(!is_null($value) && $value !== '')
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach

    <svg class="h-4 w-4 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>

    <select
        name="status"
        class="bg-transparent border-none text-body text-tasklab-text focus:outline-none focus:ring-0 pr-1"
        onchange="this.form.submit()"
    >
        @foreach($options as $value => $label)
            <option value="{{ $value }}" @selected($currentValue === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
</form>
