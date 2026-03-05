<x-app-layout>
    <div class="max-w-[1600px] mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Team</h1>
                <p class="text-xs text-slate-500">Manage TaskLab users, their roles and developer profiles.</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-800 bg-tasklab-bg-muted shadow-card">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-900/80">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-tasklab-muted">User</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-tasklab-muted">Department</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-tasklab-muted">Position</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-tasklab-muted">Admin</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-tasklab-muted">Dev type</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-tasklab-muted">Areas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($teamMembers as $member)
                        <tr class="hover:bg-slate-900/60">
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-[11px] font-semibold text-white">
                                        {{ strtoupper(substr($member->name ?? 'A', 0, 2)) }}
                                    </span>
                                    <div>
                                        <p class="text-xs font-medium text-tasklab-text">{{ $member->name }}</p>
                                        <p class="text-[11px] text-tasklab-muted">{{ $member->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-xs text-tasklab-muted">{{ $member->department ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-tasklab-muted">{{ $member->position ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs">
                                @if ($member->is_admin)
                                    <span class="inline-flex items-center rounded-full bg-emerald-900/60 px-2 py-0.5 text-[11px] font-medium text-emerald-200">Admin</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-400">User</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-tasklab-muted">
                                {{ optional($member->developerProfile)->type ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-xs text-tasklab-muted">
                                @php
                                    $areas = optional($member->developerProfile)->areas ?? [];
                                @endphp
                                @if ($areas)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($areas as $area)
                                            <span class="inline-flex items-center rounded-full bg-slate-900 px-2 py-0.5 text-[11px] text-tasklab-muted border border-slate-700">{{ ucfirst(str_replace('_', ' ', $area)) }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-xs text-tasklab-muted">No team members yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
