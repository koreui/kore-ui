export default (config = {}) => ({
    pairs: [{ key: '', value: '' }],

    init() {
        const modelName = this._getModelName();

        // Read the initial value from $wire (objects/arrays don't serialize to a hidden input value)
        if (modelName && this.$wire) {
            this.pairs = this._toPairs(this.$wire.$get(modelName));

            // Watch Livewire resets from the server
            this.$wire.$watch(modelName, (val) => {
                const incoming = this._toObject(this._toPairs(val));
                if (JSON.stringify(incoming) !== JSON.stringify(this._toObject(this.pairs))) {
                    this.pairs = this._toPairs(val);
                }
            });
        } else {
            // Fallback: hidden input JSON (non-Livewire usage)
            const val = this.$refs?.hiddenInput?.value;
            if (val) {
                try { this.pairs = this._toPairs(JSON.parse(val)); } catch { this.pairs = []; }
            }
        }

        if (!Array.isArray(this.pairs) || this.pairs.length === 0) {
            this.pairs = [{ key: '', value: '' }];
        }
    },

    addPair() {
        if (config.max && this.pairs.length >= config.max) return;
        this.pairs.push({ key: '', value: '' });
        this._sync();
    },

    removePair(index) {
        this.pairs.splice(index, 1);
        if (this.pairs.length === 0) this.pairs.push({ key: '', value: '' });
        this._sync();
    },

    movePair(from, to) {
        if (from === to || to < 0 || to >= this.pairs.length) return;
        const [moved] = this.pairs.splice(from, 1);
        this.pairs.splice(to, 0, moved);
        this._sync();
    },

    // Convert the internal [{key,value}] rows into the object {key: value} that Livewire stores.
    _toObject(pairs) {
        const obj = {};
        for (const pair of pairs) {
            const key = String(pair.key ?? '').trim();
            if (key === '') continue;
            obj[key] = pair.value ?? '';
        }
        return obj;
    },

    // Accept an object {k:v}, an array of {key,value}, or an array of [k,v] and normalize to rows.
    _toPairs(value) {
        if (Array.isArray(value)) {
            return value.map((v) =>
                Array.isArray(v)
                    ? { key: v[0] ?? '', value: v[1] ?? '' }
                    : { key: v?.key ?? '', value: v?.value ?? '' }
            );
        }
        if (value && typeof value === 'object') {
            return Object.entries(value).map(([key, val]) => ({
                key: String(key),
                value: val == null ? '' : String(val),
            }));
        }
        return [];
    },

    _sync() {
        const obj = this._toObject(this.pairs);

        if (this.$wire) {
            const modelName = this._getModelName();
            if (modelName) {
                this.$wire.$set(modelName, obj);
                return;
            }
        }
        // Fallback: JSON in hidden input
        if (this.$refs?.hiddenInput) {
            this.$refs.hiddenInput.value = JSON.stringify(obj);
            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },

    _getModelName() {
        const input = this.$refs?.hiddenInput;
        if (!input) return null;
        return input.getAttribute('wire:model.live')
            || input.getAttribute('wire:model.blur')
            || input.getAttribute('wire:model.defer')
            || input.getAttribute('wire:model');
    },
});
