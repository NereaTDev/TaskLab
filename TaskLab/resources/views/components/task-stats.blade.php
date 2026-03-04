@props(['stats'])

<div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
  <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
    <p class="text-[11px] font-medium text-slate-500">Total tasks</p>
    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $stats['total'] ?? 0 }}</p>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
    <p class="text-[11px] font-medium text-amber-600">Pending</p>
    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $stats['pending'] ?? 0 }}</p>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
    <p class="text-[11px] font-medium text-sky-600">In progress</p>
    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $stats['in_progress'] ?? 0 }}</p>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
    <p class="text-[11px] font-medium text-emerald-600">Done</p>
    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $stats['done'] ?? 0 }}</p>
  </div>
</div>
