# TaskLab — Registro de trabajo y requerimientos

Este documento registra los cambios realizados, decisiones tomadas y requerimientos identificados durante el desarrollo continuo de TaskLab.

---

## 2026-03-11

### Análisis: Bug — Apertura de tarea en modo lista fuerza vista kanban

**Requerimiento identificado:**
Al abrir una tarea desde el modo lista (`view_mode=list`), la pantalla vuelve a la vista kanban en lugar de mantener el modo lista.

**Causa raíz:**
`TaskController::show()` hace un redirect hardcodeado a `view=board` sin preservar el parámetro `view_mode`:

```php
// app/Http/Controllers/TaskController.php
return redirect()->route('tasks.index', ['view' => 'board', 'task' => $task->id]);
```

Adicionalmente, `task-list-view.blade.php` navega a `tasks.show` en lugar de abrir un modal inline como hace el kanban.

**Soluciones propuestas:**

**Opción A — Parche rápido** (1 archivo):
- Cambiar el `onclick` en `task-list-view.blade.php` para navegar directamente a `tasks.index` preservando todos los query params incluido `view_mode=list`, saltándose el redirect problemático.

**Opción B — Refactor limpio** (recomendada):
- Extraer el modal de tarea de `task-kanban-board.blade.php` a un componente Blade independiente.
- Incluir ese modal también en `task-list-view.blade.php`.
- Añadir la lógica Alpine.js `openTaskModal(task)` al click de cada fila en lista.
- Corregir `TaskController::show()` para preservar el `view_mode` recibido en el request.

**Archivos implicados:**
- `app/Http/Controllers/TaskController.php`
- `resources/views/components/task-list-view.blade.php`
- `resources/views/components/task-kanban-board.blade.php`
- (Opción B) nuevo componente: `resources/views/components/task-detail-modal.blade.php`

**Estado:** Aplicado el 2026-03-11 — Opción B (refactor limpio)

**Cambios aplicados:**

| Archivo | Acción |
|---------|--------|
| `resources/views/components/task-detail-modal.blade.php` | **Creado** — modal extraído del kanban como componente independiente. Props: `categoryTypes`, `users`. |
| `resources/views/components/task-kanban-board.blade.php` | Reemplazado el bloque del modal (≈300 líneas) por `<x-task-detail-modal :categoryTypes="$categoryTypes" :users="$users" />` |
| `resources/views/components/task-list-view.blade.php` | Añadido `x-data="taskBoard(...)"` con todas las tareas serializadas. Rows cambiadas de `onclick="window.location=..."` a `@click.stop="openTaskModal(tasks.find(...))"`. Nuevo prop `openTaskId` con `x-init`. Incluye `<x-task-detail-modal>` al final. Props nuevas: `categoryTypes`, `users`, `openTaskId`. |
| `resources/views/tasks/index.blade.php` | Actualizadas las 2 llamadas a `<x-task-list-view>` (board y dashboard) para pasar `categoryTypes`, `users` y `open-task-id`. |
| `app/Http/Controllers/TaskController.php` | Corregido `show()` para preservar `view` y `view_mode` del request en lugar de hardcodear `view=board`. |

---

<!-- Las siguientes entradas se añadirán conforme se vayan aplicando cambios -->
