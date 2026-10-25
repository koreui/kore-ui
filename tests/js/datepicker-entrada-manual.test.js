import { describe, expect, it, vi, afterEach } from 'vitest';
import KoreDatePicker from '../../resources/js/form/datepicker.js';

/**
 * Con `manualInput`, escribir la fecha y pulsar Enter guardaba la de hoy: Enter
 * llegaba al `onKeydown` del contenedor, que con el calendario abierto elige el
 * día enfocado, y lo escrito aún no se había aplicado (va con debounce).
 */
function conConfig(config = {}) {
    const dp = KoreDatePicker({ mode: 'single', locale: 'es-ES', startOfWeek: 1, months: 1, manualInput: true, ...config });
    dp.$refs = { hiddenInput: { value: '', attributes: [], dispatchEvent: () => {} } };
    dp.$nextTick = (fn) => fn?.();
    dp.$wire = null;
    dp.init();
    dp.open = true;
    return dp;
}

const enter = (valor) => ({ target: { value: valor } });
const iso = (d) => d && `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

afterEach(() => vi.useRealTimers());

describe('KoreDatePicker · entrada manual', () => {
    it('Enter guarda la fecha escrita, no la de hoy', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-10-24T10:00:00'));
        const dp = conConfig();

        dp.onManualEnter(enter('1980-06-15'));

        expect(iso(dp.selected)).toBe('1980-06-15');
        expect(dp.open).toBe(false);
    });

    it('acepta la fecha como se escribe en español', () => {
        const dp = conConfig();

        dp.onManualEnter(enter('15/06/1980'));
        expect(iso(dp.selected)).toBe('1980-06-15');

        dp.onManualEnter(enter('3-2-1975'));
        expect(iso(dp.selected)).toBe('1975-02-03');
    });

    it('con algo que no es fecha no elige ninguna y deja el calendario abierto', () => {
        const dp = conConfig();

        dp.onManualEnter(enter('31/02/1980'));
        dp.onManualEnter(enter('mañana'));

        expect(dp.selected).toBeNull();
        expect(dp.open).toBe(true);
    });

    it('respeta los límites', () => {
        const dp = conConfig({ maxDate: '2026-10-24' });

        dp.onManualEnter(enter('2030-01-01'));

        expect(dp.selected).toBeNull();
    });

    it('lo escrito mueve el foco del calendario', () => {
        const dp = conConfig();

        dp.onManualInput(enter('15/06/1980'));

        expect(iso(dp.focusedDate)).toBe('1980-06-15');
    });
});
