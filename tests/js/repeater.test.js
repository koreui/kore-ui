import { describe, expect, it } from 'vitest';
import KoreRepeater from '../../resources/js/form/repeater.js';

// KoreRepeater(config) -> { rows, addRow, removeRow, moveRow, _blankRow }.
// Driven directly, without a DOM or $wire (so _sync() is a no-op).
function makeRepeater(config = {}) {
    return KoreRepeater({ fields: ['name', 'qty'], ...config });
}

describe('KoreRepeater (form/repeater.js)', () => {
    it('builds a blank row from the field schema', () => {
        const r = makeRepeater();
        expect(r._blankRow()).toEqual({ name: '', qty: '' });
    });

    it('adds a row', () => {
        const r = makeRepeater();
        r.rows = [];
        r.addRow();
        expect(r.rows).toEqual([{ name: '', qty: '' }]);
    });

    it('respects the max config', () => {
        const r = makeRepeater({ max: 1 });
        r.rows = [{ name: 'a', qty: '1' }];
        r.addRow();
        expect(r.rows).toHaveLength(1);
    });

    it('removes a row', () => {
        const r = makeRepeater();
        r.rows = [{ name: 'a', qty: '1' }, { name: 'b', qty: '2' }];
        r.removeRow(0);
        expect(r.rows).toEqual([{ name: 'b', qty: '2' }]);
    });

    it('does not remove below the min config', () => {
        const r = makeRepeater({ min: 1 });
        r.rows = [{ name: 'a', qty: '1' }];
        r.removeRow(0);
        expect(r.rows).toHaveLength(1);
    });

    it('moves a row to a new position', () => {
        const r = makeRepeater();
        r.rows = [
            { name: 'a', qty: '1' },
            { name: 'b', qty: '2' },
            { name: 'c', qty: '3' },
        ];
        r.moveRow(2, 0);
        expect(r.rows.map((row) => row.name)).toEqual(['c', 'a', 'b']);
    });
});
