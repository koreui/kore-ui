import { lockScroll, unlockScroll } from './utils/scroll-lock.js';

/**
 * Clave del dueño del scroll lock. Cadena y no `this`: cada expresión de Alpine
 * evalúa sobre un proxy nuevo del componente, así que un objeto no sirve para
 * identificar al dueño entre el lock y el unlock (ver `utils/scroll-lock.js`).
 */
const CLAVE_SCROLL = 'spotlight';

// ─── Fuzzy search (~35 lines) ─────────────────────────────────────────────

function fuzzyMatch(pattern, str) {
    pattern = pattern.toLowerCase();
    str = str.toLowerCase();

    if (str.includes(pattern)) return { match: true, score: 1 + pattern.length };

    let pi = 0;
    let score = 0;
    let consecutive = 0;

    for (let si = 0; si < str.length && pi < pattern.length; si++) {
        if (str[si] === pattern[pi]) {
            pi++;
            consecutive++;
            score += consecutive * 2;
        } else {
            consecutive = 0;
        }
    }

    if (pi === pattern.length) {
        if (str[0] === pattern[0]) score += 5;
        return { match: true, score };
    }

    return { match: false, score: 0 };
}

function searchItems(query, items) {
    if (!query) return items;

    return items
        .map(item => {
            const fields = [
                { text: item.name ?? '', weight: 3 },
                { text: item.description ?? '', weight: 1 },
                ...((item.synonyms ?? []).map(s => ({ text: s, weight: 2 }))),
            ];
            let bestScore = 0;
            for (const { text, weight } of fields) {
                const { match, score } = fuzzyMatch(query, text);
                if (match) bestScore = Math.max(bestScore, score * weight);
            }
            return { item, score: bestScore };
        })
        .filter(({ score }) => score > 0)
        .sort((a, b) => b.score - a.score)
        .map(({ item }) => item);
}

// ─── Alpine component ──────────────────────────────────────────────────────

