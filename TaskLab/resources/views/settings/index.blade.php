<x-app-layout>
    <div class="max-w-[1200px] mx-auto px-4 py-6 space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h1 class="text-heading font-semibold text-tasklab-text">Configuración</h1>
                <p class="text-label text-tasklab-muted">Ajustes globales de TaskLab. Solo Super Admin.</p>
            </div>
        </header>

        <section class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card">
            <div class="grid grid-cols-1 md:grid-cols-[260px,minmax(0,1fr)] gap-4">
                {{-- Columna izquierda: lista de tipos --}}
                <div class="border border-slate-800 rounded-xl bg-tasklab-bg p-3 flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-meta font-semibold text-tasklab-text">Tipos de categoría</p>
                    </div>

                    {{-- Crear tipo de categoría --}}
                    <form method="POST" action="{{ route('settings.category-types.store') }}" class="space-y-2">
                        @csrf
                        <div>
                            <x-input-label for="new_category_type" value="Nuevo tipo" />
                            <x-text-input id="new_category_type" name="name" type="text" class="mt-1 block w-full" placeholder="Ej. Departamentos, Áreas, Seniority" required />
                        </div>
                        <x-primary-button class="text-body w-full justify-center">Añadir tipo</x-primary-button>
                    </form>

                    <div class="border-t border-slate-800 pt-3 mt-2 space-y-1 max-h-[320px] overflow-y-auto">
                        @forelse($categoryTypes as $type)
                            @php $isActive = $activeType && $activeType->id === $type->id; @endphp
                            <div class="flex items-center gap-2">
                                <a
                                    href="{{ route('settings.index', ['type' => $type->slug]) }}"
                                    class="flex-1 flex items-center justify-between rounded-lg px-3 py-1.5 text-meta {{ $isActive ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text border border-transparent' }}"
                                >
                                    <span class="truncate">{{ $type->name }}</span>
                                    @if($isActive)
                                        <span class="text-[10px] text-tasklab-accent">activo</span>
                                    @endif
                                </a>
                                <form method="POST" action="{{ route('settings.category-types.destroy', $type) }}" onsubmit="return confirm('¿Eliminar tipo y todas sus categorías?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] text-tasklab-danger hover:text-red-400 px-2 py-1 rounded-md hover:bg-slate-900">✕</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-meta text-tasklab-muted">Aún no hay tipos de categoría configurados.</p>
                        @endforelse
                    </div>

                    @if ($errors->any())
                        <div class="mt-2 text-meta text-tasklab-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif
                </div>

                {{-- Columna derecha: detalle del tipo activo --}}
                <div class="border border-slate-800 rounded-xl bg-tasklab-bg p-4">
                    @if($activeType && $activeTypeWithValues)
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h2 class="text-title font-semibold text-tasklab-text">{{ $activeTypeWithValues->name }}</h2>
                                <p class="text-label text-tasklab-muted">Configura los valores y subcategorías que verás como columnas y secciones en la vista de Equipo.</p>
                            </div>
                        </div>

                        {{-- Valores de primer nivel --}}
                        <div class="space-y-4">
                            <p class="text-meta text-tasklab-muted mb-1">Valores principales</p>

                            @if($activeTypeWithValues->values->isEmpty())
                                <p class="text-meta text-tasklab-muted">Aún no hay valores para este tipo.</p>
                            @else
                                <div class="space-y-3">
                                    @foreach($activeTypeWithValues->values as $value)
                                        <div class="border border-slate-800 rounded-lg p-3 bg-tasklab-bg-muted">
                                            <div class="flex items-center justify-between gap-2">
                                                <form method="POST" action="{{ route('settings.category-values.update', $value) }}" class="flex-1 flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input
                                                        type="text"
                                                        name="name"
                                                        value="{{ $value->name }}"
                                                        class="w-full rounded-md border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1 focus:border-tasklab-accent focus:ring-tasklab-accent"
                                                    />
                                                    <button type="submit" class="text-meta text-tasklab-muted hover:text-tasklab-accent">
                                                        Guardar
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('settings.category-values.destroy', $value) }}" onsubmit="return confirm('¿Eliminar valor?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-meta text-tasklab-danger hover:text-red-400">Eliminar</button>
                                                </form>
                                            </div>

                                            {{-- Subcategorías --}}
                                            <div class="mt-3 pl-3 border-l border-slate-800 space-y-2">
                                                <p class="text-meta text-tasklab-muted">Subcategorías</p>

                                                @forelse($value->children as $child)
                                                    <div class="flex items-center justify-between gap-2">
                                                        <form method="POST" action="{{ route('settings.category-values.update', $child) }}" class="flex-1 flex items-center gap-2">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input
                                                                type="text"
                                                                name="name"
                                                                value="{{ $child->name }}"
                                                                class="w-full rounded-md border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1 focus:border-tasklab-accent focus:ring-tasklab-accent"
                                                            />
                                                            <button type="submit" class="text-meta text-tasklab-muted hover:text-tasklab-accent">
                                                                Guardar
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('settings.category-values.destroy', $child) }}" onsubmit="return confirm('¿Eliminar subcategoría?');" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-meta text-tasklab-danger hover:text-red-400">Eliminar</button>
                                                        </form>
                                                    </div>
                                                @empty
                                                    <p class="text-meta text-tasklab-muted">Sin subcategorías.</p>
                                                @endforelse

                                                {{-- Añadir subcategoría --}}
                                                <form method="POST" action="{{ route('settings.category-values.store', $activeTypeWithValues) }}" class="flex flex-col sm:flex-row gap-2 items-start sm:items-center mt-2">
                                                    @csrf
                                                    <input type="hidden" name="parent_id" value="{{ $value->id }}" />
                                                    <div class="flex-1 w-full">
                                                        <x-input-label :for="'new_sub_'.$value->id" value="Nueva subcategoría" />
                                                        <x-text-input :id="'new_sub_'.$value->id" name="name" type="text" class="mt-1 block w-full" placeholder="Ej. Ventas online" required />
                                                    </div>
                                                    <div class="pt-6">
                                                        <x-primary-button class="text-body">Añadir</x-primary-button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Añadir valor de primer nivel --}}
                            <form method="POST" action="{{ route('settings.category-values.store', $activeTypeWithValues) }}" class="flex flex-col sm:flex-row gap-2 items-start sm:items-center mt-2">
                                @csrf
                                <div class="flex-1 w-full">
                                    <x-input-label :for="'new_value_'.$activeTypeWithValues->id" value="Nuevo valor" />
                                    <x-text-input :id="'new_value_'.$activeTypeWithValues->id" name="name" type="text" class="mt-1 block w-full" placeholder="Ej. Ventas, Producto" required />
                                </div>
                                <div class="pt-6">
                                    <x-primary-button class="text-body">Añadir valor</x-primary-button>
                                </div>
                            </form>
                        </div>
                    @else
                        <p class="text-body text-tasklab-muted">Empieza creando un tipo de categoría a la izquierda para configurarlo.</p>
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
