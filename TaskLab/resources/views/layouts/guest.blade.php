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
    {{-- Global toast notifications for guest layouts --}}
    <x-toast :message="session('success') ?? session('status')" type="success" />
    <x-toast :message="session('error')" type="error" />

    <div class="min-h-screen flex items-center justify-center px-4">
      <div class="w-full max-w-md">
        <div class="flex items-center justify-center mb-6">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600 text-sm font-semibold text-white mr-2">TL</span>
          <span class="text-sm font-semibold tracking-tight text-slate-900">TaskLab</span>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white/80 shadow-sm px-6 py-5">
          {{ $slot }}
        </div>
      </div>
    </div>
  </body>
</html>
