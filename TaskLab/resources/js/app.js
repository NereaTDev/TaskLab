import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('taskBoard', (updateUrlTemplate, initialTasks = []) => ({
    draggedTaskId: null,
    isUpdating: false,
    updateUrlTemplate: updateUrlTemplate || '',

    // Estado en memoria del tablero
    tasks: initialTasks,

    // Modal de detalle de tarea
    isTaskModalOpen: false,
    modalTask: null,

    openTaskModal(task) {
        this.modalTask = task;
        this.isTaskModalOpen = true;
    },

    closeTaskModal() {
        this.isTaskModalOpen = false;
        this.modalTask = null;
    },

    // Devuelve las tareas cuya columna contenga alguno de los estados indicados
    columnTasks(statuses) {
        return this.tasks.filter(t => statuses.includes(t.status));
    },

    async moveTaskToStatus(newStatus) {
        const currentTaskId = this.draggedTaskId;

        if (!currentTaskId || this.isUpdating || !newStatus) {
            return;
        }

        this.isUpdating = true;
        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;
            const urlTemplate = this.updateUrlTemplate || '';
            const url = urlTemplate.replace('TASK_ID_PLACEHOLDER', currentTaskId);

            const res = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status: newStatus }),
            });

            if (!res.ok) {
                return;
            }

            // Actualizamos el estado local (versión fluida)
            const taskIdNum = Number(currentTaskId);
            const idx = this.tasks.findIndex(t => Number(t.id) === taskIdNum);
            if (idx !== -1) {
                this.tasks[idx].status = newStatus;
            }

            // Fallback defensivo: mover también la card en el DOM
            try {
                const cardSelector = `[data-task-id="${currentTaskId}"]`;
                const targetColumnSelector = `[data-column-body="${newStatus}"]`;
                const cardEl = document.querySelector(cardSelector);
                const targetColumnBody = document.querySelector(targetColumnSelector);
                if (cardEl && targetColumnBody) {
                    targetColumnBody.appendChild(cardEl);
                }
            } catch (_) {
                // Silenciamos errores de DOM fallback
            }
        } catch (e) {
            console.error('Error updating task status', e);
        } finally {
            this.isUpdating = false;
            this.draggedTaskId = null;
        }
    },
}));

Alpine.start();
