import { computePosition, flip, shift, offset, autoUpdate } from '@floating-ui/dom';

/**
 * Shared floating positioning for kore-ui components.
 *
 * Usage in an Alpine component:
 *   import { startFloating, stopFloating } from '../utils/floating.js';
 *
 *   openDropdown() {
 *       this.open = true;
 *       this.$nextTick(() => {
 *           this._floatingCleanup = startFloating(this.$refs.trigger, this.$refs.dropdown, {
 *               placement: 'bottom-start',
 *               offset: 4,
 *               sameWidth: true,         // match trigger width
 *               fixedWidth: 380,         // or set a fixed px width
 *               onClose: () => this.close(),   // se llama si el ancla deja de pintarse
 *           });
 *       });
 *   }
 *
 *   close() {
 *       this.open = false;
 *       stopFloating(this._floatingCleanup);
 *   }
 *
 * The reference may also be a VIRTUAL element (see `virtualReference` below): an object
 * with `getBoundingClientRect()` instead of a DOM node. A chart tooltip does not hang off
 * an element — it hangs off a data point, which is just a coordinate. Floating UI supports
 * that natively, but nothing would ever tell us to reposition, because a moving cursor
 * fires no scroll, resize or mutation. That is why the cleanup function returned here also
 * carries an `.update()`: with a virtual reference, the caller drives the repositioning.
 *
 *   const point = virtualReference(this.$refs.plot);
 *   const handle = startFloating(point, this.$refs.tooltip, { placement: 'top' });
 *   // on every pointermove:
 *   point.setPoint(clientX, clientY);
 *   handle.update();
 */

/**
 * A reference that is a point, not an element.
 *
 * `contextElement` is not decoration: Floating UI uses it to find the scroll ancestors it
 * has to listen to. Without it, a tooltip inside a scrollable panel would stay behind when
 * the panel scrolls.
 */
export function virtualReference(contextElement = null) {
    let rect = { x: 0, y: 0, width: 0, height: 0, top: 0, right: 0, bottom: 0, left: 0 };

    return {
        contextElement,
        getBoundingClientRect: () => rect,

        /** Anchor on a single viewport coordinate (a data point). */
        setPoint(x, y) {
            rect = { x, y, width: 0, height: 0, top: y, right: x, bottom: y, left: x };
        },

        /** Anchor on an area (a bar, a band of the x axis). */
        setRect({ x, y, width = 0, height = 0 }) {
            rect = { x, y, width, height, top: y, right: x + width, bottom: y + height, left: x };
        },
    };
}

const isDomNode = (value) => typeof value?.nodeType === 'number';

export function startFloating(reference, floating, opts = {}) {
    if (!reference || !floating) return null;

    const placement = opts.placement || 'bottom-start';
    const gap = opts.offset ?? 4;
    const middleware = [offset(gap), flip(), shift({ padding: 8 })];

    floating.style.position = 'fixed';
    // Keep the panel invisible until coordinates are calculated so it never
    // flashes at an incorrect position (e.g. top-left corner of the viewport).
    floating.style.visibility = 'hidden';

    if (opts.fixedWidth) {
        floating.style.width = opts.fixedWidth + 'px';
    }

    let positioned = false;
    let cerrado = false;

    /**
     * ¿El ancla ha dejado de pintarse?
     *
     * Un panel se posiciona contra su disparador, pero nadie le avisa cuando ese
     * disparador desaparece: basta con que un `display:none` caiga sobre un
     * contenedor por encima —una pestaña que cambia, un acordeón que se cierra,
     * una fila que se colapsa— y el panel se queda en pantalla, sin referencia,
     * encogido en la esquina superior izquierda. Medido: 2px de ancho en (8, 4).
     *
     * `getClientRects()` vacío es la prueba estándar de que un elemento no se
     * está renderizando, y cubre tanto el `display:none` como salir del DOM.
     * Solo se aplica a anclas que son nodos: una referencia virtual —el punto de
     * datos de un gráfico— no tiene caja por definición, y su rect vacío es
     * legítimo.
     */
    const anclaPerdida = () => isDomNode(reference)
        && (!reference.isConnected || reference.getClientRects().length === 0);

    const update = () => {
        // Solo después de haberse colocado una vez: si el ancla no llegó a estar
        // visible nunca, no hay nada que cerrar y cerrar sería inventarse un
        // evento que el usuario no ha provocado.
        if (positioned && !cerrado && anclaPerdida()) {
            cerrado = true;
            floating.style.visibility = 'hidden';
            opts.onClose?.();
            return;
        }

        if (opts.sameWidth) {
            floating.style.width = reference.getBoundingClientRect().width + 'px';
        }
        computePosition(reference, floating, { placement, middleware, strategy: 'fixed' }).then(({ x, y }) => {
            floating.style.left = x + 'px';
            floating.style.top = y + 'px';
            floating.style.bottom = 'auto';
            floating.style.right = 'auto';
            if (!positioned) {
                floating.style.visibility = '';
                positioned = true;
            }
        });
    };

    // autoUpdate handles scroll (all ancestors), resize, DOM mutations
    const cleanup = autoUpdate(reference, floating, update, {
        ancestorScroll: true,
        ancestorResize: true,
        // A virtual reference has no box to observe. Floating UI copes (it unwraps to
        // `contextElement`, which may be absent), but asking for a ResizeObserver on
        // something that is not an element buys nothing.
        elementResize: isDomNode(reference),
        layoutShift: isDomNode(reference),
        animationFrame: false,
    });

    // Expose update() on the cleanup function. It stays a function, so `stopFloating` and
    // every existing caller keep working unchanged — but a caller with a virtual reference
    // can now ask for a reposition when the point moves.
    cleanup.update = update;

    return cleanup;
}

export function stopFloating(cleanup) {
    if (typeof cleanup === 'function') {
        cleanup();
    }
}
