// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreOrderList from '../../resources/js/ui/order-list.js';

const ITEMS = [
    { value: 'item-uno', label: 'Uno' },
    { value: 'item-dos', label: 'Dos' },
    { value: 'item-tres', label: 'Tres' },
];

const [UNO, DOS, TRES] = ITEMS.map((i) => i.value);

/**
 * Mismo arreglo que en el transfer: los items vienen de un nodo JSON de fuera
 * del `wire:ignore`, porque dentro del `x-data` se quedaban congelados.
 */
describe('KoreOrderList · items desde el nodo JSON', () => {
    it('los lee del nodo y respeta lo que el usuario había movido', async () => {
        document.body.innerHTML = `
            <div id="contenedor">
                <script type="application/json" id="ol-items">${JSON.stringify(ITEMS)}</script>
                <div id="raiz"></div>
            </div>`;

        const ol = KoreOrderList({ itemsId: 'ol-items' });
        ol.$el = document.getElementById('raiz');
        ol.$refs = {};
        ol.init();

        expect(ol.order).toEqual([UNO, DOS, TRES]);

        ol.moveUp(2);                       // el usuario sube «Tres»
        expect(ol.order).toEqual([UNO, TRES, DOS]);

        const nuevo = document.createElement('script');
        nuevo.type = 'application/json';
        nuevo.id = 'ol-items';
        nuevo.textContent = JSON.stringify([...ITEMS, { value: 'item-cuatro', label: 'Cuatro' }]);
        document.getElementById('ol-items').replaceWith(nuevo);

        await new Promise((r) => setTimeout(r, 20));

        // Lo nuevo se añade al final; lo que el usuario movió sigue donde estaba.
        expect(ol.order).toEqual([UNO, TRES, DOS, 'item-cuatro']);
    });
});
