<x-guest-layout>
    <div class="mb-4 text-body text-tasklab-muted">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-body text-tasklab-success">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="text-body">
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="underline text-body text-tasklab-muted hover:text-tasklab-text rounded-md focus:outline-none focus:ring-2 focus:ring-tasklab-primary"
            >
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
