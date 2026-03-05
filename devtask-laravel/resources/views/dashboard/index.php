<?php
$title = 'Dashboard';

// Helper functions
function priorityColor($priority) {
    return match($priority) {
        'critica' => 'bg-red-500 text-white',
        'alta' => 'bg-orange-500 text-white',
        'media' => 'bg-yellow-500 text-black',
        'baja' => 'bg-green-500 text-white',
        default => 'bg-slate-500 text-white'
    };
}

function priorityLabel($priority) {
    return match($priority) {
        'critica' => 'Crítica',
        'alta' => 'Alta',
        'media' => 'Media',
        'baja' => 'Baja',
        default => $priority
    };
}

function typeColor($type) {
    return match($type) {
        'evolutiva' => 'bg-blue-100 text-blue-700 border-blue-200',
        'correctiva' => 'bg-red-100 text-red-700 border-red-200',
        'preventiva' => 'bg-purple-100 text-purple-700 border-purple-200',
        'soporte' => 'bg-slate-100 text-slate-700 border-slate-200',
        default => 'bg-slate-100 text-slate-700'
    };
}

function typeLabel($type) {
    return match($type) {
        'evolutiva' => 'Evolutiva',
        'correctiva' => 'Correctiva',
        'preventiva' => 'Preventiva',
        'soporte' => 'Soporte',
        default => $type
    };
}

function statusLabel($status) {
    return match($status) {
        'pendiente' => 'Pendiente',
        'en-progreso' => 'En Progreso',
        'en-revision' => 'En Revisión',
        'completada' => 'Completada',
        default => $status
    };
}

function statusIcon($status) {
    return match($status) {
        'pendiente' => 'fa-clock',
        'en-progreso' => 'fa-spinner',
        'en-revision' => 'fa-eye',
        'completada' => 'fa-check-circle',
        default => 'fa-circle'
    };
}

function statusColor($status) {
    return match($status) {
        'pendiente' => 'bg-amber-100 text-amber-700 border-amber-200',
        'en-progreso' => 'bg-blue-100 text-blue-700 border-blue-200',
        'en-revision' => 'bg-purple-100 text-purple-700 border-purple-200',
        'completada' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        default => 'bg-slate-100 text-slate-700'
    };
}

function workloadColor($percent) {
    if ($percent >= 90) return 'bg-red-500';
    if ($percent >= 70) return 'bg-yellow-500';
    return 'bg-emerald-500';
}

function isOverdue($dueDate, $status) {
    return $status !== 'completada' && strtotime($dueDate) < time();
}

function formatDate($date) {
    $timestamp = strtotime($date);
    $today = strtotime('today');
    $tomorrow = strtotime('tomorrow');
    
    if ($timestamp == $today) return 'Hoy';
    if ($timestamp == $tomorrow) return 'Mañana';
    return date('d M', $timestamp);
}

// Group tasks by status
$tasksByStatus = [
    'pendiente' => [],
    'en-progreso' => [],
    'en-revision' => [],
    'completada' => []
];

foreach ($filteredTasks as $task) {
    $tasksByStatus[$task['status']][] = $task;
}

ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-slate-500">Total Tareas</span>
            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                <i class="fas fa-clipboard-list text-blue-500"></i>
            </div>
        </div>
        <div class="text-2xl font-bold"><?= $stats['total'] ?></div>
    </div>
    
    <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-slate-500">Pendientes</span>
            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                <i class="fas fa-clock text-amber-500"></i>
            </div>
        </div>
        <div class="text-2xl font-bold"><?= $stats['pending'] ?></div>
    </div>
    
    <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-slate-500">En Progreso</span>
            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                <i class="fas fa-spinner text-purple-500"></i>
            </div>
        </div>
        <div class="text-2xl font-bold"><?= $stats['in_progress'] ?></div>
    </div>
    
    <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-slate-500">Completadas</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i class="fas fa-check-circle text-emerald-500"></i>
            </div>
        </div>
        <div class="text-2xl font-bold"><?= $stats['completed'] ?></div>
    </div>
    
    <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-slate-500">Vencidas</span>
            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                <i class="fas fa-exclamation-circle text-red-500"></i>
            </div>
        </div>
        <div class="text-2xl font-bold"><?= $stats['overdue'] ?></div>
    </div>
    
    <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-slate-500">Tasa de Éxito</span>
            <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                <i class="fas fa-chart-line text-cyan-500"></i>
            </div>
        </div>
        <div class="text-2xl font-bold">
            <?= $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0 ?>%
        </div>
    </div>
