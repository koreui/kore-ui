import { beforeEach, describe, expect, it, vi } from 'vitest';

// Vitest corre en 'node': se stubea lo justo del DOM para ejercitar la lógica del
// store (toggle, persistencia, breakpoint), que es lo que puede romperse de verdad.
function stubDom(cookie = '') {
    const els = new Map();

    globalThis.document = {
        cookie,
        body: { style: {} },
        addEventListener: vi.fn(),
        querySelector: (selector) => els.get(selector) ?? null,
    };

    globalThis.window = {
        scrollY: 0,
        scrollTo: vi.fn(),
        dispatchEvent: vi.fn(),
        matchMedia: (query) => ({
            matches: globalThis.__mobile ?? false,
            media: query,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        }),
    };

    globalThis.CustomEvent = class {
        constructor(type, init) {
            this.type = type;
            this.detail = init?.detail;
        }
    };

    globalThis.location = { protocol: 'http:' };

    return {
        addElement(selector, el) {
            els.set(selector, el);
        },
    };
}

function fakeEl() {
    const attrs = {};

    return {
        attrs,
        setAttribute: (k, v) => { attrs[k] = v; },
        getAttribute: (k) => attrs[k] ?? null,
    };
}

let store, parseState, serializeState;

beforeEach(async () => {
    globalThis.__mobile = false;
    vi.resetModules();
});

async function loadStore(cookie = '') {
    const dom = stubDom(cookie);
    const mod = await import('../../resources/js/sidebar.js');

    store = mod.default;
    parseState = mod.parseState;
    serializeState = mod.serializeState;

    return dom;
}

describe('parseState', () => {
    it('lee el mapa de la cookie', async () => {
        await loadStore();

        expect(parseState('{"main":1,"tools":0}')).toEqual({ main: true, tools: false });
    });

    it('descarta basura sin reventar', async () => {
        await loadStore();

        expect(parseState('no es json')).toEqual({});
        expect(parseState('[1,2]')).toEqual({});
        expect(parseState('')).toEqual({});
        expect(parseState(null)).toEqual({});
    });

    it('descarta ids que no son identificadores', async () => {
        await loadStore();

        expect(parseState('{"main":1,"<script>":1}')).toEqual({ main: true });
    });

    it('serializa a 1/0 para que la cookie sea pequeña', async () => {
        await loadStore();

        expect(serializeState({ main: true, tools: false })).toBe('{"main":1,"tools":0}');
    });

    it('parse y serialize son simétricos', async () => {
        await loadStore();

        expect(parseState(serializeState({ a: true, b: false }))).toEqual({ a: true, b: false });
    });
});

describe('colapsar', () => {
    it('toma el estado inicial del servidor, no de la cookie', async () => {
        // PHP ya resolvió cookie > prop > config. Si el store volviera a leer la cookie
        // podría contradecir al HTML ya pintado.
        await loadStore('kore_sidebar={"main":1}');

        store.register({ id: 'main', collapsed: false });

        expect(store.isCollapsed('main')).toBe(false);
    });

    it('alterna el estado', async () => {
        const dom = await loadStore();
        const sidebar = fakeEl();
        dom.addElement('[data-kore-sidebar-id="main"]', sidebar);

        store.register({ id: 'main', collapsed: false });
        store.toggle('main');

        expect(store.isCollapsed('main')).toBe(true);
        expect(sidebar.attrs['data-kore-sidebar']).toBe('collapsed');
    });

    it('actualiza el shell para que el contenido se desplace', async () => {
        const dom = await loadStore();
        const shell = fakeEl();
        dom.addElement('[data-kore-shell]', shell);

        store.register({ id: 'main', collapsed: false });
        store.toggle('main');

        expect(shell.attrs['data-sidebar-left']).toBe('collapsed');
    });

    it('en rail el contenido reserva siempre el ancho colapsado', async () => {
        // El sidebar se ensancha al hover, pero el contenido NO se mueve: por eso el
        // shell recibe 'rail' y no 'expanded'.
        const dom = await loadStore();
        const shell = fakeEl();
        dom.addElement('[data-kore-shell]', shell);

        store.register({ id: 'main', collapsed: true, rail: true });
        store.applyAttributes('main');

        expect(shell.attrs['data-sidebar-left']).toBe('rail');
    });

    it('no colapsa un sidebar que no es colapsable', async () => {
        await loadStore();

        store.register({ id: 'main', collapsed: false, collapsible: false });
        store.toggle('main');

        expect(store.isCollapsed('main')).toBe(false);
    });
});

