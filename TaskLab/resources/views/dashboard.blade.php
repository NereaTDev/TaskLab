<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-tasklab-text leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-tasklab-bg-muted border border-slate-800 shadow-card sm:rounded-lg">
                <div class="p-6 text-body text-tasklab-text">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
