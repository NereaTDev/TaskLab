<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskLab</title>
    <link rel="preconnect" href="https://rsms.me">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white/80 backdrop-blur">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <a href="{{ route('tasks.index') }}" class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sky-600 text-xs font-semibold text-white">TL</span>
                <span class="text-sm font-semibold tracking-tight text-slate-900">TaskLab</span>
            </a>
            <nav class="text-xs text-slate-500 flex items-center gap-3">
                <a href="{{ route('tasks.index') }}" class="hover:text-slate-900">Tasks</a>
                <a href="{{ route('tasks.create') }}" class="hover:text-slate-900">New task</a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>
