// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreTransfer from '../../resources/js/ui/transfer.js';

const ITEMS = [
    { value: 'a', label: 'Alpha' },
    { value: 'b', label: 'Beta' },
    { value: 'c', label: 'Gamma' },
];

/**
 * Los items llegan de un nodo JSON de fuera del `wire:ignore`, no del `x-data`.
 *
 * Dentro del `x-data` se quedaban con los de la primera carga: medido en un
 * navegador, el servidor pasaba de cuatro elementos a cinco y los dos paneles
 * seguían enseñando cuatro para siempre.
 */
describe('KoreTransfer · items desde el nodo JSON', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    function montar(json, contenido = ITEMS) {
        document.body.innerHTML = `
            <div id="contenedor">
                <script type="application/json" id="${json}">${JSON.stringify(contenido)}</script>
                <div id="raiz"></div>
            </div>`;

        const t = KoreTransfer({ itemsId: json });
        t.$el = document.getElementById('raiz');
        t.$refs = {};
        t.init();
        return t;
    }

    it('los lee del nodo al arrancar, sin recibirlos por config', () => {
        const t = montar('items-1');
        expect(t.items.map((i) => i.value)).toEqual(['a', 'b', 'c']);
        expect(t.sourceItems).toHaveLength(3);
    });

    it('se entera cuando el servidor cambia el nodo', async () => {
        const t = montar('items-2');
        expect(t.items).toHaveLength(3);

        // El morph sustituye el <script> entero, no edita su texto: por eso se
        // vigila el CONTENEDOR y se vuelve a resolver el nodo por id.
        const viejo = document.getElementById('items-2');
        const nuevo = document.createElement('script');
        nuevo.type = 'application/json';
        nuevo.id = 'items-2';
        nuevo.textContent = JSON.stringify([...ITEMS, { value: 'd', label: 'Delta' }]);
        viejo.replaceWith(nuevo);

        await new Promise((r) => setTimeout(r, 20));
        expect(t.items).toHaveLength(4);
        expect(t.sourceItems.map((i) => i.value)).toContain('d');
    });

    it('aguanta un nodo ilegible sin romperse', () => {
        document.body.innerHTML = `
            <div id="contenedor">
                <script type="application/json" id="roto">{no es json}</script>
                <div id="raiz"></div>
            </div>`;
        const espia = vi.spyOn(console, 'error').mockImplementation(() => {});

        const t = KoreTransfer({ itemsId: 'roto' });
        t.$el = document.getElementById('raiz');
        t.$refs = {};
        t.init();

        expect(t.items).toEqual([]);
        expect(t.sourceItems).toEqual([]);
        espia.mockRestore();
    });
});
