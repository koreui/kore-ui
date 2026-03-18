export default (config) => ({
    value: null,
    open: false,
    customHex: '',

    init() {
        const val = this.$refs.hiddenInput?.value;
        if (val) {
            this.value = val;
            this.customHex = val;
        }

        // Watch Livewire changes
        const input = this.$refs.hiddenInput;
        if (input && this.$wire) {
            const wireModel = input.getAttribute('wire:model.live')
                || input.getAttribute('wire:model.blur')
                || input.getAttribute('wire:model.defer')
                || input.getAttribute('wire:model');
            if (wireModel) {
                this.$wire.$watch(wireModel, (val) => {
                    this.value = val || null;
                    this.customHex = val || '';
                });
            }
        }
    },

    destroy() {
        this._removeGlobalListeners();
    },

    // Dropdown
    toggle() {
        this.open ? this.close() : this.openPanel();
    },

    openPanel() {
        this.open = true;
        this.customHex = this.value || '';
        this.$nextTick(() => {
            if (!config.inline) this.positionDropdown();
            this._addGlobalListeners();
        });
    },

    close() {
        if (!this.open) return;
        this.open = false;
        this._removeGlobalListeners();
    },

    selectColor(hex) {
        this.value = hex;
        this.customHex = hex;
        this._sync();
        if (!config.inline) this.close();
    },

    applyCustom() {
        const hex = this.customHex.trim();
        if (!this.isValidHex(hex)) return;
        this.value = hex.startsWith('#') ? hex : '#' + hex;
        this.customHex = this.value;
        this._sync();
        if (!config.inline) this.close();
    },

    clear() {
        this.value = null;
        this.customHex = '';
        this._sync();
    },

    isValidHex(hex) {
        return /^#?([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(hex);
    },

    isLight(hex) {
        const c = hex.replace('#', '');
        const full = c.length === 3
            ? c[0]+c[0]+c[1]+c[1]+c[2]+c[2]
            : c;
        const r = parseInt(full.substring(0, 2), 16);
        const g = parseInt(full.substring(2, 4), 16);
        const b = parseInt(full.substring(4, 6), 16);
        return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.6;
    },

    _sync() {
        if (!this.$refs.hiddenInput) return;
        this.$refs.hiddenInput.value = this.value || '';
        this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    },

    // Positioning — same pattern as select.js and time-picker.js
    positionDropdown() {
        const trigger = this.$refs.trigger;
        const dropdown = this.$refs.dropdown;
        if (!trigger || !dropdown) return;

        const rect = trigger.getBoundingClientRect();
        const dropdownHeight = dropdown.offsetHeight || 300;
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;
        const openAbove = spaceBelow < dropdownHeight && spaceAbove > spaceBelow;

        dropdown.style.position = 'fixed';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.width = rect.width + 'px';

        if (openAbove) {
            dropdown.style.top = 'auto';
            dropdown.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
        } else {
            dropdown.style.top = (rect.bottom + 4) + 'px';
            dropdown.style.bottom = 'auto';
        }
    },

    _addGlobalListeners() {
        this._removeGlobalListeners();

        this._boundScroll = () => this._onScroll();
        this._boundResize = () => this._onResize();
        this._boundMousedown = (e) => this._onMousedown(e);

        window.addEventListener('scroll', this._boundScroll, { capture: true, passive: true });
        window.addEventListener('resize', this._boundResize, { passive: true });
        document.addEventListener('mousedown', this._boundMousedown);
    },

    _removeGlobalListeners() {
        if (this._boundScroll) {
            window.removeEventListener('scroll', this._boundScroll, { capture: true });
            this._boundScroll = null;
        }
        if (this._boundResize) {
            window.removeEventListener('resize', this._boundResize);
            this._boundResize = null;
        }
        if (this._boundMousedown) {
            document.removeEventListener('mousedown', this._boundMousedown);
            this._boundMousedown = null;
        }
    },

    _onMousedown(e) {
        if (!this.open) return;
        if (this.$refs.trigger?.contains(e.target)) return;
        if (this.$refs.dropdown?.contains(e.target)) return;
        this.close();
    },

    _onScroll() {
        if (!this.open) return;
        this.positionDropdown();
    },

    _onResize() {
        if (!this.open) return;
        this.positionDropdown();
    },
});
