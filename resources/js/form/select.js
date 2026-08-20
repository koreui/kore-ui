import { startFloating, stopFloating } from '../utils/floating.js';

/** Lee y parsea el nodo JSON de opciones. Ante un JSON roto, lista vacía. */
function parsearOpciones(nodo) {
    try {
        const crudo = (nodo.textContent || '').trim();
        const datos = crudo ? JSON.parse(crudo) : [];
        return Array.isArray(datos) ? datos : [];
    } catch (e) {
        console.error('KoreSelect: opciones ilegibles', e);
        return [];
    }
}

/** Opciones iniciales: del nodo JSON si existe, del config si no (tests). */
function leerOpciones(config) {
    if (config.options) return config.options;
    if (typeof document === 'undefined' || ! config.optionsId) return [];

    const nodo = document.getElementById(config.optionsId);
    return nodo ? parsearOpciones(nodo) : [];
}

export default (config) => ({
    open: false,
    search: '',
    selected: config.multiple ? (config.value ?? []) : (config.value ?? null),
    options: leerOpciones(config),
    highlighted: -1,
    loading: false,
    abortController: null,

    // Sin declarar, Alpine las escribiría en el x-data ancestro, no aquí — y `_floatingCleanup`
    // lo comparten seis componentes. Ver tests/js/alpine-scope.test.js.
    _floatingCleanup: null,
    _boundMousedown: null,
    _debounceTimer: null,
    _observadorOpciones: null,

    init() {
        this._vigilarOpciones();

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
                // Con el desplegable cerrado, Enter tiene que abrirlo. Antes
                // caía aquí, se comía el evento con preventDefault() y no hacía
                // nada más: el disparador es un <button>, así que sin ese
                // preventDefault el navegador habría disparado el click por su
                // cuenta. Resultado: desde el teclado solo se podía abrir con
                // las flechas, y quien pulsaba Enter —lo que hace cualquiera
                // sobre un botón— se quedaba sin respuesta y sin señal.
                if (! this.open) { this.openDropdown(); return; }

                if (this.highlighted >= 0 && opts[this.highlighted]) {
                    this.select(opts[this.highlighted]);
                } else if (this.showCreateOption && this.highlighted === opts.length) {
                    this.createFromSearch();
                }
                break;
            case 'Escape':
                // Solo si hay algo que cerrar. Con el desplegable ya cerrado
                // seguía llamando a preventDefault(), y ese Escape «consumido»
                // no llegaba a quien de verdad le tocaba: dentro de un modal, el
                // primer Escape cerraba el panel y el segundo no cerraba nada,
                // porque el select se lo quedaba otra vez.
                if (! this.open) return;

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

    /**
     * Vuelve a leer las opciones cuando el servidor las cambia.
     *
     * La raíz del componente lleva `wire:ignore`, así que su `x-data` se queda
     * con lo de la primera carga. Las opciones viven fuera, en un nodo JSON que
     * Livewire sí actualiza al hacer morph; esto se entera de ese cambio.
     *
     * Sin ello, un select dependiente —provincia según país— seguía enseñando
     * la lista original: el estado del componente sí cambiaba, pero el panel
     * está teleportado a `body` y nunca volvía a pintarse.
     */
    _vigilarOpciones() {
        if (config.async) return;   // en modo async las opciones las trae fetch

        if (typeof MutationObserver === 'undefined') return;

        // Se vigila el CONTENEDOR, no el nodo de opciones: al hacer morph
        // Livewire sustituye el <script> entero, así que un observador colgado
        // de él se queda mirando un nodo ya desconectado. Por eso también se
        // vuelve a resolver por id en cada aviso.
        const contenedor = this.$el.parentElement;
        if (! contenedor) return;

        // Se busca por atributo dentro del propio campo y no por id: para un
        // select sin `name` el id sale de `uniqid()`, que cambia en cada render,
        // así que el id que quedó congelado en el `x-data` deja de existir en
        // cuanto el servidor vuelve a pintar.
        const releer = () => {
            const nodo = contenedor.querySelector('script[data-kore-select-options]');
            if (! nodo) return;
            const nuevas = parsearOpciones(nodo);
            if (JSON.stringify(nuevas) !== JSON.stringify(this.options)) this.options = nuevas;
        };

        this._observadorOpciones = new MutationObserver(releer);
        this._observadorOpciones.observe(contenedor, { childList: true, characterData: true, subtree: true });
    },

    destroy() {
        this._observadorOpciones?.disconnect();
        this._observadorOpciones = null;
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
