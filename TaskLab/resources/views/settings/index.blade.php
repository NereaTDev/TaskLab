<x-app-layout>
    <div class="max-w-[1200px] mx-auto px-4 py-6 space-y-6">

        {{-- ── Header ── --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-heading font-semibold text-tasklab-text">Configuración</h1>
                <p class="text-meta text-tasklab-muted mt-0.5">Gestiona los equipos y sus categorías. Solo Super Admin.</p>
            </div>

            @if(session('status'))
                <div class="inline-flex items-center gap-2 rounded-full border border-tasklab-success/30 bg-tasklab-success/10 px-3 py-1.5 text-xs font-medium text-tasklab-success">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ session('status') }}
                </div>
            @endif
        </div>

        {{-- ── Errors ── --}}
        @if($errors->any())
            <div class="rounded-xl border border-red-500/30 bg-red-500/5 px-4 py-3 text-sm text-red-400">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ── Main layout ── --}}
        <div class="grid grid-cols-1 md:grid-cols-[280px,minmax(0,1fr)] gap-5">

            {{-- ── Left sidebar: team list ── --}}
            <div class="space-y-3">
                <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted shadow-card overflow-hidden">

                    {{-- Sidebar header --}}
                    <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-800/60">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-tasklab-primary/10 border border-tasklab-primary/20">
                            <svg class="h-3.5 w-3.5 text-tasklab-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-tasklab-text">Equipos</p>
                            <p class="text-[11px] text-tasklab-muted">{{ $categoryTypes->count() }} {{ $categoryTypes->count() === 1 ? 'equipo' : 'equipos' }}</p>
                        </div>
                    </div>

                    {{-- Team list --}}
                    <div class="p-3 space-y-1">
                        @forelse($categoryTypes as $type)
                            @php $isActive = $activeType && $activeType->id === $type->id; @endphp
                            <div class="flex items-center gap-1 group">
                                <a
                                    href="{{ route('settings.index', ['type' => $type->slug]) }}"
                                    class="flex-1 flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm transition-colors {{ $isActive ? 'bg-tasklab-bg border border-tasklab-accent/50 text-tasklab-text' : 'text-tasklab-muted hover:text-tasklab-text hover:bg-tasklab-bg/60 border border-transparent' }}"
                                >
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md {{ $isActive ? 'bg-tasklab-accent/20 text-tasklab-accent' : 'bg-slate-800 text-tasklab-muted' }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                        </svg>
                                    </span>
                                    <span class="truncate font-medium">{{ $type->name }}</span>
                                </a>
                                <form
                                    method="POST"
                                    action="{{ route('settings.category-types.destroy', $type) }}"
                                    onsubmit="return confirm('¿Eliminar el equipo «{{ addslashes($type->name) }}» y todas sus categorías?');"
                                    class="shrink-0"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-tasklab-muted opacity-0 group-hover:opacity-100 hover:bg-red-500/10 hover:text-red-400 transition-all"
                                        title="Eliminar equipo"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="px-3 py-4 text-center text-xs text-tasklab-muted">Aún no hay equipos.</p>
                        @endforelse
                    </div>

                    {{-- Add new team form --}}
                    <div class="border-t border-slate-800/60 p-3">
                        <form
                            method="POST"
                            action="{{ route('settings.category-types.store') }}"
                            class="flex items-center gap-2"
                            x-data="{ name: '' }"
                        >
                            @csrf
                            <input
                                type="text"
                                name="name"
                                x-model="name"
                                placeholder="Nuevo equipo…"
                                required
                                class="flex-1 rounded-xl border border-slate-700 bg-tasklab-bg px-3 py-2 text-xs text-tasklab-text placeholder-tasklab-muted/50 focus:border-tasklab-accent focus:outline-none"
                            />
                            <button
                                type="submit"
                                :disabled="!name.trim()"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-tasklab-accent/40 bg-tasklab-accent/20 text-tasklab-accent hover:bg-tasklab-accent/30 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                title="Crear equipo"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Hint --}}
                <p class="px-1 text-[11px] text-tasklab-muted leading-relaxed">
                    Cada equipo agrupa a los miembros según su rol y categoría. La IA usa estos equipos para asignar tareas automáticamente.
                </p>
            </div>

            {{-- ── Right panel: active team detail ── --}}
            <div>
                @if($activeType && $activeTypeWithValues)
                    <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted shadow-card overflow-hidden">

                        {{-- Panel header --}}
                        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-800/60">
                            <div>
                                <h2 class="text-sm font-semibold text-tasklab-text">{{ $activeTypeWithValues->name }}</h2>
                                <p class="text-[11px] text-tasklab-muted mt-0.5">
                                    Categorías y subcategorías que agrupan a los miembros de este equipo.
                                </p>
                            </div>
                            <span class="shrink-0 text-xs text-tasklab-muted rounded-full border border-slate-700 bg-tasklab-bg px-2.5 py-1">
                                {{ $activeTypeWithValues->values->count() }} {{ $activeTypeWithValues->values->count() === 1 ? 'categoría' : 'categorías' }}
                            </span>
                        </div>

                        <div class="p-5 space-y-3">

                            {{-- Category values --}}
                            @forelse($activeTypeWithValues->values as $value)
                                <div
                                    class="rounded-xl border border-slate-800 bg-tasklab-bg overflow-hidden"
                                    x-data="{ expanded: true, editing: false, editName: '{{ addslashes($value->name) }}' }"
                                >
                                    {{-- Value header row --}}
                                    <div class="flex items-center gap-2 px-4 py-3">
                                        {{-- Expand toggle --}}
                                        <button
                                            type="button"
                                            @click="expanded = !expanded"
                                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded text-tasklab-muted hover:text-tasklab-text transition-colors"
                                        >
                                            <svg class="h-3.5 w-3.5 transition-transform" :class="expanded ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>

                                        {{-- Inline edit form --}}
                                        <div class="flex-1 min-w-0" x-show="!editing">
                                            <span class="text-sm font-medium text-tasklab-text">{{ $value->name }}</span>
                                            @if($value->children->count())
                                                <span class="ml-2 text-[10px] text-tasklab-muted">{{ $value->children->count() }} sub</span>
                                            @endif
                                        </div>

                                        <form
                                            method="POST"
                                            action="{{ route('settings.category-values.update', $value) }}"
                                            class="flex-1 flex items-center gap-2"
                                            x-show="editing"
                                            x-cloak
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <input
                                                type="text"
                                                name="name"
                                                x-model="editName"
                                                @keydown.escape="editing = false"
                                                x-ref="editInput_{{ $value->id }}"
                                                class="flex-1 rounded-lg border border-tasklab-accent/50 bg-tasklab-bg-muted px-2.5 py-1 text-sm text-tasklab-text focus:outline-none focus:border-tasklab-accent"
                                            />
                                            <button type="submit" class="text-xs font-medium text-tasklab-accent hover:text-tasklab-accent/80 transition-colors">Guardar</button>
                                            <button type="button" @click="editing = false" class="text-xs text-tasklab-muted hover:text-tasklab-text transition-colors">Cancelar</button>
                                        </form>

                                        {{-- Action buttons --}}
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button
                                                type="button"
                                                @click="editing = true; $nextTick(() => $refs.editInput_{{ $value->id }}.focus())"
                                                x-show="!editing"
                                                class="flex h-6 w-6 items-center justify-center rounded-lg text-tasklab-muted hover:text-tasklab-text hover:bg-slate-800 transition-colors"
                                                title="Editar"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>
                                            <form
                                                method="POST"
                                                action="{{ route('settings.category-values.destroy', $value) }}"
                                                onsubmit="return confirm('¿Eliminar «{{ addslashes($value->name) }}» y sus subcategorías?');"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="flex h-6 w-6 items-center justify-center rounded-lg text-tasklab-muted hover:text-red-400 hover:bg-red-500/10 transition-colors"
                                                    title="Eliminar"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Subcategories (expandable) --}}
                                    <div x-show="expanded" class="border-t border-slate-800/60">
                                        <div class="px-4 py-3 space-y-2 bg-tasklab-bg-muted/30">

                                            {{-- Subcategory list --}}
                                            @forelse($value->children as $child)
                                                <div
                                                    class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-tasklab-bg/60 transition-colors group/child"
                                                    x-data="{ editing: false, editName: '{{ addslashes($child->name) }}' }"
                                                >
                                                    <span class="h-1 w-1 shrink-0 rounded-full bg-slate-600"></span>

                                                    <div class="flex-1 min-w-0" x-show="!editing">
                                                        <span class="text-xs text-tasklab-text">{{ $child->name }}</span>
                                                    </div>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('settings.category-values.update', $child) }}"
                                                        class="flex-1 flex items-center gap-2"
                                                        x-show="editing"
                                                        x-cloak
                                                    >
                                                        @csrf
                                                        @method('PATCH')
                                                        <input
                                                            type="text"
                                                            name="name"
                                                            x-model="editName"
                                                            @keydown.escape="editing = false"
                                                            x-ref="subEditInput_{{ $child->id }}"
                                                            class="flex-1 rounded-md border border-tasklab-accent/50 bg-tasklab-bg px-2 py-0.5 text-xs text-tasklab-text focus:outline-none focus:border-tasklab-accent"
                                                        />
                                                        <button type="submit" class="text-[11px] font-medium text-tasklab-accent hover:text-tasklab-accent/80">Guardar</button>
                                                        <button type="button" @click="editing = false" class="text-[11px] text-tasklab-muted hover:text-tasklab-text">Cancelar</button>
                                                    </form>

                                                    <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover/child:opacity-100 transition-opacity">
                                                        <button
                                                            type="button"
                                                            @click="editing = true; $nextTick(() => $refs.subEditInput_{{ $child->id }}.focus())"
                                                            x-show="!editing"
                                                            class="flex h-5 w-5 items-center justify-center rounded text-tasklab-muted hover:text-tasklab-text transition-colors"
                                                        >
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                            </svg>
                                                        </button>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('settings.category-values.destroy', $child) }}"
                                                            onsubmit="return confirm('¿Eliminar «{{ addslashes($child->name) }}»?');"
                                                            class="inline"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="flex h-5 w-5 items-center justify-center rounded text-tasklab-muted hover:text-red-400 transition-colors"
                                                            >
                                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="px-3 text-[11px] text-tasklab-muted">Sin subcategorías.</p>
                                            @endforelse

                                            {{-- Add subcategory --}}
                                            <form
                                                method="POST"
                                                action="{{ route('settings.category-values.store', $activeTypeWithValues) }}"
                                                class="flex items-center gap-2 pt-1"
                                                x-data="{ subName: '' }"
                                            >
                                                @csrf
                                                <input type="hidden" name="parent_id" value="{{ $value->id }}" />
                                                <input
                                                    type="text"
                                                    name="name"
                                                    x-model="subName"
                                                    placeholder="Nueva subcategoría…"
                                                    required
                                                    class="flex-1 rounded-lg border border-slate-700 bg-tasklab-bg px-2.5 py-1.5 text-xs text-tasklab-text placeholder-tasklab-muted/40 focus:border-tasklab-accent/60 focus:outline-none"
                                                />
                                                <button
                                                    type="submit"
                                                    :disabled="!subName.trim()"
                                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-tasklab-bg text-tasklab-muted hover:border-tasklab-accent/40 hover:text-tasklab-accent hover:bg-tasklab-accent/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                                    title="Añadir subcategoría"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-700 px-5 py-8 text-center">
                                    <p class="text-sm text-tasklab-muted">Aún no hay categorías para este equipo.</p>
                                    <p class="text-xs text-tasklab-muted/60 mt-1">Añade la primera categoría abajo.</p>
                                </div>
                            @endforelse

                            {{-- Add top-level category --}}
                            <form
                                method="POST"
                                action="{{ route('settings.category-values.store', $activeTypeWithValues) }}"
                                class="flex items-center gap-2 pt-1"
                                x-data="{ catName: '' }"
                            >
                                @csrf
                                <input
                                    type="text"
                                    name="name"
                                    x-model="catName"
                                    placeholder="Nueva categoría…"
                                    required
                                    class="flex-1 rounded-xl border border-slate-700 bg-tasklab-bg px-3 py-2 text-sm text-tasklab-text placeholder-tasklab-muted/40 focus:border-tasklab-accent/60 focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    :disabled="!catName.trim()"
                                    class="flex h-9 items-center gap-1.5 shrink-0 rounded-xl border border-tasklab-accent/40 bg-tasklab-accent/20 px-3 text-xs font-medium text-tasklab-accent hover:bg-tasklab-accent/30 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Añadir categoría
                                </button>
                            </form>
                        </div>
                    </div>

                @else
                    {{-- Empty state --}}
                    <div class="rounded-2xl border border-dashed border-slate-700 bg-tasklab-bg-muted/50 flex flex-col items-center justify-center gap-3 px-8 py-20 text-center shadow-card">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-700 bg-tasklab-bg">
                            <svg class="h-6 w-6 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-tasklab-text">Ningún equipo seleccionado</p>
                            <p class="text-xs text-tasklab-muted mt-1">Crea un equipo en el panel izquierdo o selecciona uno existente para configurar sus categorías.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
