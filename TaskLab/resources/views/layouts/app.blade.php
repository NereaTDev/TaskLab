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
  <body class="min-h-screen bg-white text-slate-900 antialiased">
    <div class="min-h-screen flex flex-col">
      {{-- Global toast notifications --}}
      <x-toast :message="session('success') ?? session('status')" type="success" />
      <x-toast :message="session('error')" type="error" />

      {{-- Header estilo DevTask Manager --}}
      <header class="border-b border-slate-200 bg-white">
        <div class="max-w-[1600px] mx-auto px-4 py-3">
          <div class="flex items-center justify-between gap-4">
            {{-- Logo + título --}}
            <a href="{{ route('tasks.index') }}" class="flex items-center gap-3 shrink-0">
              <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-800 text-white">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zm0 8a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zm8-8a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5a2 2 0 012-2h2z"/></svg>
              </span>
              <div>
                <h1 class="text-lg font-bold tracking-tight text-slate-900">TaskLab Manager</h1>
                <p class="text-xs text-slate-500">Gestión de tareas para equipos</p>
              </div>
            </a>

            {{-- Centro: toggle, notificaciones, configuración --}}
            <div class="hidden md:flex items-center gap-4">
              <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="text-xs text-slate-600">Auto-asignación</span>
                <button type="button" role="switch" aria-checked="false" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border border-slate-200 bg-slate-100 transition-colors">
                  <span class="pointer-events-none inline-block h-4 w-4 translate-x-0.5 rounded-full bg-white shadow ring-0 transition translate-y-0.5"></span>
                </button>
              </div>
              <button type="button" class="relative p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-medium text-white">9+</span>
              </button>
              <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              </button>
            </div>

            {{-- Derecha: usuario + Nueva Tarea --}}
            <div class="flex items-center gap-3 ml-auto">
              @auth
                <div class="hidden sm:flex items-center gap-2 text-left">
                  <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-xs font-semibold text-white">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}</span>
                  <div>
                    <p class="text-xs font-medium text-slate-900 leading-tight">{{ Auth::user()->name }}</p>
                    <p class="text-[11px] text-slate-500 leading-tight">Project Manager</p>
                  </div>
                </div>
                <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-slate-800">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                  Nueva Tarea
                </a>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:inline">
                  @csrf
                  <button type="submit" class="text-xs text-slate-500 hover:text-slate-700">Cerrar sesión</button>
                </form>
              @endauth

              @guest
                <a href="{{ route('login') }}" class="text-xs text-slate-600 hover:text-slate-900">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-slate-800">Registrarse</a>
              @endguest
            </div>
          </div>
        </div>

        {{-- Tabs de navegación --}}
        <nav class="border-t border-slate-100">
          <div class="max-w-[1600px] mx-auto px-4">
            <div class="flex gap-6 text-sm">
              <a href="{{ route('tasks.index') }}" class="flex items-center gap-2 py-3 border-b-2 {{ request()->routeIs('tasks.index') && in_array(request()->get('view'), [null, 'dashboard'], true) ? 'border-slate-900 text-slate-900 font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
              </a>
              <a href="{{ route('tasks.index') }}?view=board" class="flex items-center gap-2 py-3 border-b-2 {{ request()->get('view') === 'board' ? 'border-slate-900 text-slate-900 font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Tablero
              </a>
              <a href="{{ route('tasks.index') }}?view=analysis" class="flex items-center gap-2 py-3 border-b-2 {{ request()->get('view') === 'analysis' ? 'border-slate-900 text-slate-900 font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Análisis
              </a>
            </div>
          </div>
        </nav>
      </header>

      <main class="flex-1">
        {{ $slot }}
      </main>
    </div>
  </body>
</html>
