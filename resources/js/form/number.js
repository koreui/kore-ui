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
