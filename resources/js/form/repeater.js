export default (config = {}) => ({
    rows: [],

    init() {
        const modelName = this._getModelName();

        if (modelName && this.$wire) {
            const initial = this.$wire.$get(modelName);
            this.rows = Array.isArray(initial) ? initial.map((r) => ({ ...r })) : [];

            this.$wire.$watch(modelName, (val) => {
                const incoming = Array.isArray(val) ? val : [];
                if (JSON.stringify(incoming) !== JSON.stringify(this.rows)) {
                    this.rows = incoming.map((r) => ({ ...r }));
                }
            });
        } else {
            const val = this.$refs?.hiddenInput?.value;
            if (val) {
                try { this.rows = JSON.parse(val); } catch { this.rows = []; }
            }
        }

        if (!Array.isArray(this.rows)) this.rows = [];

        // Seed defaults / minimum rows when the model comes back empty.
        let seeded = false;
        if (this.rows.length === 0 && Array.isArray(config.default) && config.default.length > 0) {
            this.rows = config.default.map((r) => ({ ...r }));
            seeded = true;
        }
        while (this.rows.length < (config.min || 0)) {
            this.rows.push(this._blankRow());
            seeded = true;
        }
        if (seeded) this._sync();
    },

    _blankRow() {
        const row = {};
        for (const key of (config.fields || [])) row[key] = '';
        return row;
    },

    addRow() {
        if (config.max && this.rows.length >= config.max) return;
        this.rows.push(this._blankRow());
        this._sync();
    },

    removeRow(index) {
        if (config.min && this.rows.length <= config.min) return;
        this.rows.splice(index, 1);
        this._sync();
    },

    moveRow(from, to) {
        if (from === to || to < 0 || to >= this.rows.length) return;
        const [moved] = this.rows.splice(from, 1);
        this.rows.splice(to, 0, moved);
        this._sync();
    },

    _sync() {
        if (this.$wire) {
            const modelName = this._getModelName();
            if (modelName) {
                this.$wire.$set(modelName, [...this.rows]);
                return;
            }
        }
        if (this.$refs?.hiddenInput) {
            this.$refs.hiddenInput.value = JSON.stringify(this.rows);
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
