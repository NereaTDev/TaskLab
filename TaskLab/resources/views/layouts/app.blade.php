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
    <div class="min-h-screen flex flex-col">
      {{-- Global toast notifications --}}
      <x-toast :message="session('success') ?? session('status')" type="success" />
      <x-toast :message="session('error')" type="error" />

      {{-- Header con logo + tabs + controles usuario --}}
      <header>
        <div class="max-w-[1600px] mx-auto px-4 py-3">
          <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              {{-- Tabs de navegación en pastilla, alineadas con el logo --}}
              @php $navUser = auth()->user(); @endphp
              <div class="inline-flex items-center gap-1 rounded-full bg-slate-900 px-1 py-1 text-xs">
                <a href="{{ route('tasks.index') }}"
                   class="inline-flex text-sm items-center gap-1.5 rounded-full px-3 py-1.5 font-medium {{ request()->routeIs('tasks.index') && in_array(request()->get('view'), [null, 'dashboard'], true) ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                  </svg>
                  Dashboard
                </a>

                <span class="h-5 w-px bg-slate-700"></span>

                {{-- Todos los usuarios autenticados pueden ver Dashboard, Tablero y Análisis --}}
                <a href="{{ route('tasks.index', ['view' => 'board']) }}"
                   class="inline-flex text-sm items-center gap-1.5 rounded-full px-3 py-1.5 font-medium {{ request()->get('view') === 'board' ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                  Tablero
                </a>

                <a href="{{ route('tasks.index', ['view' => 'analysis']) }}"
                   class="inline-flex text-sm items-center gap-1.5 rounded-full px-3 py-1.5 font-medium {{ request()->get('view') === 'analysis' ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                  </svg>
                  Análisis
                </a>

                @auth
                  @php $navUser = auth()->user(); @endphp
                  @if ($navUser && ($navUser->is_admin || (method_exists($navUser, 'isAreaAdmin') && $navUser->isAreaAdmin()) || (method_exists($navUser, 'isSuperAdmin') && $navUser->isSuperAdmin())))
                    <a href="{{ route('team.index') }}"
                       class="inline-flex text-sm items-center gap-1.5 rounded-full px-3 py-1.5 font-medium {{ request()->routeIs('team.index') ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20h6M3 20h5v-2a4 4 0 00-3-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0zM5 10a4 4 0 100-8 4 4 0 000 8zM19 10a4 4 0 100-8 4 4 0 000 8z"/>
                      </svg>
                      Equipo
                    </a>
                  @endif

                  @if ($navUser && method_exists($navUser, 'isSuperAdmin') && $navUser->isSuperAdmin())
                    <a href="{{ route('settings.index') }}"
                       class="inline-flex text-sm items-center gap-1.5 rounded-full px-3 py-1.5 font-medium {{ request()->routeIs('settings.index') ? 'bg-tasklab-bg text-tasklab-text border border-tasklab-accent' : 'text-tasklab-muted hover:text-tasklab-text' }}">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                      Configuración
                    </a>
                  @endif
                @endauth
              </div>
            </div>

            {{-- Derecha: controles + usuario --}}
            <div class="flex items-center gap-4 ml-auto">
              {{-- Controles pegados a la derecha --}}
              <div class="hidden md:flex items-center gap-3 mr-2">
                <div class="flex items-center gap-2">
                  <svg class="h-4 w-4 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                  <span class="text-xs text-tasklab-muted">Auto-asignación</span>
                  <button type="button" role="switch" aria-checked="false" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border border-slate-700 bg-slate-900 transition-colors">
                    <span class="pointer-events-none inline-block h-4 w-4 translate-x-0.5 rounded-full bg-tasklab-primary shadow ring-0 transition translate-y-0.5"></span>
                  </button>
                </div>
                <button type="button" class="relative p-1.5 rounded-lg text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                  </svg>
                  <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-medium text-white">9+</span>
                </button>
              </div>

              @auth
                <div class="hidden sm:flex items-center gap-2 text-left" x-data="{ open: false }" @click.outside="open = false">
                  <button type="button" class="inline-flex items-center gap-2" @click="open = !open">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-xs font-semibold text-white">
                      {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}
                    </span>
                    <div class="text-left">
                      <p class="text-xs font-medium text-tasklab-text leading-tight">{{ Auth::user()->name }}</p>
                      <p class="text-[11px] text-tasklab-muted leading-tight">Project Manager</p>
                    </div>
                  </button>

                  {{-- Dropdown usuario --}}
                  <div x-show="open" x-transition class="absolute top-16 w-44 rounded-xl border border-slate-800 bg-tasklab-bg-muted shadow-card text-xs z-50">
                    <div class="px-3 py-2 border-b border-slate-800">
                      <p class="font-medium text-tasklab-text truncate">{{ Auth::user()->name }}</p>
                      <p class="text-[11px] text-tasklab-muted truncate">{{ Auth::user()->email }}</p>
                    </div>
                    <div class="py-1">
                      <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-1.5 text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Perfil
                      </a>
                      <a href="#" class="flex items-center gap-2 px-3 py-1.5 text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Ajustes
                      </a>
                      <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-1.5 text-tasklab-muted hover:bg-slate-900 hover:text-tasklab-text">
                          <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 11-4 0v-1m0-10V5a2 2 0 114 0v1"/>
                          </svg>
                          Cerrar sesión
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              @endauth

              @guest
                <a href="{{ route('login') }}" class="text-xs text-slate-600 hover:text-slate-900">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-slate-800">Registrarse</a>
              @endguest
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1">
        {{ $slot }}
      </main>
    </div>
  </body>
</html>
