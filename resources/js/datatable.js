export default (config = {}) => ({
    density: config.density || 'normal',

    get densityClasses() {
        return {
            compact: 'px-3 py-1 text-sm',
            relaxed: 'px-4 py-4 text-base',
            normal: 'px-4 py-2.5 text-sm',
        }[this.density] || 'px-4 py-2.5 text-sm';
    },

    get headerDensityClasses() {
        return {
            compact: 'px-3 py-1.5 text-xs',
            relaxed: 'px-4 py-3 text-sm',
            normal: 'px-4 py-2 text-xs',
        }[this.density] || 'px-4 py-2 text-xs';
    },

    init() {
        this._onKeydown = (e) => {
            if (e.key === '/' && !this._isInputFocused()) {
                e.preventDefault();
                const searchInput = this.$root.querySelector('[data-datatable-search]');
                if (searchInput) {
                    searchInput.focus();
                }
            }
        };

        document.addEventListener('keydown', this._onKeydown);
    },

    destroy() {
        if (this._onKeydown) {
            document.removeEventListener('keydown', this._onKeydown);
        }
    },

    _isInputFocused() {
        const el = document.activeElement;
        return el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
    },
});
