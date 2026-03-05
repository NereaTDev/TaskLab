@props(['message' => null, 'type' => 'success'])

@if ($message)
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 3500)"
        x-show="show"
        x-transition.opacity.duration.200ms
        class="fixed inset-x-0 top-3 flex justify-center z-50"
    >
        <div class="inline-flex items-start gap-2 rounded-full px-4 py-2 text-xs font-medium shadow-lg border
            {{ $type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700' }}
        ">
            @if ($type === 'error')
                <svg class="h-4 w-4 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 5a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"/></svg>
            @else
                <svg class="h-4 w-4 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @endif
            <span>{{ $message }}</span>
        </div>
    </div>
@endif
