@props(['current' => 'all'])

@php
    $options = [
        'all'      => 'Todas las prioridades',
        'critical' => 'Crítica',
        'high'     => 'Alta',
        'medium'   => 'Media',
        'low'      => 'Baja',
    ];

    $currentValue = $current ?? 'all';
    if (! array_key_exists($currentValue, $options)) {
        $currentValue = 'all';
    }

    // Preservamos el resto de filtros de la query, excepto priority y paginación
    $baseQuery = request()->except('priority', 'page');
@endphp

<form
    method="GET"
    action="{{ route('tasks.index') }}"
    class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-1.5"
>
    @foreach($baseQuery as $name => $value)
        @if(!is_null($value) && $value !== '')
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach

    <svg class="h-4 w-4 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>

    <select
        name="priority"
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
