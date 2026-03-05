<?php

class TaskController {
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $developers = $_SESSION['developers'];
            $assignee = null;
            
            if (!empty($_POST['assignee_id'])) {
                foreach ($developers as $dev) {
                    if ($dev['id'] === $_POST['assignee_id']) {
                        $assignee = $dev;
                        break;
                    }
                }
            }
            
            // Auto-assign if enabled and no assignee
            $autoAssign = $_SESSION['auto_assign'] ?? true;
            if ($autoAssign && !$assignee) {
                $assignee = $this->getAvailableDeveloper($developers);
            }
            
            $newTask = [
                'id' => uniqid(),
                'title' => $_POST['title'],
                'description' => $_POST['description'] ?? '',
                'type' => $_POST['type'],
                'priority' => $_POST['priority'],
                'status' => 'pendiente',
                'assignee' => $assignee,
                'created_at' => date('Y-m-d H:i:s'),
                'due_date' => $_POST['due_date'] ?? null,
                'estimated_hours' => (int)($_POST['estimated_hours'] ?? 4),
                'tags' => explode(',', $_POST['tags'] ?? ''),
                'project' => $_POST['project'] ?? 'General',
            ];
            
            $_SESSION['tasks'][] = $newTask;
            
            // Update developer task count
            if ($assignee) {
                foreach ($_SESSION['developers'] as &$dev) {
                    if ($dev['id'] === $assignee['id']) {
                        $dev['current_tasks']++;
                        break;
                    }
                }
            }
            
            header('Location: /');
            exit;
        }
    }
    
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskId = $_POST['task_id'];
            $newStatus = $_POST['status'];
            
            foreach ($_SESSION['tasks'] as &$task) {
                if ($task['id'] === $taskId) {
                    $task['status'] = $newStatus;
                    break;
                }
            }
            
            header('Location: /');
            exit;
        }
    }
    
    public function assign() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskId = $_POST['task_id'];
            $developerId = $_POST['developer_id'];
            
            $newAssignee = null;
            foreach ($_SESSION['developers'] as $dev) {
                if ($dev['id'] === $developerId) {
                    $newAssignee = $dev;
                    break;
                }
            }
            
            foreach ($_SESSION['tasks'] as &$task) {
                if ($task['id'] === $taskId) {
                    // Decrement old assignee
                    if ($task['assignee']) {
                        foreach ($_SESSION['developers'] as &$dev) {
                            if ($dev['id'] === $task['assignee']['id']) {
                                $dev['current_tasks'] = max(0, $dev['current_tasks'] - 1);
                                break;
                            }
                        }
                    }
                    
                    $task['assignee'] = $newAssignee;
                    
                    // Increment new assignee
                    if ($newAssignee) {
                        foreach ($_SESSION['developers'] as &$dev) {
                            if ($dev['id'] === $newAssignee['id']) {
                                $dev['current_tasks']++;
                                break;
                            }
                        }
                    }
                    break;
                }
            }
            
            header('Location: /');
            exit;
        }
    }
    
    private function getAvailableDeveloper($developers) {
        $available = array_filter($developers, fn($d) => $d['current_tasks'] < $d['max_tasks']);
        if (empty($available)) return null;
        
        usort($available, fn($a, $b) => $a['current_tasks'] <=> $b['current_tasks']);
        return $available[0];
    }
}

// Handle actions
$action = $_GET['action'] ?? '';
$controller = new TaskController();

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'update-status':
        $controller->updateStatus();
        break;
    case 'assign':
        $controller->assign();
        break;
}
