import { startFloating, stopFloating } from '../utils/floating.js';

export default (config) => ({
    open: false,
    highlighted: -1,

    // Lo que no se declara aquí, Alpine lo escribe en el x-data ancestro más externo, no en
    // el componente. Ver tests/js/alpine-scope.test.js.
    _clickAwayHandler: null,
    _floatingCleanup: null,

    init() {
        // Permanent click-away listener — survives Livewire re-renders
        this._clickAwayHandler = (e) => {
            if (!this.open) return;
            if (this.$refs.trigger?.contains(e.target)) return;
            if (this.$refs.dropdown?.contains(e.target)) return;
            // Don't close if clicking inside a child floating panel
            // (teleported select options, datepicker, etc.)
            if (e.target.closest('[role="listbox"], [role="dialog"]')) return;
            this.close();
        };
        document.addEventListener('mousedown', this._clickAwayHandler);
    },

    destroy() {
        stopFloating(this._floatingCleanup);
        this._floatingCleanup = null;
        if (this._clickAwayHandler) {
            document.removeEventListener('mousedown', this._clickAwayHandler);
            this._clickAwayHandler = null;
        }
    },

    toggle() {
        this.open ? this.close() : this.openDropdown();
    },

    openDropdown() {
        this.open = true;
        this.highlighted = -1;
        // Pre-apply position:fixed + visibility:hidden before $nextTick so the
        // panel never exists in normal document flow when Alpine makes it visible.
        // This prevents the ~17px scrollbar layout shift on h-screen layouts.
        if (this.$refs.dropdown) {
            this.$refs.dropdown.style.position = 'fixed';
            this.$refs.dropdown.style.visibility = 'hidden';
        }
        this.$nextTick(() => {
            const numericWidth = Number(config.width);
            const widthMap = { sm: 128, md: 192, lg: 256 };
            const fixedWidth = numericWidth > 0
                ? numericWidth
                : (widthMap[config.width] || null);

            this._floatingCleanup = startFloating(this.$refs.trigger, this.$refs.dropdown, {
                placement: config.position?.replace('end', 'end').replace('start', 'start') || 'bottom-start',
                offset: 4,
                sameWidth: config.width === 'auto',
                fixedWidth: fixedWidth,
            });
        });
    },

    close() {
        if (!this.open) return;
        this.open = false;
        this.highlighted = -1;
        stopFloating(this._floatingCleanup);
        this._floatingCleanup = null;
    },

    get _items() {
        if (!this.$refs.dropdown) return [];
        return [...this.$refs.dropdown.querySelectorAll('[role="menuitem"]:not([disabled]):not([aria-disabled="true"])')];
    },

    onKeydown(e) {
        const items = this._items;
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (!this.open) { this.openDropdown(); return; }
                this.highlighted = items.length > 0 ? (this.highlighted + 1) % items.length : -1;
                items[this.highlighted]?.focus();
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (!this.open) { this.openDropdown(); return; }
                this.highlighted = this.highlighted <= 0 ? items.length - 1 : this.highlighted - 1;
                items[this.highlighted]?.focus();
                break;
            case 'Enter':
            case ' ':
                if (this.open && this.highlighted >= 0 && items[this.highlighted]) {
                    e.preventDefault();
                    items[this.highlighted].click();
                }
                break;
            case 'Escape':
                // Con el menú ya cerrado no hay nada que consumir, y llamar a
                // preventDefault() aquí dejaba el Escape marcado como atendido:
                // dentro de un modal, quien lo esperaba —el overlay manager— lo
                // descartaba y no se cerraba nada.
                if (! this.open) return;

                e.preventDefault();
                this.close();
                this._focusTrigger();
                break;
            case 'Tab':
                this.close();
                break;
        }
    },

    /**
     * Devuelve el foco al control que abrió el menú.
     *
     * `$refs.trigger` es el envoltorio —un `<div>` sin tabindex—, así que
     * llamarle `focus()` no enfocaba nada: al cerrar con Escape el foco se
     * perdía y el siguiente Tab volvía al principio de la página. Lo que hay que
     * enfocar es el control que el consumidor puso dentro.
     */
    _focusTrigger() {
        const envoltorio = this.$refs.trigger;
        if (! envoltorio) return;

        const control = envoltorio.querySelector('button, a[href], [tabindex]:not([tabindex="-1"])');
        (control ?? envoltorio).focus();
    },
});
