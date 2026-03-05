<x-app-layout>
    <div class="max-w-[1000px] mx-auto px-4 py-6 space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h1 class="text-heading font-semibold text-tasklab-text">Configuración</h1>
                <p class="text-label text-tasklab-muted">Ajustes globales de TaskLab. Solo Super Admin.</p>
            </div>
        </header>

        {{-- Sección: Departamentos --}}
        <section class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-4 shadow-card space-y-4">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-title font-semibold text-tasklab-text">Departamentos</h2>
                    <p class="text-label text-tasklab-muted">Define los departamentos que se usan en el tablero de Equipo.</p>
                </div>
            </div>

            {{-- Crear departamento --}}
            <form method="POST" action="{{ route('settings.departments.store') }}" class="flex flex-col sm:flex-row gap-2 items-start sm:items-center">
                @csrf
                <div class="flex-1 w-full">
                    <x-input-label for="new_department" value="Nuevo departamento" />
                    <x-text-input id="new_department" name="name" type="text" class="mt-1 block w-full" placeholder="Ej. Tech, Learning, Ventas" required />
                </div>
                <div class="pt-6">
                    <x-primary-button class="text-body">
                        Añadir
                    </x-primary-button>
                </div>
            </form>

            @if ($errors->any())
                <div class="mt-2 text-meta text-tasklab-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Lista de departamentos --}}
            <div class="mt-2">
                @if($departments->isEmpty())
                    <p class="text-body text-tasklab-muted">Aún no hay departamentos configurados.</p>
                @else
                    <table class="min-w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-slate-800 text-meta text-tasklab-muted">
                                <th class="py-2 pr-4">Nombre</th>
                                <th class="py-2 pr-4">Slug</th>
                                <th class="py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach($departments as $department)
                                <tr>
                                    <td class="py-2 pr-4 align-middle">
                                        <form method="POST" action="{{ route('settings.departments.update', $department) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input
                                                type="text"
                                                name="name"
                                                value="{{ $department->name }}"
                                                class="w-full rounded-md border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1 focus:border-tasklab-accent focus:ring-tasklab-accent"
                                            />
                                            <button type="submit" class="text-meta text-tasklab-muted hover:text-tasklab-accent">
                                                Guardar
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-2 pr-4 align-middle text-meta text-tasklab-muted">
                                        {{ $department->slug }}
                                    </td>
                                    <td class="py-2 align-middle text-right">
                                        <form method="POST" action="{{ route('settings.departments.destroy', $department) }}" onsubmit="return confirm('¿Eliminar departamento?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-meta text-tasklab-danger hover:text-red-400">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