</div>

<!-- Filters & Main Content -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Left Column: Filters & Board -->
    <div class="lg:col-span-3 space-y-4">
        
        <!-- Filters -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
            <form method="GET" class="space-y-4">
                <div class="flex flex-wrap gap-3 items-center">
                    <!-- Search -->
                    <div class="relative flex-1 min-w-[250px]">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?= htmlspecialchars($filters['search']) ?>"
                            placeholder="Buscar tareas..." 
                            class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Type Filter -->
                    <div class="relative">
                        <select name="types[]" multiple class="hidden" id="typeFilter">
                            <?php foreach (['evolutiva', 'correctiva', 'preventiva', 'soporte'] as $type): ?>
                                <option value="<?= $type ?>" <?= in_array($type, $filters['types']) ? 'selected' : '' ?>><?= typeLabel($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="toggleFilterDropdown('typeDropdown')" class="px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 flex items-center gap-2">
                            <i class="fas fa-layer-group"></i>
                            Tipo
                            <?php if (!empty($filters['types'])): ?>
                                <span class="bg-primary-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?= count($filters['types']) ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="typeDropdown" class="hidden absolute top-full left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-slate-200 z-50">
                            <?php foreach (['evolutiva', 'correctiva', 'preventiva', 'soporte'] as $type): ?>
                                <label class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50 cursor-pointer">
                                    <input type="checkbox" name="types[]" value="<?= $type ?>" <?= in_array($type, $filters['types']) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span class="w-2 h-2 rounded-full <?= str_replace(['bg-', 'text-', '/10', '/20'], '', explode(' ', typeColor($type))[0]) ?>"></span>
                                    <?= typeLabel($type) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Priority Filter -->
                    <div class="relative">
                        <button type="button" onclick="toggleFilterDropdown('priorityDropdown')" class="px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            Prioridad
                            <?php if (!empty($filters['priorities'])): ?>
                                <span class="bg-primary-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?= count($filters['priorities']) ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="priorityDropdown" class="hidden absolute top-full left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-slate-200 z-50">
                            <?php foreach (['critica', 'alta', 'media', 'baja'] as $priority): ?>
                                <label class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50 cursor-pointer">
                                    <input type="checkbox" name="priorities[]" value="<?= $priority ?>" <?= in_array($priority, $filters['priorities']) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span class="w-2 h-2 rounded-full <?= str_replace(['bg-', 'text-white'], '', priorityColor($priority)) ?>"></span>
                                    <?= priorityLabel($priority) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <button type="button" onclick="toggleFilterDropdown('statusDropdown')" class="px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            Estado
                            <?php if (!empty($filters['statuses'])): ?>
                                <span class="bg-primary-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?= count($filters['statuses']) ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="statusDropdown" class="hidden absolute top-full left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-slate-200 z-50">
                            <?php foreach (['pendiente', 'en-progreso', 'en-revision', 'completada'] as $status): ?>
                                <label class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50 cursor-pointer">
                                    <input type="checkbox" name="statuses[]" value="<?= $status ?>" <?= in_array($status, $filters['statuses']) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <?= statusLabel($status) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Assignee Filter -->
                    <div class="relative">
                        <button type="button" onclick="toggleFilterDropdown('assigneeDropdown')" class="px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 flex items-center gap-2">
                            <i class="fas fa-user"></i>
                            Asignado
                            <?php if (!empty($filters['assignees'])): ?>
                                <span class="bg-primary-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?= count($filters['assignees']) ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="assigneeDropdown" class="hidden absolute top-full left-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-slate-200 z-50">
                            <?php foreach ($developers as $dev): ?>
                                <label class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50 cursor-pointer">
                                    <input type="checkbox" name="assignees[]" value="<?= $dev['id'] ?>" <?= in_array($dev['id'], $filters['assignees']) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center text-xs font-medium"><?= $dev['avatar'] ?></span>
                                    <?= $dev['name'] ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    <?php if (!empty(array_filter($filters))): ?>
                        <a href="/" class="px-4 py-2 text-slate-500 hover:text-slate-700 flex items-center gap-2">
                            <i class="fas fa-times"></i>
                            Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Active Filters -->
            <?php if (!empty(array_filter($filters))): ?>
                <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-100">
                    <?php foreach ($filters['types'] as $type): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full border <?= typeColor($type) ?>">
                            <?= typeLabel($type) ?>
                            <a href="?<?= http_build_query(array_diff_key($_GET, ['types' => '']) + ['types' => array_diff($filters['types'], [$type])]) ?>" class="hover:opacity-70"><i class="fas fa-times"></i></a>
                        </span>
                    <?php endforeach; ?>
                    <?php foreach ($filters['priorities'] as $priority): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full border <?= priorityColor($priority) ?>">
                            <?= priorityLabel($priority) ?>
                            <a href="?<?= http_build_query(array_diff_key($_GET, ['priorities' => '']) + ['priorities' => array_diff($filters['priorities'], [$priority])]) ?>" class="hover:opacity-70"><i class="fas fa-times"></i></a>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kanban Board -->
        <div class="bg-slate-100 rounded-xl p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold flex items-center gap-2">
                    <i class="fas fa-columns"></i>
                    Tablero Kanban (<?= count($filteredTasks) ?> tareas)
                </h3>
                <button onclick="openModal('createTaskModal')" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Nueva Tarea
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach (['pendiente' => 'Pendiente', 'en-progreso' => 'En Progreso', 'en-revision' => 'En Revisión', 'completada' => 'Completada'] as $status => $statusLabel): ?>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <!-- Column Header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg border <?= statusColor($status) ?>">
                                <i class="fas <?= statusIcon($status) ?>"></i>
                                <span class="font-medium text-sm"><?= $statusLabel ?></span>
                                <span class="bg-white/50 text-xs rounded-full px-2 py-0.5"><?= count($tasksByStatus[$status]) ?></span>
                            </div>
                        </div>

                        <!-- Tasks -->
                        <div class="space-y-3 max-h-[600px] overflow-y-auto scrollbar-thin">
                            <?php foreach ($tasksByStatus[$status] as $task): ?>
                                <div class="task-card bg-white rounded-lg p-4 shadow-sm border-l-4 <?= $status === 'pendiente' ? 'border-l-amber-400' : ($status === 'en-progreso' ? 'border-l-blue-400' : ($status === 'en-revision' ? 'border-l-purple-400' : 'border-l-emerald-400')) ?>">
                                    <!-- Header -->
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <h4 class="font-semibold text-sm line-clamp-2"><?= htmlspecialchars($task['title']) ?></h4>
                                        <div class="relative">
                                            <button onclick="toggleDropdown('task-<?= $task['id'] ?>')" class="text-slate-400 hover:text-slate-600">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <div id="task-<?= $task['id'] ?>" class="hidden absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-slate-200 z-50">
                                                <?php if ($status !== 'completada'): ?>
                                                    <?php 
                                                    $nextStatus = match($status) {
                                                        'pendiente' => 'en-progreso',
                                                        'en-progreso' => 'en-revision',
                                                        'en-revision' => 'completada',
                                                        default => null
                                                    };
                                                    if ($nextStatus): 
                                                    ?>
                                                        <form method="POST" action="/tasks/update-status?action=update-status" class="block">
                                                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                            <input type="hidden" name="status" value="<?= $nextStatus ?>">
                                                            <button type="submit" class="w-full text-left px-4 py-2 hover:bg-slate-50 text-sm">
                                                                <i class="fas fa-arrow-right mr-2"></i> Mover a <?= statusLabel($nextStatus) ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <button onclick="openAssignModal('<?= $task['id'] ?>')" class="w-full text-left px-4 py-2 hover:bg-slate-50 text-sm">
                                                    <i class="fas fa-user-plus mr-2"></i> Reasignar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Badges -->
                                    <div class="flex flex-wrap gap-1.5 mb-2">
                                        <span class="text-xs px-2 py-0.5 rounded-full <?= priorityColor($task['priority']) ?>">
                                            <?= priorityLabel($task['priority']) ?>
                                        </span>
                                        <span class="text-xs px-2 py-0.5 rounded-full border <?= typeColor($task['type']) ?>">
                                            <i class="fas fa-layer-group mr-1"></i><?= typeLabel($task['type']) ?>
                                        </span>
                                    </div>

                                    <!-- Meta -->
                                    <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                                        <div class="flex items-center gap-3">
                                            <span><i class="fas fa-clock mr-1"></i><?= $task['estimated_hours'] ?>h</span>
                                            <span class="<?= isOverdue($task['due_date'], $task['status']) ? 'text-red-500 font-medium' : '' ?>">
                                                <i class="fas fa-calendar mr-1"></i><?= formatDate($task['due_date']) ?>
                                            </span>
                                        </div>
                                        <span><?= $task['project'] ?></span>
                                    </div>

                                    <!-- Assignee & Tags -->
                                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                        <?php if ($task['assignee']): ?>
                                            <div class="flex items-center gap-1.5">
                                                <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center text-xs font-medium">
                                                    <?= $task['assignee']['avatar'] ?>
                                                </div>
                                                <span class="text-xs text-slate-500"><?= explode(' ', $task['assignee']['name'])[0] ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400"><i class="fas fa-user mr-1"></i>Sin asignar</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($task['tags'])): ?>
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            <?php foreach (array_slice($task['tags'], 0, 3) as $tag): ?>
                                                <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 rounded text-slate-500">#<?= $tag ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($task['tags']) > 3): ?>
                                                <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 rounded text-slate-500">+<?= count($task['tags']) - 3 ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($tasksByStatus[$status])): ?>
                                <div class="text-center py-8 text-slate-400">
                                    <i class="fas fa-inbox text-3xl mb-2"></i>
                                    <p class="text-sm">Sin tareas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Team & Info -->
    <div class="space-y-6">
        <!-- Team List -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="p-4 border-b border-slate-100">
                <h3 class="font-semibold flex items-center gap-2">
                    <i class="fas fa-users"></i>
                    Equipo de Desarrollo
                </h3>
            </div>
            <div class="p-4 space-y-4">
                <?php foreach ($developers as $dev): 
                    $workloadPercent = ($dev['current_tasks'] / $dev['max_tasks']) * 100;
                ?>
                    <div class="group">
                        <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-200 to-primary-100 flex items-center justify-center text-sm font-medium border border-primary-200">
                                <?= $dev['avatar'] ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="font-medium text-sm truncate"><?= $dev['name'] ?></h4>
                                    <span class="text-xs px-2 py-0.5 rounded-full <?= $dev['current_tasks'] >= $dev['max_tasks'] ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-600' ?>">
                                        <?= $dev['current_tasks'] ?>/<?= $dev['max_tasks'] ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-briefcase text-[10px]"></i>
                                    <?= $dev['role'] ?>
                                </p>
                                <div class="flex flex-wrap gap-1 mt-2">
                                    <?php foreach (array_slice($dev['skills'], 0, 3) as $skill): ?>
                                        <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 rounded text-slate-500"><?= $skill ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3">
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-slate-500">Carga de trabajo</span>
                                        <span class="<?= $workloadPercent >= 90 ? 'text-red-500' : '' ?>"><?= round($workloadPercent) ?>%</span>
                                    </div>
                                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full <?= workloadColor($workloadPercent) ?> transition-all duration-500" style="width: <?= $workloadPercent ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Auto-assign Info -->
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 rounded-xl p-4 border border-primary-200">
            <h4 class="font-medium text-sm mb-2">Auto-asignación activa</h4>
            <p class="text-xs text-slate-600 mb-3">
                Las nuevas tareas se asignan automáticamente al desarrollador con menor carga de trabajo.
            </p>
            <div class="flex items-center gap-2 text-xs">
                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                <span class="text-emerald-600 font-medium">Sistema activo</span>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h3 class="font-semibold text-sm mb-4">Distribución</h3>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-500">Por Tipo</span>
                    </div>
                    <div class="space-y-1">
                        <?php foreach ($stats['by_type'] as $type => $count): ?>
                            <div class="flex items-center justify-between text-xs">
                                <span class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full <?= str_replace(['bg-', '/10', 'text-', '/20'], '', explode(' ', typeColor($type))[0]) ?>"></span>
                                    <?= typeLabel($type) ?>
                                </span>
                                <span class="font-medium"><?= $count ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-100">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-500">Por Prioridad</span>
                    </div>
                    <div class="space-y-1">
                        <?php foreach ($stats['by_priority'] as $priority => $count): ?>
                            <div class="flex items-center justify-between text-xs">
                                <span class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full <?= str_replace(['bg-', 'text-white'], '', priorityColor($priority)) ?>"></span>
                                    <?= priorityLabel($priority) ?>
                                </span>
                                <span class="font-medium"><?= $count ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div id="createTaskModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold">Crear Nueva Tarea</h3>
            <button onclick="closeModal('createTaskModal')" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="/tasks/create?action=create" class="p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Título *</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Ej: Implementar módulo de reportes">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Descripción</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Describe los detalles de la tarea..."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tipo</label>
                    <select name="type" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <?php foreach (['evolutiva', 'correctiva', 'preventiva', 'soporte'] as $type): ?>
                            <option value="<?= $type ?>"><?= typeLabel($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Prioridad</label>
                    <select name="priority" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <?php foreach (['critica', 'alta', 'media', 'baja'] as $priority): ?>
                            <option value="<?= $priority ?>" <?= $priority === 'media' ? 'selected' : '' ?>><?= priorityLabel($priority) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Proyecto</label>
                <select name="project" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= $project ?>"><?= $project ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Asignar a</label>
                <select name="assignee_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">Auto-asignar</option>
                    <?php foreach ($developers as $dev): ?>
                        <option value="<?= $dev['id'] ?>"><?= $dev['name'] ?> (<?= $dev['current_tasks'] ?>/<?= $dev['max_tasks'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Fecha de entrega</label>
                    <input type="date" name="due_date" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Horas estimadas</label>
                    <input type="number" name="estimated_hours" value="4" min="1" max="100" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Etiquetas (separadas por coma)</label>
                <input type="text" name="tags" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="frontend, backend, urgente">
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('createTaskModal')" class="px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">Crear Tarea</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Task Modal -->
<div id="assignTaskModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-sm w-full">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold">Reasignar Tarea</h3>
            <button onclick="closeModal('assignTaskModal')" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="/tasks/assign?action=assign" class="p-4 space-y-4">
            <input type="hidden" name="task_id" id="assignTaskId">
            <div>
                <label class="block text-sm font-medium mb-1">Nuevo asignado</label>
                <select name="developer_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">Sin asignar</option>
                    <?php foreach ($developers as $dev): ?>
                        <option value="<?= $dev['id'] ?>"><?= $dev['name'] ?> (<?= $dev['current_tasks'] ?>/<?= $dev['max_tasks'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('assignTaskModal')" class="px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">Asignar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleFilterDropdown(id) {
        const dropdown = document.getElementById(id);
        const allDropdowns = document.querySelectorAll('[id$="Dropdown"]');
        allDropdowns.forEach(d => {
            if (d.id !== id) d.classList.add('hidden');
        });
        dropdown.classList.toggle('hidden');
    }

    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        dropdown.classList.toggle('hidden');
    }

    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function openAssignModal(taskId) {
        document.getElementById('assignTaskId').value = taskId;
        openModal('assignTaskModal');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.relative')) {
            document.querySelectorAll('[id$="Dropdown"]').forEach(d => d.classList.add('hidden'));
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
