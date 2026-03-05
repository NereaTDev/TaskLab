<x-app-layout>
    <div
        class="max-w-[1600px] mx-auto px-4 py-6 space-y-6"
        x-data="teamBoard('{{ $categoryBoardType->slug ?? '' }}')"
    >
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

        {{-- Header equipo --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-heading font-semibold text-tasklab-text">Equipo</h1>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-meta">
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-tasklab-bg px-3 py-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-tasklab-primary"></span>
                    <span class="text-tasklab-muted">Miembros</span>
                    <span class="text-tasklab-text font-semibold">{{ $totalMembers }}</span>
                </div>
                @if(auth()->user()->isSuperAdmin())
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-tasklab-bg px-3 py-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-tasklab-success"></span>
                    <span class="text-tasklab-muted">Admins</span>
                    <span class="text-tasklab-text font-semibold">{{ $adminsCount }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Selector de vista --}}
        <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('team.index') }}"
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-meta font-medium {{ $group ? 'text-tasklab-muted hover:text-tasklab-text bg-transparent border border-transparent' : 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' }}"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    Miembros
                </a>
                @foreach($categoryTypes as $type)
                    <a
                        href="{{ route('team.index', ['group' => 'category:'.$type->slug]) }}"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-meta font-medium {{ $group === 'category:'.$type->slug ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text bg-transparent border border-transparent' }}"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h6v6H3zM9 13h6v6H9zM15 5h6v6h-6z"/></svg>
                        {{ $type->name }}
                    </a>
                @endforeach
            </div>
            <p class="text-meta text-tasklab-muted">
                @if(str_starts_with($group ?? '', 'category:') && $categoryBoardType)
                    Arrastra personas entre valores y subcategorías de "{{ $categoryBoardType->name }}" (solo Super Admin).
                @else
                    Usa las vistas para analizar y organizar tu equipo.
                @endif
            </p>
        </div>

        {{-- Contenido principal --}}
        @if($teamMembers->isEmpty())
            <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-8 text-center shadow-card">
                <p class="text-body text-tasklab-muted">No team members yet.</p>
            </div>
        @else
            @if(str_starts_with($group ?? '', 'category:') && $categoryBoardType)
                {{-- Tablero por CategoryType (ej. Áreas, Departamentos, etc.) --}}
                @php
                    // Paleta de columnas: evitamos usar tasklab.accent aquí para no confundir con el color de selección
                    $palette = [
                        ['dot' => 'bg-tasklab-primary', 'childBorder' => 'border-tasklab-primary/40'],
                        ['dot' => 'bg-tasklab-success', 'childBorder' => 'border-tasklab-success/40'],
                        ['dot' => 'bg-tasklab-warning', 'childBorder' => 'border-tasklab-warning/40'],
                    ];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @foreach($categoryColumns as $columnKey => $column)
                        @php
                            $isNone = $columnKey === '__none__';
                            $variant = null;
                            if (! $isNone) {
                                $variant = $palette[$loop->index % count($palette)];
                            }
                            $canDeleteAssignment = function($memberId) use ($categoryAssignCounts) {
                                return ($categoryAssignCounts[$memberId] ?? 0) > 1;
                            };
                        @endphp
                        <div
                            class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card flex flex-col min-h-[260px] @if($columnKey === '__none__') bg-[#0b122000] shadow-none @endif"
                            @dragover.prevent
                            @drop.prevent="moveUserToCategory('{{ $columnKey }}')"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full {{ $variant['dot'] ?? 'bg-slate-600' }}"></span>
                                    <div>
                                        <p class="text-label font-semibold text-tasklab-text">{{ $column['label'] }}</p>
                                        @php
                                            $count = $column['users_for_parent']->count();
                                            foreach ($column['children'] as $child) {
                                                $count += $child['users']->count();
                                            }
                                        @endphp
                                        <p class="text-meta text-tasklab-muted">{{ $count }} personas</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2 space-y-3 flex-1">
                                {{-- Usuarios asignados al valor sin subcategoría --}}
                                @if($column['users_for_parent']->isNotEmpty())
                                    <div class="space-y-1">
                                        @foreach($column['users_for_parent'] as $member)
                                            @php $profile = $member->developerProfile; @endphp
                                            <article
                                                class="rounded-xl border border-slate-800 bg-tasklab-bg px-3 py-2 flex items-center justify-between gap-2 cursor-move"
                                                :class="{
                                                    'ring-1 ring-tasklab-accent/60 ring-offset-1 ring-offset-tasklab-bg-muted': highlightUserId === {{ $member->id }},
                                                    'border-tasklab-accent/70 bg-tasklab-accent/5': cloneUserId === {{ $member->id }}
                                                }"
                                                draggable="true"
                                                @dragstart="draggedUserId = {{ $member->id }}"
                                                @dragend="draggedUserId = null"
                                                @click.stop="toggleHighlight({{ $member->id }})"
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
                                                    <div class="flex items-center gap-1">
                                                        @if ($member->is_admin)
                                                            <span class="inline-flex items-center rounded-full border border-tasklab-success/60 bg-tasklab-success/10 px-2 py-0.5 text-[10px] font-medium text-tasklab-success">
                                                                Admin
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center rounded-full border border-slate-700 bg-tasklab-bg px-2 py-0.5 text-[10px] font-medium text-tasklab-muted">
                                                                Trabajador
                                                            </span>
                                                        @endif
                                                        <div x-data="{ open: false }" class="relative">
                                                            <button
                                                                type="button"
                                                                class="inline-flex items-center justify-center h-5 w-5 rounded-full text-[10px] text-tasklab-muted hover:text-tasklab-accent hover:bg-slate-900"
                                                                :class="cloneUserId === {{ $member->id }} ? 'text-tasklab-accent bg-slate-900' : ''"
                                                                @click.stop="open = !open"
                                                                title="Más acciones"
                                                            >
                                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.75a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 13.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 20.25a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                                                </svg>
                                                            </button>
                                                            <div
                                                                x-cloak
                                                                x-show="open"
                                                                @click.outside="open = false"
                                                                class="absolute right-0 mt-1 w-28 rounded-md border border-slate-800 bg-tasklab-bg-muted shadow-card text-[11px] z-20"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    class="w-full text-left px-3 py-1.5 text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text"
                                                                    @click.stop="setClone({{ $member->id }}); open = false"
                                                                >
                                                                    Clonar
                                                                </button>
                                                                @if($canDeleteAssignment($member->id))
                                                                    <button
                                                                        type="button"
                                                                        class="w-full text-left px-3 py-1.5 text-tasklab-danger hover:bg-slate-900 hover:text-red-400"
                                                                        @click.stop="deleteCategoryAssignment({{ $member->id }}, {{ $columnKey === '__none__' ? 'null' : $columnKey }}); open = false"
                                                                    >
                                                                        Eliminar
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Subcategorías dentro del valor --}}
                                @foreach($column['children'] as $childId => $child)
                                    <div
                                        class="border {{ $variant['childBorder'] ?? 'border-slate-800' }} rounded-lg bg-tasklab-bg p-2 min-h-[120px]"
                                        @dragover.prevent
                                        @drop.prevent="moveUserToCategory('{{ $childId }}')"
                                    >
                                        <p class="text-label font-semibold text-tasklab-text mb-1">{{ $child['value']->name }}</p>
                                        <div class="space-y-1">
                                            @forelse($child['users'] as $member)
                                                @php $profile = $member->developerProfile; @endphp
                                                <article
                                                    class="rounded-md border border-slate-800 bg-tasklab-bg-muted px-2 py-1.5 flex items-center justify-between gap-2 cursor-move"
                                                    :class="{
                                                        'ring-1 ring-tasklab-accent/60 ring-offset-1 ring-offset-tasklab-bg-muted': highlightUserId === {{ $member->id }},
                                                        'border-tasklab-accent/70 bg-tasklab-accent/5': cloneUserId === {{ $member->id }}
                                                    }"
                                                    draggable="true"
                                                    @dragstart="draggedUserId = {{ $member->id }}"
                                                    @dragend="draggedUserId = null"
                                                    @click.stop="toggleHighlight({{ $member->id }})"
                                                >
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-[10px] font-semibold text-tasklab-text">
                                                            {{ strtoupper(substr($member->name ?? 'A', 0, 2)) }}
                                                        </span>
                                                        <div class="min-w-0">
                                                            <p class="text-[11px] font-medium text-tasklab-text truncate">{{ $member->name }}</p>
                                                            <p class="text-[10px] text-tasklab-muted truncate">{{ $member->position ?: 'Sin posición' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col items-end gap-1">
                                                        <div class="flex items-center gap-1">
                                                            @if ($member->is_admin)
                                                                <span class="inline-flex items-center rounded-full border border-tasklab-success/60 bg-tasklab-success/10 px-2 py-0.5 text-[10px] font-medium text-tasklab-success">
                                                                    Admin
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center rounded-full border border-slate-700 bg-tasklab-bg px-2 py-0.5 text-[10px] font-medium text-tasklab-muted">
                                                                    Trabajador
                                                                </span>
                                                            @endif
                                                            <div x-data="{ open: false }" class="relative">
                                                                <button
                                                                    type="button"
                                                                    class="inline-flex items-center justify-center h-5 w-5 rounded-full text-[10px] text-tasklab-muted hover:text-tasklab-accent hover:bg-slate-900"
                                                                    :class="cloneUserId === {{ $member->id }} ? 'text-tasklab-accent bg-slate-900' : ''"
                                                                    @click.stop="open = !open"
                                                                    title="Más acciones"
                                                                >
                                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.75a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 13.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 20.25a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                                                    </svg>
                                                                </button>
                                                                <div
                                                                    x-cloak
                                                                    x-show="open"
                                                                    @click.outside="open = false"
                                                                    class="absolute right-0 mt-1 w-28 rounded-md border border-slate-800 bg-tasklab-bg-muted shadow-card text-[11px] z-20"
                                                                >
                                                                    <button
                                                                        type="button"
                                                                        class="w-full text-left px-3 py-1.5 text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text"
                                                                        @click.stop="setClone({{ $member->id }}); open = false"
                                                                    >
                                                                        Clonar
                                                                    </button>
                                                                    @if($canDeleteAssignment($member->id))
                                                                        <button
                                                                            type="button"
                                                                            class="w-full text-left px-3 py-1.5 text-tasklab-danger hover:bg-slate-900 hover:text-red-400"
                                                                            @click.stop="deleteCategoryAssignment({{ $member->id }}, {{ $childId }}); open = false"
                                                                        >
                                                                            Eliminar
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </article>
                                            @empty
                                                <p class="text-[11px] text-tasklab-muted pt-4 text-center">Sin personas asignadas.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Vista lista: Admins y trabajadores --}}
                @if($admins->isNotEmpty())
                    <div class="space-y-2">
                        <h2 class="text-label font-semibold text-tasklab-muted uppercase tracking-wide">Administradores</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($admins as $member)
                                @php
                                    $profile = $member->developerProfile;
                                @endphp

                                <article class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card flex flex-col justify-between gap-4">
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

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-label text-tasklab-muted">
                                        <div>
                                            @include('team.partials.user-categories', ['member' => $member])
                                        </div>
                                        <div>
                                            <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Posición</p>
                                            <p class="mt-0.5 text-body text-tasklab-text">{{ $member->position ?: '—' }}</p>
                                        </div>
                                    </div>

                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAreaAdmin())
                                        <div class="pt-2 border-t border-slate-800 flex justify-between items-center gap-2">
                                            <a
                                                href="{{ route('tasks.index', ['assignee_id' => $member->id]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-1.5 text-meta text-tasklab-text hover:border-tasklab-accent hover:text-tasklab-accent"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-2 8l-4 0a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v5"/></svg>
                                                Ver tareas
                                            </a>

                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-tasklab-accent/60 bg-tasklab-accent/10 px-2 py-1.5 text-label font-medium text-tasklab-accent hover:bg-tasklab-accent/20"
                                                @click.stop="openUserModal(@js([
                                                    'id'             => $member->id,
                                                    'name'           => $member->name,
                                                    'email'          => $member->email,
                                                    'position'       => $member->position,
                                                    'department'     => $member->department,
                                                    'user_type'      => $member->user_type,
                                                    'is_admin'       => (bool) $member->is_admin,
                                                    'is_super_admin' => method_exists($member, 'isSuperAdmin') ? $member->isSuperAdmin() : false,
                                                    'created_at'     => optional($member->created_at)->format('d/m/Y'),
                                                    'developer_profile' => $profile ? [
                                                        'type'               => $profile->type,
                                                        'areas'              => $profile->areas,
                                                        'max_parallel_tasks' => $profile->max_parallel_tasks,
                                                        'active'             => (bool) $profile->active,
                                                    ] : null,
                                                ]))"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
                                            </button>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($admins->isNotEmpty() && $workers->isNotEmpty())
                    <div class="border-t border-slate-800 my-6"></div>
                @endif

                @if($workers->isNotEmpty())
                    <div class="space-y-2">
                        <h2 class="text-label font-semibold text-tasklab-muted uppercase tracking-wide">Trabajadores</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($workers as $member)
                                @php
                                    $profile = $member->developerProfile;
                                @endphp

                                <article class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card flex flex-col justify-between gap-4">
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

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-label text-tasklab-muted">
                                        <div>
                                            @include('team.partials.user-categories', ['member' => $member])
                                        </div>
                                        <div>
                                            <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Posición</p>
                                            <p class="mt-0.5 text-body text-tasklab-text">{{ $member->position ?: '—' }}</p>
                                        </div>
                                    </div>

                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAreaAdmin())
                                        <div class="pt-2 border-t border-slate-800 flex justify-between items-center gap-2">
                                            <a
                                                href="{{ route('tasks.index', ['assignee_id' => $member->id]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-3 py-1.5 text-meta text-tasklab-text hover:border-tasklab-accent hover:text-tasklab-accent"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-2 8l-4 0a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012-2h8a2 2 0 012 2v5"/></svg>
                                                Ver tareas
                                            </a>

                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-tasklab-accent/60 bg-tasklab-accent/10 px-2 py-1.5 text-label font-medium text-tasklab-accent hover:bg-tasklab-accent/20"
                                                @click.stop="openUserModal(@js([
                                                    'id'             => $member->id,
                                                    'name'           => $member->name,
                                                    'email'          => $member->email,
                                                    'position'       => $member->position,
                                                    'department'     => $member->department,
                                                    'user_type'      => $member->user_type,
                                                    'is_admin'       => (bool) $member->is_admin,
                                                    'is_super_admin' => method_exists($member, 'isSuperAdmin') ? $member->isSuperAdmin() : false,
                                                    'created_at'     => optional($member->created_at)->format('d/m/Y'),
                                                    'developer_profile' => $profile ? [
                                                        'type'               => $profile->type,
                                                        'areas'              => $profile->areas,
                                                        'max_parallel_tasks' => $profile->max_parallel_tasks,
                                                        'active'             => (bool) $profile->active,
                                                    ] : null,
                                                ]))"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
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

        {{-- Modal detalle de usuario / gestión de rol --}}
        <div
            x-cloak
            x-show="isUserModalOpen"
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/60"
            @keydown.escape.window="closeUserModal()"
        >
            <div
                class="w-full max-w-lg rounded-2xl border border-slate-800 bg-tasklab-bg shadow-card p-6"
                @click.outside="closeUserModal()"
            >
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-tasklab-text">
                            <span x-text="modalUser?.name ? modalUser.name.substring(0,2).toUpperCase() : 'US'"></span>
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-body font-semibold text-tasklab-text" x-text="modalUser?.name"></p>
                                @if(auth()->user()->isSuperAdmin())
                                    <div x-data="{ open: false }" class="relative">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center h-6 w-6 rounded-full text-[11px] text-tasklab-muted hover:text-tasklab-accent hover:bg-slate-900"
                                            @click.stop="open = !open"
                                            title="Gestión de rol"
                                        >
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.75a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 13.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM12 20.25a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                            </svg>
                                        </button>
                                        <div
                                            x-cloak
                                            x-show="open"
                                            @click.outside="open = false"
                                            class="absolute left-0 mt-1 w-40 rounded-md border border-slate-800 bg-tasklab-bg-muted shadow-card text-[11px] z-50"
                                        >
                                            <p class="px-3 pt-2 pb-1 text-meta text-tasklab-muted border-b border-slate-800">Cambiar rol</p>
                                            <button
                                                type="button"
                                                class="w-full text-left px-3 py-1.5 text-tasklab-text hover:bg-slate-900"
                                                @click.stop="setUserRole('standard'); open = false"
                                            >
                                                Trabajador
                                            </button>
                                            <button
                                                type="button"
                                                class="w-full text-left px-3 py-1.5 text-tasklab-text hover:bg-slate-900"
                                                @click.stop="setUserRole('admin'); open = false"
                                            >
                                                Admin
                                            </button>
                                            <button
                                                type="button"
                                                class="w-full text-left px-3 py-1.5 text-tasklab-accent hover:bg-slate-900"
                                                @click.stop="setUserRole('super_admin'); open = false"
                                            >
                                                Super Admin
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <p class="text-meta text-tasklab-muted" x-text="modalUser?.email"></p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-8 w-8 rounded-full border border-slate-700 bg-tasklab-bg text-tasklab-muted hover:text-tasklab-accent hover:border-tasklab-accent"
                        @click="closeUserModal()"
                        aria-label="Cerrar"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Datos estructurados --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4 text-label text-tasklab-muted">
                    <div>
                        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Departamento</p>
                        <p class="mt-0.5 text-body text-tasklab-text" x-text="modalUser?.department || '—'"></p>
                    </div>
                    <div>
                        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Posición</p>
                        <p class="mt-0.5 text-body text-tasklab-text" x-text="modalUser?.position || '—'"></p>
                    </div>
                    <div>
                        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Rol</p>
                        <p class="mt-0.5 text-body text-tasklab-text" x-text="modalUser?.is_super_admin ? 'Super Admin' : (modalUser?.is_admin ? 'Admin' : 'Trabajador')"></p>
                    </div>
                    <div>
                        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Fecha de alta</p>
                        <p class="mt-0.5 text-body text-tasklab-text" x-text="modalUser?.created_at || '—'"></p>
                    </div>
                    <div>
                        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Áreas</p>
                        <p class="mt-0.5 text-body text-tasklab-text"
                           x-text="modalUser?.developer_profile && modalUser.developer_profile.areas
                                ? (Array.isArray(modalUser.developer_profile.areas)
                                    ? modalUser.developer_profile.areas.join(', ')
                                    : modalUser.developer_profile.areas)
                                : '—'"
                        ></p>
                    </div>
                </div>

                {{-- Developer profile si existe --}}
                <template x-if="modalUser?.developer_profile">
                    <div class="mb-4 rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3">
                        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Perfil de desarrollo</p>
                        <p class="text-body text-tasklab-text">
                            <span class="font-semibold">Tipo:</span>
                            <span x-text="modalUser.developer_profile.type"></span>
                        </p>
                        <p class="text-body text-tasklab-text mt-1">
                            <span class="font-semibold">Áreas:</span>
                            <span x-text="Array.isArray(modalUser.developer_profile.areas) ? modalUser.developer_profile.areas.join(', ') : (modalUser.developer_profile.areas || '—')"></span>
                        </p>
                        <p class="text-body text-tasklab-text mt-1">
                            <span class="font-semibold">Máx. tareas en paralelo:</span>
                            <span x-text="modalUser.developer_profile.max_parallel_tasks ?? '—'"></span>
                        </p>
                        <p class="text-body text-tasklab-text mt-1">
                            <span class="font-semibold">Activo:</span>
                            <span x-text="modalUser.developer_profile.active ? 'Sí' : 'No'"></span>
                        </p>
                    </div>
                </template>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('teamBoard', (categoryTypeSlug) => ({
                draggedUserId: null,
                isLoading: false,
                categoryTypeSlug: categoryTypeSlug || '',
                cloneUserId: null,
                highlightUserId: null,

                // Estado del modal de gestión de usuario
                isUserModalOpen: false,
                modalUser: null,

                setClone(userId) {
                    this.cloneUserId = userId;
                },

                toggleHighlight(userId) {
                    this.highlightUserId = this.highlightUserId === userId ? null : userId;
                },

                openUserModal(user) {
                    this.modalUser = user;
                    this.isUserModalOpen = true;
                },

                closeUserModal() {
                    this.isUserModalOpen = false;
                    this.modalUser = null;
                },

                async setUserRole(role) {
                    if (!this.modalUser) return;

                    this.isLoading = true;
                    try {
                        const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
                        const payload = {
                            user_id: this.modalUser.id,
                            role,
                        };

                        const res = await fetch('{{ route('team.users.update-role') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        if (!res.ok) {
                            console.error('Failed to update user role', await res.text());
                            return;
                        }

                        window.location.reload();
                    } catch (e) {
                        console.error('Error updating user role', e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async moveUserToCategory(columnKey) {
                    if (!this.draggedUserId || this.isLoading || !this.categoryTypeSlug) return;

                    this.isLoading = true;
                    try {
                        const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');

                        const payload = {
                            user_id: this.draggedUserId,
                            category_type_slug: this.categoryTypeSlug,
                            category_value_id: columnKey === '__none__' ? null : Number(columnKey),
                            clone: this.cloneUserId === this.draggedUserId,
                        };

                        const res = await fetch('{{ route('team.reassign-category') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        if (!res.ok) {
                            console.error('Failed to reassign category', await res.text());
                            return;
                        }

                        window.location.reload();
                    } catch (e) {
                        console.error('Error reassigning category', e);
                    } finally {
                        this.isLoading = false;
                        this.draggedUserId = null;
                        this.cloneUserId = null;
                    }
                },

                async deleteCategoryAssignment(userId, categoryValueId) {
                    if (!this.categoryTypeSlug) return;


                    this.isLoading = true;
                    try {
                        const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');

                        const payload = {
                            user_id: userId,
                            category_type_slug: this.categoryTypeSlug,
                            category_value_id: categoryValueId,
                            delete_single: true,
                        };

                        const res = await fetch('{{ route('team.reassign-category') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        if (!res.ok) {
                            console.error('Failed to delete assignment', await res.text());
                            return;
                        }

                        window.location.reload();
                    } catch (e) {
                        console.error('Error deleting assignment', e);
                    } finally {
                        this.isLoading = false;
                    }
                },
            }));
        });
    </script>
</x-app-layout>
