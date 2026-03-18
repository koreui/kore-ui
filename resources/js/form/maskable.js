export default (config) => ({
    formatted: '',
    raw: '',
    _currentMask: '',

    init() {
        this._currentMask = config.masks[0] || '';
        const initial = config.emitFormatted
            ? this.$refs.input?.value
            : this.$refs.hiddenInput?.value;
        if (initial) {
            this.raw = this._cleanRaw(initial);
            if (config.masks.length > 1) {
                this._currentMask = this._selectBestMask(this.raw);
            }
            this.formatted = this._applyMask(this.raw);
            this.$refs.input.value = this.formatted;
        }

        // Watch Livewire changes
        const watchInput = config.emitFormatted ? this.$refs.input : this.$refs.hiddenInput;
        if (watchInput && this.$wire) {
            const wireModel = watchInput.getAttribute('wire:model.live')
                || watchInput.getAttribute('wire:model.blur')
                || watchInput.getAttribute('wire:model.defer')
                || watchInput.getAttribute('wire:model');
            if (wireModel) {
                this.$wire.$watch(wireModel, (val) => {
                    if (val === (config.emitFormatted ? this.formatted : this.raw)) return;
                    this.raw = this._cleanRaw(val || '');
                    if (config.masks.length > 1) {
                        this._currentMask = this._selectBestMask(this.raw);
                    }
                    this.formatted = this._applyMask(this.raw);
                    this.$refs.input.value = this.formatted;
                });
            }
        }
    },

    // --- Core mask engine ---

    _isToken(c) {
        return '#A*!'.includes(c);
    },

    _tokenMatches(token, char) {
        switch (token) {
            case '#': return /\d/.test(char);
            case 'A': return /[a-zA-Z]/.test(char);
            case '!': return /[a-zA-Z]/.test(char);
            case '*': return true;
            default: return false;
        }
    },

    _transformChar(token, char) {
        return token === '!' ? char.toUpperCase() : char;
    },

    _applyMask(rawValue) {
        const mask = this._currentMask;
        if (!mask || !rawValue) return '';
        let result = '', rawIdx = 0;
        for (let i = 0; i < mask.length && rawIdx < rawValue.length; i++) {
            const m = mask[i];
            if (this._isToken(m)) {
                while (rawIdx < rawValue.length) {
                    const ch = rawValue[rawIdx++];
                    if (this._tokenMatches(m, ch)) {
                        result += this._transformChar(m, ch);
                        break;
                    }
                }
            } else {
                result += m; // literal
            }
        }
        return result;
    },

    _extractRaw(formatted) {
        const mask = this._currentMask;
        if (!mask || !formatted) return '';
        let raw = '';
        for (let i = 0; i < formatted.length && i < mask.length; i++) {
            if (this._isToken(mask[i])) {
                raw += formatted[i] || '';
            }
        }
        return raw;
    },

    _cleanRaw(value) {
        // Remove all non-alphanumeric chars for raw
        return value.replace(/[^a-zA-Z0-9]/g, '');
    },

    _selectBestMask(rawValue) {
        if (config.masks.length === 1) return config.masks[0];
        const sorted = [...config.masks].sort((a, b) => {
            return (a.match(/[#A*!]/g) || []).length - (b.match(/[#A*!]/g) || []).length;
        });
        const len = rawValue.length;
        for (const mask of sorted) {
            if (len <= (mask.match(/[#A*!]/g) || []).length) return mask;
        }
        return sorted[sorted.length - 1];
    },

    // --- Event handlers ---

    _onInput(e) {
        const input = this.$refs.input;
        const rawFromDisplay = this._extractRaw(input.value);
        // Also pick up any characters the user typed that may not match the current mask position
        const allChars = input.value.replace(/[^a-zA-Z0-9]/g, '');
        const rawValue = allChars.length > rawFromDisplay.length ? allChars : rawFromDisplay;

        if (config.masks.length > 1) {
            this._currentMask = this._selectBestMask(rawValue);
        }

        this.raw = rawValue;
        this.formatted = this._applyMask(rawValue);

        const cursorPos = this._calcCursorPos(input.selectionStart);
        input.value = this.formatted;
        input.setSelectionRange(cursorPos, cursorPos);

        this._sync();
    },

    _onPaste(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        if (!text) return;
        const cleaned = text.replace(/[^a-zA-Z0-9]/g, '');
        if (config.masks.length > 1) {
            this._currentMask = this._selectBestMask(cleaned);
        }
        this.raw = cleaned;
        this.formatted = this._applyMask(cleaned);
        this.$refs.input.value = this.formatted;
        this._sync();
    },

    _onBlur() {
        if (!config.autoClear) return;
        const tokenCount = (this._currentMask.match(/[#A*!]/g) || []).length;
        if (this.raw.length < tokenCount) {
            this.raw = '';
            this.formatted = '';
            this.$refs.input.value = '';
            this._sync();
        }
    },

    _onFocus(e) {
        this.$nextTick(() => {
            e.target.setSelectionRange(this.formatted.length, this.formatted.length);
        });
    },

    _calcCursorPos(rawCursorPos) {
        const mask = this._currentMask;
        if (!mask) return this.formatted.length;
        let rawCount = 0;
        for (let i = 0; i < mask.length; i++) {
            if (this._isToken(mask[i])) {
                rawCount++;
                if (rawCount >= rawCursorPos) return i + 1;
            }
        }
        return this.formatted.length;
    },

    _sync() {
        if (config.emitFormatted) {
            this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
            if (!this.$refs.hiddenInput) return;
            this.$refs.hiddenInput.value = this.raw;
            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },

    clear() {
        this.raw = '';
        this.formatted = '';
        this.$refs.input.value = '';
        this._sync();
    },
});
