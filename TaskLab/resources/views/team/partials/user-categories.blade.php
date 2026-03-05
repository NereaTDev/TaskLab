@php
    $categoriesByType = $member->categoryValues->groupBy(function ($cv) {
        return optional($cv->type)->name ?? 'Sin tipo';
    });
@endphp

<div>
    <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Categorías de equipo</p>
    @if($categoriesByType->isEmpty())
        <p class="mt-0.5 text-body text-tasklab-text">—</p>
    @else
        <div class="mt-1 flex flex-wrap gap-1">
            @foreach($categoriesByType as $typeName => $values)
                @foreach($values as $val)
                    @php
                        $label = $val->parent
                            ? $val->parent->name.' / '.$val->name
                            : $val->name;
                    @endphp
                    <span class="inline-flex items-center rounded-full border border-slate-700 bg-tasklab-bg px-2 py-0.5 text-[11px] text-tasklab-muted">
                        <span class="font-semibold text-tasklab-text mr-1">{{ $typeName }}:</span> {{ $label }}
                    </span>
                @endforeach
            @endforeach
        </div>
    @endif
</div>
