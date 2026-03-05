<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 rounded-md border border-transparent bg-red-600 text-body font-medium text-white hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-tasklab-accent focus:ring-offset-2 focus:ring-offset-tasklab-bg transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
