// --- Column pinning: measure real widths and apply cumulative sticky offsets ---
// PHP sets initial offsets assuming a fixed width; this corrects them against
// the actual rendered widths so multiple pinned columns line up exactly.
function recalcPinnedOffsets(root) {
    if (!root) return;
    const tables = Array.from(root.querySelectorAll('table'));
    const table = tables.find(t => t.querySelector('[data-pinned]'));
    if (!table || table.getBoundingClientRect().width === 0) return;

    const headerCells = Array.from(table.querySelectorAll('thead th[data-pinned]'));

    let leftOffset = 0;
    headerCells.filter(th => th.dataset.pinned === 'left').forEach(th => {
        const idx = th.dataset.colIndex;
        const width = th.getBoundingClientRect().width;
        table.querySelectorAll(`[data-col-index="${idx}"][data-pinned="left"]`)
            .forEach(cell => { cell.style.left = leftOffset + 'px'; });
        leftOffset += width;
    });

    let rightOffset = 0;
    headerCells.filter(th => th.dataset.pinned === 'right').reverse().forEach(th => {
        const idx = th.dataset.colIndex;
        const width = th.getBoundingClientRect().width;
        table.querySelectorAll(`[data-col-index="${idx}"][data-pinned="right"]`)
            .forEach(cell => { cell.style.right = rightOffset + 'px'; });
        rightOffset += width;
    });
}

// Register a single global Livewire hook (not one per component) so pinned
// offsets are recomputed after every morph, since PHP re-applies fixed offsets.
let pinnedMorphHookRegistered = false;
function ensurePinnedMorphHook() {
    if (pinnedMorphHookRegistered) return;
    if (typeof window === 'undefined' || !window.Livewire || !window.Livewire.hook) return;
    pinnedMorphHookRegistered = true;
    window.Livewire.hook('morphed', ({ el }) => {
        document.querySelectorAll('[data-kore-datatable]').forEach(root => {
            if (root === el || root.contains(el) || el.contains(root)) {
                recalcPinnedOffsets(root);
            }
        });
    });
}

