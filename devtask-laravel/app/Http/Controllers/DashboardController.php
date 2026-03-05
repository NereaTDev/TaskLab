<?php

class DashboardController {
    public function index() {
        $tasks = $_SESSION['tasks'];
        $developers = $_SESSION['developers'];
        $projects = array_unique(array_column($tasks, 'project'));
        
        // Apply filters
        $filteredTasks = $this->applyFilters($tasks);
        
        // Calculate stats
        $stats = $this->calculateStats($tasks);
        
        // Get filter values from query
        $filters = [
            'search' => $_GET['search'] ?? '',
            'types' => $_GET['types'] ?? [],
            'priorities' => $_GET['priorities'] ?? [],
            'statuses' => $_GET['statuses'] ?? [],
            'assignees' => $_GET['assignees'] ?? [],
        ];
        
        require __DIR__ . '/../../../resources/views/dashboard/index.php';
    }
    
    private function applyFilters($tasks) {
        $search = $_GET['search'] ?? '';
        $types = $_GET['types'] ?? [];
        $priorities = $_GET['priorities'] ?? [];
        $statuses = $_GET['statuses'] ?? [];
        $assignees = $_GET['assignees'] ?? [];
        
        return array_filter($tasks, function($task) use ($search, $types, $priorities, $statuses, $assignees) {
            if ($search && !str_contains(strtolower($task['title']), strtolower($search))) {
                return false;
            }
            if (!empty($types) && !in_array($task['type'], $types)) {
                return false;
            }
            if (!empty($priorities) && !in_array($task['priority'], $priorities)) {
                return false;
            }
            if (!empty($statuses) && !in_array($task['status'], $statuses)) {
                return false;
            }
            if (!empty($assignees) && !in_array($task['assignee']['id'], $assignees)) {
                return false;
            }
            return true;
        });
    }
    
    private function calculateStats($tasks) {
        $now = time();
        $overdue = 0;
        
        foreach ($tasks as $task) {
            if ($task['status'] !== 'completada' && strtotime($task['due_date']) < $now) {
                $overdue++;
            }
        }
        
        return [
            'total' => count($tasks),
            'pending' => count(array_filter($tasks, fn($t) => $t['status'] === 'pendiente')),
            'in_progress' => count(array_filter($tasks, fn($t) => $t['status'] === 'en-progreso')),
            'completed' => count(array_filter($tasks, fn($t) => $t['status'] === 'completada')),
            'overdue' => $overdue,
            'by_type' => [
                'evolutiva' => count(array_filter($tasks, fn($t) => $t['type'] === 'evolutiva')),
                'correctiva' => count(array_filter($tasks, fn($t) => $t['type'] === 'correctiva')),
                'preventiva' => count(array_filter($tasks, fn($t) => $t['type'] === 'preventiva')),
                'soporte' => count(array_filter($tasks, fn($t) => $t['type'] === 'soporte')),
            ],
            'by_priority' => [
                'critica' => count(array_filter($tasks, fn($t) => $t['priority'] === 'critica')),
                'alta' => count(array_filter($tasks, fn($t) => $t['priority'] === 'alta')),
                'media' => count(array_filter($tasks, fn($t) => $t['priority'] === 'media')),
                'baja' => count(array_filter($tasks, fn($t) => $t['priority'] === 'baja')),
            ],
        ];
    }
}

$controller = new DashboardController();
$controller->index();
