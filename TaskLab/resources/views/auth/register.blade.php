<x-guest-layout>
    <h1 class="text-heading font-semibold text-tasklab-text mb-1">Create your TaskLab account</h1>
    <p class="text-label text-tasklab-muted mb-4">Sign up to start capturing and refining tasks with your team.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Basic info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <p class="text-meta text-tasklab-muted">Already have an account?
                <a href="{{ route('login') }}" class="font-medium text-tasklab-accent hover:text-tasklab-accent-soft">Sign in</a>
            </p>
            <x-primary-button class="text-body">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