export default (config = {}) => ({
    density: config.density || 'normal',
    selected: [],
    selectAllMatching: false,
    rowIds: config.rowIds || [],
    totalRows: config.totalRows || 0,
    slideDownOpen: config.slideDownOpen ?? false,
    responsiveMode: config.responsiveMode || 'scroll',
    responsiveBreakpoint: config.responsiveBreakpoint || 768,
    isMobileView: false,
    expandedRows: [],

    // Keyboard navigation
    activeRow: -1,
    activeCell: -1,
    keyboardMode: false,

    // Copyable feedback
    copyFeedback: null,

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

    get activeRowId() {
        if (this.activeRow < 0 || this.activeRow >= this.rowIds.length) return null;
        return this.rowIds[this.activeRow];
    },

    toggleRow(id) {
        // Manually toggling a row exits "select all matching" mode and falls
        // back to an explicit selection of the current page.
        if (this.selectAllMatching) {
            this.selectAllMatching = false;
            this.selected = [...this.rowIds];
        }

        const stringId = String(id);
        const index = this.selected.indexOf(stringId);

        if (index === -1) {
            this.selected.push(stringId);
        } else {
            this.selected.splice(index, 1);
        }
    },

    toggleAll() {
        if (this.selectAllMatching) {
            this.clearSelection();
            return;
        }

        if (this.isAllSelected) {
            this.selected = this.selected.filter(id => !this.rowIds.includes(id));
        } else {
            const newIds = this.rowIds.filter(id => !this.selected.includes(id));
            this.selected.push(...newIds);
        }
    },

    isSelected(id) {
        return this.selectAllMatching || this.selected.includes(String(id));
    },

    get isAllSelected() {
        if (this.selectAllMatching) return true;
        if (this.rowIds.length === 0) return false;
        return this.rowIds.every(id => this.selected.includes(id));
    },

    get isIndeterminate() {
        if (this.selectAllMatching || this.rowIds.length === 0) return false;
        const someSelected = this.rowIds.some(id => this.selected.includes(id));
        return someSelected && !this.isAllSelected;
    },

    get selectedCount() {
        return this.selected.length;
    },

    get hasSelection() {
        return this.selected.length > 0 || this.selectAllMatching;
    },

    // True when the selection includes rows that are not on the current page.
    get hasOffPageSelection() {
        return this.selected.some(id => !this.rowIds.includes(String(id)));
    },

    // Offer "select all matching" once the whole page is selected and more
    // rows match the current filters beyond this page.
    get canSelectAllMatching() {
        return !this.selectAllMatching
            && this.isAllSelected
            && this.totalRows > this.selectedCount;
    },

    enableSelectAllMatching() {
        this.selectAllMatching = true;
    },

    // Dispatch a bulk action. In "all matching" mode the backend resolves the
    // ids from the filtered query instead of the client-provided list.
    runBulk(identifier) {
        if (this.selectAllMatching) {
            this.$wire.executeBulkActionMatching(identifier);
        } else {
            this.$wire.executeBulkAction(identifier, this.selected);
        }
    },

    clearSelection() {
        this.selected = [];
        this.selectAllMatching = false;
    },

    toggleExpand(id) {
        const stringId = String(id);
        const index = this.expandedRows.indexOf(stringId);

        if (index === -1) {
            this.expandedRows.push(stringId);
        } else {
            this.expandedRows.splice(index, 1);
        }
    },

    isExpanded(id) {
        return this.expandedRows.includes(String(id));
    },

    // --- Inline Editing (boolean toggle only, text editing is local x-data) ---

    async toggleBooleanEdit(rowId, field, currentValue) {
        try {
            await this.$wire.updateCell(String(rowId), field, !currentValue);
        } catch (e) {
            // silent
        }
    },

    // --- Copyable ---

    async copyToClipboard(text) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                // Fallback for non-secure contexts
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
            this.copyFeedback = text;
            setTimeout(() => { this.copyFeedback = null; }, 2000);
        } catch (e) {
            // silent
        }
    },

    // --- Keyboard Navigation ---

    moveDown() {
        if (this.activeRow < this.rowIds.length - 1) {
            this.activeRow++;
            this.keyboardMode = true;
            this.scrollActiveRowIntoView();
        }
    },

    moveUp() {
        if (this.activeRow > 0) {
            this.activeRow--;
            this.keyboardMode = true;
            this.scrollActiveRowIntoView();
        } else if (this.activeRow === -1 && this.rowIds.length > 0) {
            this.activeRow = 0;
            this.keyboardMode = true;
            this.scrollActiveRowIntoView();
        }
    },

    moveRight() {
        if (this.activeRow < 0) return;
        const totalCols = this.$root.querySelectorAll('thead th').length;
        if (this.activeCell < totalCols - 1) {
            this.activeCell++;
        }
    },

    moveLeft() {
        if (this.activeRow < 0) return;
        if (this.activeCell > 0) {
            this.activeCell--;
        }
    },

    activateCurrentCell() {
        if (this.activeRow < 0) return;
        const rowId = this.activeRowId;
        if (!rowId) return;

        const row = this.$root.querySelector(`tr[data-row-id="${rowId}"]`);
        if (!row) return;

        const cells = row.querySelectorAll('td');
        const cell = cells[this.activeCell >= 0 ? this.activeCell : 0];
        if (!cell) return;

        // Click the editable display div or boolean toggle
        const clickable = cell.querySelector('[data-editable-display]') || cell.querySelector('[data-boolean-toggle]');
        if (clickable) {
            clickable.click();
        }
    },

    toggleCurrentRowSelection() {
        if (this.activeRow < 0) return;
        const rowId = this.activeRowId;
        if (rowId) {
            this.toggleRow(rowId);
        }
    },

    scrollActiveRowIntoView() {
        this.$nextTick(() => {
            const rowId = this.activeRowId;
            if (!rowId) return;
            const row = this.$root.querySelector(`tr[data-row-id="${rowId}"]`);
            if (row) {
                row.scrollIntoView({ block: 'nearest' });
            }
        });
    },

    // --- Responsive ---

    checkBreakpoint() {
        this.isMobileView = window.innerWidth < this.responsiveBreakpoint;
    },

    // --- Lifecycle ---

    init() {
        // Normalize ids to strings once so every selection comparison is
        // type-consistent regardless of the model's key type.
        this.rowIds = (this.rowIds || []).map(String);

        this._onKeydown = (e) => {
            // Skip keyboard nav when any input is focused
            if (this._isInputFocused()) {
                if (e.key === 'Escape') {
                    document.activeElement.blur();
                }
                return;
            }

            switch (e.key) {
                case '/':
                    e.preventDefault();
                    const searchInput = this.$root.querySelector('[data-datatable-search]');
                    if (searchInput) searchInput.focus();
                    break;

                case 'ArrowDown':
                    e.preventDefault();
                    this.moveDown();
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    this.moveUp();
                    break;

                case 'ArrowRight':
                    if (this.activeRow >= 0) {
                        e.preventDefault();
                        this.moveRight();
                    }
                    break;

                case 'ArrowLeft':
                    if (this.activeRow >= 0) {
                        e.preventDefault();
                        this.moveLeft();
                    }
                    break;

                case 'Enter':
                    if (this.activeRow >= 0) {
                        e.preventDefault();
                        this.activateCurrentCell();
                    }
                    break;

                case ' ':
                    if (this.activeRow >= 0) {
                        e.preventDefault();
                        this.toggleCurrentRowSelection();
                    }
                    break;

                case 'Escape':
                    this.activeRow = -1;
                    this.activeCell = -1;
                    this.keyboardMode = false;
                    break;

                case 'a':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        this.toggleAll();
                    }
                    break;
            }
        };

        document.addEventListener('keydown', this._onKeydown);

        // Breakpoint check + pinned-column offset recalculation on resize,
        // throttled to one pass per animation frame to avoid layout thrashing.
        if (this.responsiveMode !== 'scroll') {
            this.checkBreakpoint();
        }

        let resizeScheduled = false;
        this._onResize = () => {
            if (resizeScheduled) return;
            resizeScheduled = true;
            requestAnimationFrame(() => {
                resizeScheduled = false;
                if (this.responsiveMode !== 'scroll') this.checkBreakpoint();
                recalcPinnedOffsets(this.$root);
            });
        };
        window.addEventListener('resize', this._onResize);

        // Pinned columns: correct offsets after first paint and after morphs.
        this.$nextTick(() => recalcPinnedOffsets(this.$root));
        ensurePinnedMorphHook();

        if (this.$wire) {
            this.$wire.on('kore:datatable-rows-updated', ({ rowIds, total }) => {
                this.rowIds = (rowIds || []).map(String);
                if (typeof total !== 'undefined') this.totalRows = total;
                this.activeRow = -1;
                this.activeCell = -1;
                this.keyboardMode = false;
                this.$nextTick(() => recalcPinnedOffsets(this.$root));
            });

            this.$wire.on('kore:datatable-clear-selection', () => {
                this.clearSelection();
            });
        }
    },

    destroy() {
        if (this._onKeydown) {
            document.removeEventListener('keydown', this._onKeydown);
        }
        if (this._onResize) {
            window.removeEventListener('resize', this._onResize);
        }
    },

    _isInputFocused() {
        const el = document.activeElement;
        return el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
    },
});
