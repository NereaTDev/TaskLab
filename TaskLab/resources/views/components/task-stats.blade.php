@props(['stats'])

<div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
  <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
    <div class="flex items-center justify-between">
      <p class="text-[11px] font-medium text-slate-500">Total tasks</p>
      <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-xs">
        #
      </span>
    </div>
    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['total'] ?? 0 }}</p>
    <p class="mt-1 text-[11px] text-slate-400">Todas las tareas en el sistema</p>
  </div>

  <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4 shadow-sm">
    <div class="flex items-center justify-between">
      <p class="text-[11px] font-medium text-amber-800">Pendientes</p>
      <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/80 text-amber-500 text-xs">
        ⏱
      </span>
    </div>
    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['pending'] ?? 0 }}</p>
    <p class="mt-1 text-[11px] text-amber-700/80">Aún sin empezar</p>
  </div>

  <div class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-4 shadow-sm">
    <div class="flex items-center justify-between">
      <p class="text-[11px] font-medium text-sky-800">En progreso</p>
      <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/80 text-sky-500 text-xs">
        ●
      </span>
    </div>
    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['in_progress'] ?? 0 }}</p>
    <p class="mt-1 text-[11px] text-sky-700/80">En manos del equipo</p>
  </div>

  <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4 shadow-sm">
    <div class="flex items-center justify-between">
      <p class="text-[11px] font-medium text-emerald-800">Completadas</p>
      <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/80 text-emerald-500 text-xs">
        ✓
      </span>
    </div>
    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $stats['done'] ?? 0 }}</p>
    <p class="mt-1 text-[11px] text-emerald-700/80">Marcadas como done</p>
  </div>
</div>
