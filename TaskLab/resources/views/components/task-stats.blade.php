@props(['stats'])

<div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
  <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted px-4 py-4 shadow-card">
    <div class="flex items-center justify-between">
      <p class="text-label font-medium text-tasklab-muted">Total tasks</p>
      <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-tasklab-bg text-tasklab-muted text-xs">
        #
      </span>
    </div>
    <p class="mt-2 text-3xl font-semibold text-tasklab-text">{{ $stats['total'] ?? 0 }}</p>
    <p class="mt-1 text-meta text-tasklab-muted">Todas las tareas en el sistema</p>
  </div>

  <div class="rounded-2xl border border-tasklab-warning/40 bg-tasklab-warning/10 px-4 py-4 shadow-card">
    <div class="flex items-center justify-between">
      <p class="text-label font-medium text-tasklab-text">Pendientes</p>
      <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-tasklab-bg text-tasklab-warning text-xs">
        ⏱
      </span>
    </div>
    <p class="mt-2 text-3xl font-semibold text-tasklab-text">{{ $stats['pending'] ?? 0 }}</p>
    <p class="mt-1 text-meta text-tasklab-warning/90">Aún sin empezar</p>
  </div>

  <div class="rounded-2xl border border-tasklab-primary/40 bg-tasklab-primary/10 px-4 py-4 shadow-card">
    <div class="flex items-center justify-between">
      <p class="text-label font-medium text-tasklab-text">En progreso</p>
      <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-tasklab-bg text-tasklab-primary text-xs">
        ●
      </span>
    </div>
    <p class="mt-2 text-3xl font-semibold text-tasklab-text">{{ $stats['in_progress'] ?? 0 }}</p>
    <p class="mt-1 text-meta text-tasklab-primary/90">En manos del equipo</p>
  </div>

  <div class="rounded-2xl border border-tasklab-success/40 bg-tasklab-success/10 px-4 py-4 shadow-card">
    <div class="flex items-center justify-between">
      <p class="text-label font-medium text-tasklab-text">Completadas</p>
      <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-tasklab-bg text-tasklab-success text-xs">
        ✓
      </span>
    </div>
    <p class="mt-2 text-3xl font-semibold text-tasklab-text">{{ $stats['done'] ?? 0 }}</p>
    <p class="mt-1 text-meta text-tasklab-success/90">Marcadas como done</p>
  </div>
</div>
