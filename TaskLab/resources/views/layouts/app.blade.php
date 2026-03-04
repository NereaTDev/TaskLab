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
  <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen flex flex-col">
      <header class="border-b border-slate-200 bg-white/80 backdrop-blur">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
          <a href="{{ route('tasks.index') }}" class="flex items-center gap-2">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sky-600 text-xs font-semibold text-white">TL</span>
            <span class="text-sm font-semibold tracking-tight text-slate-900">TaskLab</span>
          </a>

          @auth
            <nav class="flex items-center gap-4 text-xs text-slate-500">
              <a href="{{ route('tasks.index') }}" class="hover:text-slate-900 {{ request()->routeIs('tasks.index') ? 'text-slate-900 font-semibold' : '' }}">Tasks</a>
              <a href="{{ route('tasks.create') }}" class="hover:text-slate-900 {{ request()->routeIs('tasks.create') ? 'text-slate-900 font-semibold' : '' }}">New task</a>
              <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50">
                  <span>{{ Auth::user()->name }}</span>
                  <span class="text-slate-400">·</span>
                  <span>Log out</span>
                </button>
              </form>
            </nav>
          @endauth

          @guest
            <nav class="flex items-center gap-3 text-xs text-slate-500">
              <a href="{{ route('login') }}" class="hover:text-slate-900">Log in</a>
              <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-[11px] font-medium text-white hover:bg-slate-800">Sign up</a>
            </nav>
          @endguest
        </div>
      </header>

      <main class="flex-1">
        {{ $slot }}
      </main>
    </div>
  </body>
</html>
