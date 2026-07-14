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

// Selection checkboxes are plain inputs (no wire:model), so Livewire's morph
// updates their `checked`/`indeterminate` ATTRIBUTES but not the live DOM
// PROPERTIES. A header/row element reused across a morph would keep its stale
// visual state (e.g. the "select all" box staying checked on page 2 even though
// that page has no selected rows). Re-assert the server truth from
// data-checked / data-indeterminate after every morph. `indeterminate` has no
// HTML attribute at all, so this is the only way to drive it.
function syncSelectionCheckboxes(root) {
    if (!root) return;
    root.querySelectorAll('input[type="checkbox"][data-checked]').forEach(cb => {
        cb.checked = cb.dataset.checked === '1';
    });
    root.querySelectorAll('input[type="checkbox"][data-indeterminate]').forEach(cb => {
        cb.indeterminate = cb.dataset.indeterminate === '1';
    });
}

// Register a single global Livewire hook (not one per component) so pinned
// offsets and indeterminate checkboxes are recomputed after every morph, since
// PHP re-applies fixed offsets and indeterminate can't be server-rendered.
let pinnedMorphHookRegistered = false;
function ensurePinnedMorphHook() {
    if (pinnedMorphHookRegistered) return;
    if (typeof window === 'undefined' || !window.Livewire || !window.Livewire.hook) return;
    pinnedMorphHookRegistered = true;
    // Wrapped defensively: a throw here would break Livewire's morph cycle and
    // freeze wire:loading. el may be undefined depending on the Livewire build.
    window.Livewire.hook('morphed', (payload) => {
        try {
            const el = payload && payload.el;
            document.querySelectorAll('[data-kore-datatable]').forEach(root => {
                const related = !el || root === el ||
                    (typeof el.contains === 'function' && (root.contains(el) || el.contains(root)));
                if (related) {
                    recalcPinnedOffsets(root);
                    syncSelectionCheckboxes(root);
                }
            });
        } catch (e) {
            // pinning recalculation must never interfere with morphs
        }
    });
}

export default (config = {}) => ({
    density: config.density || 'normal',
    // Selection state lives server-side (public $selected / $selectAllMatching
    // on the Livewire component) so it survives morphs and pagination. The only
    // selection bit kept on the client is lastSelectedIndex, used to compute the
    // shift-click range from the visible rowIds.
    lastSelectedIndex: null,
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

    // Sin declarar, Alpine las escribiría en el x-data ancestro, no aquí: dos tablas en la
    // misma página compartirían el ResizeObserver y sólo una se desengancharía.
    // Ver tests/js/alpine-scope.test.js.
    _copyTimer: null,
    _onKeydown: null,
    _resizeObserver: null,

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

    // --- Selection (server-driven) ---

    // Row checkbox click. A normal click toggles a single row server-side; the
    // native checkbox flips instantly for optimistic feedback and the morph
    // confirms it. Shift-click selects the contiguous range from the last
    // toggled row — computed here from the visible rowIds and sent to the
    // server in a single selectRange() call.
    onRowCheckboxClick(id, event) {
        const stringId = String(id);
        const index = this.rowIds.indexOf(stringId);

        if (event && event.shiftKey && this.lastSelectedIndex !== null && index !== -1) {
            event.preventDefault();
            const [from, to] = [this.lastSelectedIndex, index].sort((a, b) => a - b);
            const range = [];
            for (let i = from; i <= to; i++) {
                if (this.rowIds[i] !== undefined) range.push(this.rowIds[i]);
            }
            this.lastSelectedIndex = index;
            this.$wire.selectRange(range);
            return;
        }

        if (index !== -1) this.lastSelectedIndex = index;
        this.$wire.toggleRow(stringId);
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

    async copyToClipboard(text, key = null) {
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
            // Track feedback by a unique key (rowId+field) so cells sharing the
            // same value don't all light up the "copied" check.
            this.copyFeedback = key ?? text;
            clearTimeout(this._copyTimer);
            this._copyTimer = setTimeout(() => { this.copyFeedback = null; }, 2000);
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
            const index = this.rowIds.indexOf(String(rowId));
            if (index !== -1) this.lastSelectedIndex = index;
            this.$wire.toggleRow(String(rowId));
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
        // Compare against the container width (not the viewport) so a datatable
        // inside a narrow panel collapses even on a wide screen.
        const width = this.$root ? this.$root.clientWidth : window.innerWidth;
        this.isMobileView = width < this.responsiveBreakpoint;
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
                        this.$wire.toggleSelectAll();
                    }
                    break;
            }
        };

        document.addEventListener('keydown', this._onKeydown);

        if (this.responsiveMode !== 'scroll') {
            this.checkBreakpoint();
        }

        // A single ResizeObserver on the root drives both the responsive
        // breakpoint (container-based) and pinned-offset recalculation,
        // throttled to one pass per animation frame to avoid layout thrashing.
        if (typeof ResizeObserver !== 'undefined') {
            let scheduled = false;
            this._resizeObserver = new ResizeObserver(() => {
                if (scheduled) return;
                scheduled = true;
                requestAnimationFrame(() => {
                    scheduled = false;
                    if (this.responsiveMode !== 'scroll') this.checkBreakpoint();
                    recalcPinnedOffsets(this.$root);
                });
            });
            this._resizeObserver.observe(this.$root);
        }

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
                this.lastSelectedIndex = null;
                this.$nextTick(() => recalcPinnedOffsets(this.$root));
            });

            // Selection is cleared server-side after a bulk action; only the
            // client-side shift anchor needs resetting here.
            this.$wire.on('kore:datatable-clear-selection', () => {
                this.lastSelectedIndex = null;
            });
        }
    },

    destroy() {
        if (this._onKeydown) {
            document.removeEventListener('keydown', this._onKeydown);
        }
        if (this._resizeObserver) {
            this._resizeObserver.disconnect();
        }
        clearTimeout(this._copyTimer);
    },

    _isInputFocused() {
        const el = document.activeElement;
        return el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
    },
});
