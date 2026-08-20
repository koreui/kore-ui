// @vitest-environment jsdom

import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { createFocusTrap } from '../../resources/js/utils/focus-trap.js';

// La librería no incluye @alpinejs/focus (focus-trap + tabbable no caben en el
// presupuesto de bundle), así que este trap es propio y necesita su red.

const traps = [];

function trapOn(panel) {
    const trap = createFocusTrap(panel);
    traps.push(trap);
    return trap;
}

function build() {
    document.body.innerHTML = `
        <button id="outside">Fuera</button>
        <div id="panel">
            <button id="first">Primero</button>
            <input id="middle" />
            <button id="last">Último</button>
        </div>
    `;
    return document.getElementById('panel');
}

afterEach(() => {
    // Un trap sin desactivar deja su listener en document y sigue capturando
    // Tab; en la aplicación lo hace el cleanup de la directiva Alpine.
    traps.splice(0).forEach(t => t.deactivate());
});

beforeEach(() => {
    document.body.innerHTML = '';
    // jsdom no calcula layout: offsetParent es null para todo, lo que dejaría
    // la lista de focusables vacía. Se simula que están visibles.
    Object.defineProperty(window.HTMLElement.prototype, 'offsetParent', {
        configurable: true,
        get() { return this.parentNode; },
    });
});

function tab({ shift = false } = {}) {
    const event = new window.KeyboardEvent('keydown', { key: 'Tab', shiftKey: shift, bubbles: true, cancelable: true });
    document.dispatchEvent(event);
    return event;
}

describe('focus trap', () => {
    it('cicla del último al primero con Tab', () => {
        const panel = build();
        const trap = trapOn(panel);
        trap.activate();

        document.getElementById('last').focus();
        const event = tab();

        expect(event.defaultPrevented).toBe(true);
        expect(document.activeElement.id).toBe('first');
    });

    it('cicla del primero al último con Shift+Tab', () => {
        const panel = build();
        const trap = trapOn(panel);
        trap.activate();

        document.getElementById('first').focus();
        const event = tab({ shift: true });

        expect(event.defaultPrevented).toBe(true);
        expect(document.activeElement.id).toBe('last');
    });

    it('no interfiere en el resto de tabulaciones', () => {
        const panel = build();
        const trap = trapOn(panel);
        trap.activate();

        document.getElementById('middle').focus();
        const event = tab();

        // El navegador se encarga: el trap solo actúa en los bordes.
        expect(event.defaultPrevented).toBe(false);
    });

    it('recupera el foco si se escapó del panel', () => {
        const panel = build();
        const trap = trapOn(panel);
        trap.activate();

        document.getElementById('outside').focus();
        tab();

        expect(document.activeElement.id).toBe('first');
    });

    it('devuelve el foco a donde estaba al desactivarse', () => {
        const panel = build();
        const opener = document.getElementById('outside');
        opener.focus();

        const trap = trapOn(panel);
        trap.activate();
        document.getElementById('last').focus();

        trap.deactivate();

        expect(document.activeElement.id).toBe('outside');
    });

    it('deja de escuchar tras desactivarse', () => {
        const panel = build();
        const trap = trapOn(panel);
        trap.activate();
        trap.deactivate();

        document.getElementById('outside').focus();
        const event = tab();

        expect(event.defaultPrevented).toBe(false);
        expect(document.activeElement.id).toBe('outside');
    });

    it('activarlo dos veces no duplica listeners', () => {
        const panel = build();
        const trap = trapOn(panel);
        const opener = document.getElementById('outside');
        opener.focus();

        trap.activate();
        trap.activate();
        trap.deactivate();

        expect(document.activeElement.id).toBe('outside');
    });
});
