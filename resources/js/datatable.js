export default (config = {}) => ({
    density: config.density || 'normal',
    selected: [],
    rowIds: config.rowIds || [],
    slideDownOpen: config.slideDownOpen ?? false,

    get densityClasses() {
        return {
            compact: 'px-3 py-1 text-sm',
            relaxed: 'px-4 py-4 text-base',
            normal: 'px-4 py-2.5 text-sm',
        }[this.density] || 'px-4 py-2.5 text-sm';
    },

    get headerDensityClasses() {
        return {
            compact: 'px-3 py-1.5 text-xs',
            relaxed: 'px-4 py-3 text-sm',
            normal: 'px-4 py-2 text-xs',
        }[this.density] || 'px-4 py-2 text-xs';
    },

    toggleRow(id) {
        const stringId = String(id);
        const index = this.selected.indexOf(stringId);

        if (index === -1) {
            this.selected.push(stringId);
        } else {
            this.selected.splice(index, 1);
        }
    },

    toggleAll() {
        if (this.isAllSelected) {
            // Deselect all current page rows
            this.selected = this.selected.filter(id => !this.rowIds.includes(id));
        } else {
            // Select all current page rows
            const newIds = this.rowIds.filter(id => !this.selected.includes(id));
            this.selected.push(...newIds);
        }
    },

    isSelected(id) {
        return this.selected.includes(String(id));
    },

    get isAllSelected() {
        if (this.rowIds.length === 0) return false;
        return this.rowIds.every(id => this.selected.includes(id));
    },

    get isIndeterminate() {
        if (this.rowIds.length === 0) return false;
        const someSelected = this.rowIds.some(id => this.selected.includes(id));
        return someSelected && !this.isAllSelected;
    },

    get selectedCount() {
        return this.selected.length;
    },

    get hasSelection() {
        return this.selected.length > 0;
    },

    clearSelection() {
        this.selected = [];
    },

    init() {
        this._onKeydown = (e) => {
            if (e.key === '/' && !this._isInputFocused()) {
                e.preventDefault();
                const searchInput = this.$root.querySelector('[data-datatable-search]');
                if (searchInput) {
                    searchInput.focus();
                }
            }
        };

        document.addEventListener('keydown', this._onKeydown);

        if (this.$wire) {
            // Sync rowIds when Livewire re-renders (pagination, filters, sort)
            this.$wire.on('kore:datatable-rows-updated', ({ rowIds }) => {
                this.rowIds = rowIds;
            });

            // Listen for clear-selection event from Livewire
            this.$wire.on('kore:datatable-clear-selection', () => {
                this.clearSelection();
            });
        }
    },

    destroy() {
        if (this._onKeydown) {
            document.removeEventListener('keydown', this._onKeydown);
        }
    },

    _isInputFocused() {
        const el = document.activeElement;
        return el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
    },
});
