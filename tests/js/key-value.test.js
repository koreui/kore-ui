import { describe, expect, it } from 'vitest';
import KoreKeyValue from '../../resources/js/form/key-value.js';

// KoreKeyValue(config) -> { pairs, addPair, removePair, movePair, _toObject, _toPairs }.
// We drive the pure array/object logic directly, without a DOM or $wire.
function makeKeyValue(config = {}) {
    const instance = KoreKeyValue(config);
    // no $wire / $refs in the test: _sync() becomes a no-op, which is what we want
    return instance;
}

describe('KoreKeyValue (form/key-value.js)', () => {
    it('starts with one empty pair', () => {
        const kv = makeKeyValue();
        expect(kv.pairs).toEqual([{ key: '', value: '' }]);
    });

    it('converts an object into rows', () => {
        const kv = makeKeyValue();
        expect(kv._toPairs({ env: 'prod', region: 'mx' })).toEqual([
            { key: 'env', value: 'prod' },
            { key: 'region', value: 'mx' },
        ]);
    });

    it('converts rows back into an object, skipping empty keys', () => {
        const kv = makeKeyValue();
        const obj = kv._toObject([
            { key: 'a', value: '1' },
            { key: '', value: 'ignored' },
            { key: '  b  ', value: '2' },
        ]);
        expect(obj).toEqual({ a: '1', b: '2' });
    });

    it('adds a pair', () => {
        const kv = makeKeyValue();
        kv.addPair();
        expect(kv.pairs).toHaveLength(2);
    });

    it('respects the max config when adding', () => {
        const kv = makeKeyValue({ max: 1 });
        kv.addPair();
        expect(kv.pairs).toHaveLength(1);
    });

    it('removes a pair but never leaves zero rows', () => {
        const kv = makeKeyValue();
        kv.pairs = [{ key: 'a', value: '1' }, { key: 'b', value: '2' }];
        kv.removePair(0);
        expect(kv.pairs).toEqual([{ key: 'b', value: '2' }]);

        kv.removePair(0);
        expect(kv.pairs).toEqual([{ key: '', value: '' }]);
    });

    it('moves a pair to a new position', () => {
        const kv = makeKeyValue();
        kv.pairs = [
            { key: 'a', value: '1' },
            { key: 'b', value: '2' },
            { key: 'c', value: '3' },
        ];
        kv.movePair(0, 2);
        expect(kv.pairs.map((p) => p.key)).toEqual(['b', 'c', 'a']);
    });

    it('accepts an array of {key,value} on _toPairs', () => {
        const kv = makeKeyValue();
        expect(kv._toPairs([{ key: 'x', value: 'y' }])).toEqual([{ key: 'x', value: 'y' }]);
    });
});
