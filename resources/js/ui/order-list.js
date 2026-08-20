/** Lee un nodo JSON de items; devuelve [] si no hay nada legible. */
function parsearItems(nodo) {
    try {
        const crudo = (nodo.textContent || '').trim();
        const datos = crudo ? JSON.parse(crudo) : [];
        return Array.isArray(datos) ? datos : [];
    } catch (e) {
        console.error('KoreOrderList: items ilegibles', e);
        return [];
    }
}

export default (config = {}) => ({
    /**
     * Los items NO viven en el `x-data`.
     *
     * La raíz lleva `wire:ignore`, así que lo que entre por el `x-data` se queda
     * con lo de la primera carga y un `:items` que cambie en el servidor no
     * llega nunca. Vienen de un nodo JSON de fuera, que Livewire sí actualiza.
     * `config.items` se sigue admitiendo para las pruebas de unidad.
     */
    items: config.items ?? [],

    order: [],
    _observadorItems: null,

    init() {
        this._leerItems();
        this._vigilarItems();

        const modelName = this._getModelName();
        const allValues = this.items.map((i) => i.value);

        if (modelName && this.$wire) {
            const initial = this._arr(this.$wire.$get(modelName));
            this.order = initial.length ? this._reconcile(initial, allValues) : allValues;
            this.$wire.$watch(modelName, (val) => {
                const incoming = this._reconcile(this._arr(val), allValues);
                if (JSON.stringify(incoming) !== JSON.stringify(this.order)) {
                    this.order = incoming;
                }
            });
        } else {
            this.order = allValues;
        }
    },

    _arr(v) {
        return Array.isArray(v) ? [...v] : [];
    },

    /** Los items del nodo JSON, si lo hay. */
    _leerItems() {
        if (! config.itemsId || typeof document === 'undefined') return;

        const nodo = document.getElementById(config.itemsId);
        if (nodo) this.items = parsearItems(nodo);
    },

    /**
     * Se vigila el CONTENEDOR y no el `<script>`: el morph lo sustituye entero,
     * así que un observador colgado de él se queda mirando un nodo desconectado.
     *
     * Al releer se reconcilia el orden: lo que el usuario había movido se
     * respeta y lo nuevo se añade al final, en vez de perderse el orden entero
     * cada vez que el servidor toca la lista.
     */
    _vigilarItems() {
        if (! config.itemsId || typeof MutationObserver === 'undefined') return;

        const contenedor = this.$el?.parentElement;
        if (! contenedor) return;

        this._observadorItems = new MutationObserver(() => {
            const nodo = document.getElementById(config.itemsId);
            if (! nodo) return;

            const nuevos = parsearItems(nodo);
            if (JSON.stringify(nuevos) === JSON.stringify(this.items)) return;

            this.items = nuevos;
            this.order = this._reconcile(this.order, nuevos.map((i) => i.value));
        });
        this._observadorItems.observe(contenedor, { childList: true, characterData: true, subtree: true });
    },

    destroy() {
        this._observadorItems?.disconnect();
        this._observadorItems = null;
    },

    // Keep only known values, then append any item missing from the stored order.
    _reconcile(stored, allValues) {
        const known = stored.filter((v) => allValues.includes(v));
        const missing = allValues.filter((v) => !known.includes(v));
        return [...known, ...missing];
    },

    get orderedItems() {
        const map = new Map((this.items || []).map((i) => [i.value, i]));
        return this.order.map((v) => map.get(v)).filter(Boolean);
    },

    // x-sort handler: value = the dragged item's key, position = its new index.
    move(value, position) {
        const from = this.order.indexOf(value);
        if (from === -1) return;
        this.order.splice(from, 1);
        this.order.splice(position, 0, value);
        this._sync();
    },

    moveUp(index) {
        if (index <= 0) return;
        [this.order[index - 1], this.order[index]] = [this.order[index], this.order[index - 1]];
        this._sync();
    },

    moveDown(index) {
        if (index >= this.order.length - 1) return;
        [this.order[index + 1], this.order[index]] = [this.order[index], this.order[index + 1]];
        this._sync();
    },

    _sync() {
        if (this.$wire) {
            const modelName = this._getModelName();
            if (modelName) {
                this.$wire.$set(modelName, [...this.order]);
                return;
            }
        }
        if (this.$refs?.hiddenInput) {
            this.$refs.hiddenInput.value = JSON.stringify(this.order);
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
