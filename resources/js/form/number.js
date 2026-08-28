export default (config) => ({
    raw: null,
    formatted: '',
    _formatter: null,

    holdInterval: null,
    holdTimeout: null,

    init() {
        this._formatter = this._createFormatter();
        this._syncFromInput();
        this._watchWire();
        // Livewire populates the hidden input after Alpine's init — catch it on next tick
        if (!this.$refs.hiddenInput?.value) {
            this.$nextTick(() => this._syncFromInput());
        }
    },

    _createFormatter() {
        const opts = {
            minimumFractionDigits: config.precision ?? 2,
            maximumFractionDigits: config.precision ?? 2,
        };
        if (config.currency) {
            opts.style = 'currency';
            opts.currency = config.currency;
        }
        return new Intl.NumberFormat(config.locale || undefined, opts);
    },

    _format(value) {
        if (value === null || value === undefined || isNaN(value)) return '';
        let result = this._formatter.format(value);
        if (!config.currency && config.prefix) result = config.prefix + result;
        if (!config.currency && config.suffix) result = result + config.suffix;
        return result;
    },

    _parse(text) {
        if (!text || !text.trim()) return null;
        const parts = this._formatter.formatToParts(1234.5);
        const decSep = parts.find(p => p.type === 'decimal')?.value || '.';
        const grpSep = parts.find(p => p.type === 'group')?.value || ',';
        let cleaned = text.replace(new RegExp(`[^\\d\\-${decSep === '.' ? '\\.' : decSep}]`, 'g'), '');
        if (decSep !== '.') cleaned = cleaned.replace(decSep, '.');
        const num = parseFloat(cleaned);
        return isNaN(num) ? null : num;
    },

    _clamp(value) {
        if (value === null) return null;
        if (config.min !== undefined && config.min !== null && value < config.min) return config.min;
        if (config.max !== undefined && config.max !== null && value > config.max) return config.max;
        return value;
    },

    _onFocus(e) {
        if (this.raw !== null) {
            this.$refs.input.value = this.raw;
        }
        this.$nextTick(() => e.target.select());
    },

    _onBlur() {
        const parsed = this._parse(this.$refs.input.value);
        if (parsed !== null) {
            this.raw = this._clamp(parsed);
        } else if (this.$refs.input.value.trim() === '') {
            this.raw = null;
        }
        this.formatted = this._format(this.raw);
        this.$refs.input.value = this.formatted;
        this._sync();
    },

    _onInput(e) {
        // Allow free typing, format on blur
    },

    _onKeydown(e) {
        // Block scientific notation always; block decimal separators when precision is 0.
        const blocked = ['e', 'E'];
        if (config.precision === 0) blocked.push('.', ',');
        if (blocked.includes(e.key)) e.preventDefault();
    },

    increment() {
        let val = this.raw;
        if (val === null || val === undefined) {
            val = config.min ?? 0;
        } else {
            val = Math.round((val + (config.step ?? 1)) * 1e10) / 1e10;
        }
        if (config.max === undefined || config.max === null || val <= config.max) {
            this.raw = val;
            this.formatted = this._format(val);
            this.$refs.input.value = this.formatted;
            this._sync();
        }
    },

    decrement() {
        let val = this.raw;
        if (val === null || val === undefined) {
            val = config.min ?? 0;
        } else {
            val = Math.round((val - (config.step ?? 1)) * 1e10) / 1e10;
        }
        if (config.min === undefined || config.min === null || val >= config.min) {
            this.raw = val;
            this.formatted = this._format(val);
            this.$refs.input.value = this.formatted;
            this._sync();
        }
    },

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
        this._commit();
    },

    /**
     * Cierra la interacción con las flechas.
     *
     * `_sync()` despacha `input`, que es lo que escucha `wire:model.live`. Los
     * demás modificadores esperan otro evento: `.blur` espera `blur` y `.change`
     * espera `change`, y ninguno de los dos ocurre cuando el valor lo cambia un
     * botón —el foco no ha estado nunca dentro del campo—. Medido: tres clics en
     * «+» con `wire:model.blur` dejaban el cliente en 3 y el servidor en 0, y
     * salir del campo tampoco lo arreglaba, porque no había nada de lo que salir.
     *
     * Un clic en la flecha es una interacción terminada, así que aquí se cierra:
     * al soltar, no en cada paso, para que mantener el botón pulsado no dispare
     * una petición por unidad.
     */
    _commit() {
        const input = this.$refs.hiddenInput;
        if (!input) return;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.dispatchEvent(new Event('blur', { bubbles: true }));
    },

    _sync() {
        if (!this.$refs.hiddenInput) return;
        this.$refs.hiddenInput.value = this.raw ?? '';
        this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    },

    _syncFromInput() {
        const val = this.$refs.hiddenInput?.value;
        if (val !== undefined && val !== '') {
            this.raw = parseFloat(val);
            this.formatted = this._format(this.raw);
            this.$refs.input.value = this.formatted;
        }
    },

    _watchWire() {
        const input = this.$refs.hiddenInput;
        if (!input || !this.$wire) return;
        const modelName = input.getAttribute('wire:model.live')
            || input.getAttribute('wire:model.blur')
            || input.getAttribute('wire:model.defer')
            || input.getAttribute('wire:model');
        if (modelName) {
            this.$wire.$watch(modelName, (val) => {
                const numVal = (val === null || val === '') ? null : parseFloat(val);
                if (numVal !== this.raw) {
                    this.raw = numVal;
                    this.formatted = this._format(numVal);
                    this.$refs.input.value = this.formatted;
                }
            });
        }
    },
});
