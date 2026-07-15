export default (config = {}) => ({
    order: [],

    init() {
        const modelName = this._getModelName();
        const allValues = (config.items || []).map((i) => i.value);

        if (modelName && this.$wire) {
            const initial = this._arr(this.$wire.$get(modelName));
            this.order = initial.length ? this._reconcile(initial, allValues) : allValues;
            this.$wire.$watch(modelName, (val) => {
                const incoming = this._reconcile(this._arr(val), allValues);
                if (JSON.stringify(incoming) !== JSON.stringify(this.order)) {
                    this.order = incoming;
                }
            });
        } else {
            this.order = allValues;
        }
    },

    _arr(v) {
        return Array.isArray(v) ? [...v] : [];
    },

    // Keep only known values, then append any item missing from the stored order.
    _reconcile(stored, allValues) {
        const known = stored.filter((v) => allValues.includes(v));
        const missing = allValues.filter((v) => !known.includes(v));
        return [...known, ...missing];
    },

    get orderedItems() {
        const map = new Map((config.items || []).map((i) => [i.value, i]));
        return this.order.map((v) => map.get(v)).filter(Boolean);
    },

    // x-sort handler: value = the dragged item's key, position = its new index.
    move(value, position) {
        const from = this.order.indexOf(value);
        if (from === -1) return;
        this.order.splice(from, 1);
        this.order.splice(position, 0, value);
        this._sync();
    },

    moveUp(index) {
        if (index <= 0) return;
        [this.order[index - 1], this.order[index]] = [this.order[index], this.order[index - 1]];
        this._sync();
    },

    moveDown(index) {
        if (index >= this.order.length - 1) return;
        [this.order[index + 1], this.order[index]] = [this.order[index], this.order[index + 1]];
        this._sync();
    },

    _sync() {
        if (this.$wire) {
            const modelName = this._getModelName();
            if (modelName) {
                this.$wire.$set(modelName, [...this.order]);
                return;
            }
        }
        if (this.$refs?.hiddenInput) {
            this.$refs.hiddenInput.value = JSON.stringify(this.order);
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
