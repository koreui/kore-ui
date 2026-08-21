/** Lee el nodo JSON donde viaja el árbol. Devuelve `null` si no hay o no parsea. */
function leerNodos(nodo) {
    if (! nodo) return null;

    try {
        const datos = JSON.parse(nodo.textContent || '[]');

        return Array.isArray(datos) ? datos : null;
    } catch {
        return null;
    }
}

export default (config = {}) => ({
    nodes: config.nodes || [],
    selectable: config.selectable || false,
    selectionMode: config.selectionMode || 'single',
    expandedKeys: new Set(config.expandedKeys || []),
    selectedKeys: new Set(config.selectedKeys || []),
    filter: config.filter || false,
    filterText: '',

    /** Qué nodo entra en el tabulador. Un tree tiene UNA parada, no una por nodo. */
    focusKey: null,

    /** Textos del chevrón, para que cada uno diga de qué rama es. */
    labels: config.labels || { expand: 'Abrir', collapse: 'Cerrar' },

    // Sin declarar, Alpine lo escribiría en el x-data ancestro y no aquí.
    // Ver tests/js/alpine-scope.test.js.
    _observadorNodos: null,

    init() {
        // El estado arranca vacío a propósito: los nodos llegan del `<script>`
        // en este mismo tick, y tenerlos además dentro del `x-data` doblaría el
        // JSON en el HTML.
        this._leerNodosDelServidor();
        this._vigilarNodos();
    },

    destroy() {
        this._observadorNodos?.disconnect();
        this._observadorNodos = null;
    },

    /**
     * Vuelve a leer los nodos cuando el servidor los cambia.
     *
     * La raíz lleva `wire:ignore`, así que el `x-data` se queda con lo de la
     * primera carga: sin él, el morph de Livewire reemplazaba el `<template
     * x-for>` por el del servidor y el árbol se quedaba MUERTO —medido: el
     * estado pasaba a nueve filas y el DOM se quedaba en siete, y ni tocando el
     * estado a mano volvía a pintar—.
     *
     * A cambio, los nodos viajan fuera del `wire:ignore`, en un `<script>` que
     * Livewire sí actualiza. Mismo mecanismo que las opciones de
     * `<x-kore::select>` (`resources/js/form/select.js`).
     */
    _leerNodosDelServidor() {
        const nuevos = leerNodos(this._nodoJson());

        if (nuevos && JSON.stringify(nuevos) !== JSON.stringify(this.nodes)) {
            this.nodes = nuevos;
        }
    },

    _nodoJson() {
        // Se resuelve en cada aviso y no se cachea: al hacer morph Livewire
        // SUSTITUYE el <script> entero en vez de editarlo, así que una
        // referencia guardada apuntaría a un nodo ya desconectado.
        return this.$el.parentElement?.querySelector('script[data-kore-tree-nodes]') ?? null;
    },

    _vigilarNodos() {
        if (typeof MutationObserver === 'undefined') return;

        // Se vigila el CONTENEDOR por el mismo motivo: el <script> no sobrevive
        // al morph, pero su padre sí.
        const contenedor = this.$el.parentElement;
        if (! contenedor) return;

        this._observadorNodos = new MutationObserver(() => this._leerNodosDelServidor());
        this._observadorNodos.observe(contenedor, { childList: true, characterData: true, subtree: true });
    },

    get flatNodes() {
        const result = [];
        const walk = (nodes, level, parentVisible) => {
            // `posicion` y `hermanos` alimentan `aria-posinset` y `aria-setsize`:
            // sin ellos un lector dice en qué NIVEL está un nodo, pero no por
            // cuál de sus hermanos va. Se cuentan los que pasan el filtro, no
            // todos: anunciar «3 de 7» con dos a la vista sería mentir.
            const delNivel = nodes.filter((n) => this._matchesFilter(n));
            let posicion = 0;

            nodes.forEach(node => {
                const hasChildren = node.children && node.children.length > 0;
                const matchesFilter = this._matchesFilter(node);
                const visible = parentVisible && matchesFilter;

                if (matchesFilter) posicion++;

                result.push({
                    node, level, hasChildren, visible,
                    posicion: matchesFilter ? posicion : 0,
                    hermanos: delNivel.length,
                });

                if (hasChildren) {
                    const childrenVisible = visible && this.expandedKeys.has(node.key);
                    walk(node.children, level + 1, childrenVisible);
                }
            });
        };
        walk(this.nodes, 0, true);
        return result;
    },

    _matchesFilter(node) {
        if (!this.filterText) return true;
        const text = this.filterText.toLowerCase();

        if (node.label && node.label.toLowerCase().includes(text)) return true;

        if (node.children) {
            return node.children.some(child => this._matchesFilter(child));
        }
        return false;
    },

    isExpanded(key) {
        return this.expandedKeys.has(key);
    },

    /**
     * El nodo que entra en el tabulador: el que tenga el foco, o el primero
     * visible. Es el patrón de un `tree`: una sola parada, y flechas dentro.
     */
    esFoco(key) {
        if (this.focusKey !== null) return this.focusKey === key;

        return this.flatNodes.find((i) => i.visible)?.node.key === key;
    },

    /** «Abrir Documentos» en vez de «Toggle expand» cinco veces seguidas. */
    etiquetaDeChevron(item) {
        const verbo = this.isExpanded(item.node.key) ? this.labels.collapse : this.labels.expand;

        return `${verbo} ${item.node.label ?? ''}`.trim();
    },

    /**
     * Teclado del árbol.
     *
     * No había ninguno: los `treeitem` tenían `tabindex="-1"` y el único
     * enfocable de cada fila era el chevrón, así que con `selectable` no había
     * forma de elegir un nodo sin ratón. Sigue el patrón ARIA de un tree.
     */
    onKeydown(e) {
        const visibles = this.flatNodes.filter((i) => i.visible);
        if (visibles.length === 0) return;

        const actual = this.focusKey ?? visibles[0].node.key;
        const indice = visibles.findIndex((i) => i.node.key === actual);
        if (indice === -1) return;

        const item = visibles[indice];
        let destino = null;

        switch (e.key) {
            case 'ArrowDown':
                destino = visibles[Math.min(indice + 1, visibles.length - 1)];
                break;

            case 'ArrowUp':
                destino = visibles[Math.max(indice - 1, 0)];
                break;

            case 'Home':
                destino = visibles[0];
                break;

            case 'End':
                destino = visibles[visibles.length - 1];
                break;

            case 'ArrowRight':
                // Abre la rama; si ya está abierta, entra en el primer hijo.
                if (item.hasChildren && !this.isExpanded(item.node.key)) {
                    this.toggleExpand(item.node.key);
                } else if (item.hasChildren) {
                    destino = visibles[indice + 1];
                }
                break;

            case 'ArrowLeft':
                // Cierra la rama; si ya está cerrada, sube al padre.
                if (item.hasChildren && this.isExpanded(item.node.key)) {
                    this.toggleExpand(item.node.key);
                } else {
                    for (let i = indice - 1; i >= 0; i--) {
                        if (visibles[i].level < item.level) { destino = visibles[i]; break; }
                    }
                }
                break;

            case 'Enter':
            case ' ':
                this.onNodeClick(item.node);
                break;

            default:
                return;
        }

        e.preventDefault();

        if (destino) this._enfocar(destino.node.key);
    },

    _enfocar(key) {
        this.focusKey = key;

        this.$nextTick(() => {
            // Se busca comparando el atributo en vez de construir un selector:
            // una clave puede traer comillas o corchetes, y `CSS.escape` no está
            // en todos los entornos donde corre este código.
            const destino = [...this.$el.querySelectorAll('[data-kore-tree-key]')]
                .find((el) => el.getAttribute('data-kore-tree-key') === String(key));

            destino?.focus();
        });
    },

    isSelected(key) {
        return this.selectedKeys.has(key);
    },

    toggleExpand(key) {
        if (this.expandedKeys.has(key)) {
            this.expandedKeys.delete(key);
        } else {
            this.expandedKeys.add(key);
        }
        this.expandedKeys = new Set(this.expandedKeys);
    },

    toggleSelect(key) {
        if (!this.selectable) return;

        if (this.selectionMode === 'single') {
            if (this.selectedKeys.has(key)) {
                this.selectedKeys = new Set();
            } else {
                this.selectedKeys = new Set([key]);
            }
        } else {
            if (this.selectedKeys.has(key)) {
                this.selectedKeys.delete(key);
            } else {
                this.selectedKeys.add(key);
            }
            this.selectedKeys = new Set(this.selectedKeys);
        }

        this.$dispatch('tree-selection-change', {
            selectedKeys: Array.from(this.selectedKeys),
        });
    },

    onNodeClick(node) {
        if (this.selectable) {
            this.toggleSelect(node.key);
        }
        if (node.children && node.children.length > 0) {
            this.toggleExpand(node.key);
        }
    },

    isVisible(node) {
        return this._matchesFilter(node);
    },

    expandAll() {
        const keys = [];
        const collect = (nodes) => {
            nodes.forEach(node => {
                if (node.children && node.children.length > 0) {
                    keys.push(node.key);
                    collect(node.children);
                }
            });
        };
        collect(this.nodes);
        this.expandedKeys = new Set(keys);
    },

    collapseAll() {
        this.expandedKeys = new Set();
    },
});
