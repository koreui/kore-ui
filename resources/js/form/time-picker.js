import { startFloating, stopFloating } from '../utils/floating.js';

export default (config) => ({
    open: false,
    hours: config.timeFormat === '12' ? 12 : 0,
    minutes: 0,
    ampm: 'AM',
    holdInterval: null,
    holdTimeout: null,
    _hasBeenSet: false,

    // Sin declarar, Alpine las escribiría en el x-data ancestro, no aquí.
    // Ver tests/js/alpine-scope.test.js.
    _floatingCleanup: null,
    _boundMousedown: null,

    init() {
        const input = this.$refs.hiddenInput;
        this._syncFromInput(input);

        if (input) {
            const wireModel = input.getAttribute('wire:model.live')
                || input.getAttribute('wire:model.blur')
                || input.getAttribute('wire:model.defer')
                || input.getAttribute('wire:model');
            if (wireModel && this.$wire) {
                this.$wire.$watch(wireModel, (val) => {
                    if (val !== this._lastSynced) {
                        this._syncFromInput({ value: val });
                    }
                });
            }
        }

        if (input && !input.value) {
            this.$nextTick(() => this._syncFromInput(input));
        }
    },

    destroy() {
        this._cleanup();
    },

    // ── Dropdown ─────────────────────────────────────────────────

    toggle() {
        this.open ? this.close() : this.openDropdown();
    },

    openDropdown() {
        this.open = true;
        this.$nextTick(() => {
            this._floatingCleanup = startFloating(this.$refs.trigger, this.$refs.dropdown, {
                placement: 'bottom-start',
                offset: 4,
                sameWidth: true,
                // Si el disparador deja de pintarse —una pestaña que cambia, un
                // acordeón que se cierra— el panel se cerraba a medias: dejaba de
                // tener contra qué colocarse pero seguía en pantalla.
                onClose: () => this.close(),
            });
            this._addClickAwayListener();
        });
    },

    close() {
        if (!this.open) return;
        this.open = false;
        this._cleanup();
    },

    _cleanup() {
        stopFloating(this._floatingCleanup);
        this._floatingCleanup = null;
        this._removeClickAwayListener();
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

    // ── Time logic ───────────────────────────────────────────────

    incrementHour() {
        const max = config.timeFormat === '12' ? 12 : 23;
        const min = config.timeFormat === '12' ? 1 : 0;
        this.hours = this.hours >= max ? min : this.hours + 1;
        this._onTimeChange();
    },

    decrementHour() {
        const max = config.timeFormat === '12' ? 12 : 23;
        const min = config.timeFormat === '12' ? 1 : 0;
        this.hours = this.hours <= min ? max : this.hours - 1;
        this._onTimeChange();
    },

    incrementMinute() {
        const step = config.minuteStep || 1;
        this.minutes = this.minutes + step > 59 ? 0 : this.minutes + step;
        this._onTimeChange();
    },

    decrementMinute() {
        const step = config.minuteStep || 1;
        this.minutes = this.minutes - step < 0 ? 60 - step : this.minutes - step;
        this._onTimeChange();
    },

    toggleAmPm() {
        this.ampm = this.ampm === 'AM' ? 'PM' : 'AM';
        this._onTimeChange();
    },

    _onTimeChange() {
        this._hasBeenSet = true;
        this.syncModel();
    },

    // ── Hold ─────────────────────────────────────────────────────

    startHold(fn) {
        fn();
        this.holdTimeout = setTimeout(() => {
            this.holdInterval = setInterval(() => fn(), 75);
        }, 400);
    },

    stopHold() {
        clearTimeout(this.holdTimeout);
        clearInterval(this.holdInterval);
        this.holdTimeout = null;
        this.holdInterval = null;
    },

    // ── Display ──────────────────────────────────────────────────

    get displayValue() {
        if (!this._hasBeenSet) return '';
        const h = String(this.hours).padStart(2, '0');
        const m = String(this.minutes).padStart(2, '0');
        if (config.timeFormat === '12') {
            return `${h}:${m} ${this.ampm}`;
        }
        return `${h}:${m}`;
    },

    get hasValue() {
        return this._hasBeenSet;
    },

    // ── Wire:model sync ──────────────────────────────────────────

    _lastSynced: null,

    syncModel() {
        if (!this.$refs.hiddenInput) return;

        // Always store in 24h format: HH:mm
        let h = this.hours;
        if (config.timeFormat === '12') {
            if (this.ampm === 'PM' && h !== 12) h += 12;
            if (this.ampm === 'AM' && h === 12) h = 0;
        }
        const val = `${String(h).padStart(2, '0')}:${String(this.minutes).padStart(2, '0')}`;
        this._lastSynced = val;

        this.$refs.hiddenInput.value = val;
        this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    },

    _syncFromInput(input) {
        if (!input?.value) return;
        const val = String(input.value).trim();

        // Parse HH:mm or HH:mm AM/PM
        const match = val.match(/^(\d{1,2}):(\d{2})(?:\s*(AM|PM))?$/i);
        if (!match) return;

        let h = parseInt(match[1], 10);
        let m = parseInt(match[2], 10);
        const period = match[3]?.toUpperCase();

        if (period) {
            // Input has AM/PM — convert to 24h first for normalization
            if (period === 'PM' && h !== 12) h += 12;
            if (period === 'AM' && h === 12) h = 0;
        }

        // Now h is in 24h. Convert to display format.
        if (config.timeFormat === '12') {
            this.ampm = h >= 12 ? 'PM' : 'AM';
            this.hours = h % 12 || 12;
        } else {
            this.hours = h;
        }
        this.minutes = m;
        this._hasBeenSet = true;
        this._lastSynced = val;
    },

    // ── Clear ────────────────────────────────────────────────────

    clear() {
        this.hours = config.timeFormat === '12' ? 12 : 0;
        this.minutes = 0;
        this.ampm = 'AM';
        this._hasBeenSet = false;
        this._lastSynced = null;
        if (this.$refs.hiddenInput) {
            this.$refs.hiddenInput.value = '';
            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },

    // ── Keyboard ─────────────────────────────────────────────────

    onKeydown(e) {
        if (!this.open) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                this.openDropdown();
            }
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            this.close();
            this.$refs.trigger?.focus();
        }

        // Igual que el calendario: al tabular, el foco se va del campo y el
        // panel se quedaba abierto encima del formulario. Sin `preventDefault`,
        // que la tabulación tiene que seguir su camino.
        if (e.key === 'Tab') {
            this.close();
        }
    },
});
