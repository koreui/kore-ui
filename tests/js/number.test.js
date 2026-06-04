import { describe, expect, it } from 'vitest';
import KoreNumber from '../../resources/js/form/number.js';

// The plugin is an Alpine factory: KoreNumber(config) -> { ...methods }. We can
// drive its pure numeric logic (_format / _parse / _clamp) directly, without a
// DOM, by building the instance and wiring up the formatter the way init() does.
function makeNumber(config = {}) {
    const instance = KoreNumber({ locale: 'en-US', ...config });
    instance._formatter = instance._createFormatter();

    return instance;
}

describe('KoreNumber (form/number.js)', () => {
    it('formats a decimal value using the configured precision', () => {
        const n = makeNumber({ precision: 2 });

        expect(n._format(1234.5)).toBe('1,234.50');
    });

    it('parses a grouped/decimal-formatted string back to a number', () => {
        const n = makeNumber({ precision: 2 });

        expect(n._parse('1,234.50')).toBe(1234.5);
        expect(n._parse('')).toBeNull();
        expect(n._parse('   ')).toBeNull();
    });

    it('round-trips format -> parse', () => {
        const n = makeNumber({ precision: 2 });

        expect(n._parse(n._format(9876.54))).toBe(9876.54);
    });

    it('parses a currency-formatted string back to its numeric value', () => {
        const n = makeNumber({ currency: 'USD', precision: 2 });

        expect(n._parse('$1,234.50')).toBe(1234.5);
    });

    it('clamps values to the configured min/max', () => {
        const n = makeNumber({ min: 0, max: 10 });

        expect(n._clamp(-5)).toBe(0);
        expect(n._clamp(99)).toBe(10);
        expect(n._clamp(5)).toBe(5);
        expect(n._clamp(null)).toBeNull();
    });
});
