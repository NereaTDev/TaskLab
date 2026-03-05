@php
    $dev = $developerProfile;
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Developer Profile') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Configure how TaskLab assigns tasks to you based on your role and areas.') }}
        </p>
    </header>

    <div class="mt-6 space-y-6">
        <div>
            <x-input-label for="developer_type" :value="__('Developer type')" />

            <select id="developer_type" name="developer[type]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">{{ __('Select type') }}</option>
                <option value="frontend" @selected(old('developer.type', $dev->type) === 'frontend')>Frontend</option>
                <option value="backend" @selected(old('developer.type', $dev->type) === 'backend')>Backend</option>
                <option value="fullstack" @selected(old('developer.type', $dev->type) === 'fullstack')>Fullstack</option>
            </select>
        </div>

        <div>
            <x-input-label :value="__('Areas where you can work')" />

            @php
                $areas = old('developer.areas', $dev->areas ?? []);
            @endphp

            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="developer[areas][]" value="web" @checked(in_array('web', $areas ?? [])) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span>Web</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="developer[areas][]" value="plataforma" @checked(in_array('plataforma', $areas ?? [])) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span>Plataforma</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="developer[areas][]" value="frontierz" @checked(in_array('frontierz', $areas ?? [])) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span>Frontierz</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="developer[areas][]" value="dashboard_empresas" @checked(in_array('dashboard_empresas', $areas ?? [])) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span>Dashboard empresas</span>
                </label>
            </div>
        </div>

        <div class="flex gap-4 items-center">
            <div class="flex-1">
                <x-input-label for="developer_max_parallel_tasks" :value="__('Max parallel tasks')" />
                <x-text-input id="developer_max_parallel_tasks" name="developer[max_parallel_tasks]" type="number" min="1" class="mt-1 block w-full" :value="old('developer.max_parallel_tasks', $dev->max_parallel_tasks)" />
                <p class="mt-1 text-xs text-gray-500">{{ __('Leave empty for no hard limit.') }}</p>
            </div>

            <div class="pt-6">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="hidden" name="developer[active]" value="0" />
                    <input type="checkbox" name="developer[active]" value="1" @checked(old('developer.active', $dev->active)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span>{{ __('Available for auto-assignment') }}</span>
                </label>
            </div>
        </div>
    </div>
</section>
