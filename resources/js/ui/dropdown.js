export default (config) => ({
    open: false,
    highlighted: -1,

    init() {
        // Nothing special to initialize
    },

    destroy() {
        this._removeGlobalListeners();
    },

    toggle() {
        this.open ? this.close() : this.openDropdown();
    },

    openDropdown() {
        this.open = true;
        this.highlighted = -1;
        this.$nextTick(() => {
            this.positionDropdown();
            this._addGlobalListeners();
        });
    },

    close() {
        if (!this.open) return;
        this.open = false;
        this.highlighted = -1;
        this._removeGlobalListeners();
    },

    get _items() {
        if (!this.$refs.dropdown) return [];
        return [...this.$refs.dropdown.querySelectorAll('[role="menuitem"]:not([disabled]):not([aria-disabled="true"])')];
    },

    _onScroll() {
        if (!this.open) return;
        this.positionDropdown();
    },

    _onResize() {
        if (!this.open) return;
        this.positionDropdown();
    },

    _onMousedown(e) {
        if (!this.open) return;
        if (this.$refs.trigger?.contains(e.target)) return;
        if (this.$refs.dropdown?.contains(e.target)) return;
        if (!config.persistent) this.close();
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

    positionDropdown() {
        const trigger = this.$refs.trigger;
        const dropdown = this.$refs.dropdown;
        if (!trigger || !dropdown) return;

        const rect = trigger.getBoundingClientRect();
        const dropdownHeight = dropdown.offsetHeight || 200;
        const dropdownWidth = dropdown.offsetWidth || 200;
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;

        // Width calculation
        const widthMap = { sm: 128, md: 192, lg: 256 };
        const resolvedWidth = config.width === 'auto'
            ? rect.width
            : (widthMap[config.width] || rect.width);

        dropdown.style.position = 'fixed';
        dropdown.style.width = resolvedWidth + 'px';
        dropdown.style.minWidth = Math.max(resolvedWidth, 128) + 'px';

        // Vertical positioning
        const openAbove = config.position?.startsWith('top')
            || (spaceBelow < dropdownHeight && spaceAbove > spaceBelow);

        if (openAbove) {
            dropdown.style.top = 'auto';
            dropdown.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
        } else {
            dropdown.style.top = (rect.bottom + 4) + 'px';
            dropdown.style.bottom = 'auto';
        }

        // Horizontal positioning
        const isEnd = config.position?.endsWith('end');
        if (isEnd) {
            const right = window.innerWidth - rect.right;
            dropdown.style.left = 'auto';
            dropdown.style.right = right + 'px';
        } else {
            dropdown.style.left = rect.left + 'px';
            dropdown.style.right = 'auto';
        }
    },

    onKeydown(e) {
        const items = this._items;
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (!this.open) { this.openDropdown(); return; }
                this.highlighted = items.length > 0 ? (this.highlighted + 1) % items.length : -1;
                items[this.highlighted]?.focus();
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (!this.open) { this.openDropdown(); return; }
                this.highlighted = this.highlighted <= 0 ? items.length - 1 : this.highlighted - 1;
                items[this.highlighted]?.focus();
                break;
            case 'Enter':
            case ' ':
                if (this.open && this.highlighted >= 0 && items[this.highlighted]) {
                    e.preventDefault();
                    items[this.highlighted].click();
                }
                break;
            case 'Escape':
                e.preventDefault();
                this.close();
                this.$refs.trigger?.focus();
                break;
            case 'Tab':
                this.close();
                break;
        }
    },
});
