@props(['current' => 'all', 'users' => collect()])

@php
    $currentValue = $current ?? 'all';

    $baseQuery = request()->except('assignee_id', 'page');
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

    <svg class="h-4 w-4 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>

    <select
        name="assignee_id"
        class="bg-transparent border-none text-body text-tasklab-text focus:outline-none focus:ring-0 pr-1 max-w-[220px]"
        onchange="this.form.submit()"
    >
        <option value="all" @selected($currentValue === 'all')>Todos los responsables</option>
        <option value="unassigned" @selected($currentValue === 'unassigned')>Sin asignar</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" @selected((string) $currentValue === (string) $user->id)>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
</form>
