@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-tasklab-accent text-sm font-medium leading-5 text-tasklab-text focus:outline-none focus:border-tasklab-accent transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-tasklab-muted hover:text-tasklab-text hover:border-tasklab-accent focus:outline-none focus:text-tasklab-text focus:border-tasklab-accent transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
