import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('taskBoard', (updateUrlTemplate, initialTasks = [], initialCategoryTypes = []) => ({
    draggedTaskId: null,
    isUpdating: false,
    updateUrlTemplate: updateUrlTemplate || '',

    // Estado en memoria del tablero
    tasks: initialTasks,

    // Tipos de categoría genéricos
    categoryTypes: initialCategoryTypes,
    categorySelections: {},

    // Modal de detalle de tarea
    isTaskModalOpen: false,
    modalTask: null,

    // Imágenes subidas
    taskImages: [],
    uploadingImages: false,

    openTaskModal(task) {
        this.modalTask = task;
        this.isTaskModalOpen = true;
        this.taskImages = task.task_images ? [...task.task_images] : [];

        // Inicializar selección de categorías por tipo
        this.categorySelections = {};
        this.categoryTypes.forEach((type) => {
            this.categorySelections[type.slug] = {
                value_id: '',
                child_value_id: '',
                children: [],
            };
        });
    },

    closeTaskModal() {
        this.isTaskModalOpen = false;
        this.modalTask = null;
        this.categorySelections = {};
        this.taskImages = [];
    },

    async uploadImage(file) {
        if (!this.modalTask || this.uploadingImages) return;
        if (!file.type.startsWith('image/')) return;

        this.uploadingImages = true;
        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;

            const formData = new FormData();
            formData.append('image', file);

            const url = '{{ route('tasks.images.store', ['task' => 'TASK_ID_PLACEHOLDER']) }}'.replace('TASK_ID_PLACEHOLDER', this.modalTask.id);
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: formData,
            });

            if (res.ok) {
                const data = await res.json();
                this.taskImages.push(data);
            }
        } catch (e) {
            console.error('Error uploading image', e);
        } finally {
            this.uploadingImages = false;
        }
    },

    async deleteTaskImage(imageId) {
        if (!this.modalTask) return;
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;

        const url = '{{ route('tasks.images.destroy', ['task' => 'TASK_ID_PLACEHOLDER', 'image' => 'IMG_ID_PLACEHOLDER']) }}'
            .replace('TASK_ID_PLACEHOLDER', this.modalTask.id)
            .replace('IMG_ID_PLACEHOLDER', imageId);

        const res = await fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });

        if (res.ok) {
            this.taskImages = this.taskImages.filter(img => img.id !== imageId);
        }
    },

    handleImagePaste(event) {
        const items = event.clipboardData?.items ?? [];
        for (const item of items) {
            if (item.kind === 'file' && item.type.startsWith('image/')) {
                this.uploadImage(item.getAsFile());
                break;
            }
        }
    },

    handleImageDrop(event) {
        const files = event.dataTransfer?.files ?? [];
        for (const file of files) {
            if (file.type.startsWith('image/')) {
                this.uploadImage(file);
            }
        }
    },

    handleImageFileInput(event) {
        const files = event.target.files ?? [];
        for (const file of files) {
            this.uploadImage(file);
        }
        event.target.value = '';
    },

    onCategoryRootChange(typeSlug) {
        const selection = this.categorySelections[typeSlug];
        const typeData = this.categoryTypes.find(t => t.slug === typeSlug);
        if (!selection || !typeData) return;

        const rootId = Number(selection.value_id) || null;
        if (!rootId) {
            selection.children = [];
            selection.child_value_id = '';
            return;
        }

        const allValues = typeData.values || [];
        selection.children = allValues.filter(v => v.parent_id === rootId);
        selection.child_value_id = '';
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
