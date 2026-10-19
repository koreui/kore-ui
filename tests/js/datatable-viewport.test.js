// @vitest-environment jsdom

import { afterEach, describe, expect, it } from 'vitest';
import KoreDataTable from '../../resources/js/datatable.js';

// Si un padre tiene display:none al iniciar (x-cloak en el <body>, pestaña,
// modal o x-show cerrados), clientWidth vale 0. Informar ese ancho pedía la vista
// móvil en escritorio y luego había que deshacerlo: dos viajes al servidor y la
// tabla desaparecía durante el primero.

let mounted = [];

function mount({ width }) {
    const root = document.createElement('div');
    document.body.appendChild(root);

    let current = width;
    Object.defineProperty(root, 'clientWidth', { get: () => current });

    const wire = { calls: [], on() {}, setViewport(v) { wire.calls.push(v); } };

    const component = KoreDataTable({ rowIds: [], responsiveMode: 'card', responsiveBreakpoint: 768 });
    component.$root = root;
    component.$wire = wire;
    component.$nextTick = (fn) => fn && fn();
    component.init();
    mounted.push(component);

    return { component, wire, resize: (w) => { current = w; } };
}

afterEach(() => {
    mounted.forEach(c => c.destroy());
    mounted = [];
    document.body.innerHTML = '';
});

describe('checkBreakpoint con el contenedor oculto', () => {
    it('no informa al servidor si el contenedor mide 0', () => {
        const { component, wire } = mount({ width: 0 });

        expect(wire.calls).toEqual([]);
        expect(component.isMobileView).toBe(false);
    });

    it('informa en cuanto el contenedor tiene tamaño', () => {
        const { component, wire, resize } = mount({ width: 0 });

        resize(1200);
        component.checkBreakpoint();

        expect(wire.calls).toEqual([false]);
    });

    it('sigue pidiendo la vista móvil con un contenedor estrecho visible', () => {
        const { component, wire } = mount({ width: 400 });

        expect(wire.calls).toEqual([true]);
        expect(component.isMobileView).toBe(true);
    });
});
