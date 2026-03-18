export default (config) => ({
    openItems: [],
    multiple: config.multiple ?? false,
    variant: config.variant ?? 'bordered',

    init() {
        // Collect pre-opened items from DOM
        this.$nextTick(() => {
            const preOpened = [];
            this.$el.querySelectorAll('[data-accordion-item][data-open="true"]').forEach(el => {
                const id = el.dataset.accordionItem;
                if (id) preOpened.push(id);
            });
            if (preOpened.length > 0) {
                this.openItems = this.multiple ? preOpened : [preOpened[0]];
            }
        });
    },

    toggle(itemId) {
        if (this.isOpen(itemId)) {
            this.openItems = this.openItems.filter(id => id !== itemId);
        } else {
            this.openItems = this.multiple ? [...this.openItems, itemId] : [itemId];
        }
        this._syncWireModel();
    },

    isOpen(itemId) {
        return this.openItems.includes(itemId);
    },

    _syncWireModel() {
        const input = this.$refs.hiddenInput;
        if (input) {
            input.value = JSON.stringify(this.openItems);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },
});
