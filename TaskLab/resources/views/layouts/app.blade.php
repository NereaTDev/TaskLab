<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'TaskLab') }}</title>
    <link rel="preconnect" href="https://rsms.me">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>
  <body class="min-h-screen bg-tasklab-bg text-tasklab-text antialiased">
    @auth
      <x-task-modal />
    @endauth

    <div class="min-h-screen flex flex-col">
      <x-toast :message="session('success') ?? session('status')" type="success" />
      <x-toast :message="session('error')" type="error" />

      {{-- ── Header ─────────────────────────────────────────────────────────── --}}
      <header class="sticky top-0 z-30 border-b border-slate-800 bg-tasklab-bg/95 backdrop-blur">
        <div class="max-w-[1600px] mx-auto px-4 py-2.5 flex items-center justify-between gap-3">

          {{-- Nav tabs — solo desktop --}}
          @auth
          @php $navUser = auth()->user(); @endphp
          <nav class="hidden md:flex items-center gap-1 rounded-full bg-slate-900 px-1 py-1 text-xs">
            <a href="{{ route('tasks.index', ['view' => 'dashboard']) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ request()->get('view') === 'dashboard' || (request()->routeIs('tasks.index') && !request()->has('view')) ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
              Dashboard
            </a>
            <span class="h-5 w-px bg-slate-700"></span>
            <a href="{{ route('tasks.index', ['view' => 'board']) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ request()->get('view') === 'board' ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
              Tablero
            </a>
            <a href="{{ route('tasks.index', ['view' => 'analysis']) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ request()->get('view') === 'analysis' ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
              Análisis
            </a>
            @if ($navUser && ($navUser->is_admin || $navUser->isAreaAdmin() || $navUser->isSuperAdmin()))
              <a href="{{ route('team.index') }}"
                 class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ request()->routeIs('team.index') ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20h6M3 20h5v-2a4 4 0 00-3-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Equipo
              </a>
            @endif
            @if ($navUser && $navUser->isSuperAdmin())
              <a href="{{ route('settings.index') }}"
                 class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ request()->routeIs('settings.index') ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Configuración
              </a>
            @endif
          </nav>
          @endauth

          <!-- Logo -->
          <a href="{{ route('tasks.index') }}" class="md:hidden inline-flex items-center gap-2">
            <img
              src="{{ Vite::asset('resources/assets/taskLabLogo.png') }}"
              alt="TaskLab"
              class="h-12 w-12 rounded-lg"
            />
          </a>

          {{-- Derecha: campana + usuario --}}
          <div class="flex items-center gap-2 ml-auto">
            @auth
            {{-- Campana --}}
            <div x-data="notificationBell()" x-init="init()" @click.outside="open = false" class="relative">
              <button type="button" class="relative p-2 rounded-lg text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text" @click="toggle()">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread" class="absolute -top-0.5 -right-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-medium text-white"></span>
              </button>
              <div x-show="open" x-transition class="absolute right-0 top-11 z-50 w-80 rounded-xl border border-slate-800 bg-tasklab-bg-muted shadow-card" style="display:none">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800">
                  <span class="text-xs font-semibold text-tasklab-text">Notificaciones</span>
                  <button x-show="unread > 0" @click="markAllRead()" class="text-[10px] text-tasklab-muted hover:text-tasklab-text">Marcar todas como leídas</button>
                </div>
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-800">
                  <template x-if="notifications.length === 0">
                    <p class="px-4 py-6 text-center text-xs text-tasklab-muted">Sin notificaciones</p>
                  </template>
                  <template x-for="n in notifications" :key="n.id">
                    <div class="flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-slate-900 transition-colors" :class="{ 'opacity-50': n.read }" @click="markRead(n)">
                      <span class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="n.read ? 'bg-slate-700' : 'bg-tasklab-primary'"></span>
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-tasklab-text truncate" x-text="n.data.task_title || n.data.name || 'Notificación'"></p>
                        <p class="text-[11px] text-tasklab-muted mt-0.5" x-text="n.data.message"></p>
                        <span class="text-[10px] text-tasklab-muted" x-text="n.time"></span>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>

            {{-- Avatar + dropdown usuario --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
              <button type="button" @click="open = !open" class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-xs font-semibold text-white shrink-0">
                  {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}
                </span>
                <span class="hidden sm:block text-xs font-medium text-tasklab-text">{{ Auth::user()->name }}</span>
              </button>
              <div x-show="open" x-transition class="absolute right-0 top-11 z-50 w-44 rounded-xl border border-slate-800 bg-tasklab-bg-muted shadow-card text-xs" style="display:none">
                <div class="px-3 py-2 border-b border-slate-800">
                  <p class="font-medium text-tasklab-text truncate">{{ Auth::user()->name }}</p>
                  <p class="text-[11px] text-tasklab-muted truncate">{{ Auth::user()->email }}</p>
                </div>
                <div class="py-1">
                  <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Perfil
                  </a>
                  <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text">
                      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                      Cerrar sesión
                    </button>
                  </form>
                </div>
              </div>
            </div>
            @endauth

            @guest
              <a href="{{ route('login') }}" class="text-xs text-tasklab-muted hover:text-tasklab-text">Entrar</a>
              <a href="{{ route('register') }}" class="rounded-lg bg-tasklab-accent px-3 py-1.5 text-xs font-medium text-slate-950">Registrarse</a>
            @endguest
          </div>

        </div>
      </header>

      {{-- ── Contenido principal ─────────────────────────────────────────────── --}}
      <main class="flex-1 pb-16 md:pb-0">
        {{ $slot }}
      </main>

      {{-- ── Bottom nav móvil ────────────────────────────────────────────────── --}}
      @auth
      @php $bottomNavUser = auth()->user(); @endphp
      <nav class="md:hidden fixed bottom-0 inset-x-0 z-30 border-t border-slate-800 bg-tasklab-bg/95 backdrop-blur flex items-stretch">
        <a href="{{ route('tasks.index', ['view' => 'dashboard']) }}"
           class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-[10px] font-medium transition-colors {{ request()->get('view') === 'dashboard' || (request()->routeIs('tasks.index') && !request()->has('view')) ? 'text-tasklab-accent' : 'text-tasklab-muted' }}">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
          Dashboard
        </a>
        <a href="{{ route('tasks.index', ['view' => 'board']) }}"
           class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-[10px] font-medium transition-colors {{ request()->get('view') === 'board' ? 'text-tasklab-accent' : 'text-tasklab-muted' }}">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          Tablero
        </a>
        <a href="{{ route('tasks.index', ['view' => 'analysis']) }}"
           class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-[10px] font-medium transition-colors {{ request()->get('view') === 'analysis' ? 'text-tasklab-accent' : 'text-tasklab-muted' }}">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          Análisis
        </a>
        @if ($bottomNavUser && ($bottomNavUser->is_admin || $bottomNavUser->isAreaAdmin() || $bottomNavUser->isSuperAdmin()))
        <a href="{{ route('team.index') }}"
           class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-[10px] font-medium transition-colors {{ request()->routeIs('team.index') ? 'text-tasklab-accent' : 'text-tasklab-muted' }}">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20h6M3 20h5v-2a4 4 0 00-3-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          Equipo
        </a>
        @endif
        @if ($bottomNavUser && $bottomNavUser->isSuperAdmin())
        <a href="{{ route('settings.index') }}"
           class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-[10px] font-medium transition-colors {{ request()->routeIs('settings.index') ? 'text-tasklab-accent' : 'text-tasklab-muted' }}">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Config
        </a>
        @endif
      </nav>
      @endauth

    </div>
  </body>
</html>
