<x-app-layout>
    <div class="max-w-[1600px] mx-auto px-4 py-6 space-y-6">
        {{-- Header equipo + resumen rápido --}}
        @php
            $totalMembers = $teamMembers->count();
            $adminsCount = $teamMembers->where('is_admin', true)->count();
            $devProfilesCount = $teamMembers->filter(fn ($m) => $m->developerProfile)->count();
        @endphp

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-heading font-semibold text-tasklab-text">Equipo</h1>
                <p class="text-label text-tasklab-muted">Personas con acceso a TaskLab, sus roles y perfiles de desarrollador.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-meta">
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-tasklab-bg px-3 py-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-tasklab-primary"></span>
                    <span class="text-tasklab-muted">Miembros</span>
                    <span class="text-tasklab-text font-semibold">{{ $totalMembers }}</span>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-tasklab-bg px-3 py-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-tasklab-success"></span>
                    <span class="text-tasklab-muted">Admins</span>
                    <span class="text-tasklab-text font-semibold">{{ $adminsCount }}</span>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-tasklab-bg px-3 py-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-tasklab-accent"></span>
                    <span class="text-tasklab-muted">Con perfil dev</span>
                    <span class="text-tasklab-text font-semibold">{{ $devProfilesCount }}</span>
                </div>
            </div>
        </div>

        {{-- Grid de cards de equipo (otro rollo respecto a la tabla) --}}
        @if($teamMembers->isEmpty())
            <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-8 text-center shadow-card">
                <p class="text-body text-tasklab-muted">No team members yet.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($teamMembers as $member)
                    @php
                        $profile = $member->developerProfile;
                        $areas = $profile->areas ?? [];
                    @endphp

                    <article class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card flex flex-col gap-4">
                        {{-- Cabecera: avatar + nombre + rol --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-tasklab-text">
                                    {{ strtoupper(substr($member->name ?? 'A', 0, 2)) }}
                                </span>
                                <div>
                                    <p class="text-body font-medium text-tasklab-text">{{ $member->name }}</p>
                                    <p class="text-meta text-tasklab-muted">{{ $member->email }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                @if ($member->is_admin)
                                    <span class="inline-flex items-center rounded-full border border-tasklab-success/60 bg-tasklab-success/10 px-2 py-0.5 text-meta font-medium text-tasklab-success">
                                        Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-slate-700 bg-tasklab-bg px-2 py-0.5 text-meta font-medium text-tasklab-muted">
                                        User
                                    </span>
                                @endif

                                @if($profile)
                                    <span class="inline-flex items-center gap-1 rounded-full border border-tasklab-primary/50 bg-tasklab-primary/10 px-2 py-0.5 text-meta text-tasklab-primary">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m9-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ ucfirst($profile->type ?? 'dev') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Info de negocio: departamento / posición --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-label text-tasklab-muted">
                            <div>
                                <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Departamento</p>
                                <p class="mt-0.5 text-body text-tasklab-text">{{ $member->department ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Posición</p>
                                <p class="mt-0.5 text-body text-tasklab-text">{{ $member->position ?: '—' }}</p>
                            </div>
                        </div>

                        {{-- Perfil de desarrollador --}}
                        <div class="space-y-2">
                            <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Perfil de desarrollador</p>

                            @if($profile)
                                <div class="flex flex-wrap items-center gap-2 text-meta">
                                    <span class="inline-flex items-center rounded-full border border-slate-700 bg-tasklab-bg px-2 py-0.5 text-tasklab-text">
                                        {{ $profile->type ? ucfirst($profile->type) : 'Sin tipo definido' }}
                                    </span>

                                    @if(!empty($areas))
                                        <span class="text-tasklab-muted">· Áreas:</span>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($areas as $area)
                                                <span class="inline-flex items-center rounded-full bg-tasklab-bg px-2 py-0.5 text-meta text-tasklab-muted border border-slate-700">
                                                    {{ ucfirst(str_replace('_', ' ', $area)) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-tasklab-muted">Sin áreas seleccionadas</span>
                                    @endif
                                </div>
                            @else
                                <p class="text-meta text-tasklab-muted">Sin perfil de desarrollador configurado.</p>
                            @endif
                        </div>

                        {{-- Acciones --}}
                        @if(auth()->user()->is_admin)
                            <div class="pt-2 border-t border-slate-800 flex justify-end">
                                <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-tasklab-accent/60 bg-tasklab-accent/10 px-3 py-1.5 text-body font-medium text-tasklab-accent hover:bg-tasklab-accent/20">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
                                    Gestionar
                                </button>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
