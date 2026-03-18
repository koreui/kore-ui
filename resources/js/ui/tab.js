export default (config) => ({
    selected: config.selected ?? null,
    tabs: [],
    variant: config.variant ?? 'line',
    orientation: config.orientation ?? 'horizontal',

    init() {
        this.$nextTick(() => {
            if (!this.selected && this.tabs.length > 0) {
                this.selected = this.tabs.find(t => !t.disabled)?.id ?? null;
            }
        });
    },

    registerTab(tab) {
        // Avoid duplicate registration
        if (!this.tabs.find(t => t.id === tab.id)) {
            this.tabs.push(tab);
        }
    },

    select(tabId) {
        const tab = this.tabs.find(t => t.id === tabId);
        if (!tab || tab.disabled) return;
        this.selected = tabId;
        this._syncWireModel();
    },

    isSelected(tabId) {
        return this.selected === tabId;
    },

    closeTab(tabId) {
        const index = this.tabs.findIndex(t => t.id === tabId);
        if (index === -1) return;

        this.tabs.splice(index, 1);

        if (this.selected === tabId) {
            const next = this.tabs[index] || this.tabs[index - 1];
            this.selected = next?.id ?? null;
        }
        this._syncWireModel();
    },

    onKeydown(e) {
        const enabledTabs = this.tabs.filter(t => !t.disabled);
        const currentIndex = enabledTabs.findIndex(t => t.id === this.selected);
        if (currentIndex === -1) return;

        const isHorizontal = this.orientation === 'horizontal';
        const nextKey = isHorizontal ? 'ArrowRight' : 'ArrowDown';
        const prevKey = isHorizontal ? 'ArrowLeft' : 'ArrowUp';

        let newIndex = currentIndex;

        switch (e.key) {
            case nextKey:
                e.preventDefault();
                newIndex = (currentIndex + 1) % enabledTabs.length;
                break;
            case prevKey:
                e.preventDefault();
                newIndex = currentIndex === 0 ? enabledTabs.length - 1 : currentIndex - 1;
                break;
            case 'Home':
                e.preventDefault();
                newIndex = 0;
                break;
            case 'End':
                e.preventDefault();
                newIndex = enabledTabs.length - 1;
                break;
            default:
                return;
        }

        const newTab = enabledTabs[newIndex];
        if (newTab) {
            this.select(newTab.id);
            this._focusTab(newTab.id);
        }
    },

    _focusTab(tabId) {
        this.$nextTick(() => {
            const btn = this.$el.querySelector(`[data-tab-id="${tabId}"]`);
            btn?.focus();
        });
    },

    _syncWireModel() {
        const input = this.$refs.hiddenInput;
        if (input) {
            input.value = this.selected ?? '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },
});
