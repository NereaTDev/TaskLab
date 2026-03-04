<x-guest-layout>
    <h1 class="text-lg font-semibold text-slate-900 mb-1">Sign in</h1>
    <p class="text-xs text-slate-500 mb-4">Access your TaskLab workspace to see and manage tasks.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-3">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-[11px] text-slate-500 hover:text-slate-700" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-[11px] text-slate-500">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <p class="text-[11px] text-slate-500">Don't have an account?
                <a href="{{ route('register') }}" class="font-medium text-sky-600 hover:text-sky-700">Sign up</a>
            </p>
            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
