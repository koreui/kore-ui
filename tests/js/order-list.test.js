import { describe, expect, it } from 'vitest';
import KoreOrderList from '../../resources/js/ui/order-list.js';

// Valores de TEXTO con guiones a propósito: con números, un valor que acabe
// dentro de una expresión de Alpine evalúa a una resta sin quejarse, y así se
// escondieron dos defectos durante versiones.
const ITEMS = [
    { value: 'item-uno', label: 'Uno' },
    { value: 'item-dos', label: 'Dos' },
    { value: 'item-tres', label: 'Tres' },
];

const [UNO, DOS, TRES] = ITEMS.map((i) => i.value);

function makeOrderList(config = {}) {
    return KoreOrderList({ items: ITEMS, ...config });
}

describe('KoreOrderList (ui/order-list.js)', () => {
    it('derives ordered items from the order array', () => {
        const ol = makeOrderList();
        ol.order = [TRES, UNO, DOS];
        expect(ol.orderedItems.map((i) => i.label)).toEqual(['Tres', 'Uno', 'Dos']);
    });

    it('moves an item to a new position by value', () => {
        const ol = makeOrderList();
        ol.order = [UNO, DOS, TRES];
        ol.move(UNO, 2);
        expect(ol.order).toEqual([DOS, TRES, UNO]);
    });

    it('moves an item up', () => {
        const ol = makeOrderList();
        ol.order = [UNO, DOS, TRES];
        ol.moveUp(2);
        expect(ol.order).toEqual([UNO, TRES, DOS]);
    });

    it('moves an item down', () => {
        const ol = makeOrderList();
        ol.order = [UNO, DOS, TRES];
        ol.moveDown(0);
        expect(ol.order).toEqual([DOS, UNO, TRES]);
    });

    it('does not move up past the top', () => {
        const ol = makeOrderList();
        ol.order = [UNO, DOS, TRES];
        ol.moveUp(0);
        expect(ol.order).toEqual([UNO, DOS, TRES]);
    });

    it('reconciles a stored order against known values, appending missing', () => {
        const ol = makeOrderList();
        expect(ol._reconcile([DOS, 'ya-no-existe'], [UNO, DOS, TRES])).toEqual([DOS, UNO, TRES]);
    });
});
