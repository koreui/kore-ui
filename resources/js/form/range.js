export default (config) => ({
    value: config.min ?? 0,
    low: config.min ?? 0,
    high: config.max ?? 100,

    init() {
        if (config.single) {
            const input = this.$refs.input;
            if (input?.value) this.value = parseFloat(input.value);
        } else {
            const hiddenInput = this.$refs.hiddenInput;
            const wireModel = this._getModelName();

            // Read initial value from $wire (arrays don't serialize to hidden input value)
            if (wireModel && this.$wire) {
                const initial = this.$wire.$get(wireModel);
                if (Array.isArray(initial) && initial.length === 2) {
                    this.low = parseFloat(initial[0]);
                    this.high = parseFloat(initial[1]);
                }

                // Watch Livewire changes (resets, external updates)
                this.$wire.$watch(wireModel, (val) => {
                    if (Array.isArray(val) && val.length === 2) {
                        this.low = parseFloat(val[0]);
                        this.high = parseFloat(val[1]);
                    }
                });
            } else {
                // Fallback: try hidden input value (non-Livewire usage)
                const val = hiddenInput?.value;
                if (val) {
                    try {
                        const arr = JSON.parse(val);
                        if (Array.isArray(arr) && arr.length === 2) {
                            this.low = parseFloat(arr[0]);
                            this.high = parseFloat(arr[1]);
                        }
                    } catch {}
                }
            }
        }
    },

    get percent() {
        const range = config.max - config.min;
        return range > 0 ? ((this.value - config.min) / range) * 100 : 0;
    },

    get filledTrackStyle() {
        const range = config.max - config.min;
        if (range <= 0) return 'left:0;width:0';
        const left = ((this.low - config.min) / range) * 100;
        const width = ((this.high - this.low) / range) * 100;
        return `left:${left}%;width:${width}%`;
    },

    onLowInput() {
        const step = config.step || 1;
        this.low = Math.min(parseFloat(this.low), parseFloat(this.high) - step);
        this._syncRange();
    },

    onHighInput() {
        const step = config.step || 1;
        this.high = Math.max(parseFloat(this.high), parseFloat(this.low) + step);
        this._syncRange();
    },

    _syncRange() {
        if (!this.$refs.hiddenInput) return;

        // Use $wire.$set for arrays (same pattern as select multiple)
        const modelName = this._getModelName();
        if (modelName && this.$wire) {
            this.$wire.$set(modelName, [this.low, this.high]);
            return;
        }

        this.$refs.hiddenInput.value = JSON.stringify([this.low, this.high]);
        this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
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
