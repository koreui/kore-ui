// @vitest-environment jsdom

import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';
import KoreDataTable from '../../resources/js/datatable.js';

// El listener de teclado vive en `document` a propósito: el foco puede estar en el
// <body> y el usuario espera que las flechas muevan la fila activa igualmente. Lo
// que estos tests fijan es el guard que decide CUÁNDO esa tabla responde — sin él,
// dos datatables en la misma página reaccionan a la vez y Ctrl/Cmd+A queda
// secuestrado en toda la aplicación.

// Alpine no interviene: se instancia la factoría a mano con los mínimos que usa
// init() (`$root`, `$wire`, `$nextTick`), que es lo que hace falta para ejercitar
// el handler real registrado en document.
function mount({ id, rowIds = ['1', '2', '3'], hovered = false }) {
    const root = document.createElement('div');
    root.dataset.koreDatatable = '';
    root.id = id;
    root.innerHTML = `
        <input data-datatable-search />
        <table><thead><tr><th></th><th></th></tr></thead>
        <tbody>${rowIds.map(r => `<tr data-row-id="${r}"><td></td><td></td></tr>`).join('')}</tbody></table>
    `;
    document.body.appendChild(root);

    // jsdom no resuelve :hover (no hay puntero), así que se simula en matches().
    const nativeMatches = root.matches.bind(root);
    root.matches = (selector) => (selector === ':hover' ? hovered : nativeMatches(selector));

    const wire = { calls: [], on() {}, toggleSelectAll() { wire.calls.push('toggleSelectAll'); }, toggleRow(id) { wire.calls.push(`toggleRow:${id}`); } };

    const component = KoreDataTable({ rowIds });
    component.$root = root;
    component.$wire = wire;
    component.$nextTick = (fn) => fn && fn();
    component.init();

    return { component, root, wire, setHovered: (v) => { hovered = v; } };
}

function press(key, opts = {}) {
    document.dispatchEvent(new window.KeyboardEvent('keydown', { key, bubbles: true, cancelable: true, ...opts }));
}

let mounted = [];

// jsdom no implementa scrollIntoView y la navegación por teclado lo llama al
// mover la fila activa. Sin este doble, el fallo se escapa del test como error
// no capturado.
window.Element.prototype.scrollIntoView = () => {};

beforeEach(() => {
    document.body.innerHTML = '';
    mounted = [];
});

afterEach(() => {
    mounted.forEach(m => m.component.destroy());
});

describe('el datatable solo responde al teclado cuando lo tiene', () => {
    it('ignora las flechas si ni el cursor ni el foco están dentro', () => {
        const m = mount({ id: 'tabla', hovered: false });
        mounted.push(m);

        press('ArrowDown');

        expect(m.component.activeRow).toBe(-1);
    });

    it('responde a las flechas con el cursor encima', () => {
        const m = mount({ id: 'tabla', hovered: true });
        mounted.push(m);

        press('ArrowDown');

        expect(m.component.activeRow).toBe(0);
    });

    it('responde a las flechas con el foco dentro', () => {
        const m = mount({ id: 'tabla', hovered: false });
        mounted.push(m);

        const cell = m.root.querySelector('td');
        cell.setAttribute('tabindex', '-1');
        cell.focus();

        press('ArrowDown');

        expect(m.component.activeRow).toBe(0);
    });

    it('no secuestra Ctrl+A cuando el usuario está lejos de la tabla', () => {
        const m = mount({ id: 'tabla', hovered: false });
        mounted.push(m);

        press('a', { ctrlKey: true });

        expect(m.wire.calls).toEqual([]);
    });

    it('sí toma Ctrl+A con el cursor encima', () => {
        const m = mount({ id: 'tabla', hovered: true });
        mounted.push(m);

        press('a', { ctrlKey: true });

        expect(m.wire.calls).toEqual(['toggleSelectAll']);
    });

    it('con dos tablas en la página, solo se mueve la que tiene el cursor', () => {
        const a = mount({ id: 'a', hovered: true });
        const b = mount({ id: 'b', hovered: false });
        mounted.push(a, b);

        press('ArrowDown');

        expect(a.component.activeRow).toBe(0);
        expect(b.component.activeRow).toBe(-1);
    });
});

describe('campos de formulario enfocados', () => {
    it('no navega mientras se escribe en un input propio', () => {
        const m = mount({ id: 'tabla', hovered: true });
        mounted.push(m);

        m.root.querySelector('[data-datatable-search]').focus();
        press('ArrowDown');

        expect(m.component.activeRow).toBe(-1);
    });

    it('Escape suelta el foco de un input propio', () => {
        const m = mount({ id: 'tabla', hovered: true });
        mounted.push(m);

        const input = m.root.querySelector('[data-datatable-search]');
        input.focus();
        press('Escape');

        expect(document.activeElement).not.toBe(input);
    });

    it('Escape NO toca un input ajeno que esté bajo el cursor de la tabla', () => {
        const m = mount({ id: 'tabla', hovered: true });
        mounted.push(m);

        const foreign = document.createElement('input');
        document.body.appendChild(foreign);
        foreign.focus();

        press('Escape');

        expect(document.activeElement).toBe(foreign);
    });

    it('un <select> enfocado cuenta como campo de formulario', () => {
        const m = mount({ id: 'tabla', hovered: true });
        mounted.push(m);

        const select = document.createElement('select');
        m.root.appendChild(select);
        select.focus();

        press('ArrowDown');

        expect(m.component.activeRow).toBe(-1);
    });
});
