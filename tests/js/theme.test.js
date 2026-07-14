import { beforeEach, describe, expect, it, vi } from 'vitest';

// Vitest corre en environment 'node': no hay DOM. El store solo toca document/window
// dentro de sus métodos, así que se pueden stubear y probar lo que de verdad importa:
// QUIÉN emite el evento `theme-changed` y cuándo.

let mediaHandlers;   // los listeners registrados sobre matchMedia
let dispatched;      // los CustomEvent que se despachan sobre document
let stored;          // el localStorage falso

function stubDom({ systemPrefersDark = false } = {}) {
    mediaHandlers = [];
    dispatched = [];
    stored = {};

    const mediaQuery = {
        matches: systemPrefersDark,
        addEventListener: (_, handler) => mediaHandlers.push(handler),
        removeEventListener: (_, handler) => {
            mediaHandlers = mediaHandlers.filter((h) => h !== handler);
        },
    };

    globalThis.document = {
        documentElement: {
            classList: { add: vi.fn(), remove: vi.fn() },
            setAttribute: vi.fn(),
        },
        addEventListener: vi.fn(),
        dispatchEvent: (event) => dispatched.push(event),
    };

    globalThis.window = { matchMedia: () => mediaQuery };
    globalThis.localStorage = {
        getItem: (k) => stored[k] ?? null,
        setItem: (k, v) => { stored[k] = v; },
    };
    globalThis.CustomEvent = class {
        constructor(type, init) {
            this.type = type;
            this.detail = init?.detail;
        }
    };

    return mediaQuery;
}

let theme;

async function loadStore() {
    vi.resetModules();
    theme = (await import('../../resources/js/theme.js')).default;
    theme.init();
}

describe('cambio de tema desde la interfaz', () => {
    beforeEach(() => stubDom());

    it('emite theme-changed al llamar a setMode', async () => {
        await loadStore();
        theme.setMode('dark');

        expect(dispatched).toHaveLength(1);
        expect(dispatched[0].type).toBe('theme-changed');
        expect(dispatched[0].detail).toEqual({ mode: 'dark', resolved: 'dark' });
    });
});

describe('cambio de tema desde el SISTEMA OPERATIVO', () => {
    // Éste era el bug: con mode 'system' —que es el DEFAULT— el store aplicaba la clase
    // pero no emitía ningún evento. Cualquiera que necesitase releer colores desde JS se
    // quedaba con el tema anterior, y justo en la configuración por defecto.
    beforeEach(() => stubDom({ systemPrefersDark: false }));

    it('escucha el cambio del sistema cuando el modo es system', async () => {
        await loadStore();

        expect(theme.mode).toBe('system');
        expect(mediaHandlers).toHaveLength(1);
    });

    it('emite theme-changed cuando el SO pasa a oscuro', async () => {
        const mediaQuery = stubDom({ systemPrefersDark: false });
        await loadStore();

        expect(theme.resolved).toBe('light');

        // el SO cambia a oscuro
        mediaQuery.matches = true;
        mediaHandlers.forEach((handler) => handler());

        expect(theme.resolved).toBe('dark');
        expect(dispatched).toHaveLength(1);
        expect(dispatched[0].type).toBe('theme-changed');
        expect(dispatched[0].detail).toEqual({ mode: 'system', resolved: 'dark' });
    });

    it('no escucha al sistema cuando el modo es explícito', async () => {
        await loadStore();
        theme.setMode('light');

        expect(mediaHandlers).toHaveLength(0);
    });

    it('no emite nada al arrancar', async () => {
        await loadStore();

        expect(dispatched).toHaveLength(0);
    });
});
