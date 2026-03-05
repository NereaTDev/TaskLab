<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevTask Manager - @yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .kanban-column { min-height: 400px; }
        .task-card { transition: all 0.2s ease; }
        .task-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #f1f5f9; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-600 to-primary-500 flex items-center justify-center">
                        <i class="fas fa-tasks text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg leading-tight">DevTask Manager</h1>
                        <p class="text-xs text-slate-500">Gestión de tareas para equipos</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="hidden md:flex items-center gap-6">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i class="fas fa-bolt text-yellow-500"></i>
                        <span>Auto-asignación</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="autoAssignToggle" class="sr-only peer" checked>
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                        </label>
                    </div>
                    
                    <button class="relative p-2 text-slate-600 hover:text-slate-900">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                    </button>
                    
                    <button class="p-2 text-slate-600 hover:text-slate-900">
                        <i class="fas fa-cog text-lg"></i>
                    </button>

                    <div class="flex items-center gap-2 pl-4 border-l border-slate-200">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white text-sm font-medium">
                            AD
                        </div>
                        <div class="hidden lg:block">
                            <p class="text-sm font-medium">Admin User</p>
                            <p class="text-xs text-slate-500">Project Manager</p>
                        </div>
                    </div>
                </nav>

                <!-- Mobile menu button -->
                <button class="md:hidden p-2" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-slate-200">
            <div class="px-4 py-4 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Auto-asignación</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-500"></div>
                    </label>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // Auto-assign toggle
        document.getElementById('autoAssignToggle')?.addEventListener('change', function(e) {
            fetch('/api/auto-assign', {
                method: 'POST',
                body: JSON.stringify({ enabled: e.target.checked })
            });
        });
    </script>
</body>
</html>
