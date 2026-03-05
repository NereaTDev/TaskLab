@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-700 bg-tasklab-bg-muted text-tasklab-text placeholder:text-tasklab-muted focus:border-tasklab-accent focus:ring-tasklab-accent rounded-md shadow-sm']) }}>
