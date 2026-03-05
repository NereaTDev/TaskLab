@php
    $categoriesByType = $member->categoryValues->groupBy(function ($cv) {
        return optional($cv->type)->name ?? 'Sin tipo';
    });

    // Ordenar los tipos según el orden de los botones de vista (categoryTypes en la cabecera):
    // primero el tipo más a la izquierda (por ejemplo, Departamentos), luego el resto (Áreas, etc.).
    $typeOrder = collect($categoryTypes ?? [])
        ->values()
        ->mapWithKeys(function ($type, $index) {
            return [$type->name => $index];
        });

    $sortedTypeNames = $categoriesByType->keys()->sort(function ($a, $b) use ($typeOrder) {
        $aOrder = $typeOrder[$a] ?? 9999;
        $bOrder = $typeOrder[$b] ?? 9999;

        if ($aOrder === $bOrder) {
            return strcmp($a, $b);
        }

        return $aOrder <=> $bOrder;
    });
@endphp

<div>
    @if($categoriesByType->isEmpty())
        {{-- Sin categorías asignadas --}} 
        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Categorías</p>
        <p class="mt-0.5 text-body text-tasklab-text">—</p>
    @else
        @foreach($sortedTypeNames as $typeName)
            @php $values = $categoriesByType[$typeName]; @endphp
            {{-- Nombre del tipo (alineado y con el mismo formato que "Posición") --}} 
            <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mt-1">{{ $typeName }}</p>
            <div class="mt-0.5 flex flex-wrap gap-1.5">
                @foreach($values as $val)
                    @php
                        $label = $val->parent
                            ? $val->parent->name.' / '.$val->name
                            : $val->name;
                    @endphp
                    <span class="inline-flex items-center rounded-full border border-slate-700 bg-tasklab-bg px-2 py-0.5 text-[11px] text-tasklab-muted">
                        {{ $label }}
                    </span>
                @endforeach
            </div>
        @endforeach
    @endif
</div>
