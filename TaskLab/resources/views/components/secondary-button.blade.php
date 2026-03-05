<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 rounded-md border border-slate-700 bg-tasklab-bg text-body font-medium text-tasklab-text shadow-sm hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-tasklab-accent focus:ring-offset-2 focus:ring-offset-tasklab-bg disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
