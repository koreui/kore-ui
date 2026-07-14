import { readCookie, writeCookie } from './utils/cookie.js';
import { lockScroll, unlockScroll } from './utils/scroll-lock.js';

/**
 * Store global del sidebar (`$store.koreSidebar`).
 *
 * Es un store y no un x-data para que el `main`, la navbar y cualquier botón
 * hamburguesa suelto puedan leer y cambiar el estado sin estar anidados dentro
 * del sidebar.
 *
 * Reparto de responsabilidades, y conviene respetarlo:
 *   - PHP es dueño del ESTADO INICIAL. Lee la cookie y estampa los atributos
 *     `data-*` en el HTML. El store nunca lee la cookie al arrancar; la recibe ya
 *     resuelta en register().
 *   - El store es dueño de los ATRIBUTOS a partir de ahí, y los escribe con
 *     setAttribute() — nunca con x-bind. Alpine evalúa x-bind ANTES que x-init, así
 *     que un bind podría pintar con el store todavía vacío y borrar lo que el
 *     servidor había dejado bien puesto.
 */

export const COOKIE = 'kore_sidebar';

const MAX_ENTRIES = 10;
const ID_PATTERN = /^[A-Za-z0-9_-]{1,32}$/;

// Los MediaQueryList viven fuera del estado reactivo: meter objetos del navegador
// en el Proxy de Alpine da problemas. Mismo motivo que el comentario de ui/splitter.js.
const medias = new Map(); // id -> { mq, handler }

export function parseState(raw) {
    if (!raw) return {};

    try {
        const decoded = JSON.parse(raw);

        if (!decoded || typeof decoded !== 'object' || Array.isArray(decoded)) return {};

        const state = {};

        for (const [id, collapsed] of Object.entries(decoded)) {
            if (Object.keys(state).length >= MAX_ENTRIES) break;
            if (!ID_PATTERN.test(id)) continue;

            state[id] = collapsed === 1 || collapsed === true || collapsed === '1';
        }

        return state;
    } catch {
        return {};
    }
}

export function serializeState(state) {
    const out = {};

    for (const [id, collapsed] of Object.entries(state)) {
        out[id] = collapsed ? 1 : 0;
    }

    return JSON.stringify(out);
}

export default {
    /** id -> { collapsed, collapsible, placement, persist, rail, expandOnHover, breakpointPx, overlay } */
    sidebars: {},

    /** id -> bool — drawer móvil abierto */
    open: {},

    /** id -> bool — el viewport está por debajo del breakpoint */
    mobile: {},

    /**
     * Lo llama el cuerpo de la factory KoreSidebar (no su init()), para que el
     * store esté poblado antes de que corra cualquier x-bind.
     */
    register(config) {
        const id = config.id;

        if (!id || !ID_PATTERN.test(id)) return;

        this.sidebars[id] = {
            collapsed: !!config.collapsed, // ya resuelto en servidor: cookie > prop > config
            collapsible: config.collapsible !== false,
            placement: config.placement === 'right' ? 'right' : 'left',
            persist: config.persist !== false,
            rail: !!config.rail,
            expandOnHover: !!config.expandOnHover,
            overlay: config.overlay !== false,
            breakpointPx: Number(config.breakpointPx) || 1024,
        };

        if (this.open[id] === undefined) this.open[id] = false;

        this.watchViewport(id);
    },

    // --- Colapsar / expandir (escritorio) ---

    isCollapsed(id) {
        return this.sidebars[id]?.collapsed ?? false;
    },

    toggle(id) {
        this.setCollapsed(id, !this.isCollapsed(id));
    },

    setCollapsed(id, collapsed) {
        const sidebar = this.sidebars[id];

        if (!sidebar || !sidebar.collapsible) return;

        sidebar.collapsed = !!collapsed;

        this.applyAttributes(id);
        this.persist(id);

        window.dispatchEvent(new CustomEvent('kore:sidebar-toggle', {
            detail: { id, collapsed: sidebar.collapsed },
        }));
    },

    // --- Drawer móvil ---

    isOpen(id) {
        return this.open[id] ?? false;
    },

    isMobile(id) {
        return this.mobile[id] ?? false;
    },

    openMobile(id) {
        if (this.open[id]) return;

        this.open[id] = true;
        this.applyAttributes(id);

        if (this.sidebars[id]?.overlay) lockScroll(`sidebar:${id}`);

        window.dispatchEvent(new CustomEvent('kore:sidebar-mobile-open', { detail: { id } }));
    },

    closeMobile(id) {
        if (!this.open[id]) return;

        this.open[id] = false;
        this.applyAttributes(id);

        unlockScroll(`sidebar:${id}`);

        window.dispatchEvent(new CustomEvent('kore:sidebar-mobile-close', { detail: { id } }));
    },

    toggleMobile(id) {
        this.isOpen(id) ? this.closeMobile(id) : this.openMobile(id);
    },

    /**
     * El toggle "que hace lo correcto": colapsa en escritorio y abre el drawer en
     * móvil. Es lo que quiere el 99% de los botones hamburguesa.
     */
    handleToggle(id) {
        this.isMobile(id) ? this.toggleMobile(id) : this.toggle(id);
    },

    // --- DOM ---

    /**
     * Busca los nodos cada vez en vez de cachearlos: Livewire reemplaza elementos
     * al hacer morph y una referencia guardada apuntaría a un nodo ya muerto.
     */
    applyAttributes(id) {
        const sidebar = this.sidebars[id];

        if (!sidebar || typeof document === 'undefined') return;

        const el = document.querySelector(`[data-kore-sidebar-id="${id}"]`);
        const shell = document.querySelector('[data-kore-shell]');

        const state = sidebar.collapsed ? 'collapsed' : 'expanded';

        if (el) {
            el.setAttribute('data-kore-sidebar', state);
            el.setAttribute('data-open', this.isOpen(id) ? 'true' : 'false');
        }

        if (shell) {
            // En rail el contenido reserva siempre el ancho colapsado, aunque el
            // sidebar se ensanche al hover: por eso es un estado propio y no "collapsed".
            const reserved = sidebar.rail ? 'rail' : state;

            shell.setAttribute(`data-sidebar-${sidebar.placement}`, reserved);
        }
    },

    // --- Persistencia ---

    /**
     * Merge con lo que ya hubiera en la cookie. Sin esto, una página que solo
     * declara `main` borraría el estado de `tools` al guardar.
     */
    persist(id) {
        const sidebar = this.sidebars[id];

        if (!sidebar?.persist) return;

        const state = parseState(readCookie(COOKIE));
        state[id] = sidebar.collapsed;

        writeCookie(COOKIE, serializeState(state));
    },

    // --- Viewport ---

    watchViewport(id) {
        if (typeof window === 'undefined' || !window.matchMedia) return;

        // Re-registrar (tras un morph) no debe acumular listeners.
        this.unwatchViewport(id);

        const px = this.sidebars[id].breakpointPx;
        const mq = window.matchMedia(`(max-width: ${px - 0.02}px)`);

        const handler = (e) => {
            this.mobile[id] = e.matches;

            // Al pasar a escritorio el drawer deja de existir: hay que soltar el
            // scroll-lock o el body se queda fijo para siempre.
            if (!e.matches && this.open[id]) this.closeMobile(id);
        };

        this.mobile[id] = mq.matches;
        mq.addEventListener('change', handler);

        medias.set(id, { mq, handler });
    },

    unwatchViewport(id) {
        const entry = medias.get(id);

        if (!entry) return;

        entry.mq.removeEventListener('change', entry.handler);
        medias.delete(id);
    },
};
