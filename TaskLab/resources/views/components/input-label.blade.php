@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-label text-tasklab-muted']) }}>
    {{ $value ?? $slot }}
</label>
