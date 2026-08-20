import { describe, expect, it, vi, afterEach } from 'vitest';
import KoreDatePicker from '../../resources/js/form/datepicker.js';

/**
 * Por qué mes se abre el calendario.
 *
 * Se abría siempre por hoy, también cuando hoy caía fuera de [minDate, maxDate]:
 * el usuario se encontraba una rejilla con TODOS los días deshabilitados y sin
 * ninguna pista de hacia dónde navegar. Con un rango de marzo abierto en agosto,
 * eran cinco clics a ciegas en la flecha de mes.
 */
function conConfig(config = {}) {
    const dp = KoreDatePicker({ mode: 'single', locale: 'es-ES', startOfWeek: 1, months: 1, ...config });
    dp.$refs = { hiddenInput: null };
    dp.$nextTick = (fn) => fn?.();
    dp.$wire = null;
    return dp;
}

/** Congela «hoy» para que el test no dependa del día en que se ejecute. */
function hoyEs(iso) {
    vi.useFakeTimers();
    vi.setSystemTime(new Date(iso));
}

afterEach(() => vi.useRealTimers());

describe('KoreDatePicker · mes de arranque', () => {
    it('se abre por hoy cuando no hay límites', () => {
        hoyEs('2026-08-20T10:00:00');
        const dp = conConfig();

        const mes = dp._mesDeArranque();
        expect(mes.getFullYear()).toBe(2026);
        expect(mes.getMonth()).toBe(7);   // agosto
    });

    it('se abre por el mínimo cuando hoy queda por debajo', () => {
        hoyEs('2026-01-10T10:00:00');
        const dp = conConfig({ minDate: '2026-03-05', maxDate: '2026-03-20' });

        const mes = dp._mesDeArranque();
        expect(mes.getMonth()).toBe(2);   // marzo
    });

    it('se abre por el máximo cuando hoy queda por encima', () => {
        hoyEs('2026-08-20T10:00:00');
        const dp = conConfig({ minDate: '2026-03-05', maxDate: '2026-03-20' });

        const mes = dp._mesDeArranque();
        expect(mes.getFullYear()).toBe(2026);
        expect(mes.getMonth()).toBe(2);   // marzo
    });

    it('se abre por hoy si hoy cae dentro del rango', () => {
        hoyEs('2026-03-12T10:00:00');
        const dp = conConfig({ minDate: '2026-03-05', maxDate: '2026-03-20' });

        const mes = dp._mesDeArranque();
        expect(mes.getDate()).toBe(12);
    });

    it('respeta un solo límite', () => {
        hoyEs('2026-01-10T10:00:00');

        expect(conConfig({ minDate: '2026-06-01' })._mesDeArranque().getMonth()).toBe(5);
        expect(conConfig({ maxDate: '2026-06-01' })._mesDeArranque().getMonth()).toBe(0);
    });
});
