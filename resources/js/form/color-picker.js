import { startFloating, stopFloating } from '../utils/floating.js';

export default (config) => ({
    value: null,
    open: false,
    customHex: '',

    // Sin declarar, Alpine las escribiría en el x-data ancestro, no aquí.
    // Ver tests/js/alpine-scope.test.js.
    _floatingCleanup: null,
    _boundMousedown: null,

    init() {
        const val = this.$refs.hiddenInput?.value;
        if (val) {
            this.value = val;
            this.customHex = val;
        }

        // Watch Livewire changes
        const input = this.$refs.hiddenInput;
        if (input && this.$wire) {
            const wireModel = input.getAttribute('wire:model.live')
                || input.getAttribute('wire:model.blur')
                || input.getAttribute('wire:model.defer')
                || input.getAttribute('wire:model');
            if (wireModel) {
                this.$wire.$watch(wireModel, (val) => {
                    this.value = val || null;
                    this.customHex = val || '';
                });
            }
        }
    },

    destroy() {
        this._cleanup();
    },

    // Dropdown
    toggle() {
        this.open ? this.close() : this.openPanel();
    },

    openPanel() {
        this.open = true;
        this.customHex = this.value || '';
        this.$nextTick(() => {
            if (!config.inline) {
                this._floatingCleanup = startFloating(this.$refs.trigger, this.$refs.dropdown, {
                    placement: 'bottom-start',
                    offset: 4,
                    sameWidth: true,
                });
            }
            this._addClickAwayListener();
        });
    },

    close() {
        if (!this.open) return;
        this.open = false;
        this._cleanup();
    },

    /**
     * Escape cierra el panel.
     *
     * No lo hacía: el panel solo se cerraba con un clic fuera, y dentro de un
     * modal ese Escape sin dueño llegaba al overlay manager, que cerraba el
     * modal entero. El `preventDefault()` es lo que marca el evento como
     * atendido para que el manager lo descarte, y por eso solo se llama cuando
     * hay algo que cerrar de verdad.
     */
    onEscape(e) {
        if (!this.open) return;

        e.preventDefault();
        this.close();
        this.$refs.trigger?.focus();
    },

    _cleanup() {
        stopFloating(this._floatingCleanup);
        this._floatingCleanup = null;
        this._removeClickAwayListener();
    },

    selectColor(hex) {
        this.value = hex;
        this.customHex = hex;
        this._sync();
        if (!config.inline) this.close();
    },

    applyCustom() {
        const hex = this.customHex.trim();
        if (!this.isValidHex(hex)) return;
        this.value = hex.startsWith('#') ? hex : '#' + hex;
        this.customHex = this.value;
        this._sync();
        if (!config.inline) this.close();
    },

    clear() {
        this.value = null;
        this.customHex = '';
        this._sync();
    },

    isValidHex(hex) {
        return /^#?([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(hex);
    },

    isLight(hex) {
        const c = hex.replace('#', '');
        const full = c.length === 3
            ? c[0]+c[0]+c[1]+c[1]+c[2]+c[2]
            : c;
        const r = parseInt(full.substring(0, 2), 16);
        const g = parseInt(full.substring(2, 4), 16);
        const b = parseInt(full.substring(4, 6), 16);
        return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.6;
    },

    _sync() {
        if (!this.$refs.hiddenInput) return;
        this.$refs.hiddenInput.value = this.value || '';
        this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    },

    _onMousedown(e) {
        if (!this.open) return;
        if (this.$refs.trigger?.contains(e.target)) return;
        if (this.$refs.dropdown?.contains(e.target)) return;
        this.close();
    },

    _addClickAwayListener() {
        this._removeClickAwayListener();
        this._boundMousedown = (e) => this._onMousedown(e);
        document.addEventListener('mousedown', this._boundMousedown);
    },

    _removeClickAwayListener() {
        if (this._boundMousedown) {
            document.removeEventListener('mousedown', this._boundMousedown);
            this._boundMousedown = null;
        }
    },
});
