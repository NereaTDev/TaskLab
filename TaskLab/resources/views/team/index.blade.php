<x-app-layout>
    <div
        class="max-w-[1600px] mx-auto px-4 py-6 space-y-6"
        x-data="teamBoard()"
    >
        {{-- Header equipo + resumen rápido --}}
        @php
            $totalMembers = $teamMembers->count();
            $adminsCount = $teamMembers->where('is_admin', true)->count();
            $devProfilesCount = $teamMembers->filter(fn ($m) => $m->developerProfile)->count();

            $admins = $teamMembers->filter(function ($member) {
                return method_exists($member, 'isSuperAdmin')
                    ? ($member->isSuperAdmin() || $member->is_admin)
                    : (bool) $member->is_admin;
            });
            $workers = $teamMembers->diff($admins);
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

        {{-- Modos de vista (lista vs columnas) --}}
        <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-meta text-tasklab-muted">Vista</span>
                <a
                    href="{{ route('team.index') }}"
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-meta font-medium {{ $group ? 'text-tasklab-muted hover:text-tasklab-text bg-transparent border border-transparent' : 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' }}"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    Lista
                </a>
                <a
                    href="{{ route('team.index', ['group' => 'department']) }}"
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-meta font-medium {{ $group === 'department' ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text bg-transparent border border-transparent' }}"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h4v12H4zM10 6h4v12h-4zM16 6h4v12h-4z"/></svg>
                    Por departamento
                </a>
            </div>
            <p class="text-meta text-tasklab-muted">Arrastra personas entre columnas para cambiar su departamento (solo Super Admin).</p>
        </div>

        {{-- Lista de equipo / vista por departamento --}}
        @if($teamMembers->isEmpty())
            <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-8 text-center shadow-card">
                <p class="text-body text-tasklab-muted">No team members yet.</p>
            </div>
        @else
            @if($group === 'department')
                {{-- Vista columnas por departamento --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($groupedDepartments as $departmentName => $members)
                        <div
                            class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-col min-h-[260px]"
                            @dragover.prevent
                            @drop.prevent="moveUserToDepartment('{{ $departmentName }}')"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="text-label font-semibold text-tasklab-text">{{ $departmentName }}</p>
                                    <p class="text-meta text-tasklab-muted">{{ $members->count() }} personas</p>
                                </div>
                            </div>

                            <div class="mt-2 space-y-2 flex-1">
                                @foreach($members as $member)
                                    @php
                                        $profile = $member->developerProfile;
                                    @endphp
                                    <article
                                        class="rounded-xl border border-slate-800 bg-tasklab-bg px-3 py-2 flex items-center justify-between gap-2 cursor-move"
                                        draggable="true"
                                        @dragstart="draggedUserId = {{ $member->id }}"
                                        @dragend="draggedUserId = null"
                                    >
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-[11px] font-semibold text-tasklab-text">
                                                {{ strtoupper(substr($member->name ?? 'A', 0, 2)) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-meta font-medium text-tasklab-text truncate">{{ $member->name }}</p>
                                                <p class="text-[10px] text-tasklab-muted truncate">{{ $member->position ?: 'Sin posición' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-1">
                                            @if (method_exists($member, 'isSuperAdmin') && $member->isSuperAdmin())
                                                <span class="inline-flex items-center rounded-full border border-tasklab-accent/70 bg-tasklab-accent/10 px-2 py-0.5 text-[10px] font-medium text-tasklab-accent">
                                                    SA
                                                </span>
                                            @elseif ($member->is_admin)
                                                <span class="inline-flex items-center rounded-full border border-tasklab-success/60 bg-tasklab-success/10 px-2 py-0.5 text-[10px] font-medium text-tasklab-success">
                                                    Admin
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full border border-slate-700 bg-tasklab-bg px-2 py-0.5 text-[10px] font-medium text-tasklab-muted">
                                                    Trabajador
                                                </span>
                                            @endif

                                            @if($profile)
                                                <span class="inline-flex items-center rounded-full border border-tasklab-primary/40 bg-tasklab-primary/10 px-2 py-0.5 text-[10px] text-tasklab-primary">
                                                    {{ $profile->type ? ucfirst($profile->type) : 'Sin tipo' }}
                                                </span>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Vista lista clásica: Admins arriba, trabajadores abajo --}}
                {{-- Sección: Admins --}}
                @if($admins->isNotEmpty())
                    <div class="space-y-2">
                        <h2 class="text-label font-semibold text-tasklab-muted uppercase tracking-wide">Administradores</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($admins as $member)
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
                                            @if (method_exists($member, 'isSuperAdmin') && $member->isSuperAdmin())
                                                <span class="inline-flex items-center rounded-full border border-tasklab-accent/70 bg-tasklab-accent/10 px-2 py-0.5 text-meta font-medium text-tasklab-accent">
                                                    Super Admin
                                                </span>
                                            @elseif ($member->is_admin)
                                                <span class="inline-flex items-center rounded-full border border-tasklab-success/60 bg-tasklab-success/10 px-2 py-0.5 text-meta font-medium text-tasklab-success">
                                                    Admin
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full border border-slate-700 bg-tasklab-bg px-2 py-0.5 text-meta font-medium text-tasklab-muted">
                                                    Trabajador
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
                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAreaAdmin())
                                        <div class="pt-2 border-t border-slate-800 flex justify-between items-center gap-2">
                                            <a
                                                href="{{ route('tasks.index', ['assignee_id' => $member->id]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-1.5 text-meta text-tasklab-text hover:border-tasklab-accent hover:text-tasklab-accent"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-2 8l-4 0a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v5"/></svg>
                                                Ver tareas
                                            </a>

                                            <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-tasklab-accent/60 bg-tasklab-accent/10 px-3 py-1.5 text-body font-medium text-tasklab-accent hover:bg-tasklab-accent/20">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
                                                Gestionar
                                            </button>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Separador entre admins y trabajadores --}}
                @if($admins->isNotEmpty() && $workers->isNotEmpty())
                    <div class="border-t border-slate-800 my-6"></div>
                @endif

                {{-- Sección: Trabajadores --}}
                @if($workers->isNotEmpty())
                    <div class="space-y-2">
                        <h2 class="text-label font-semibold text-tasklab-muted uppercase tracking-wide">Trabajadores</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($workers as $member)
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
                                            @if (method_exists($member, 'isSuperAdmin') && $member->isSuperAdmin())
                                                <span class="inline-flex items-center rounded-full border border-tasklab-accent/70 bg-tasklab-accent/10 px-2 py-0.5 text-meta font-medium text-tasklab-accent">
                                                    Super Admin
                                                </span>
                                            @elseif ($member->is_admin)
                                                <span class="inline-flex items-center rounded-full border border-tasklab-success/60 bg-tasklab-success/10 px-2 py-0.5 text-meta font-medium text-tasklab-success">
                                                    Admin
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full border border-slate-700 bg-tasklab-bg px-2 py-0.5 text-meta font-medium text-tasklab-muted">
                                                    Trabajador
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
                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAreaAdmin())
                                        <div class="pt-2 border-t border-slate-800 flex justify-between items-center gap-2">
                                            <a
                                                href="{{ route('tasks.index', ['assignee_id' => $member->id]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-1.5 text-meta text-tasklab-text hover:border-tasklab-accent hover:text-tasklab-accent"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-2 8l-4 0a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v5"/></svg>
                                                Ver tareas
                                            </a>

                                            <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-tasklab-accent/60 bg-tasklab-accent/10 px-3 py-1.5 text-body font-medium text-tasklab-accent hover:bg-tasklab-accent/20">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
                                                Gestionar
                                            </button>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        @endif
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('teamBoard', () => ({
                draggedUserId: null,
                isLoading: false,
                async moveUserToDepartment(departmentLabel) {
                    if (!this.draggedUserId || this.isLoading) return;

                    this.isLoading = true;
                    try {
                        const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
                        const res = await fetch('{{ route('team.reassign-department') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                user_id: this.draggedUserId,
                                department: departmentLabel,
                            }),
                        });

                        if (!res.ok) {
                            console.error('Failed to reassign department', await res.text());
                            return;
                        }

                        // Recarga suave de la página para reflejar el cambio
                        window.location.reload();
                    } catch (e) {
                        console.error('Error reassigning department', e);
                    } finally {
                        this.isLoading = false;
                        this.draggedUserId = null;
                    }
                },
            }));
        });
    </script>
</x-app-layout>
