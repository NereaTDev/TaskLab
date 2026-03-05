<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 rounded-md border border-transparent bg-tasklab-accent text-body font-medium text-slate-950 hover:bg-tasklab-accent-soft focus:outline-none focus:ring-2 focus:ring-tasklab-accent focus:ring-offset-2 focus:ring-offset-tasklab-bg transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
