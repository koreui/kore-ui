export default (config) => ({
    tags: [],

    init() {
        const modelName = this._getModelName();

        // Read initial value from $wire (arrays don't serialize to hidden input value)
        if (modelName && this.$wire) {
            const initial = this.$wire.$get(modelName);
            this.tags = Array.isArray(initial) ? [...initial] : [];

            // Watch Livewire resets
            this.$wire.$watch(modelName, (val) => {
                const newTags = Array.isArray(val) ? val : [];
                if (JSON.stringify(newTags) !== JSON.stringify(this.tags)) {
                    this.tags = [...newTags];
                }
            });
        } else {
            // Fallback: try hidden input value (non-Livewire usage)
            const val = this.$refs.hiddenInput?.value;
            if (val) {
                try { this.tags = JSON.parse(val); } catch { this.tags = []; }
            }
            if (!Array.isArray(this.tags)) this.tags = [];
        }
    },

    addTag(text) {
        const trimmed = text.trim();
        if (!trimmed) return false;
        if (!config.allowDuplicate && this.tags.includes(trimmed)) return false;
        if (config.max && this.tags.length >= config.max) return false;
        this.tags.push(trimmed);
        this._sync();
        return true;
    },

    addCurrentTag() {
        const input = this.$refs.textInput;
        if (this.addTag(input.value)) input.value = '';
    },

    removeTag(index) {
        this.tags.splice(index, 1);
        this._sync();
    },

    clearAll() {
        this.tags = [];
        this.$refs.textInput.value = '';
        this._sync();
        this.$refs.textInput.focus();
    },

    onKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.addCurrentTag();
            return;
        }
        const sep = config.separator || ',';
        if (e.key === sep) {
            e.preventDefault();
            this.addCurrentTag();
            return;
        }
        if (e.key === 'Backspace' && !this.$refs.textInput.value && this.tags.length > 0) {
            this.removeTag(this.tags.length - 1);
        }
    },

    onPaste(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        if (!text) return;
        const sep = config.separator || ',';
        const parts = text.split(sep).map(s => s.trim()).filter(Boolean);
        for (const part of parts) this.addTag(part);
        this.$refs.textInput.value = '';
    },

    _sync() {
        // Use $wire.$set for arrays (same pattern as select multiple)
        if (this.$wire) {
            const modelName = this._getModelName();
            if (modelName) {
                this.$wire.$set(modelName, [...this.tags]);
                return;
            }
        }
        // Fallback: JSON in hidden input
        if (this.$refs.hiddenInput) {
            this.$refs.hiddenInput.value = JSON.stringify(this.tags);
            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },

    _getModelName() {
        const input = this.$refs.hiddenInput;
        if (!input) return null;
        return input.getAttribute('wire:model.live')
            || input.getAttribute('wire:model.blur')
            || input.getAttribute('wire:model.defer')
            || input.getAttribute('wire:model');
    },
});