export default function KoreSpotlight(initialItems = [], config = {}) {
    return {
        // State
        isOpen: false,
        query: '',
        activeIndex: -1,
        isSearching: false,
        allItems: initialItems,
        remoteItems: [],
        recentItems: [],

        // Multi-step dependencies
        steps: [],                  // [{label, value}] — completed steps
        currentItem: null,          // SpotlightItem being resolved
        currentDependencyIndex: 0,  // which dependency we're on

        // Config
        config: Object.assign({
            shortcut: 'k',
            placeholder: 'Buscar acciones, páginas...',
            searchUrl: null,
            searchMethod: 'GET',
            debounce: 300,
            showRecent: true,
            recentCount: 5,
            maxResults: 50,
            zIndex: 'z-[70]',
            maxHistory: 10,
        }, config),

        // History
        HISTORY_KEY: 'kore-spotlight-history',

        // Focus management
        previousFocusEl: null,
        _debounceTimer: null,
        _listeners: [],

        // ── Lifecycle ──

        init() {
            // Livewire events
            this._listeners.push(
                Livewire.on('kore:spotlight-results', ({ items }) => {
                    this.remoteItems = items ?? [];
                    this.isSearching = false;
                })
            );

            this._listeners.push(
                Livewire.on('kore:spotlight-dependency-results', ({ items }) => {
                    this.remoteItems = items ?? [];
                    this.isSearching = false;
                })
            );

            // Watch query for debounced remote search
            this.$watch('query', (value) => {
                clearTimeout(this._debounceTimer);
                this.remoteItems = [];
                this.activeIndex = -1;

                if (!value.trim()) {
                    this.isSearching = false;
                    return;
                }

                if (this.currentItem && this.currentDependency) {
                    // Dependency search mode
                    if (this.currentDependency.type === 'search' && this.currentDependency.searchUrl) {
                        this.isSearching = true;
                        this._debounceTimer = setTimeout(() => {
                            this.$wire.searchDependency(
                                this.currentItem.id,
                                this.currentDependency,
                                value
                            );
                        }, this.config.debounce);
                    }
                } else if (this.config.searchUrl) {
                    // Global remote search mode
                    this.isSearching = true;
                    this._debounceTimer = setTimeout(() => {
                        this.$wire.search(value);
                    }, this.config.debounce);
                }
            });
        },

        destroy() {
            this._listeners.forEach(cleanup => {
                if (typeof cleanup === 'function') cleanup();
            });
            this._listeners = [];
            clearTimeout(this._debounceTimer);
        },

        // ── Open / Close ──

        open(options = {}) {
            if (this.isOpen) return;
            this.previousFocusEl = document.activeElement;
            this.isOpen = true;
            this.recentItems = this.getHistory().slice(0, this.config.recentCount);

            if (options?.query) {
                this.query = options.query;
            }

            // El panel es modal —backdrop, aria-modal, foco atrapado— pero la
            // página seguía desplazándose por detrás con la rueda del ratón: el
            // panel quedaba quieto mientras el contenido pasaba de largo. El
            // modal sí lo bloqueaba desde siempre; esto era la incoherencia.
            lockScroll(CLAVE_SCROLL);

            this.$nextTick(() => {
                this.$refs.input?.focus();
            });

            window.dispatchEvent(new CustomEvent('kore:spotlight:open'));
        },

        close() {
            if (!this.isOpen) return;
            this.isOpen = false;
            this.query = '';
            this.remoteItems = [];
            this.activeIndex = -1;
            this.isSearching = false;
            this.steps = [];
            this.currentItem = null;
            this.currentDependencyIndex = 0;
            clearTimeout(this._debounceTimer);

            unlockScroll(CLAVE_SCROLL);

            this.$nextTick(() => {
                this.previousFocusEl?.focus();
            });

            window.dispatchEvent(new CustomEvent('kore:spotlight:close'));
        },

        toggle(options = {}) {
            this.isOpen ? this.close() : this.open(options);
        },

        // ── Computed ──

        get currentDependency() {
            if (!this.currentItem) return null;
            return this.currentItem.dependencies?.[this.currentDependencyIndex] ?? null;
        },

        get currentPlaceholder() {
            if (this.currentDependency) {
                return this.currentDependency.placeholder;
            }
            return this.config.placeholder;
        },

        get activeItemId() {
            if (this.activeIndex < 0) return null;
            const flat = this.flatResults;
            const item = flat[this.activeIndex];
            return item ? `kore-item-${item.id}` : null;
        },

        get flatResults() {
            // In dependency mode
            if (this.currentDependency) {
                if (this.currentDependency.type === 'select') {
                    return this.selectDependencyItems;
                }
                // search or input modes — use remoteItems
                return this.remoteItems.map((item, i) => ({ ...item, _index: i }));
            }

            // Normal mode — gather all groups' items in order
            let index = 0;
            return this.groupedResults.flatMap(group =>
                group.items.map(item => ({ ...item, _index: index++ }))
            );
        },

        get selectDependencyItems() {
            if (!this.currentDependency || this.currentDependency.type !== 'select') return [];
            const options = this.currentDependency.options ?? {};
            return Object.entries(options).map(([value, label], i) => ({
                id: `dep-select-${i}`,
                name: label,
                _value: value,
                _index: i,
            }));
        },

        get groupedResults() {
            // Multi-step: select mode
            if (this.currentDependency?.type === 'select') {
                return [{
                    name: this.currentDependency.placeholder,
                    items: this.selectDependencyItems,
                }];
            }

            // Multi-step: dependency search/input results
            if (this.currentDependency) {
                const items = this.remoteItems.map((item, i) => ({ ...item, _index: i }));
                return items.length > 0 ? [{
                    name: this.currentDependency.placeholder,
                    items,
                }] : [];
            }

            // Normal mode
            const sourceItems = this.query.trim()
                ? this.filteredItems
                : (this.config.showResultsWithoutInput !== false ? this.allItems.filter(i => !i.hidden) : []);

            // Group local items
            const localGroups = this.groupItems(sourceItems);

            // Merge remote items
            if (this.remoteItems.length > 0) {
                const remoteGroups = this.groupItems(this.remoteItems);
                // Merge: remote groups overwrite local groups of same name
                const merged = new Map();
                [...localGroups, ...remoteGroups].forEach(g => {
                    if (merged.has(g.name)) {
                        merged.get(g.name).items.push(...g.items);
                    } else {
                        merged.set(g.name, { ...g });
                    }
                });
                return this.reindexGroups([...merged.values()]);
            }

            return this.reindexGroups(localGroups);
        },

        get filteredItems() {
            return searchItems(this.query.trim(), this.allItems.filter(i => !i.hidden));
        },

        // ── Grouping helpers ──

        groupItems(items) {
            const map = new Map();
            items.forEach(item => {
                const g = item.group ?? 'General';
                if (!map.has(g)) map.set(g, []);
                map.get(g).push(item);
            });
            return [...map.entries()].map(([name, items]) => ({ name, items }));
        },

        reindexGroups(groups) {
            let idx = 0;
            return groups.map(group => ({
                ...group,
                items: group.items.map(item => ({ ...item, _index: idx++ })),
            }));
        },

        // ── Navigation ──

        moveDown() {
            const total = this.flatResults.length;
            if (total === 0) return;
            this.activeIndex = this.activeIndex < total - 1 ? this.activeIndex + 1 : 0;
            this.scrollToActive();
        },

        moveUp() {
            const total = this.flatResults.length;
            if (total === 0) return;
            this.activeIndex = this.activeIndex > 0 ? this.activeIndex - 1 : total - 1;
            this.scrollToActive();
        },

        scrollToActive() {
            this.$nextTick(() => {
                const el = document.getElementById(this.activeItemId);
                el?.scrollIntoView({ block: 'nearest' });
            });
        },

        selectCurrent() {
            const flat = this.flatResults;
            if (this.activeIndex < 0 || this.activeIndex >= flat.length) return;
            this.selectItem(flat[this.activeIndex]);
        },

        isActive(item, isRecent = false) {
            return false; // Recent items use a separate check via activeIndex
        },

        setActiveByItem(item) {
            this.activeIndex = item._index ?? -1;
        },

        // ── Selection & Execution ──

        selectItem(item) {
            // In dependency mode
            if (this.currentDependency) {
                this.resolveCurrentDependency(item);
                return;
            }

            // Has unresolved dependencies?
            if (item.dependencies && item.dependencies.length > 0) {
                this.currentItem = item;
                this.currentDependencyIndex = 0;
                this.steps = [{ label: item.name, value: item.id }];
                this.query = '';
                this.remoteItems = [];
                this.activeIndex = -1;

                // If first dep is 'select', no input needed — show list immediately
                // Input focus stays — Alpine will show select options via groupedResults
                return;
            }

            // Execute the item
            this.executeItem(item);
        },

        resolveCurrentDependency(item) {
            const dep = this.currentDependency;
            const value = dep.type === 'select' ? item._value : (item.id ?? this.query);
            const label = item.name ?? this.query;

            // Add step pill
            this.steps.push({ label, value });

            this.currentDependencyIndex++;
            this.query = '';
            this.remoteItems = [];
            this.activeIndex = -1;

            // More dependencies?
            if (this.currentDependencyIndex < (this.currentItem.dependencies?.length ?? 0)) {
                return; // Continue to next dependency step
            }

            // All dependencies resolved — execute
            const resolvedValues = this.steps.slice(1).map(s => s.value);
            this.executeItem(this.currentItem, resolvedValues);
        },

        removeStep(index) {
            // Remove step and all after it
            this.steps = this.steps.slice(0, index);

            if (index === 0) {
                // Cancelled the whole flow
                this.currentItem = null;
                this.currentDependencyIndex = 0;
                this.steps = [];
            } else {
                this.currentDependencyIndex = index - 1; // Go back
            }

            this.query = '';
            this.remoteItems = [];
        },

        onBackspace(event) {
            if (this.query === '' && this.steps.length > 0) {
                event.preventDefault();
                this.removeStep(this.steps.length - 1);
            }
        },

        executeItem(item, resolvedDependencies = []) {
            // Save to history (skip items without a meaningful action)
            this.addToHistory(item);

            window.dispatchEvent(new CustomEvent('kore:spotlight:select', {
                detail: { item },
            }));

            switch (item.actionType) {
                case 'route':
                    this.$wire.executeRoute(item.actionTarget, item.actionParams ?? {});
                    break;

                case 'url': {
                    const newTab = item.actionParams?.newTab ?? false;
                    if (newTab) {
                        window.open(item.actionTarget, '_blank', 'noopener');
                    } else {
                        window.location.href = item.actionTarget;
                    }
                    break;
                }

                case 'action':
                    this.$dispatch('kore:spotlight-execute-action', {
                        method: item.actionTarget,
                        params: [...(item.actionParams ?? []), ...resolvedDependencies],
                    });
                    break;

                case 'dispatch':
                    this.$dispatch(item.actionTarget, item.actionParams ?? {});
                    break;

                default:
                    // No action defined — just close
                    break;
            }

            this.close();
        },

        trapFocus(e) {
            if (!this.isOpen) return;
            const focusable = this.$el.querySelectorAll('button, input, [tabindex]:not([tabindex="-1"])');
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last?.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first?.focus();
            }
        },

        // ── History (localStorage) ──

        getHistory() {
            try {
                return JSON.parse(localStorage.getItem(this.HISTORY_KEY) ?? '[]');
            } catch {
                return [];
            }
        },

        addToHistory(item) {
            if (!item.id || !item.name) return;
            const history = this.getHistory().filter(h => h.id !== item.id);
            history.unshift({
                id: item.id,
                name: item.name,
                icon: item.icon ?? null,
                at: Date.now(),
            });
            try {
                localStorage.setItem(
                    this.HISTORY_KEY,
                    JSON.stringify(history.slice(0, this.config.maxHistory))
                );
            } catch { /* storage full */ }
        },

        clearHistory() {
            try {
                localStorage.removeItem(this.HISTORY_KEY);
            } catch { /* noop */ }
            this.recentItems = [];
        },
    };
}
