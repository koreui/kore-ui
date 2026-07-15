export default (config = {}) => ({
    target: [],
    checkedSource: [],
    checkedTarget: [],
    sourceSearch: '',
    targetSearch: '',

    init() {
        const modelName = this._getModelName();

        if (modelName && this.$wire) {
            this.target = this._arr(this.$wire.$get(modelName));
            this.$wire.$watch(modelName, (val) => {
                const incoming = this._arr(val);
                if (JSON.stringify(incoming) !== JSON.stringify(this.target)) {
                    this.target = incoming;
                }
            });
        } else {
            const val = this.$refs?.hiddenInput?.value;
            if (val) {
                try { this.target = this._arr(JSON.parse(val)); } catch { this.target = []; }
            }
        }
    },

    _arr(v) {
        return Array.isArray(v) ? [...v] : [];
    },

    _items() {
        return config.items || [];
    },

    _match(item, query) {
        if (!query) return true;
        return String(item.label).toLowerCase().includes(query.toLowerCase());
    },

    get sourceItems() {
        return this._items().filter((i) => !this.target.includes(i.value) && this._match(i, this.sourceSearch));
    },

    get targetItems() {
        return this._items().filter((i) => this.target.includes(i.value) && this._match(i, this.targetSearch));
    },

    get availableCount() {
        return this._items().filter((i) => !this.target.includes(i.value)).length;
    },

    get selectedCount() {
        return this.target.length;
    },

    isChecked(list, value) {
        return (list === 'source' ? this.checkedSource : this.checkedTarget).includes(value);
    },

    toggleCheck(list, value) {
        const arr = list === 'source' ? this.checkedSource : this.checkedTarget;
        const idx = arr.indexOf(value);
        if (idx === -1) arr.push(value); else arr.splice(idx, 1);
    },

    moveToTarget() {
        for (const v of this.checkedSource) {
            if (!this.target.includes(v)) this.target.push(v);
        }
        this.checkedSource = [];
        this._sync();
    },

    moveToSource() {
        this.target = this.target.filter((v) => !this.checkedTarget.includes(v));
        this.checkedTarget = [];
        this._sync();
    },

    moveAllToTarget() {
        this.target = this._items().map((i) => i.value);
        this.checkedSource = [];
        this._sync();
    },

    moveAllToSource() {
        this.target = [];
        this.checkedTarget = [];
        this._sync();
    },

    _sync() {
        if (this.$wire) {
            const modelName = this._getModelName();
            if (modelName) {
                this.$wire.$set(modelName, [...this.target]);
                return;
            }
        }
        if (this.$refs?.hiddenInput) {
            this.$refs.hiddenInput.value = JSON.stringify(this.target);
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
