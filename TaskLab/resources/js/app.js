import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ---------------------------------------------------------------------------
// taskModal — global component, mounted in the layout
// Listens to window events so any view can open create/view/edit modals
// ---------------------------------------------------------------------------
Alpine.data('taskModal', (initialCategoryTypes = []) => ({
    isTaskModalOpen: false,
    modalTask: null,
    modalMode: 'view', // 'view' | 'edit' | 'create'

    categoryTypes: initialCategoryTypes,
    categorySelections: {},

    init() {
        window.addEventListener('open-task-modal', (e) => {
            this.openTaskModal(e.detail);
        });
        window.addEventListener('open-create-task', (e) => {
            this.openCreateTaskModal(e.detail || {});
        });
    },

    openTaskModal(task) {
        this.modalTask = task;
        this.modalMode = 'view';
        this.isTaskModalOpen = true;
        this._initCategorySelections();
    },

    openCreateTaskModal(defaults = {}) {
        this.modalTask = Object.assign({
            id: null,
            source: 'web_form',
            type: 'bug',
            priority: 'medium',
            status: defaults.status || 'new',
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
        this._initCategorySelections();
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

    _initCategorySelections() {
        this.categorySelections = {};
        this.categoryTypes.forEach((type) => {
            this.categorySelections[type.slug] = {
                value_id: '',
                child_value_id: '',
                children: [],
            };
        });
    },
}));

// ---------------------------------------------------------------------------
// taskBoard — kanban / list board logic (drag-drop + task list state)
// Modal is now global; this component dispatches window events to open it
// ---------------------------------------------------------------------------
Alpine.data('taskBoard', (updateUrlTemplate, initialTasks = []) => ({
    draggedTaskId: null,
    isUpdating: false,
    updateUrlTemplate: updateUrlTemplate || '',
    tasks: initialTasks,

    openTaskModal(task) {
        window.dispatchEvent(new CustomEvent('open-task-modal', { detail: task }));
    },

    openCreateTaskModal(defaults = {}) {
        window.dispatchEvent(new CustomEvent('open-create-task', { detail: defaults }));
    },

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

            const taskIdNum = Number(currentTaskId);
            const idx = this.tasks.findIndex(t => Number(t.id) === taskIdNum);
            if (idx !== -1) {
                this.tasks[idx].status = newStatus;
            }

            try {
                const cardSelector = `[data-task-id="${currentTaskId}"]`;
                const targetColumnSelector = `[data-column-body="${newStatus}"]`;
                const cardEl = document.querySelector(cardSelector);
                const targetColumnBody = document.querySelector(targetColumnSelector);
                if (cardEl && targetColumnBody) {
                    targetColumnBody.appendChild(cardEl);
                }
            } catch (_) {
                // silenciar errores de DOM fallback
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
