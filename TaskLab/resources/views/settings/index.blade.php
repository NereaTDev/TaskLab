<x-app-layout>
    <div class="max-w-[1200px] mx-auto px-4 py-6 space-y-6">

        {{-- ── Header ── --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-heading font-semibold text-tasklab-text">Configuración</h1>
                <p class="text-meta text-tasklab-muted mt-0.5">Gestiona los equipos y sus categorías. Solo Super Admin.</p>
            </div>

            @if(session('status'))
                <div class="inline-flex items-center gap-2 rounded-full border border-tasklab-success/30 bg-tasklab-success/10 px-3 py-1.5 text-xs font-medium text-tasklab-success">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ session('status') }}
                </div>
            @endif
        </div>

        {{-- ── Errors ── --}}
        @if($errors->any())
            <div class="rounded-xl border border-red-500/30 bg-red-500/5 px-4 py-3 text-sm text-red-400">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ── GitHub Connection ── --}}
        @php $githubOAuthConfigured = config('services.github.client_id') && config('services.github.client_secret'); @endphp

        @if(session('github_error'))
            <div class="rounded-xl border border-red-500/30 bg-red-500/5 px-4 py-3 text-sm text-red-400">
                {{ session('github_error') }}
            </div>
        @endif

        <div
            class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted shadow-card overflow-hidden"
            x-data="githubPicker()"
            x-init="init()"
        >
            {{-- Header --}}
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-800/60">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-800 border border-slate-700">
                    <svg class="h-4 w-4 text-tasklab-text" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-tasklab-text">Repositorio GitHub</p>
                    <p class="text-[11px] text-tasklab-muted">
                        Conecta el código fuente del proyecto para que la IA entienda la arquitectura y enriquezca las tareas con contexto real.
                    </p>
                </div>
                @if($githubConnection)
                    <span class="shrink-0 inline-flex items-center gap-1.5 rounded-full border border-tasklab-success/30 bg-tasklab-success/10 px-2.5 py-1 text-[11px] font-medium text-tasklab-success">
                        <span class="h-1.5 w-1.5 rounded-full bg-tasklab-success"></span>
                        Conectado
                    </span>
                @else
                    <span class="shrink-0 inline-flex items-center gap-1.5 rounded-full border border-slate-700 bg-tasklab-bg px-2.5 py-1 text-[11px] font-medium text-tasklab-muted">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-600"></span>
                        Sin conectar
                    </span>
                @endif
            </div>

            <div class="p-5">
                @if($githubConnection)
                    {{-- ── Connected state ── --}}
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-800 border border-slate-700">
                                <svg class="h-5 w-5 text-tasklab-text" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-tasklab-text">{{ $githubConnection->owner }}/{{ $githubConnection->repo }}</p>
                                <div class="flex items-center gap-2 mt-0.5 text-[11px] text-tasklab-muted">
                                    <span>rama: <span class="font-medium text-tasklab-text">{{ $githubConnection->branch }}</span></span>
                                    @if($githubConnection->file_tree)
                                        <span>·</span>
                                        <span>{{ count($githubConnection->file_tree) }} archivos</span>
                                    @endif
                                    @if($githubConnection->last_synced_at)
                                        <span>·</span>
                                        <span>{{ $githubConnection->last_synced_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('settings.github.sync') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-tasklab-bg px-3 py-1.5 text-xs font-medium text-tasklab-muted hover:text-tasklab-text hover:border-slate-600 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Sincronizar
                                </button>
                            </form>
                            <form method="POST" action="{{ route('settings.github.destroy') }}" onsubmit="return confirm('¿Desconectar el repositorio?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-1.5 text-xs font-medium text-red-400 hover:bg-red-500/20 transition-colors">
                                    Desconectar
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- ── Site URL ── --}}
                    <div class="mt-4 pt-4 border-t border-slate-800/60"
                         x-data="{ editing: false, url: '{{ $githubConnection->site_url ?? '' }}' }">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="h-3.5 w-3.5 shrink-0 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                                </svg>
                                <div class="min-w-0">
                                    <span class="text-[11px] font-medium text-tasklab-muted uppercase tracking-wider">URL de producción</span>
                                    <p class="text-xs text-tasklab-muted mt-0.5">Dominio base que la IA usará para construir las URLs de las tareas.</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="editing = !editing"
                                class="shrink-0 text-[11px] font-medium text-tasklab-accent hover:text-tasklab-accent/80 transition-colors"
                                x-text="editing ? 'Cancelar' : (url ? 'Editar' : 'Añadir')"
                            ></button>
                        </div>

                        {{-- Current value (non-editing) --}}
                        <div x-show="!editing && url" class="mt-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-tasklab-bg px-2.5 py-1 text-xs font-mono text-tasklab-text" x-text="url"></span>
                        </div>
                        <div x-show="!editing && !url" class="mt-2">
                            <span class="text-xs text-tasklab-muted/60 italic">Sin URL configurada</span>
                        </div>

                        {{-- Edit form --}}
                        <form
                            x-show="editing"
                            method="POST"
                            action="{{ route('settings.github.site-url') }}"
                            class="mt-3 flex items-center gap-2"
                        >
                            @csrf
                            @method('PATCH')
                            <input
                                type="url"
                                name="site_url"
                                x-model="url"
                                placeholder="https://tu-app.onrender.com"
                                class="flex-1 rounded-xl border border-slate-700 bg-tasklab-bg px-3 py-2 text-sm text-tasklab-text placeholder-tasklab-muted/40 focus:border-tasklab-accent/60 focus:outline-none"
                            />
                            <button
                                type="submit"
                                class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-tasklab-accent/40 bg-tasklab-accent/20 px-3 py-2 text-xs font-medium text-tasklab-accent hover:bg-tasklab-accent/30 transition-colors"
                            >
                                Guardar
                            </button>
                        </form>
                    </div>

                @elseif(request('github_pick') && session('github_pending_token'))
                    {{-- ── Repo picker (after OAuth callback) ── --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-tasklab-success shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <p class="text-sm font-medium text-tasklab-text">GitHub autorizado. Elige el repositorio a conectar:</p>
                        </div>

                        <div x-show="loading" class="flex items-center gap-2 text-xs text-tasklab-muted">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            Cargando repositorios…
                        </div>

                        <div x-show="!loading && repos.length" class="space-y-3">
                            {{-- Search --}}
                            <input
                                type="text"
                                x-model="search"
                                placeholder="Buscar repositorio…"
                                class="w-full rounded-xl border border-slate-700 bg-tasklab-bg px-3 py-2 text-sm text-tasklab-text placeholder-tasklab-muted/40 focus:border-tasklab-accent/60 focus:outline-none"
                            />

                            {{-- Repo list --}}
                            <div class="max-h-64 overflow-y-auto space-y-1 rounded-xl border border-slate-800 bg-tasklab-bg p-2">
                                <template x-for="repo in filteredRepos" :key="repo.full_name">
                                    <button
                                        type="button"
                                        @click="selectRepo(repo)"
                                        :class="selected?.full_name === repo.full_name ? 'border-tasklab-accent/50 bg-tasklab-accent/10 text-tasklab-text' : 'border-transparent text-tasklab-muted hover:text-tasklab-text hover:bg-slate-800'"
                                        class="flex w-full items-center gap-3 rounded-lg border px-3 py-2.5 text-left transition-colors"
                                    >
                                        <svg class="h-4 w-4 shrink-0 opacity-60" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium truncate" x-text="repo.full_name"></p>
                                            <p class="text-[10px] truncate opacity-60" x-text="repo.description || 'Sin descripción'"></p>
                                        </div>
                                        <span x-show="repo.private" class="shrink-0 rounded-full border border-slate-700 px-1.5 py-0.5 text-[10px] text-tasklab-muted">privado</span>
                                        <span x-show="selected?.full_name === repo.full_name" class="shrink-0 text-tasklab-accent">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                    </button>
                                </template>
                                <p x-show="filteredRepos.length === 0 && !loading" class="px-3 py-4 text-center text-xs text-tasklab-muted">Sin resultados.</p>
                            </div>

                            {{-- Confirm form --}}
                            <form x-show="selected" method="POST" action="{{ route('settings.github.store') }}" class="flex items-end gap-3">
                                @csrf
                                <input type="hidden" name="owner" :value="selected?.owner">
                                <input type="hidden" name="repo" :value="selected?.name">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-medium text-tasklab-muted mb-1.5 uppercase tracking-wider">Rama</label>
                                    <input
                                        type="text"
                                        name="branch"
                                        :value="selected?.default_branch || 'main'"
                                        class="w-full rounded-xl border border-slate-700 bg-tasklab-bg px-3 py-2 text-sm text-tasklab-text focus:border-tasklab-accent/60 focus:outline-none"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-tasklab-accent/40 bg-tasklab-accent/20 px-4 py-2 text-sm font-medium text-tasklab-accent hover:bg-tasklab-accent/30 transition-colors"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    Conectar <span x-text="selected?.full_name" class="opacity-70"></span>
                                </button>
                            </form>
                        </div>
                    </div>

                @elseif($githubOAuthConfigured)
                    {{-- ── OAuth connect button ── --}}
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <p class="text-xs text-tasklab-muted max-w-md">
                            Autoriza TaskLab en GitHub para elegir el repositorio de tu proyecto. El token se almacena cifrado y solo se usa para leer el código.
                        </p>
                        <a
                            href="{{ route('settings.github.auth') }}"
                            class="shrink-0 inline-flex items-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-sm font-medium text-tasklab-text hover:bg-slate-700 transition-colors"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                            </svg>
                            Conectar con GitHub
                        </a>
                    </div>

                @else
                    {{-- ── OAuth not configured: guide to create the GitHub App ── --}}
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-tasklab-text">Primero crea una OAuth App en GitHub</p>
                            <p class="text-xs text-tasklab-muted">
                                El botón te lleva a GitHub con la URL de callback ya rellena. Después añade las dos credenciales que GitHub te dé a tus variables de entorno.
                            </p>
                        </div>
                        @php
                            $ghCreateUrl = 'https://github.com/settings/applications/new?' . http_build_query([
                                'oauth_application[name]'         => config('app.name', 'TaskLab'),
                                'oauth_application[url]'          => config('app.url'),
                                'oauth_application[callback_url]' => config('services.github.redirect'),
                            ]);
                        @endphp
                        <a
                            href="{{ $ghCreateUrl }}"
                            target="_blank"
                            rel="noopener"
                            class="shrink-0 inline-flex items-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-sm font-medium text-tasklab-text hover:bg-slate-700 transition-colors"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                            </svg>
                            Crear OAuth App en GitHub
                            <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Main layout ── --}}
        <div class="grid grid-cols-1 md:grid-cols-[280px,minmax(0,1fr)] gap-5">

            {{-- ── Left sidebar: team list ── --}}
            <div class="space-y-3">
                <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted shadow-card overflow-hidden">

                    {{-- Sidebar header --}}
                    <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-800/60">
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-tasklab-primary/10 border border-tasklab-primary/20">
                            <svg class="h-3.5 w-3.5 text-tasklab-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-tasklab-text">Equipos</p>
                            <p class="text-[11px] text-tasklab-muted">{{ $categoryTypes->count() }} {{ $categoryTypes->count() === 1 ? 'equipo' : 'equipos' }}</p>
                        </div>
                    </div>

                    {{-- Team list --}}
                    <div class="p-3 space-y-1">
                        @forelse($categoryTypes as $type)
                            @php $isActive = $activeType && $activeType->id === $type->id; @endphp
                            <div class="flex items-center gap-1 group">
                                <a
                                    href="{{ route('settings.index', ['type' => $type->slug]) }}"
                                    class="flex-1 flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm transition-colors {{ $isActive ? 'bg-tasklab-bg border border-tasklab-accent/50 text-tasklab-text' : 'text-tasklab-muted hover:text-tasklab-text hover:bg-tasklab-bg/60 border border-transparent' }}"
                                >
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md {{ $isActive ? 'bg-tasklab-accent/20 text-tasklab-accent' : 'bg-slate-800 text-tasklab-muted' }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                        </svg>
                                    </span>
                                    <span class="truncate font-medium">{{ $type->name }}</span>
                                </a>
                                <form
                                    method="POST"
                                    action="{{ route('settings.category-types.destroy', $type) }}"
                                    onsubmit="return confirm('¿Eliminar el equipo «{{ addslashes($type->name) }}» y todas sus categorías?');"
                                    class="shrink-0"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-tasklab-muted opacity-0 group-hover:opacity-100 hover:bg-red-500/10 hover:text-red-400 transition-all"
                                        title="Eliminar equipo"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="px-3 py-4 text-center text-xs text-tasklab-muted">Aún no hay equipos.</p>
                        @endforelse
                    </div>

                    {{-- Add new team form --}}
                    <div class="border-t border-slate-800/60 p-3">
                        <form
                            method="POST"
                            action="{{ route('settings.category-types.store') }}"
                            class="flex items-center gap-2"
                            x-data="{ name: '' }"
                        >
                            @csrf
                            <input
                                type="text"
                                name="name"
                                x-model="name"
                                placeholder="Nuevo equipo…"
                                required
                                class="flex-1 rounded-xl border border-slate-700 bg-tasklab-bg px-3 py-2 text-xs text-tasklab-text placeholder-tasklab-muted/50 focus:border-tasklab-accent focus:outline-none"
                            />
                            <button
                                type="submit"
                                :disabled="!name.trim()"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-tasklab-accent/40 bg-tasklab-accent/20 text-tasklab-accent hover:bg-tasklab-accent/30 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                title="Crear equipo"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Hint --}}
                <p class="px-1 text-[11px] text-tasklab-muted leading-relaxed">
                    Cada equipo agrupa a los miembros según su rol y categoría. La IA usa estos equipos para asignar tareas automáticamente.
                </p>
            </div>

            {{-- ── Right panel: active team detail ── --}}
            <div>
                @if($activeType && $activeTypeWithValues)
                    <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted shadow-card overflow-hidden">

                        {{-- Panel header --}}
                        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-800/60">
                            <div>
                                <h2 class="text-sm font-semibold text-tasklab-text">{{ $activeTypeWithValues->name }}</h2>
                                <p class="text-[11px] text-tasklab-muted mt-0.5">
                                    Categorías y subcategorías que agrupan a los miembros de este equipo.
                                </p>
                            </div>
                            <span class="shrink-0 text-xs text-tasklab-muted rounded-full border border-slate-700 bg-tasklab-bg px-2.5 py-1">
                                {{ $activeTypeWithValues->values->count() }} {{ $activeTypeWithValues->values->count() === 1 ? 'categoría' : 'categorías' }}
                            </span>
                        </div>

                        <div class="p-5 space-y-3">

                            {{-- Category values --}}
                            @forelse($activeTypeWithValues->values as $value)
                                <div
                                    class="rounded-xl border border-slate-800 bg-tasklab-bg overflow-hidden"
                                    x-data="{ expanded: true, editing: false, editName: '{{ addslashes($value->name) }}' }"
                                >
                                    {{-- Value header row --}}
                                    <div class="flex items-center gap-2 px-4 py-3">
                                        {{-- Expand toggle --}}
                                        <button
                                            type="button"
                                            @click="expanded = !expanded"
                                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded text-tasklab-muted hover:text-tasklab-text transition-colors"
                                        >
                                            <svg class="h-3.5 w-3.5 transition-transform" :class="expanded ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>

                                        {{-- Inline edit form --}}
                                        <div class="flex-1 min-w-0" x-show="!editing">
                                            <span class="text-sm font-medium text-tasklab-text">{{ $value->name }}</span>
                                            @if($value->children->count())
                                                <span class="ml-2 text-[10px] text-tasklab-muted">{{ $value->children->count() }} sub</span>
                                            @endif
                                        </div>

                                        <form
                                            method="POST"
                                            action="{{ route('settings.category-values.update', $value) }}"
                                            class="flex-1 flex items-center gap-2"
                                            x-show="editing"
                                            x-cloak
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <input
                                                type="text"
                                                name="name"
                                                x-model="editName"
                                                @keydown.escape="editing = false"
                                                x-ref="editInput_{{ $value->id }}"
                                                class="flex-1 rounded-lg border border-tasklab-accent/50 bg-tasklab-bg-muted px-2.5 py-1 text-sm text-tasklab-text focus:outline-none focus:border-tasklab-accent"
                                            />
                                            <button type="submit" class="text-xs font-medium text-tasklab-accent hover:text-tasklab-accent/80 transition-colors">Guardar</button>
                                            <button type="button" @click="editing = false" class="text-xs text-tasklab-muted hover:text-tasklab-text transition-colors">Cancelar</button>
                                        </form>

                                        {{-- Action buttons --}}
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button
                                                type="button"
                                                @click="editing = true; $nextTick(() => $refs.editInput_{{ $value->id }}.focus())"
                                                x-show="!editing"
                                                class="flex h-6 w-6 items-center justify-center rounded-lg text-tasklab-muted hover:text-tasklab-text hover:bg-slate-800 transition-colors"
                                                title="Editar"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>
                                            <form
                                                method="POST"
                                                action="{{ route('settings.category-values.destroy', $value) }}"
                                                onsubmit="return confirm('¿Eliminar «{{ addslashes($value->name) }}» y sus subcategorías?');"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="flex h-6 w-6 items-center justify-center rounded-lg text-tasklab-muted hover:text-red-400 hover:bg-red-500/10 transition-colors"
                                                    title="Eliminar"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    {{-- Subcategories (expandable) --}}
                                    <div x-show="expanded" class="border-t border-slate-800/60">
                                        <div class="px-4 py-3 space-y-2 bg-tasklab-bg-muted/30">

                                            {{-- Subcategory list --}}
                                            @forelse($value->children as $child)
                                                <div
                                                    class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-tasklab-bg/60 transition-colors group/child"
                                                    x-data="{ editing: false, editName: '{{ addslashes($child->name) }}' }"
                                                >
                                                    <span class="h-1 w-1 shrink-0 rounded-full bg-slate-600"></span>

                                                    <div class="flex-1 min-w-0" x-show="!editing">
                                                        <span class="text-xs text-tasklab-text">{{ $child->name }}</span>
                                                    </div>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('settings.category-values.update', $child) }}"
                                                        class="flex-1 flex items-center gap-2"
                                                        x-show="editing"
                                                        x-cloak
                                                    >
                                                        @csrf
                                                        @method('PATCH')
                                                        <input
                                                            type="text"
                                                            name="name"
                                                            x-model="editName"
                                                            @keydown.escape="editing = false"
                                                            x-ref="subEditInput_{{ $child->id }}"
                                                            class="flex-1 rounded-md border border-tasklab-accent/50 bg-tasklab-bg px-2 py-0.5 text-xs text-tasklab-text focus:outline-none focus:border-tasklab-accent"
                                                        />
                                                        <button type="submit" class="text-[11px] font-medium text-tasklab-accent hover:text-tasklab-accent/80">Guardar</button>
                                                        <button type="button" @click="editing = false" class="text-[11px] text-tasklab-muted hover:text-tasklab-text">Cancelar</button>
                                                    </form>

                                                    <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover/child:opacity-100 transition-opacity">
                                                        <button
                                                            type="button"
                                                            @click="editing = true; $nextTick(() => $refs.subEditInput_{{ $child->id }}.focus())"
                                                            x-show="!editing"
                                                            class="flex h-5 w-5 items-center justify-center rounded text-tasklab-muted hover:text-tasklab-text transition-colors"
                                                        >
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                            </svg>
                                                        </button>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('settings.category-values.destroy', $child) }}"
                                                            onsubmit="return confirm('¿Eliminar «{{ addslashes($child->name) }}»?');"
                                                            class="inline"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="flex h-5 w-5 items-center justify-center rounded text-tasklab-muted hover:text-red-400 transition-colors"
                                                            >
                                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="px-3 text-[11px] text-tasklab-muted">Sin subcategorías.</p>
                                            @endforelse

                                            {{-- Add subcategory --}}
                                            <form
                                                method="POST"
                                                action="{{ route('settings.category-values.store', $activeTypeWithValues) }}"
                                                class="flex items-center gap-2 pt-1"
                                                x-data="{ subName: '' }"
                                            >
                                                @csrf
                                                <input type="hidden" name="parent_id" value="{{ $value->id }}" />
                                                <input
                                                    type="text"
                                                    name="name"
                                                    x-model="subName"
                                                    placeholder="Nueva subcategoría…"
                                                    required
                                                    class="flex-1 rounded-lg border border-slate-700 bg-tasklab-bg px-2.5 py-1.5 text-xs text-tasklab-text placeholder-tasklab-muted/40 focus:border-tasklab-accent/60 focus:outline-none"
                                                />
                                                <button
                                                    type="submit"
                                                    :disabled="!subName.trim()"
                                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-tasklab-bg text-tasklab-muted hover:border-tasklab-accent/40 hover:text-tasklab-accent hover:bg-tasklab-accent/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                                    title="Añadir subcategoría"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-700 px-5 py-8 text-center">
                                    <p class="text-sm text-tasklab-muted">Aún no hay categorías para este equipo.</p>
                                    <p class="text-xs text-tasklab-muted/60 mt-1">Añade la primera categoría abajo.</p>
                                </div>
                            @endforelse

                            {{-- Add top-level category --}}
                            <form
                                method="POST"
                                action="{{ route('settings.category-values.store', $activeTypeWithValues) }}"
                                class="flex items-center gap-2 pt-1"
                                x-data="{ catName: '' }"
                            >
                                @csrf
                                <input
                                    type="text"
                                    name="name"
                                    x-model="catName"
                                    placeholder="Nueva categoría…"
                                    required
                                    class="flex-1 rounded-xl border border-slate-700 bg-tasklab-bg px-3 py-2 text-sm text-tasklab-text placeholder-tasklab-muted/40 focus:border-tasklab-accent/60 focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    :disabled="!catName.trim()"
                                    class="flex h-9 items-center gap-1.5 shrink-0 rounded-xl border border-tasklab-accent/40 bg-tasklab-accent/20 px-3 text-xs font-medium text-tasklab-accent hover:bg-tasklab-accent/30 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Añadir categoría
                                </button>
                            </form>
                        </div>
                    </div>

                @else
                    {{-- Empty state --}}
                    <div class="rounded-2xl border border-dashed border-slate-700 bg-tasklab-bg-muted/50 flex flex-col items-center justify-center gap-3 px-8 py-20 text-center shadow-card">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-700 bg-tasklab-bg">
                            <svg class="h-6 w-6 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-tasklab-text">Ningún equipo seleccionado</p>
                            <p class="text-xs text-tasklab-muted mt-1">Crea un equipo en el panel izquierdo o selecciona uno existente para configurar sus categorías.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <script>
    function githubPicker() {
        return {
            repos:    [],
            search:   '',
            selected: null,
            loading:  false,

            get filteredRepos() {
                if (! this.search.trim()) return this.repos;
                const q = this.search.toLowerCase();
                return this.repos.filter(r =>
                    r.full_name.toLowerCase().includes(q) ||
                    (r.description || '').toLowerCase().includes(q)
                );
            },

            init() {
                @if(request('github_pick') && session('github_pending_token'))
                    this.loadRepos();
                @endif
            },

            async loadRepos() {
                this.loading = true;
                try {
                    const res = await fetch('{{ route('settings.github.repos') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                    });
                    this.repos = await res.json();
                } catch {
                    this.repos = [];
                } finally {
                    this.loading = false;
                }
            },

            selectRepo(repo) {
                this.selected = this.selected?.full_name === repo.full_name ? null : repo;
            },
        };
    }
    </script>
</x-app-layout>