describe('persistencia', () => {
    it('escribe la cookie al colapsar', async () => {
        await loadStore();

        store.register({ id: 'main', collapsed: false });
        store.toggle('main');

        expect(document.cookie).toContain('kore_sidebar=');
        expect(decodeURIComponent(document.cookie)).toContain('{"main":1}');
    });

    it('NO borra el estado de los otros sidebars', async () => {
        // Sin merge, una página que solo declara `main` se cargaría el estado de `tools`.
        await loadStore('kore_sidebar=' + encodeURIComponent('{"tools":1}'));

        store.register({ id: 'main', collapsed: false });
        store.toggle('main');

        const written = decodeURIComponent(document.cookie);

        expect(written).toContain('"tools":1');
        expect(written).toContain('"main":1');
    });

    it('no escribe nada si la persistencia está desactivada', async () => {
        await loadStore();

        store.register({ id: 'main', collapsed: false, persist: false });
        store.toggle('main');

        expect(document.cookie).toBe('');
    });
});

describe('drawer móvil', () => {
    it('toma el scroll-lock al abrir y lo suelta al cerrar', async () => {
        globalThis.__mobile = true;
        await loadStore();

        store.register({ id: 'main', collapsed: false, overlay: true });

        store.openMobile('main');
        expect(store.isOpen('main')).toBe(true);
        expect(document.body.style.position).toBe('fixed');

        store.closeMobile('main');
        expect(store.isOpen('main')).toBe(false);
        expect(document.body.style.position).toBe('');
    });

    it('no toma el scroll-lock cuando no hay overlay', async () => {
        globalThis.__mobile = true;
        await loadStore();

        store.register({ id: 'main', collapsed: false, overlay: false });
        store.openMobile('main');

        expect(document.body.style.position).toBeUndefined();
    });

    it('con Escape cierra el drawer, y marca la tecla como consumida', async () => {
        globalThis.__mobile = true;
        await loadStore();

        store.register({ id: 'main', collapsed: false, overlay: true });
        store.openMobile('main');

        const evento = { defaultPrevented: false, preventDefault: vi.fn() };
        store.closeMobileOnEscape('main', evento);

        expect(store.isOpen('main')).toBe(false);
        expect(evento.preventDefault, 'para que el overlay manager no cierre además el modal')
            .toHaveBeenCalled();
    });

    it('cede el Escape si hay una capa por encima', async () => {
        // El caso real: drawer abierto y un modal encima. Los dos escuchan en
        // `window` y reciben el MISMO evento; sin esto, una sola pulsación
        // cerraba las dos cosas. Quien tomó el scroll lock después está arriba.
        globalThis.__mobile = true;
        const mod = await import('../../resources/js/utils/scroll-lock.js');
        await loadStore();

        store.register({ id: 'main', collapsed: false, overlay: true });
        store.openMobile('main');
        mod.lockScroll('overlay');   // el modal, encima del drawer

        const evento = { defaultPrevented: false, preventDefault: vi.fn() };
        store.closeMobileOnEscape('main', evento);

        expect(store.isOpen('main'), 'el drawer se queda abierto').toBe(true);
        expect(evento.preventDefault, 'y no toca la tecla').not.toHaveBeenCalled();
    });

    it('cede el Escape que otro ya ha consumido', async () => {
        globalThis.__mobile = true;
        await loadStore();

        store.register({ id: 'main', collapsed: false, overlay: true });
        store.openMobile('main');

        store.closeMobileOnEscape('main', { defaultPrevented: true, preventDefault: vi.fn() });

        expect(store.isOpen('main')).toBe(true);
    });

    it('control: con el drawer cerrado no hace nada', async () => {
        globalThis.__mobile = true;
        await loadStore();

        store.register({ id: 'main', collapsed: false, overlay: true });

        const evento = { defaultPrevented: false, preventDefault: vi.fn() };
        store.closeMobileOnEscape('main', evento);

        expect(evento.preventDefault, 'marcar una tecla que no se usa la roba a quien la esperaba')
            .not.toHaveBeenCalled();
    });

    // handleToggle es lo que espera cualquier botón hamburguesa: hacer lo correcto
    // según dónde esté. Van en tests separados porque el store es un singleton del
    // módulo (uno por página en la vida real) y no se puede reusar entre viewports.

    it('handleToggle abre el drawer en móvil, sin colapsar', async () => {
        globalThis.__mobile = true;
        await loadStore();

        store.register({ id: 'main', collapsed: false });
        store.handleToggle('main');

        expect(store.isOpen('main')).toBe(true);
        expect(store.isCollapsed('main')).toBe(false);
    });

    it('handleToggle colapsa en escritorio, sin abrir drawer', async () => {
        globalThis.__mobile = false;
        await loadStore();

        store.register({ id: 'main', collapsed: false });
        store.handleToggle('main');

        expect(store.isCollapsed('main')).toBe(true);
        expect(store.isOpen('main')).toBe(false);
    });
});
