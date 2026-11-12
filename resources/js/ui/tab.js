export default (config) => ({
    selected: config.selected ?? null,
    tabs: [],
    variant: config.variant ?? 'line',
    orientation: config.orientation ?? 'horizontal',
    _observador: null,

    init() {
        // El indicador sigue a la pestaña elegida. Hay que volver a medirlo
        // cuando cambia la selección, cuando se añade o se cierra una pestaña y
        // cuando la lista cambia de tamaño —también cuando pasa de estar oculta
        // (dentro de un modal o de otra pestaña) a verse por primera vez—.
        this.$watch('selected', () => this._moverIndicador());
        this.$watch('tabs', () => this._moverIndicador());

        const lista = this.$refs.lista;
        if (lista && typeof ResizeObserver !== 'undefined') {
            this._observador = new ResizeObserver(() => this._moverIndicador());
            this._observador.observe(lista);
        }
    },

    destroy() {
        this._observador?.disconnect();
    },

    /**
     * Coloca el indicador sobre la pestaña elegida.
     *
     * Son cuatro variables y ninguna clase: la posición y el tamaño los pone
     * el CSS a partir de ellas, y la transición también, así que apagar el
     * movimiento no pasa por aquí.
     */
    _moverIndicador() {
        this.$nextTick(() => {
            const indicador = this.$refs.indicador;
            if (!indicador) return;

            const boton = this.selected === null
                ? null
                : this.$refs.lista?.querySelector(`[data-tab-id="${CSS.escape(String(this.selected))}"]`);

            // Sin pestaña elegida, o con la lista sin pintar (ancho cero), no
            // hay dónde ponerlo: se esconde en vez de quedarse en la esquina.
            if (!boton || boton.offsetWidth === 0) {
                indicador.removeAttribute('data-ready');
                return;
            }

            indicador.style.setProperty('--kore-tab-x', boton.offsetLeft + 'px');
            indicador.style.setProperty('--kore-tab-y', boton.offsetTop + 'px');
            indicador.style.setProperty('--kore-tab-w', boton.offsetWidth + 'px');
            indicador.style.setProperty('--kore-tab-h', boton.offsetHeight + 'px');

            // La primera vez aparece ya en su sitio: `data-ready` es lo que
            // enciende la transición, y se pone un frame después de colocarlo.
            if (!indicador.hasAttribute('data-ready')) {
                requestAnimationFrame(() => indicador.setAttribute('data-ready', ''));
            }
        });
    },

    registerTab(tab) {
        // Avoid duplicate registration
        if (!this.tabs.find(t => t.id === tab.id)) {
            this.tabs.push(tab);
        }

        // La selección por defecto se decide AQUÍ y no en init().
        //
        // Estaba en un `$nextTick` dentro de `init()`, que corre antes de que
        // los items se hayan registrado: `this.tabs` seguía vacío, la condición
        // no se cumplía y nadie volvía a intentarlo. Un `<x-kore::tab>` sin
        // `selected` se quedaba con las pestañas pintadas y NINGÚN panel debajo,
        // para siempre, hasta que el usuario pulsaba una.
        if (this.selected === null && !tab.disabled) {
            this.selected = tab.id;
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
