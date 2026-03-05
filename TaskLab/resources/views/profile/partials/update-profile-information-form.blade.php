<section>
    <header>
        <h2 class="text-heading font-medium text-tasklab-text">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-body text-tasklab-muted">
            {{ __("Update your account's profile information.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div>
                        <p class="text-sm mt-2 text-tasklab-text">
                            {{ __('Your email address is unverified.') }}

                            <button form="send-verification" class="underline text-sm text-tasklab-muted hover:text-tasklab-text rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-tasklab-primary">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-sm text-tasklab-success">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="department" :value="__('Department')" />
                <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $user->department)" placeholder="e.g. Product, Tech, CX" />
                <x-input-error class="mt-2" :messages="$errors->get('department')" />
            </div>

            <div>
                <x-input-label for="position" :value="__('Position / Role')" />
                <x-text-input id="position" name="position" type="text" class="mt-1 block w-full" :value="old('position', $user->position)" placeholder="e.g. Frontend dev, PM" />
                <x-input-error class="mt-2" :messages="$errors->get('position')" />
            </div>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-label text-tasklab-muted">
                <input type="hidden" name="is_admin" value="0" />
                <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user->is_admin)) class="rounded border-slate-600 bg-tasklab-bg-muted text-tasklab-primary shadow-sm focus:ring-tasklab-primary">
                <span>{{ __('Administrator') }}</span>
            </label>
            <p class="mt-1 text-label text-tasklab-muted">{{ __('Admins can manage TaskLab configuration and team settings (future behavior).') }}</p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-body text-tasklab-muted"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
