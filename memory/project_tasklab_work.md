---
name: TaskLab — trabajo en curso
description: Estado del proyecto TaskLab y cambios aplicados en sesiones de trabajo
type: project
---

TaskLab es una herramienta interna de gestión de tareas Laravel para Founderz. Stack: Laravel 12, Blade, Tailwind, Alpine.js, SQLite/Supabase, OpenAI.

**Cambio aplicado — 2026-03-11:**
Refactor del modal de tarea para que funcione en modo lista sin redirigir a kanban.

- Creado `task-detail-modal.blade.php` como componente Blade independiente (extraído de `task-kanban-board.blade.php`)
- `task-list-view.blade.php` ahora usa `x-data="taskBoard(...)"` y abre el modal inline con `openTaskModal()` en lugar de navegar a `tasks.show`
- `tasks/index.blade.php`: las 2 llamadas a `<x-task-list-view>` pasan `categoryTypes`, `users` y `open-task-id`
- `TaskController::show()` preserva `view` y `view_mode` del request

**Why:** El modo lista redirigía a `tasks.show` que hardcodeaba `view=board`, perdiendo el `view_mode=list`.
**How to apply:** El WORK_LOG del proyecto está en `TaskLab/docs/WORK_LOG.md`. Registrar allí cada cambio futuro.

El usuario quiere NO hacer commit hasta revisar. Registrar todo en `docs/WORK_LOG.md`.
