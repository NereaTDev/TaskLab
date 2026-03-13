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
    modalMode: 'view', // 'view' | 'edit' | 'create'

    openTaskModal(task) {
        this.modalTask = task;
        this.modalMode = 'view';
        this.isTaskModalOpen = true;

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

    openCreateTaskModal(defaults = {}) {
        this.modalTask = Object.assign({
            id: null,
            source: 'web_form',
            type: 'bug',
            priority: 'medium',
            status: 'new',
            title: '',
            description_raw: '',
            description_ai: null,
            requirements: [],
            test_cases: [],
            points: null,
            primary_url: null,
            additional_urls: [],
            impact: null,
        }, defaults);

        this.modalMode = 'create';
        this.isTaskModalOpen = true;

        this.categorySelections = {};
        this.categoryTypes.forEach((type) => {
            this.categorySelections[type.slug] = {
                value_id: '',
                child_value_id: '',
                children: [],
            };
        });
    },

    enterEditMode() {
        if (!this.modalTask || this.modalMode === 'create') return;
        this.modalMode = 'edit';
    },

    cancelEditMode() {
        if (!this.modalTask || this.modalMode !== 'edit') return;
        this.modalMode = 'view';
    },

    closeTaskModal() {
        this.isTaskModalOpen = false;
        this.modalTask = null;
        this.categorySelections = {};
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
