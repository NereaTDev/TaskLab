<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bienvenido a TaskLab</title>
    <link rel="preconnect" href="https://rsms.me">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-tasklab-bg text-tasklab-text antialiased">

<div
    x-data="onboarding()"
    x-init="init()"
    class="min-h-screen flex flex-col"
>
    {{-- ── Progress bar ── --}}
    <div class="fixed top-0 inset-x-0 z-50 h-1 bg-slate-800">
        <div
            class="h-full bg-tasklab-accent transition-all duration-500 ease-out"
            :style="`width: ${(currentStep / totalSteps) * 100}%`"
        ></div>
    </div>

    {{-- ── Header ── --}}
    <header class="sticky top-1 z-40 border-b border-slate-800 bg-tasklab-bg/95 backdrop-blur">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-bold text-tasklab-text text-base tracking-tight">TaskLab</span>
                <span class="text-tasklab-muted text-sm">/ Configuración inicial</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-tasklab-muted">Paso <span x-text="currentStep"></span> de <span x-text="totalSteps"></span></span>
                <form method="POST" action="{{ route('onboarding.skip') }}">
                    @csrf
                    <button type="submit" class="text-xs text-tasklab-muted hover:text-tasklab-text transition-colors underline underline-offset-2">
                        Saltar todo
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- ── Main content ── --}}
    <main class="flex-1 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-2xl">

            {{-- ══════════════════════════════════════════════════════════════
                 STEP 1 — Perfil
            ══════════════════════════════════════════════════════════════ --}}
            <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-wider text-tasklab-accent mb-2">Paso 1 de 4</p>
                    <h1 class="text-2xl font-bold text-tasklab-text mb-1">Cuéntanos sobre ti</h1>
                    <p class="text-tasklab-muted text-sm">Estos datos ayudarán a tu equipo a identificarte y a la IA a personalizar las notificaciones.</p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-6 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-tasklab-muted mb-1.5">Nombre completo <span class="text-red-400">*</span></label>
                            <input
                                type="text"
                                x-model="profile.name"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm text-tasklab-text placeholder-slate-600 focus:border-tasklab-accent focus:outline-none focus:ring-1 focus:ring-tasklab-accent/30"
                                placeholder="Tu nombre completo"
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-tasklab-muted mb-1.5">Cargo / Rol <span class="text-red-400">*</span></label>
                            <input
                                type="text"
                                x-model="profile.position"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm text-tasklab-text placeholder-slate-600 focus:border-tasklab-accent focus:outline-none focus:ring-1 focus:ring-tasklab-accent/30"
                                placeholder="ej: CTO, Product Manager..."
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-tasklab-muted mb-1.5">Departamento</label>
                            <input
                                type="text"
                                x-model="profile.department"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm text-tasklab-text placeholder-slate-600 focus:border-tasklab-accent focus:outline-none focus:ring-1 focus:ring-tasklab-accent/30"
                                placeholder="ej: Tecnología, Producto..."
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-tasklab-muted mb-1.5">Teléfono <span class="text-tasklab-muted font-normal">(opcional)</span></label>
                            <input
                                type="tel"
                                x-model="profile.phone"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-sm text-tasklab-text placeholder-slate-600 focus:border-tasklab-accent focus:outline-none focus:ring-1 focus:ring-tasklab-accent/30"
                                placeholder="+34 600 000 000"
                            >
                        </div>
                    </div>

                    <div x-show="profileError" class="rounded-xl border border-red-500/30 bg-red-500/5 px-4 py-3 text-sm text-red-400" x-text="profileError"></div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="saveProfile()"
                        :disabled="profileSaving || !profile.name"
                        class="inline-flex items-center gap-2 rounded-xl bg-tasklab-accent px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-tasklab-accent/90 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        <span x-show="profileSaving">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </span>
                        <span x-show="!profileSaving">Continuar</span>
                        <span x-show="!profileSaving">→</span>
                    </button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════
                 STEP 2 — Importar herramientas
            ══════════════════════════════════════════════════════════════ --}}
            <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-wider text-tasklab-accent mb-2">Paso 2 de 4</p>
                    <h1 class="text-2xl font-bold text-tasklab-text mb-1">¿Tienes tareas en otra herramienta?</h1>
                    <p class="text-tasklab-muted text-sm">Importa tus proyectos existentes para no partir de cero. Puedes saltarte este paso si empiezas desde cero.</p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-6">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @php
                        $tools = [
                            ['name' => 'Jira',     'icon' => 'J',  'color' => 'bg-blue-600',    'soon' => true],
                            ['name' => 'Trello',   'icon' => 'T',  'color' => 'bg-sky-500',     'soon' => true],
                            ['name' => 'Linear',   'icon' => 'L',  'color' => 'bg-violet-600',  'soon' => true],
                            ['name' => 'Shortcut', 'icon' => 'S',  'color' => 'bg-green-600',   'soon' => true],
                            ['name' => 'Asana',    'icon' => 'A',  'color' => 'bg-rose-500',    'soon' => true],
                            ['name' => 'Notion',   'icon' => 'N',  'color' => 'bg-slate-600',   'soon' => true],
                        ];
                        @endphp

                        @foreach($tools as $tool)
                        <div class="relative rounded-xl border border-slate-700/60 bg-slate-900/40 p-4 opacity-60 cursor-not-allowed select-none">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $tool['color'] }} text-white text-xs font-bold">
                                    {{ $tool['icon'] }}
                                </div>
                                <span class="text-sm font-medium text-tasklab-text">{{ $tool['name'] }}</span>
                            </div>
                            <span class="absolute top-2 right-2 inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-amber-400">
                                Próximo
                            </span>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-5 rounded-xl border border-dashed border-slate-700 bg-slate-900/20 px-4 py-3 text-sm text-tasklab-muted">
                        Las importaciones estarán disponibles próximamente. Podrás traer todas tus tareas, sprints y proyectos con un clic.
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <button @click="currentStep = 1" class="text-sm text-tasklab-muted hover:text-tasklab-text transition-colors">← Atrás</button>
                    <button
                        @click="currentStep = 3"
                        class="inline-flex items-center gap-2 rounded-xl bg-tasklab-accent px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-tasklab-accent/90 transition-all"
                    >
                        Continuar sin importar →
                    </button>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════
                 STEP 3 — Conectar canal de chat (OBLIGATORIO)
            ══════════════════════════════════════════════════════════════ --}}
            <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-wider text-tasklab-accent mb-2">Paso 3 de 4 — Requerido</p>
                    <h1 class="text-2xl font-bold text-tasklab-text mb-1">Conecta tu canal de comunicación</h1>
                    <p class="text-tasklab-muted text-sm">Este es el núcleo de TaskLab: tu equipo escribe un mensaje y la IA crea y asigna la tarea automáticamente. Elige cómo quieres recibirlos.</p>
                </div>

                {{-- Selector de herramienta --}}
                <div x-show="!chatTool" class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-6">
                    <p class="text-xs font-semibold text-tasklab-muted uppercase tracking-wider mb-4">Selecciona tu herramienta</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                        {{-- Discord --}}
                        <button
                            @click="chatTool = 'discord'"
                            class="group relative flex items-start gap-4 rounded-xl border border-slate-700 bg-slate-900/60 p-4 text-left hover:border-indigo-500/50 hover:bg-indigo-500/5 transition-all"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600">
                                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.002.022.015.043.033.055a19.909 19.909 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-tasklab-text group-hover:text-white">Discord</p>
                                <p class="text-xs text-tasklab-muted mt-0.5">Bot en tu servidor de Discord. Activo y en producción.</p>
                                <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-green-500/30 bg-green-500/10 px-2 py-0.5 text-[10px] font-medium text-green-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>
                                    Disponible
                                </span>
                            </div>
                        </button>

                        {{-- Slack --}}
                        <button
                            @click="chatTool = 'slack'"
                            class="group relative flex items-start gap-4 rounded-xl border border-slate-700 bg-slate-900/60 p-4 text-left hover:border-green-500/50 hover:bg-green-500/5 transition-all"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#4A154B]">
                                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-tasklab-text group-hover:text-white">Slack</p>
                                <p class="text-xs text-tasklab-muted mt-0.5">Bot en tu workspace de Slack. Conecta con bot token.</p>
                                <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-green-500/30 bg-green-500/10 px-2 py-0.5 text-[10px] font-medium text-green-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>
                                    Disponible
                                </span>
                            </div>
                        </button>

                        {{-- Teams --}}
                        <button
                            class="group relative flex items-start gap-4 rounded-xl border border-slate-700/40 bg-slate-900/30 p-4 text-left opacity-50 cursor-not-allowed"
                            disabled
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#6264A7]">
                                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.625 5.25H15v-.75A2.25 2.25 0 0 0 12.75 2.25h-1.5A2.25 2.25 0 0 0 9 4.5v.75H3.375C2.339 5.25 1.5 6.089 1.5 7.125v11.25c0 1.036.839 1.875 1.875 1.875h17.25c1.036 0 1.875-.839 1.875-1.875V7.125c0-1.036-.839-1.875-1.875-1.875z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-tasklab-text">Microsoft Teams</p>
                                <p class="text-xs text-tasklab-muted mt-0.5">Integración vía webhook de Teams.</p>
                                <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-400">
                                    Próximamente
                                </span>
                            </div>
                        </button>

                        {{-- Google Chat --}}
                        <button
                            class="group relative flex items-start gap-4 rounded-xl border border-slate-700/40 bg-slate-900/30 p-4 text-left opacity-50 cursor-not-allowed"
                            disabled
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                    <path d="M20.283 10.356h-8.327v3.451h4.792c-.446 2.193-2.313 3.453-4.792 3.453a5.27 5.27 0 0 1-5.279-5.28 5.27 5.27 0 0 1 5.279-5.279c1.259 0 2.397.447 3.29 1.178l2.6-2.599c-1.584-1.381-3.615-2.233-5.89-2.233a8.908 8.908 0 0 0-8.934 8.934 8.908 8.908 0 0 0 8.934 8.934c4.467 0 8.529-3.249 8.529-8.934 0-.528-.081-1.097-.202-1.625z" fill="#4285F4"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-tasklab-text">Google Chat</p>
                                <p class="text-xs text-tasklab-muted mt-0.5">Bot para espacios de Google Workspace.</p>
                                <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-400">
                                    Próximamente
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- ─── Panel Discord ─── --}}
                <div x-show="chatTool === 'discord'" x-transition class="rounded-2xl border border-indigo-500/30 bg-indigo-500/5 p-6 space-y-5">
                    <div class="flex items-center gap-3">
                        <button @click="chatTool = null" class="text-tasklab-muted hover:text-tasklab-text transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600">
                            <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.002.022.015.043.033.055a19.909 19.909 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                        </div>
                        <span class="text-sm font-semibold text-tasklab-text">Conectar con Discord</span>
                    </div>

                    {{-- Descripción simple --}}
                    <p class="text-sm text-tasklab-muted">TaskLab escuchará un canal de Discord que tú elijas. Cuando alguien escriba un mensaje, la IA lo convierte automáticamente en una tarea asignada.</p>

                    {{-- Pasos visuales --}}
                    <div class="space-y-2">
                        <div class="flex items-start gap-3 rounded-xl bg-slate-900/60 border border-slate-800 px-4 py-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white text-xs font-bold mt-0.5">1</span>
                            <div>
                                <p class="text-sm font-medium text-tasklab-text">Añade el bot a tu servidor</p>
                                <p class="text-xs text-tasklab-muted mt-0.5">Haz clic en el botón de abajo. Discord te pedirá que elijas un servidor y autorices los permisos necesarios.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl bg-slate-900/60 border border-slate-800 px-4 py-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white text-xs font-bold mt-0.5">2</span>
                            <div>
                                <p class="text-sm font-medium text-tasklab-text">Añade el bot al canal</p>
                                <p class="text-xs text-tasklab-muted mt-0.5">En Discord, ve al canal que quieras monitorizar → clic derecho → "Editar canal" → "Permisos" → añade el bot.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl bg-slate-900/60 border border-slate-800 px-4 py-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white text-xs font-bold mt-0.5">3</span>
                            <div>
                                <p class="text-sm font-medium text-tasklab-text">¡Listo! Escribe un mensaje de prueba</p>
                                <p class="text-xs text-tasklab-muted mt-0.5">El bot empezará a capturar mensajes automáticamente. Escribe algo como "el botón de pago falla" y verás la tarea creada en segundos.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-2 border-t border-slate-800">
                        <a
                            href="https://discord.com/api/oauth2/authorize?client_id={{ env('DISCORD_CLIENT_ID') }}&permissions=68608&scope=bot"
                            target="_blank"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition-all"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.002.022.015.043.033.055a19.909 19.909 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.030z"/></svg>
                            Añadir bot a Discord
                        </a>
                        <button
                            @click="markChatConnected('discord')"
                            class="text-sm text-tasklab-muted hover:text-tasklab-text transition-colors underline underline-offset-2"
                        >
                            Ya lo tenía configurado →
                        </button>
                    </div>
                </div>

                {{-- ─── Panel Slack ─── --}}
                <div x-show="chatTool === 'slack'" x-transition class="rounded-2xl border border-green-500/30 bg-green-500/5 p-6 space-y-5">
                    <div class="flex items-center gap-3">
                        <button @click="chatTool = null" class="text-tasklab-muted hover:text-tasklab-text transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#4A154B]">
                            <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/></svg>
                        </div>
                        <span class="text-sm font-semibold text-tasklab-text">Conectar con Slack</span>
                    </div>

                    {{-- Ya conectado (viene del callback OAuth) --}}
                    @if($slackConnection)
                    <div class="flex items-center gap-3 rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3">
                        <svg class="h-5 w-5 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="text-sm font-medium text-green-300">Slack conectado</p>
                            <p class="text-xs text-tasklab-muted">Workspace: {{ $slackConnection->workspace_name }}</p>
                        </div>
                        <button @click="markChatConnected('slack')" class="ml-auto inline-flex items-center gap-1 text-sm font-medium text-green-400 hover:text-green-300">
                            Continuar →
                        </button>
                    </div>

                    @else

                    {{-- Error de OAuth (viene del callback con error) --}}
                    @if(session('slack_error'))
                    <div class="rounded-xl border border-red-500/30 bg-red-500/5 px-4 py-3 text-sm text-red-400">
                        {{ session('slack_error') }}
                    </div>
                    @endif

                    <p class="text-sm text-tasklab-muted">Conecta tu workspace de Slack con un solo clic. TaskLab pedirá los permisos necesarios y empezará a escuchar los mensajes de los canales que configures.</p>

                    <div class="flex flex-col items-center gap-4 py-4">
                        <a
                            href="{{ route('settings.slack.auth', ['from' => 'onboarding']) }}"
                            class="inline-flex items-center gap-3 rounded-xl bg-[#4A154B] px-6 py-3 text-sm font-semibold text-white hover:bg-[#611f69] transition-all shadow-lg"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/></svg>
                            Conectar con Slack
                        </a>
                        <p class="text-xs text-tasklab-muted text-center">Se abrirá Slack para que autorices la conexión.<br>No necesitas tocar ningún token ni código.</p>
                    </div>
                    @endif
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <button @click="currentStep = 2" class="text-sm text-tasklab-muted hover:text-tasklab-text transition-colors">← Atrás</button>
                    <div class="text-xs text-tasklab-muted" x-show="!chatConnected && !chatTool">
                        Debes conectar al menos una herramienta para continuar.
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════
                 STEP 4 — GitHub (opcional)
            ══════════════════════════════════════════════════════════════ --}}
            <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-wider text-tasklab-accent mb-2">Paso 4 de 4 — Opcional</p>
                    <h1 class="text-2xl font-bold text-tasklab-text mb-1">Conecta tu repositorio</h1>
                    <p class="text-tasklab-muted text-sm">Con el repo conectado, la IA puede encontrar el componente exacto, la ruta del archivo y el contexto de código al crear cada tarea. Mucho más precisión.</p>
                </div>

                @php $githubOAuthConfigured = config('services.github.client_id') && config('services.github.client_secret'); @endphp

                <div class="rounded-2xl border border-slate-800 bg-tasklab-bg-muted p-6 space-y-5">

                    @if($githubConnection)
                    <div class="flex items-center gap-3 rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3">
                        <svg class="h-5 w-5 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="text-sm font-medium text-green-300">GitHub conectado</p>
                            <p class="text-xs text-tasklab-muted">{{ $githubConnection->full_name }}</p>
                        </div>
                    </div>
                    @elseif($githubOAuthConfigured)
                    <div class="text-center py-4">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-800 border border-slate-700">
                            <svg class="h-7 w-7 text-tasklab-text" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
                        </div>
                        <p class="text-sm text-tasklab-muted mb-4">Conecta vía OAuth para que la IA pueda leer tu repositorio de forma segura.</p>
                        <a
                            href="{{ route('settings.github.auth') }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-800 px-5 py-2.5 text-sm font-semibold text-tasklab-text hover:bg-slate-700 transition-all"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
                            Conectar con GitHub
                        </a>
                    </div>
                    @else
                    <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 px-4 py-3 text-sm text-amber-400">
                        La integración con GitHub requiere configurar <code class="text-amber-300">GITHUB_CLIENT_ID</code> y <code class="text-amber-300">GITHUB_CLIENT_SECRET</code> en las variables de entorno.
                    </div>
                    @endif

                    <div class="text-xs text-tasklab-muted">
                        Puedes configurar esto más tarde desde <strong class="text-tasklab-text">Configuración → Repositorio GitHub</strong>.
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <button @click="currentStep = 3" class="text-sm text-tasklab-muted hover:text-tasklab-text transition-colors">← Atrás</button>
                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('onboarding.complete') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-tasklab-accent px-6 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-tasklab-accent/90 transition-all">
                                Entrar a TaskLab
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </main>

    {{-- ── Step dots ── --}}
    <footer class="border-t border-slate-800 py-4">
        <div class="flex items-center justify-center gap-2">
            <template x-for="step in totalSteps" :key="step">
                <button
                    @click="step < currentStep && (currentStep = step)"
                    :class="step === currentStep
                        ? 'w-6 h-2 bg-tasklab-accent rounded-full'
                        : step < currentStep
                            ? 'w-2 h-2 bg-tasklab-accent/50 rounded-full hover:bg-tasklab-accent/70'
                            : 'w-2 h-2 bg-slate-700 rounded-full'"
                    class="transition-all duration-300"
                ></button>
            </template>
        </div>
    </footer>
