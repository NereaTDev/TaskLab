<x-app-layout>
    <div class="max-w-[1600px] mx-auto px-4 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-tasklab-text">Gestión de equipos</h1>
                <p class="text-sm text-tasklab-muted mt-0.5">Crea equipos y asigna usuarios.</p>
            </div>

            {{-- Crear equipo --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = true" class="inline-flex items-center gap-2 rounded-lg bg-tasklab-accent px-4 py-2 text-xs font-semibold text-white hover:bg-tasklab-accent/90 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo equipo
                </button>

                {{-- Modal crear equipo --}}
                <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="open = false">
                    <div class="w-full max-w-sm rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-6 shadow-card" @click.stop>
                        <h2 class="text-sm font-semibold text-tasklab-text mb-4">Crear nuevo equipo</h2>
                        <form method="POST" action="{{ route('admin.teams.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <x-input-label for="team_name" value="Nombre del equipo" />
                                <x-text-input id="team_name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="team_description" value="Descripción (opcional)" />
                                <textarea id="team_description" name="description" rows="2" class="mt-1 block w-full rounded-md border border-slate-700 bg-tasklab-bg text-sm text-tasklab-text shadow-sm focus:border-tasklab-accent focus:ring-tasklab-accent placeholder-tasklab-muted">{{ old('description') }}</textarea>
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button" @click="open = false" class="text-xs text-tasklab-muted hover:text-tasklab-text">Cancelar</button>
                                <x-primary-button class="text-xs">Crear equipo</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">

            {{-- Panel izquierdo: Usuarios pendientes --}}
            <div class="xl:col-span-1">
                <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-800 flex items-center gap-2">
                        <svg class="h-4 w-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-tasklab-text">Sin equipo</span>
                        <span class="ml-auto inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-yellow-500/20 px-1.5 text-[10px] font-semibold text-yellow-400">{{ $pendingUsers->count() }}</span>
                    </div>

                    @if($pendingUsers->isEmpty())
                        <p class="px-4 py-6 text-center text-xs text-tasklab-muted">Todos los usuarios tienen equipo.</p>
                    @else
                        <div class="divide-y divide-slate-800">
                            @foreach($pendingUsers as $pUser)
                                <div x-data="{ open: false }" class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-tasklab-text">
                                            {{ strtoupper(substr($pUser->name, 0, 2)) }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium text-tasklab-text truncate">{{ $pUser->name }}</p>
                                            <p class="text-[11px] text-tasklab-muted truncate">{{ $pUser->email }}</p>
                                            @if($pUser->position)
                                                <p class="text-[10px] text-tasklab-accent/80 mt-0.5">{{ $pUser->position }}</p>
                                            @endif
                                        </div>
                                        <button @click="open = !open" class="shrink-0 rounded-md bg-tasklab-accent/10 border border-tasklab-accent/30 px-2.5 py-1 text-[10px] font-medium text-tasklab-accent hover:bg-tasklab-accent/20 transition-colors">
                                            Asignar
                                        </button>
                                    </div>

                                    {{-- Form asignar --}}
                                    <div x-show="open" x-transition class="mt-3 rounded-lg border border-slate-700 bg-tasklab-bg p-3">
                                        <form method="POST" class="space-y-2" action="#">
                                            @foreach($teams as $team)
                                                <form method="POST" action="{{ route('admin.teams.assign-user', $team) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $pUser->id }}">
                                                    <span class="flex-1 text-xs text-tasklab-text">{{ $team->name }}</span>
                                                    <select name="role" class="rounded border border-slate-700 bg-tasklab-bg-muted text-[11px] text-tasklab-muted px-1.5 py-1 focus:border-tasklab-accent focus:ring-0">
                                                        <option value="member">Miembro</option>
                                                        <option value="admin">Admin</option>
                                                    </select>
                                                    <button type="submit" class="rounded bg-emerald-600 px-2.5 py-1 text-[10px] font-medium text-white hover:bg-emerald-500 transition-colors">
                                                        Asignar
                                                    </button>
                                                </form>
                                            @endforeach
                                            @if($teams->isEmpty())
                                                <p class="text-[11px] text-tasklab-muted">Crea un equipo primero.</p>
                                            @endif
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Panel derecho: Equipos --}}
            <div class="xl:col-span-2 space-y-4">
                @if($teams->isEmpty())
                    <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted px-6 py-12 text-center">
                        <p class="text-sm text-tasklab-muted">No hay equipos todavía. Crea el primero.</p>
                    </div>
                @endif

                @foreach($teams as $team)
                    <div class="rounded-xl border border-slate-800 bg-tasklab-bg-muted overflow-hidden">
                        {{-- Cabecera equipo --}}
                        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-tasklab-accent/10 border border-tasklab-accent/30">
                                    <svg class="h-4 w-4 text-tasklab-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20h6M3 20h5v-2a4 4 0 00-3-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-tasklab-text">{{ $team->name }}</h3>
                                    @if($team->description)
                                        <p class="text-[11px] text-tasklab-muted">{{ $team->description }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-tasklab-muted">{{ $team->users_count }} {{ $team->users_count === 1 ? 'miembro' : 'miembros' }}</span>
                                <form method="POST" action="{{ route('admin.teams.destroy', $team) }}" onsubmit="return confirm('¿Eliminar equipo {{ addslashes($team->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded p-1 text-tasklab-muted hover:text-red-400 hover:bg-red-500/10 transition-colors">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Miembros --}}
                        @if($team->users->isEmpty())
                            <p class="px-4 py-4 text-xs text-tasklab-muted">Sin miembros aún.</p>
                        @else
                            <div class="divide-y divide-slate-800/50">
                                @foreach($team->users as $member)
                                    <div class="flex items-center gap-3 px-4 py-2.5">
                                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-tasklab-text">
                                            {{ strtoupper(substr($member->name, 0, 2)) }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium text-tasklab-text truncate">{{ $member->name }}</p>
                                            @if($member->position)
                                                <p class="text-[10px] text-tasklab-muted">{{ $member->position }}</p>
                                            @endif
                                        </div>

                                        {{-- Cambiar rol --}}
                                        <form method="POST" action="{{ route('admin.teams.update-role', [$team, $member]) }}" class="flex items-center gap-1">
                                            @csrf
                                            @method('PATCH')
                                            <select name="role" onchange="this.form.submit()" class="rounded border border-slate-700 bg-tasklab-bg text-[11px] px-1.5 py-0.5 focus:border-tasklab-accent focus:ring-0 {{ $member->pivot->role === 'admin' ? 'text-tasklab-accent' : 'text-tasklab-muted' }}">
                                                <option value="member" @selected($member->pivot->role === 'member')>Miembro</option>
                                                <option value="admin" @selected($member->pivot->role === 'admin')>Admin</option>
                                            </select>
                                        </form>

                                        {{-- Quitar del equipo --}}
                                        <form method="POST" action="{{ route('admin.teams.remove-user', [$team, $member]) }}" onsubmit="return confirm('¿Quitar a {{ addslashes($member->name) }} del equipo?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded p-1 text-tasklab-muted hover:text-red-400 hover:bg-red-500/10 transition-colors">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
