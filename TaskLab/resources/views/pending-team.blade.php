<x-app-layout>
    <div class="min-h-[80vh] flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center space-y-6">
            {{-- Icono --}}
            <div class="flex justify-center">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-tasklab-accent/10 border border-tasklab-accent/30">
                    <svg class="h-8 w-8 text-tasklab-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20h6M3 20h5v-2a4 4 0 00-3-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>

            {{-- Texto principal --}}
            <div class="space-y-2">
                <h1 class="text-xl font-semibold text-tasklab-text">Pendiente de asignación</h1>
                <p class="text-sm text-tasklab-muted leading-relaxed">
                    Tu cuenta está activa, pero todavía no has sido asignado a ningún equipo.<br>
                    Un administrador te asignará pronto. Te avisaremos en cuanto esté listo.
                </p>
            </div>

            {{-- Info usuario --}}
            <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted px-5 py-4 text-left space-y-3">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500 text-sm font-semibold text-white shrink-0">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                    </span>
                    <div>
                        <p class="text-sm font-medium text-tasklab-text">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-tasklab-muted">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                @if(auth()->user()->position)
                    <div class="flex items-center gap-2 text-xs text-tasklab-muted">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ auth()->user()->position }}
                    </div>
                @endif
            </div>

            {{-- Acciones --}}
            <div class="flex items-center justify-center gap-3 pt-2">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-tasklab-bg-muted px-4 py-2 text-xs font-medium text-tasklab-muted hover:text-tasklab-text hover:border-slate-600 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Editar perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-tasklab-bg-muted px-4 py-2 text-xs font-medium text-tasklab-muted hover:text-tasklab-text hover:border-slate-600 transition-colors">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                        </svg>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