</div>

<script>
function onboarding() {
    return {
        currentStep: 1,
        totalSteps: 4,

        // Step 1 — Perfil
        profile: {
            name: @json(auth()->user()->name ?? ''),
            position: @json(auth()->user()->position ?? ''),
            department: @json(auth()->user()->department ?? ''),
            phone: @json(auth()->user()->phone ?? ''),
        },
        profileSaving: false,
        profileError: null,

        // Step 3 — Chat
        chatTool: null,
        chatConnected: @json($slackConnection ? true : false),

        init() {
            // Si viene de un OAuth de Slack exitoso, ir directo al paso 3
            @if(session('slack_success'))
            this.chatTool = 'slack';
            this.chatConnected = true;
            @elseif($slackConnection)
            this.chatTool = 'slack';
            this.chatConnected = true;
            @endif

            // Si la URL tiene ?step=3 (vuelta del OAuth), posicionarse en ese paso
            const params = new URLSearchParams(window.location.search);
            if (params.get('step')) {
                this.currentStep = parseInt(params.get('step'));
            }
        },

        async saveProfile() {
            if (!this.profile.name) return;
            this.profileSaving = true;
            this.profileError = null;

            try {
                const res = await fetch('{{ route("onboarding.profile") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.profile),
                });

                const data = await res.json();

                if (res.ok && data.ok) {
                    this.currentStep = 2;
                } else {
                    const errors = data.errors ?? {};
                    this.profileError = Object.values(errors)[0]?.[0] ?? 'Error al guardar. Inténtalo de nuevo.';
                }
            } catch (e) {
                this.profileError = 'Error de conexión. Inténtalo de nuevo.';
            } finally {
                this.profileSaving = false;
            }
        },

        markChatConnected(tool) {
            this.chatConnected = true;
            this.currentStep = 4;
        },
    };
}
</script>

</body>
</html>
