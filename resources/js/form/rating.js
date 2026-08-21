export default (config) => ({
    value: 0,
    hoverValue: 0,
    hovering: false,

    init() {
        const val = this.$refs.hiddenInput?.value;
        if (val) this.value = parseFloat(val) || 0;

        // Watch Livewire changes
        const input = this.$refs.hiddenInput;
        if (input && this.$wire) {
            const wireModel = input.getAttribute('wire:model.live')
                || input.getAttribute('wire:model.blur')
                || input.getAttribute('wire:model.defer')
                || input.getAttribute('wire:model');
            if (wireModel) {
                this.$wire.$watch(wireModel, (val) => {
                    this.value = parseFloat(val) || 0;
                });
            }
        }
    },

    rate(star) {
        if (config.readonly) return;
        const clearable = config.clearable !== false;
        if (config.allowHalf && this.hovering) {
            const newVal = this.hoverValue;
            this.value = (clearable && this.value === newVal) ? 0 : newVal;
        } else {
            this.value = (clearable && this.value === star) ? 0 : star;
        }
        this._sync();
    },

    preview(star) {
        if (config.readonly) return;
        this.hovering = true;
        this.hoverValue = star;
    },

    detectHalf(event, star) {
        if (!config.allowHalf || config.readonly) return;

        // Se mide contra la ESTRELLA, no contra el botón: desde que el botón
        // tiene 24×24 para cumplir el objetivo táctil, puede ser más ancho que
        // la estrella, y con la caja grande el punto de corte de la media
        // quedaba desplazado respecto a lo que se ve.
        const caja = event.currentTarget.querySelector('[data-kore-estrella]') ?? event.currentTarget;
        const rect = caja.getBoundingClientRect();

        this.hoverValue = (event.clientX - rect.left) < (rect.width / 2)
            ? star - 0.5 : star;
    },

    clearPreview() {
        this.hovering = false;
        this.hoverValue = 0;
    },

    get displayValue() {
        return this.hovering ? this.hoverValue : this.value;
    },

    getStarFill(index) {
        const val = this.displayValue;
        if (index <= val) return 'full';
        if (config.allowHalf && index - 0.5 === val) return 'half';
        return 'empty';
    },

    _sync() {
        if (!this.$refs.hiddenInput) return;
        this.$refs.hiddenInput.value = this.value || '';
        this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    },
});
