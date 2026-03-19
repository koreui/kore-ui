import { startFloating, stopFloating } from '../utils/floating.js';

export default (config) => ({
    open: false,
    search: '',
    selected: config.multiple ? (config.value ?? []) : (config.value ?? null),
    options: config.options ?? [],
    highlighted: -1,
    loading: false,
    abortController: null,

    init() {
        const input = this.$refs.hiddenInput;

        if (config.multiple && input) {
            const wireModel = input.getAttribute('wire:model.live')
                || input.getAttribute('wire:model.blur')
                || input.getAttribute('wire:model.defer')
                || input.getAttribute('wire:model');

            // Read initial array from $wire (arrays don't serialize to hidden input value)
            if (wireModel && this.$wire) {
                const initial = this.$wire.$get(wireModel);
                if (Array.isArray(initial)) {
                    this.selected = [...initial];
                }

                // Watch Livewire re-syncs (e.g. $this->reset())
                this.$wire.$watch(wireModel, (val) => {
                    const newVal = Array.isArray(val) ? val : [];
                    if (JSON.stringify(newVal) !== JSON.stringify(this.selected)) {
                        this.selected = newVal;
                    }
                });
            } else {
                this._syncFromInput(input);
            }
        } else {
            // Single select: sync from hidden input
            this._syncFromInput(input);

            // Watch Livewire re-syncs (e.g. resetAllFilters, resetFilter)
            if (input && this.$wire) {
                const wireModel = input.getAttribute('wire:model.live')
                    || input.getAttribute('wire:model.blur')
                    || input.getAttribute('wire:model.defer')
                    || input.getAttribute('wire:model');
                if (wireModel) {
                    this.$wire.$watch(wireModel, (val) => {
                        const newVal = (val === null || val === undefined) ? null : String(val);
                        if (newVal !== String(this.selected ?? '')) {
                            this.selected = newVal === '' ? null : newVal;
                        }
                    });
                }
            }

            // Livewire sets the input property after morph — catch it on next tick
            if (input && !input.value) {
                this.$nextTick(() => this._syncFromInput(input));
            }
        }
    },

    _syncFromInput(input) {
        if (!input?.value) return;
        const val = input.value;
        if (config.multiple) {
            try { this.selected = JSON.parse(val); } catch { this.selected = []; }
        } else if (val !== String(this.selected ?? '')) {
            this.selected = val;
        }
    },

    destroy() {
        this._cleanup();
    },

    toggle() {
        this.open ? this.close() : this.openDropdown();
    },

    openDropdown() {
        this.open = true;
        this.highlighted = -1;
        this.search = '';
        this.$nextTick(() => {
            this.$refs.search?.focus();
            this._floatingCleanup = startFloating(this.$refs.trigger, this.$refs.dropdown, {
                placement: 'bottom-start',
                offset: 4,
                sameWidth: true,
            });
            this._addClickAwayListener();
        });
    },

    close() {
        if (!this.open) return;
        this.open = false;
        this.search = '';
        this.highlighted = -1;
        this._cleanup();
    },

    _cleanup() {
        stopFloating(this._floatingCleanup);
        this._floatingCleanup = null;
        this._removeClickAwayListener();
    },

    _onMousedown(e) {
        if (!this.open) return;
        if (this.$refs.trigger?.contains(e.target)) return;
        if (this.$refs.dropdown?.contains(e.target)) return;
        this.close();
    },

    _addClickAwayListener() {
        this._removeClickAwayListener();
        this._boundMousedown = (e) => this._onMousedown(e);
        document.addEventListener('mousedown', this._boundMousedown);
    },

    _removeClickAwayListener() {
        if (this._boundMousedown) {
            document.removeEventListener('mousedown', this._boundMousedown);
            this._boundMousedown = null;
        }
    },

    get showCreateOption() {
        if (!config.creatable) return false;
        if (!this.search || !this.search.trim()) return false;
        const term = this.search.trim().toLowerCase();
        return !this.options.some(opt => {
            const label = String(this.getLabel(opt)).toLowerCase();
            return label === term;
        });
    },

    createFromSearch() {
        if (!this.search || !this.search.trim()) return;
        const text = this.search.trim();
        const newOption = {};
        newOption[config.optionLabel || 'label'] = text;
        newOption[config.optionValue || 'value'] = text;
        this.options.push(newOption);
        this.select(newOption);
        this.search = '';
        if (!config.multiple) this.close();
    },

    get filteredOptions() {
        if (!this.search) return this.options;
        const term = this.search.toLowerCase();
        return this.options.filter(opt => {
            const label = (typeof opt === 'object' ? opt[config.optionLabel || 'label'] : opt);
            return String(label).toLowerCase().includes(term);
        });
    },

    getLabel(opt) {
        if (typeof opt === 'object' && opt !== null) return opt[config.optionLabel || 'label'];
        return opt;
    },

    getValue(opt) {
        if (typeof opt === 'object' && opt !== null) return opt[config.optionValue || 'value'];
        return opt;
    },

    getDescription(opt) {
        if (!config.optionDescription || typeof opt !== 'object') return null;
        return opt[config.optionDescription] ?? null;
    },

    getImage(opt) {
        if (!config.optionImage || typeof opt !== 'object') return null;
        return opt[config.optionImage] ?? null;
    },

    select(opt) {
        const val = this.getValue(opt);
        if (config.multiple) {
            const idx = this.selected.indexOf(val);
            if (idx > -1) {
                this.selected.splice(idx, 1);
            } else {
                if (config.max && this.selected.length >= config.max) return;
                this.selected.push(val);
            }
        } else {
            this.selected = val;
            this.close();
        }
        this.syncModel();
    },

    deselect(val) {
        if (!config.multiple) return;
        this.selected = this.selected.filter(v => v !== val);
        this.syncModel();
    },

    isSelected(opt) {
        const val = this.getValue(opt);
        if (config.multiple) return this.selected.includes(val);
        return String(this.selected) === String(val);
    },

    clear() {
        this.selected = config.multiple ? [] : null;
        this.syncModel();
    },

    get displayValue() {
        if (config.multiple) return '';
        if (this.selected === null || this.selected === '') return '';
        const opt = this.options.find(o => String(this.getValue(o)) === String(this.selected));
        return opt ? this.getLabel(opt) : this.selected;
    },

    get selectedOptions() {
        if (!config.multiple) return [];
        return this.selected.map(val => {
            const opt = this.options.find(o => String(this.getValue(o)) === String(val));
            return opt ? { value: val, label: this.getLabel(opt) } : { value: val, label: val };
        });
    },

    get hasValue() {
        if (config.multiple) return this.selected.length > 0;
        return this.selected !== null && this.selected !== '';
    },

    syncModel() {
        if (!this.$refs.hiddenInput) return;

        // For multi-select with wire:model, use $wire to set array directly
        // (hidden input can only hold strings, but Livewire expects an array)
        if (config.multiple && this.$wire) {
            const modelName = this.$refs.hiddenInput.getAttribute('wire:model.live')
                || this.$refs.hiddenInput.getAttribute('wire:model.blur')
                || this.$refs.hiddenInput.getAttribute('wire:model.defer')
                || this.$refs.hiddenInput.getAttribute('wire:model');
            if (modelName) {
                this.$wire.$set(modelName, [...this.selected]);
                return;
            }
        }

        const val = this.selected ?? '';
        this.$refs.hiddenInput.value = val;
        this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    },

    onKeydown(e) {
        const opts = this.filteredOptions;
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (!this.open) { this.openDropdown(); return; }
                const maxDown = opts.length + (this.showCreateOption ? 1 : 0);
                this.highlighted = maxDown > 0 ? (this.highlighted + 1) % maxDown : -1;
                this.scrollToHighlighted();
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (!this.open) { this.openDropdown(); return; }
                const maxUp = opts.length + (this.showCreateOption ? 1 : 0);
                this.highlighted = this.highlighted <= 0 ? maxUp - 1 : this.highlighted - 1;
                this.scrollToHighlighted();
                break;
            case 'Enter':
                e.preventDefault();
                if (this.open && this.highlighted >= 0 && opts[this.highlighted]) {
                    this.select(opts[this.highlighted]);
                } else if (this.open && this.showCreateOption && this.highlighted === opts.length) {
                    this.createFromSearch();
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

    scrollToHighlighted() {
        this.$nextTick(() => {
            const el = this.$refs.dropdown?.querySelector(`[data-index="${this.highlighted}"]`);
            el?.scrollIntoView({ block: 'nearest' });
        });
    },

    // Async search
    onSearch() {
        if (!config.async) return;
        if (config.minSearch && this.search.length < config.minSearch) {
            this.options = [];
            return;
        }

        if (this.abortController) this.abortController.abort();
        this.abortController = new AbortController();
        this.loading = true;

        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(async () => {
            try {
                const url = new URL(config.async, window.location.origin);
                url.searchParams.set('search', this.search);
                const res = await fetch(url, { signal: this.abortController.signal });
                if (res.ok) {
                    this.options = await res.json();
                }
            } catch (e) {
                if (e.name !== 'AbortError') console.error('KoreSelect fetch error:', e);
            } finally {
                this.loading = false;
            }
        }, config.debounce || 300);
    },
});
