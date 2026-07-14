export default {
    mode: 'system',
    resolved: 'light',
    _mediaQuery: null,
    _mediaHandler: null,

    init() {
        try {
            const stored = localStorage.getItem('kore-theme');
            this.mode = ['light', 'dark', 'system'].includes(stored) ? stored : 'system';
        } catch (e) {
            this.mode = 'system';
        }

        this._mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        this._resolve();
        this._listen();

        // Re-apply after wire:navigate morphs <html> (strips .dark / data-theme)
        document.addEventListener('livewire:navigated', () => this._apply());
    },

    setMode(mode) {
        this.mode = mode;

        try {
            localStorage.setItem('kore-theme', mode);
        } catch (e) {
            // Incognito or storage full
        }

        this._resolve();
        this._listen();

        this._dispatch();
    },

    _resolve() {
        this.resolved = this.mode === 'system'
            ? (this._mediaQuery.matches ? 'dark' : 'light')
            : this.mode;

        this._apply();
    },

    _apply() {
        const root = document.documentElement;

        if (this.resolved === 'dark') {
            root.classList.add('dark');
            root.setAttribute('data-theme', 'dark');
        } else {
            root.classList.remove('dark');
            root.setAttribute('data-theme', 'light');
        }
    },

    _listen() {
        // Cleanup previous handler
        if (this._mediaHandler) {
            this._mediaQuery.removeEventListener('change', this._mediaHandler);
            this._mediaHandler = null;
        }

        // Only listen when mode is system
        if (this.mode === 'system') {
            // The OS theme changed. This MUST emit `theme-changed` too, not just repaint:
            // 'system' is the default mode, so this is the most common way a user ever
            // switches theme. Anything that has to re-read colours from JS (a canvas, a
            // third-party widget) would otherwise be left on the previous theme forever,
            // and only in the default configuration — the worst kind of bug to find.
            this._mediaHandler = () => {
                this._resolve();
                this._dispatch();
            };
            this._mediaQuery.addEventListener('change', this._mediaHandler);
        }
    },

    _dispatch() {
        document.dispatchEvent(new CustomEvent('theme-changed', {
            detail: { mode: this.mode, resolved: this.resolved },
        }));
    },

    get isDark() {
        return this.resolved === 'dark';
    },

    get isLight() {
        return this.resolved === 'light';
    },

    get isSystem() {
        return this.mode === 'system';
    },

    get systemPrefersDark() {
        return this._mediaQuery?.matches ?? false;
    },
};
