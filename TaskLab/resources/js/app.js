import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('taskBoard', (updateUrlTemplate) => ({
    draggedTaskId: null,
    isUpdating: false,
    updateUrlTemplate: updateUrlTemplate || '',

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

    async moveTaskToStatus(newStatus) {
        if (!this.draggedTaskId || this.isUpdating || !newStatus) {
            return;
        }

        this.isUpdating = true;
        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;
            const urlTemplate = this.updateUrlTemplate || '';
            const url = urlTemplate.replace('TASK_ID_PLACEHOLDER', this.draggedTaskId);

            const res = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ status: newStatus }),
            });

            if (!res.ok) {
                const text = await res.text();
                console.error('Failed to update task status', text);
            }

            window.location.reload();
        } catch (e) {
            console.error('Error updating task status', e);
        } finally {
            this.isUpdating = false;
            this.draggedTaskId = null;
        }
    },
}));

Alpine.start();
