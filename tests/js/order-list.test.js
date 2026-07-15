import { describe, expect, it } from 'vitest';
import KoreOrderList from '../../resources/js/ui/order-list.js';

const ITEMS = [
    { value: 1, label: 'Uno' },
    { value: 2, label: 'Dos' },
    { value: 3, label: 'Tres' },
];

function makeOrderList(config = {}) {
    return KoreOrderList({ items: ITEMS, ...config });
}

describe('KoreOrderList (ui/order-list.js)', () => {
    it('derives ordered items from the order array', () => {
        const ol = makeOrderList();
        ol.order = [3, 1, 2];
        expect(ol.orderedItems.map((i) => i.label)).toEqual(['Tres', 'Uno', 'Dos']);
    });

    it('moves an item to a new position by value', () => {
        const ol = makeOrderList();
        ol.order = [1, 2, 3];
        ol.move(1, 2);
        expect(ol.order).toEqual([2, 3, 1]);
    });

    it('moves an item up', () => {
        const ol = makeOrderList();
        ol.order = [1, 2, 3];
        ol.moveUp(2);
        expect(ol.order).toEqual([1, 3, 2]);
    });

    it('moves an item down', () => {
        const ol = makeOrderList();
        ol.order = [1, 2, 3];
        ol.moveDown(0);
        expect(ol.order).toEqual([2, 1, 3]);
    });

    it('does not move up past the top', () => {
        const ol = makeOrderList();
        ol.order = [1, 2, 3];
        ol.moveUp(0);
        expect(ol.order).toEqual([1, 2, 3]);
    });

    it('reconciles a stored order against known values, appending missing', () => {
        const ol = makeOrderList();
        expect(ol._reconcile([2, 99], [1, 2, 3])).toEqual([2, 1, 3]);
    });
});
