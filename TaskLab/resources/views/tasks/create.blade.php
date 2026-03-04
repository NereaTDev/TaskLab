<x-app-layout>
    <div class="max-w-3xl mx-auto py-8 px-4">
        <h1 class="text-2xl font-semibold text-slate-900 mb-1">New task</h1>
        <p class="text-xs text-slate-500 mb-4">Describe the issue or request so TaskLab can refine it for the dev team.</p>

        <form method="POST" action="{{ route('tasks.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Type</label>
                    <select name="type" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        <option value="bug" {{ old('type') === 'bug' ? 'selected' : '' }}>Bug</option>
                        <option value="feature" {{ old('type') === 'feature' ? 'selected' : '' }}>Feature</option>
                        <option value="improvement" {{ old('type') === 'improvement' ? 'selected' : '' }}>Improvement</option>
                        <option value="question" {{ old('type') === 'question' ? 'selected' : '' }}>Question</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Priority</label>
                    <select name="priority" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    @error('priority')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Page / URL (optional)</label>
                    <input type="text" name="url" value="{{ old('url') }}" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" placeholder="https://...">
                    @error('url')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Describe the bug / feature request</label>
                <textarea name="description" rows="8" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" placeholder="Explain what you need. Include context, steps to reproduce, expected vs. actual behavior, environment, and any extra details.">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('tasks.index') }}" class="text-xs text-slate-500 hover:text-slate-700">Cancel</a>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-sky-600 text-white text-sm font-medium hover:bg-sky-700">Create task</button>
            </div>
        </form>
    </div>
</x-app-layout>
