<?php

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

// Initialize data if not exists
if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = generateInitialTasks();
}
if (!isset($_SESSION['developers'])) {
    $_SESSION['developers'] = getDevelopers();
}

// Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/devtask-laravel/public', '', $uri);
$uri = $uri ?: '/';

// Routes
switch ($uri) {
    case '/':
    case '/dashboard':
        require __DIR__ . '/../app/Http/Controllers/DashboardController.php';
        break;
    case '/tasks/create':
        require __DIR__ . '/../app/Http/Controllers/TaskController.php';
        break;
    case '/tasks/update-status':
        require __DIR__ . '/../app/Http/Controllers/TaskController.php';
        break;
    case '/tasks/assign':
        require __DIR__ . '/../app/Http/Controllers/TaskController.php';
        break;
    case '/api/tasks':
        header('Content-Type: application/json');
        echo json_encode($_SESSION['tasks']);
        break;
    default:
        http_response_code(404);
        echo "404 - Not Found";
}

function generateInitialTasks() {
    $titles = [
        'evolutiva' => ['Implementar módulo de reportes', 'Agregar exportación CSV', 'Crear dashboard analytics'],
        'correctiva' => ['Fix: Error en login', 'Fix: Memory leak', 'Fix: Validación formulario'],
        'preventiva' => ['Actualizar dependencias', 'Optimizar queries', 'Refactorizar código'],
        'soporte' => ['Atender ticket #2345', 'Revisar incidente', 'Configurar ambiente'],
    ];
    
    $projects = ['E-commerce', 'CRM', 'Mobile App', 'API Gateway'];
    $types = ['evolutiva', 'correctiva', 'preventiva', 'soporte'];
    $priorities = ['critica', 'alta', 'media', 'baja'];
    $statuses = ['pendiente', 'en-progreso', 'en-revision', 'completada'];
    $developers = getDevelopers();
    
    $tasks = [];
    for ($i = 0; $i < 12; $i++) {
        $type = $types[array_rand($types)];
        $priority = $priorities[array_rand($priorities)];
        $status = $statuses[array_rand($statuses)];
        $assignee = $developers[array_rand($developers)];
        
        $tasks[] = [
            'id' => uniqid(),
            'title' => $titles[$type][array_rand($titles[$type])],
            'description' => "Tarea $type con prioridad $priority",
            'type' => $type,
            'priority' => $priority,
            'status' => $status,
            'assignee' => $assignee,
            'created_at' => date('Y-m-d H:i:s'),
            'due_date' => date('Y-m-d', strtotime('+' . rand(1, 14) . ' days')),
            'estimated_hours' => rand(2, 16),
            'tags' => array_slice(['frontend', 'backend', 'database', 'devops'], 0, rand(1, 3)),
            'project' => $projects[array_rand($projects)],
        ];
    }
    return $tasks;
}

function getDevelopers() {
    return [
        ['id' => '1', 'name' => 'Ana García', 'avatar' => 'AG', 'role' => 'Senior Frontend', 'skills' => ['React', 'TypeScript'], 'max_tasks' => 5, 'current_tasks' => 2],
        ['id' => '2', 'name' => 'Carlos López', 'avatar' => 'CL', 'role' => 'Backend Lead', 'skills' => ['Node.js', 'Python'], 'max_tasks' => 4, 'current_tasks' => 1],
        ['id' => '3', 'name' => 'María Rodríguez', 'avatar' => 'MR', 'role' => 'Full Stack', 'skills' => ['React', 'Node.js'], 'max_tasks' => 5, 'current_tasks' => 3],
        ['id' => '4', 'name' => 'Juan Martínez', 'avatar' => 'JM', 'role' => 'DevOps', 'skills' => ['Docker', 'AWS'], 'max_tasks' => 3, 'current_tasks' => 1],
        ['id' => '5', 'name' => 'Laura Sánchez', 'avatar' => 'LS', 'role' => 'Junior Developer', 'skills' => ['JavaScript', 'HTML'], 'max_tasks' => 4, 'current_tasks' => 2],
    ];
}
