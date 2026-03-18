<x-app-layout>
    @php
        // ── Build team sections ────────────────────────────────────────────────
        // For each CategoryType (= Team), group members by their category value.
        $teamSections = [];
        foreach ($categoryTypes as $categoryType) {
            $typeId   = $categoryType->id;
            $groups   = []; // valueId => ['value' => CategoryValue, 'members' => []]
            $allInType = [];

            foreach ($teamMembers as $member) {
                $valuesForType = $member->categoryValues->filter(
                    fn ($cv) => $cv->category_type_id === $typeId
                );

                if ($valuesForType->isEmpty()) {
                    continue;
                }

                foreach ($valuesForType as $cv) {
                    $vid = $cv->id;
                    if (! isset($groups[$vid])) {
                        $groups[$vid] = ['value' => $cv, 'members' => []];
                    }
                    // Avoid duplicates when user has multiple values in same type
                    $alreadyIn = array_filter($groups[$vid]['members'], fn ($m) => $m->id === $member->id);
                    if (empty($alreadyIn)) {
                        $groups[$vid]['members'][] = $member;
                    }
                    $allInType[$member->id] = true;
                }
            }

            $teamSections[] = [
                'type'    => $categoryType,
                'groups'  => $groups,
                'count'   => count($allInType),
            ];
        }

        $totalMembers  = $teamMembers->count();
        $adminsCount   = $teamMembers->where('is_admin', true)->count();
        $isSuperAdmin  = auth()->user()->isSuperAdmin();

        // Pre-build JSON-safe data for Alpine (avoids Blade bracket-matching issues inside @json())
        $categoryTypesJson = $categoryTypes->map(function ($t) {
            return [
                'id'     => $t->id,
                'name'   => $t->name,
                'slug'   => $t->slug,
                'values' => $t->values->map(function ($v) {
                    return ['id' => $v->id, 'name' => $v->name];
                })->values()->all(),
            ];
        })->values()->all();
    @endphp

    <div
        class="max-w-[1600px] mx-auto px-4 py-6 space-y-6"
        x-data="teamManager()"
    >
        {{-- ── Header ── --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-heading font-semibold text-tasklab-text">Equipo</h1>
                <p class="text-meta text-tasklab-muted mt-0.5">Gestiona los equipos y asignaciones de tu organización.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-meta">
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-tasklab-bg px-3 py-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-tasklab-primary"></span>
                    <span class="text-tasklab-muted">Miembros</span>
                    <span class="text-tasklab-text font-semibold">{{ $totalMembers }}</span>
                </div>
                @if($isSuperAdmin)
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-tasklab-bg px-3 py-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-tasklab-success"></span>
                        <span class="text-tasklab-muted">Admins</span>
                        <span class="text-tasklab-text font-semibold">{{ $adminsCount }}</span>
                    </div>
                    @if($pendingUsers->isNotEmpty())
                        <div class="inline-flex items-center gap-2 rounded-full border border-yellow-500/40 bg-yellow-500/10 px-3 py-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-yellow-400"></span>
                            <span class="text-yellow-300">Sin equipo</span>
                            <span class="text-yellow-200 font-semibold">{{ $pendingUsers->count() }}</span>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- ── Toast notification ── --}}
        <div
            x-show="toast"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-5 right-5 z-50 flex items-center gap-3 rounded-xl border px-4 py-3 shadow-xl text-sm"
            :class="toastType === 'error' ? 'bg-red-900/90 border-red-700 text-red-100' : 'bg-tasklab-bg-muted border-slate-700 text-tasklab-text'"
            style="display:none"
        >
            <span x-text="toast"></span>
        </div>

        {{-- ── Pending users (SuperAdmin only) ── --}}
        @if($isSuperAdmin && $pendingUsers->isNotEmpty())
            <div class="rounded-2xl border border-yellow-500/30 bg-yellow-500/5 p-5 shadow-card space-y-4">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-semibold text-yellow-300">
                        {{ $pendingUsers->count() }} {{ $pendingUsers->count() === 1 ? 'usuario pendiente de asignación' : 'usuarios pendientes de asignación' }}
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach($pendingUsers as $pUser)
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-700/60 bg-tasklab-bg/60 px-4 py-3"
                            x-data="{
                                typeSlug: '',
                                valueId: '',
                                loading: false,
                                allTypes: @json($categoryTypesJson),
                                get currentValues() {
                                    const t = this.allTypes.find(t => t.slug === this.typeSlug);
                                    return t ? t.values : [];
                                }
                            }"
                        >
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-bold text-tasklab-text">
                                    {{ strtoupper(substr($pUser->name, 0, 2)) }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-tasklab-text">{{ $pUser->name }}</p>
                                    @if($pUser->position)
                                        <p class="text-xs text-tasklab-muted">{{ $pUser->position }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <select
                                    x-model="typeSlug"
                                    @change="valueId = ''"
                                    class="rounded-lg border border-slate-700 bg-tasklab-bg px-2 py-1.5 text-xs text-tasklab-text focus:border-tasklab-accent focus:outline-none"
                                >
                                    <option value="">Selecciona equipo…</option>
                                    <template x-for="t in allTypes" :key="t.slug">
                                        <option :value="t.slug" x-text="t.name"></option>
                                    </template>
                                </select>

                                <select
                                    x-model="valueId"
                                    x-show="typeSlug && currentValues.length"
                                    class="rounded-lg border border-slate-700 bg-tasklab-bg px-2 py-1.5 text-xs text-tasklab-text focus:border-tasklab-accent focus:outline-none"
                                >
                                    <option value="">Selecciona categoría…</option>
                                    <template x-for="v in currentValues" :key="v.id">
                                        <option :value="v.id" x-text="v.name"></option>
                                    </template>
                                </select>

                                <button
                                    @click="
                                        if (!typeSlug || !valueId) return;
                                        loading = true;
                                        $dispatch('assign-user', { userId: {{ $pUser->id }}, typeSlug, valueId });
                                        typeSlug = ''; valueId = '';
                                        setTimeout(() => { loading = false; }, 1200);
                                    "
                                    :disabled="!typeSlug || !valueId || loading"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-tasklab-accent/20 border border-tasklab-accent/40 px-3 py-1.5 text-xs font-medium text-tasklab-accent hover:bg-tasklab-accent/30 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                >
                                    <span x-show="!loading">Asignar</span>
                                    <span x-show="loading">…</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── Team sections ── --}}
        @forelse($teamSections as $section)
            <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted shadow-card overflow-hidden">

                {{-- Team header --}}
                <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-800/60">
                    <div class="flex items-center gap-3">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-tasklab-primary/10 border border-tasklab-primary/20">
                            <svg class="h-3.5 w-3.5 text-tasklab-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-sm font-semibold text-tasklab-text">{{ $section['type']->name }}</h2>
                    </div>
                    <span class="text-xs text-tasklab-muted rounded-full border border-slate-700 bg-tasklab-bg px-2.5 py-1">
                        {{ $section['count'] }} {{ $section['count'] === 1 ? 'miembro' : 'miembros' }}
                    </span>
                </div>

                {{-- Members grouped by category value --}}
                <div class="p-5 space-y-5">
                    @if(empty($section['groups']))
                        <p class="text-xs text-tasklab-muted text-center py-4">Sin miembros asignados a este equipo.</p>
                    @else
                        @foreach($section['groups'] as $group)
                            @php $catValue = $group['value']; @endphp
                            <div>
                                {{-- Category value label --}}
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-[11px] font-semibold uppercase tracking-wider text-tasklab-muted">
                                        @if($catValue->parent_id && $catValue->parent)
                                            <span class="text-tasklab-muted/50">{{ $catValue->parent->name }}</span>
                                            <span class="mx-1 text-tasklab-muted/30">›</span>
                                        @endif
                                        {{ $catValue->name }}
                                    </span>
                                    <div class="flex-1 h-px bg-slate-800"></div>
                                </div>

                                {{-- Member cards grid --}}
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                    @foreach($group['members'] as $member)
                                        <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-800 bg-tasklab-bg px-3 py-2.5 hover:border-slate-700 transition-colors">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-tasklab-primary/30 to-tasklab-accent/20 text-[10px] font-bold text-tasklab-text border border-slate-700">
                                                    {{ strtoupper(substr($member->name, 0, 2)) }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-medium text-tasklab-text truncate">{{ $member->name }}</p>
                                                    <p class="text-[10px] text-tasklab-muted truncate">{{ $member->position ?: '—' }}</p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1.5 shrink-0">
                                                {{-- Role badge --}}
                                                @if($member->isSuperAdmin())
                                                    <span class="rounded-full bg-violet-500/10 border border-violet-500/30 px-1.5 py-0.5 text-[10px] font-medium text-violet-300">SA</span>
                                                @elseif($member->is_admin)
                                                    <span class="rounded-full bg-tasklab-primary/10 border border-tasklab-primary/30 px-1.5 py-0.5 text-[10px] font-medium text-tasklab-primary">Admin</span>
                                                @endif

                                                {{-- Actions dropdown (SuperAdmin only) --}}
                                                @if($isSuperAdmin)
                                                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                                        <button
                                                            @click="open = !open"
                                                            class="flex h-6 w-6 items-center justify-center rounded-lg text-tasklab-muted hover:text-tasklab-text hover:bg-slate-700 transition-colors"
                                                        >
                                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                                            </svg>
                                                        </button>

                                                        <div
                                                            x-show="open"
                                                            x-transition:enter="transition ease-out duration-100"
                                                            x-transition:enter-start="opacity-0 scale-95"
                                                            x-transition:enter-end="opacity-100 scale-100"
                                                            class="absolute right-0 top-7 z-30 w-44 rounded-xl border border-slate-700 bg-tasklab-bg shadow-xl py-1"
                                                            style="display:none"
                                                        >
                                                            {{-- Role actions --}}
                                                            @if(!$member->is_admin)
                                                                <button
                                                                    @click="open=false; updateRole({{ $member->id }}, 'admin')"
                                                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs text-tasklab-text hover:bg-slate-800 transition-colors"
                                                                >
                                                                    <svg class="h-3.5 w-3.5 text-tasklab-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                                    Hacer admin
                                                                </button>
                                                            @else
                                                                <button
                                                                    @click="open=false; updateRole({{ $member->id }}, 'standard')"
                                                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs text-tasklab-text hover:bg-slate-800 transition-colors"
                                                                >
                                                                    <svg class="h-3.5 w-3.5 text-tasklab-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                                    Quitar admin
                                                                </button>
                                                            @endif

                                                            <div class="my-1 border-t border-slate-800"></div>

                                                            {{-- Reassign team --}}
                                                            <button
                                                                @click="open=false; openAssignModal({{ $member->id }}, '{{ $member->name }}', '{{ $section['type']->slug }}')"
                                                                class="flex w-full items-center gap-2 px-3 py-2 text-xs text-tasklab-text hover:bg-slate-800 transition-colors"
                                                            >
                                                                <svg class="h-3.5 w-3.5 text-tasklab-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                                Cambiar equipo
                                                            </button>

                                                            <div class="my-1 border-t border-slate-800"></div>

                                                            {{-- Remove from team --}}
                                                            <button
                                                                @click="open=false; removeFromTeam({{ $member->id }}, '{{ $section['type']->slug }}')"
                                                                class="flex w-full items-center gap-2 px-3 py-2 text-xs text-red-400 hover:bg-red-500/10 transition-colors"
                                                            >
                                                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                                                Quitar del equipo
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-10 text-center shadow-card">
                <p class="text-sm text-tasklab-muted">No hay equipos creados aún. Crea tipos de categoría en Configuración para definir los equipos.</p>
            </div>
        @endforelse

        {{-- ── Assignment modal ── --}}
        <div
            x-show="modal.open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
            @click.self="modal.open = false"
            style="display:none"
        >
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="w-full max-w-sm rounded-2xl border border-slate-700 bg-tasklab-bg shadow-2xl p-6 space-y-5"
            >
                <div>
                    <h3 class="text-sm font-semibold text-tasklab-text">Cambiar asignación de equipo</h3>
                    <p class="text-xs text-tasklab-muted mt-1" x-text="modal.userName"></p>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-medium text-tasklab-muted mb-1.5 uppercase tracking-wider">Equipo</label>
                        <select
                            x-model="modal.typeSlug"
                            class="w-full rounded-xl border border-slate-700 bg-tasklab-bg-muted px-3 py-2 text-sm text-tasklab-text focus:border-tasklab-accent focus:outline-none"
                        >
                            <option value="">Selecciona equipo…</option>
                            <template x-for="t in categoryTypes" :key="t.slug">
                                <option :value="t.slug" x-text="t.name"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="modal.typeSlug">
                        <label class="block text-[11px] font-medium text-tasklab-muted mb-1.5 uppercase tracking-wider">Categoría</label>
                        <select
                            x-model="modal.valueId"
                            class="w-full rounded-xl border border-slate-700 bg-tasklab-bg-muted px-3 py-2 text-sm text-tasklab-text focus:border-tasklab-accent focus:outline-none"
                        >
                            <option value="">Selecciona categoría…</option>
                            <template x-for="v in modalValues()" :key="v.id">
                                <option :value="v.id" x-text="v.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2 pt-1">
                    <button
                        @click="modal.open = false"
                        class="flex-1 rounded-xl border border-slate-700 bg-transparent px-4 py-2 text-xs font-medium text-tasklab-muted hover:text-tasklab-text hover:border-slate-600 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="submitAssign()"
                        :disabled="!modal.typeSlug || !modal.valueId"
                        class="flex-1 rounded-xl border border-tasklab-accent/40 bg-tasklab-accent/20 px-4 py-2 text-xs font-medium text-tasklab-accent hover:bg-tasklab-accent/30 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        Asignar
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
    function teamManager() {
        return {
            toast: null,
            toastType: 'success',
            toastTimer: null,

            modal: {
                open: false,
                userId: null,
                userName: '',
                typeSlug: '',
                valueId: '',
            },

            categoryTypes: @json($categoryTypesJson),

            init() {
                this.$el.addEventListener('assign-user', (e) => {
                    const { userId, typeSlug, valueId } = e.detail;
                    this.doAssign(userId, typeSlug, valueId);
                });
            },

            modalValues() {
                const t = this.categoryTypes.find(t => t.slug === this.modal.typeSlug);
                return t ? t.values : [];
            },

            openAssignModal(userId, userName, currentTypeSlug) {
                this.modal.userId      = userId;
                this.modal.userName    = userName;
                this.modal.typeSlug    = currentTypeSlug || '';
                this.modal.valueId     = '';
                this.modal.open        = true;
            },

            async submitAssign() {
                if (! this.modal.typeSlug || ! this.modal.valueId) return;
                this.modal.open = false;
                await this.doAssign(this.modal.userId, this.modal.typeSlug, this.modal.valueId);
            },

            async doAssign(userId, typeSlug, valueId) {
                try {
                    const res = await this.post('{{ route('team.reassign-category') }}', {
                        user_id:            userId,
                        category_type_slug: typeSlug,
                        category_value_id:  valueId,
                        clone:              false,
                    });
                    this.notify('Asignación actualizada');
                    setTimeout(() => window.location.reload(), 600);
                } catch {
                    this.notify('Error al asignar. Inténtalo de nuevo.', 'error');
                }
            },

            async removeFromTeam(userId, typeSlug) {
                if (! confirm('¿Quitar a este usuario del equipo?')) return;
                try {
                    await this.post('{{ route('team.reassign-category') }}', {
                        user_id:            userId,
                        category_type_slug: typeSlug,
                        category_value_id:  null,
                    });
                    this.notify('Usuario retirado del equipo');
                    setTimeout(() => window.location.reload(), 600);
                } catch {
                    this.notify('Error al quitar. Inténtalo de nuevo.', 'error');
                }
            },

            async updateRole(userId, role) {
                try {
                    await this.post('{{ route('team.users.update-role') }}', {
                        user_id: userId,
                        role:    role,
                    });
                    this.notify('Rol actualizado');
                    setTimeout(() => window.location.reload(), 600);
                } catch {
                    this.notify('Error al actualizar el rol.', 'error');
                }
            },

            async post(url, data) {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const res   = await fetch(url, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': token ?? '',
                    },
                    body: JSON.stringify(data),
                });
                if (! res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            },

            notify(message, type = 'success') {
                clearTimeout(this.toastTimer);
                this.toast     = message;
                this.toastType = type;
                this.toastTimer = setTimeout(() => { this.toast = null; }, 3000);
            },
        };
    }
    </script>
</x-app-layout>
