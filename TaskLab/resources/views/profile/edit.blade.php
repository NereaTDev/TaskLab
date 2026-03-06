<x-app-layout>
    <div class="max-w-[1600px] mx-auto px-4 py-6 space-y-6">
        {{-- Header perfil: usuario + resumen --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500 text-sm font-semibold text-white">
                    {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                </span>
                <div>
                    <h1 class="text-heading font-semibold text-tasklab-text">{{ $user->name }}</h1>
                    <p class="text-label text-tasklab-muted">{{ $user->email }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-meta">
                @if($user->is_super_admin)
                    <div class="inline-flex items-center gap-2 rounded-full border border-tasklab-accent/70 bg-tasklab-accent/10 px-3 py-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-tasklab-accent"></span>
                        <span class="text-tasklab-text font-semibold">Super Admin</span>
                    </div>
                @elseif($user->is_admin)
                    <div class="inline-flex items-center gap-2 rounded-full border border-tasklab-success/70 bg-tasklab-success/10 px-3 py-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-tasklab-success"></span>
                        <span class="text-tasklab-text font-semibold">Admin</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Contenido principal: info de cuenta, perfil dev y contraseña --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            {{-- Columna principal: info de cuenta + perfil dev en una sola card --}}
            <div class="lg:col-span-2">
                <div class="p-4 sm:p-6 bg-tasklab-bg-muted border border-slate-800 shadow-card sm:rounded-lg">
                    <div class="max-w-xl space-y-8">
                        @include('profile.partials.update-profile-information-form')

                        <div class="border-t border-slate-800 pt-6">
                            @include('profile.partials.update-developer-profile-form')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna lateral: seguridad / contraseña --}}
            <div>
                <div class="p-4 sm:p-6 bg-tasklab-bg-muted border border-slate-800 shadow-card sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
