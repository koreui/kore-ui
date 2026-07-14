// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Se mockea @floating-ui/dom porque lo que hay que probar NO es su matemática (que ya está
// probada aguas arriba), sino nuestro contrato: qué referencia le pasamos, con qué opciones
// llamamos a autoUpdate, y que el cleanup siga siendo una función (o romperíamos a los
// cuatro componentes que ya lo usan: tooltip, dropdown, select y datepicker).
const calls = { computePosition: [], autoUpdate: [] };
let cleanupSpy;

vi.mock('@floating-ui/dom', () => ({
    computePosition: (reference, floating, options) => {
        calls.computePosition.push({ reference, floating, options });
        return Promise.resolve({ x: 10, y: 20 });
    },
    autoUpdate: (reference, floating, update, options) => {
        calls.autoUpdate.push({ reference, floating, update, options });
        update();
        return cleanupSpy;
    },
    flip: () => 'flip',
    shift: () => 'shift',
    offset: (v) => `offset:${v}`,
}));

const { startFloating, stopFloating, virtualReference } = await import('../../resources/js/utils/floating.js');

let floating;

beforeEach(() => {
    calls.computePosition = [];
    calls.autoUpdate = [];
    cleanupSpy = vi.fn();
    document.body.innerHTML = '<div id="plot"></div><div id="tip"></div>';
    floating = document.getElementById('tip');
});

describe('referencia virtual', () => {
    it('se ancla a un punto, no a un elemento', () => {
        const point = virtualReference(document.getElementById('plot'));
        point.setPoint(120, 80);

        const rect = point.getBoundingClientRect();

        expect(rect).toEqual({ x: 120, y: 80, width: 0, height: 0, top: 80, right: 120, bottom: 80, left: 120 });
    });

    it('también puede anclarse a un área (una barra)', () => {
        const point = virtualReference();
        point.setRect({ x: 10, y: 20, width: 30, height: 40 });

        expect(point.getBoundingClientRect()).toMatchObject({ left: 10, top: 20, right: 40, bottom: 60 });
    });

    it('conserva el contextElement: sin él, el tooltip se quedaría atrás al hacer scroll en un panel', () => {
        const plot = document.getElementById('plot');

        expect(virtualReference(plot).contextElement).toBe(plot);
    });

    it('no pide observers sobre algo que no es un elemento', () => {
        const point = virtualReference(document.getElementById('plot'));
        startFloating(point, floating, { placement: 'top' });

        expect(calls.autoUpdate[0].options).toMatchObject({
            elementResize: false,
            layoutShift: false,
            ancestorScroll: true,
        });
    });

    it('deja repintar cuando el punto se mueve (el ratón no dispara autoUpdate)', async () => {
        const point = virtualReference();
        point.setPoint(0, 0);

        const handle = startFloating(point, floating, { placement: 'top' });
        expect(calls.computePosition).toHaveLength(1);

        point.setPoint(200, 150);
        handle.update();

        expect(calls.computePosition).toHaveLength(2);
        expect(calls.computePosition[1].reference.getBoundingClientRect()).toMatchObject({ left: 200, top: 150 });
    });
});

describe('no se rompen los consumidores existentes', () => {
    it('con un elemento real sigue pidiendo los observers de siempre', () => {
        startFloating(document.getElementById('plot'), floating, { placement: 'bottom-start' });

        expect(calls.autoUpdate[0].options).toMatchObject({
            elementResize: true,
            layoutShift: true,
            ancestorScroll: true,
            ancestorResize: true,
        });
    });

    it('el cleanup sigue siendo una función, así que stopFloating lo sigue soltando', () => {
        const cleanup = startFloating(document.getElementById('plot'), floating, {});

        expect(typeof cleanup).toBe('function');

        stopFloating(cleanup);

        expect(cleanupSpy).toHaveBeenCalledOnce();
    });

    it('devuelve null si falta la referencia o el flotante', () => {
        expect(startFloating(null, floating)).toBeNull();
        expect(startFloating(document.getElementById('plot'), null)).toBeNull();
    });
});
