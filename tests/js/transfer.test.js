import { describe, expect, it } from 'vitest';
import KoreTransfer from '../../resources/js/ui/transfer.js';

const ITEMS = [
    { value: 'a', label: 'Alpha' },
    { value: 'b', label: 'Beta' },
    { value: 'c', label: 'Gamma' },
];

function makeTransfer(config = {}) {
    return KoreTransfer({ items: ITEMS, ...config });
}

describe('KoreTransfer (ui/transfer.js)', () => {
    it('shows all items in the source when nothing is selected', () => {
        const t = makeTransfer();
        t.target = [];
        expect(t.sourceItems.map((i) => i.value)).toEqual(['a', 'b', 'c']);
        expect(t.targetItems).toEqual([]);
    });

    it('moves checked items to the target', () => {
        const t = makeTransfer();
        t.target = [];
        t.checkedSource = ['a', 'c'];
        t.moveToTarget();
        expect(t.target).toEqual(['a', 'c']);
        expect(t.checkedSource).toEqual([]);
        expect(t.sourceItems.map((i) => i.value)).toEqual(['b']);
    });

    it('moves checked items back to the source', () => {
        const t = makeTransfer();
        t.target = ['a', 'b'];
        t.checkedTarget = ['a'];
        t.moveToSource();
        expect(t.target).toEqual(['b']);
    });

    it('moves all to target and back', () => {
        const t = makeTransfer();
        t.moveAllToTarget();
        expect(t.target).toEqual(['a', 'b', 'c']);
        t.moveAllToSource();
        expect(t.target).toEqual([]);
    });

    it('filters the source by search', () => {
        const t = makeTransfer();
        t.target = [];
        t.sourceSearch = 'be';
        expect(t.sourceItems.map((i) => i.value)).toEqual(['b']);
    });

    it('toggles checks', () => {
        const t = makeTransfer();
        t.toggleCheck('source', 'a');
        expect(t.isChecked('source', 'a')).toBe(true);
        t.toggleCheck('source', 'a');
        expect(t.isChecked('source', 'a')).toBe(false);
    });
});
