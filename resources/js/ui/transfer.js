/** Lee un nodo JSON de items; devuelve [] si no hay nada legible. */
function parsearItems(nodo) {
    try {
        const crudo = (nodo.textContent || '').trim();
        const datos = crudo ? JSON.parse(crudo) : [];
        return Array.isArray(datos) ? datos : [];
    } catch (e) {
        console.error('KoreTransfer: items ilegibles', e);
        return [];
    }
}

export default (config = {}) => ({
    /**
     * Los items NO viven en el `x-data`.
     *
     * La raíz del componente lleva `wire:ignore`, así que lo que entre por el
     * `x-data` se queda con lo de la primera carga: medido, el servidor pasaba
     * de cuatro elementos a cinco y los dos paneles seguían enseñando cuatro
     * para siempre. Llegan de un nodo JSON que vive fuera del `wire:ignore` y
     * que Livewire sí actualiza, y un observador se entera cuando cambia.
     *
     * `config.items` sigue admitiéndose para las pruebas de unidad, donde no hay
     * DOM que leer.
     */
    items: config.items ?? [],

    target: [],
    checkedSource: [],
    checkedTarget: [],
    sourceSearch: '',
    targetSearch: '',

    _observadorItems: null,

    init() {
        this._leerItems();
        this._vigilarItems();

        const modelName = this._getModelName();

        if (modelName && this.$wire) {
            this.target = this._arr(this.$wire.$get(modelName));
            this.$wire.$watch(modelName, (val) => {
                const incoming = this._arr(val);
                if (JSON.stringify(incoming) !== JSON.stringify(this.target)) {
                    this.target = incoming;
                }
            });
        } else {
            const val = this.$refs?.hiddenInput?.value;
            if (val) {
                try { this.target = this._arr(JSON.parse(val)); } catch { this.target = []; }
            }
        }
    },

    _arr(v) {
        return Array.isArray(v) ? [...v] : [];
    },

    _items() {
        return this.items || [];
    },

    /** Los items del nodo JSON, si lo hay. */
    _leerItems() {
        if (! config.itemsId || typeof document === 'undefined') return;

        const nodo = document.getElementById(config.itemsId);
        if (nodo) this.items = parsearItems(nodo);
    },

    /**
     * Se vigila el CONTENEDOR y no el `<script>`: al hacer morph Livewire
     * sustituye el nodo entero, así que un observador colgado de él se queda
     * mirando algo ya desconectado. Mismo motivo que en el select.
     */
    _vigilarItems() {
        if (! config.itemsId || typeof MutationObserver === 'undefined') return;

        const contenedor = this.$el?.parentElement;
        if (! contenedor) return;

        this._observadorItems = new MutationObserver(() => {
            const nodo = document.getElementById(config.itemsId);
            if (! nodo) return;

            const nuevos = parsearItems(nodo);
            if (JSON.stringify(nuevos) !== JSON.stringify(this.items)) this.items = nuevos;
        });
        this._observadorItems.observe(contenedor, { childList: true, characterData: true, subtree: true });
    },

    destroy() {
        this._observadorItems?.disconnect();
        this._observadorItems = null;
    },

    _match(item, query) {
        if (!query) return true;
        return String(item.label).toLowerCase().includes(query.toLowerCase());
    },

    get sourceItems() {
        return this._items().filter((i) => !this.target.includes(i.value) && this._match(i, this.sourceSearch));
    },

    get targetItems() {
        return this._items().filter((i) => this.target.includes(i.value) && this._match(i, this.targetSearch));
    },

    get availableCount() {
        return this._items().filter((i) => !this.target.includes(i.value)).length;
    },

    get selectedCount() {
        return this.target.length;
    },

    isChecked(list, value) {
        return (list === 'source' ? this.checkedSource : this.checkedTarget).includes(value);
    },

    toggleCheck(list, value) {
        const arr = list === 'source' ? this.checkedSource : this.checkedTarget;
        const idx = arr.indexOf(value);
        if (idx === -1) arr.push(value); else arr.splice(idx, 1);
    },

    moveToTarget() {
        for (const v of this.checkedSource) {
            if (!this.target.includes(v)) this.target.push(v);
        }
        this.checkedSource = [];
        this._sync();
    },

    moveToSource() {
        this.target = this.target.filter((v) => !this.checkedTarget.includes(v));
        this.checkedTarget = [];
        this._sync();
    },

    moveAllToTarget() {
        this.target = this._items().map((i) => i.value);
        this.checkedSource = [];
        this._sync();
    },

    moveAllToSource() {
        this.target = [];
        this.checkedTarget = [];
        this._sync();
    },

    _sync() {
        if (this.$wire) {
            const modelName = this._getModelName();
            if (modelName) {
                this.$wire.$set(modelName, [...this.target]);
                return;
            }
        }
        if (this.$refs?.hiddenInput) {
            this.$refs.hiddenInput.value = JSON.stringify(this.target);
            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },

    _getModelName() {
        const input = this.$refs?.hiddenInput;
        if (!input) return null;
        return input.getAttribute('wire:model.live')
            || input.getAttribute('wire:model.blur')
            || input.getAttribute('wire:model.defer')
            || input.getAttribute('wire:model');
    },
});
