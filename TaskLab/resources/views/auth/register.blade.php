<x-guest-layout>
    <h1 class="text-lg font-semibold text-slate-900 mb-1">Create your TaskLab account</h1>
    <p class="text-xs text-slate-500 mb-4">Sign up to start capturing and refining tasks with your team.</p>

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

        <!-- Org profile -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="department" :value="__('Department')" />
                <x-text-input id="department" class="block mt-1 w-full" type="text" name="department" :value="old('department')" placeholder="e.g. Product, Tech, CX" />
                <x-input-error :messages="$errors->get('department')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="position" :value="__('Position / Role')" />
                <x-text-input id="position" class="block mt-1 w-full" type="text" name="position" :value="old('position')" placeholder="e.g. Frontend dev, PM" />
                <x-input-error :messages="$errors->get('position')" class="mt-1" />
            </div>
        </div>

        <!-- User type -->
        <div>
            <x-input-label :value="__('How will you use TaskLab?')" />
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-slate-700">
                <label class="inline-flex items-start gap-2 border rounded-md px-3 py-2 cursor-pointer hover:border-sky-400">
                    <input type="radio" name="user_type" value="requester" class="mt-1 border-gray-300 text-sky-600 focus:ring-sky-500" {{ old('user_type', 'requester') === 'requester' ? 'checked' : '' }}>
                    <span>
                        <span class="block font-medium text-slate-900">I create and follow tasks</span>
                        <span class="block text-[11px] text-slate-500">Product, CX, operations… you describe problems and requests.</span>
                    </span>
                </label>

                <label class="inline-flex items-start gap-2 border rounded-md px-3 py-2 cursor-pointer hover:border-sky-400">
                    <input type="radio" name="user_type" value="developer" class="mt-1 border-gray-300 text-sky-600 focus:ring-sky-500" {{ old('user_type') === 'developer' ? 'checked' : '' }}>
                    <span>
                        <span class="block font-medium text-slate-900">I develop / implement tasks</span>
                        <span class="block text-[11px] text-slate-500">Engineering profiles that pick up and complete tasks.</span>
                    </span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('user_type')" class="mt-1" />
        </div>

        <div class="mt-4 flex items-center justify-between">
            <p class="text-[11px] text-slate-500">Already have an account?
                <a href="{{ route('login') }}" class="font-medium text-sky-600 hover:text-sky-700">Sign in</a>
            </p>
            <x-primary-button>
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
