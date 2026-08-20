// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreStats from '../../resources/js/ui/stats.js';

/**
 * KoreStats: el contador que sube hasta su valor.
 *
 * Un número que trepa durante un segundo es justo la clase de animación que
 * `prefers-reduced-motion` pide desactivar, y no lo miraba nadie: medido en
 * navegador con la preferencia activa, el contador seguía subiendo desde cero
 * —5.695 a los 120 ms, 12.450 al final—. El resto de la librería sí la respeta
 * (ver `feedback.js`).
 */
function conMedia(reduce) {
    window.matchMedia = vi.fn().mockImplementation((consulta) => ({
        matches: consulta.includes('prefers-reduced-motion') ? reduce : false,
        media: consulta,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
    }));
}

function montar(config = {}) {
    const stats = KoreStats({ value: 1234, animated: true, ...config });
    stats.$el = document.createElement('p');

    return stats;
}

beforeEach(() => {
    conMedia(false);
    // El contador arranca al entrar en vista; el observador no está en jsdom.
    globalThis.IntersectionObserver = class {
        constructor(cb) { this.cb = cb; }
        observe() {}
        disconnect() {}
    };
});

describe('prefers-reduced-motion', () => {
    it('con la preferencia activa, enseña el valor sin animar', () => {
        conMedia(true);
        const stats = montar();

        stats.init();

        expect(stats.displayValue).toBe(new Intl.NumberFormat().format(1234));
    });

    it('sin la preferencia, arranca en cero y espera a entrar en vista', () => {
        const stats = montar();

        stats.init();

        expect(stats.displayValue).toBe('0');
    });

    it('control: la preferencia se consulta de verdad', () => {
        // Si `matchMedia` dejara de consultarse, el test de arriba pasaría
        // igualmente por el camino de `animated: false`.
        conMedia(true);
        montar().init();

        expect(window.matchMedia).toHaveBeenCalledWith('(prefers-reduced-motion: reduce)');
    });
});

describe('sin animación', () => {
    it('`animated: false` enseña el valor directamente', () => {
        const stats = montar({ animated: false });

        stats.init();

        expect(stats.displayValue).toBe(new Intl.NumberFormat().format(1234));
    });

    it('formatea el número según la configuración regional', () => {
        const stats = montar({ animated: false, value: 1234567 });

        stats.init();

        expect(stats.displayValue).toBe(new Intl.NumberFormat().format(1234567));
    });
});
