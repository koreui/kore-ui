import { startFloating, stopFloating } from '../utils/floating.js';

/**
 * Componente Alpine del sidebar (`x-data="KoreSidebar({...})"`).
 *
 * Es fino a propósito: el estado compartido vive en el store `koreSidebar` y el
 * layout lo resuelve el CSS a partir de atributos `data-*`. Esto solo gestiona lo
 * local: sub-menús, el flyout y el tooltip del modo colapsado, el teclado, y
 * cerrarse al navegar.
 */
export default function KoreSidebar(config = {}) {
    // El registro va AQUÍ, en el cuerpo de la factory, y no en init(): Alpine procesa
    // x-bind ANTES que x-init, así que cualquier bind que leyera el store (el
    // aria-expanded del toggle, por ejemplo) lo encontraría vacío.
    const store = window.Alpine?.store('koreSidebar');

    if (store) store.register(config);

    // Fuera del objeto Alpine: nodos del DOM y funciones de cleanup nunca deben entrar en
    // el Proxy reactivo (mismo motivo que el comentario de ui/splitter.js).
    //
    // Es una PILA, no un único panel: los sub-menús anidados se abren uno al lado de otro
    // (Ajustes → Seguridad → …) y el del padre tiene que seguir abierto mientras navegas
    // por el del hijo.
    let flyouts = [];
    let tooltipCleanup = null;
    let closeTimer = null;

    // El panel se dibuja separado del icono (el offset de Floating UI), y ese hueco no
    // pertenece ni al item ni al panel: al cruzarlo salta el mouseleave y el menú se
    // cerraba justo cuando ibas a usarlo. El cierre espera lo justo para dar tiempo a
    // llegar; volver a entrar lo cancela.
    const CLOSE_DELAY = 180;

    function teardownFlyout(flyout) {
        stopFloating(flyout.cleanup);
        flyout.panel.removeAttribute('data-flyout');

        // startFloating escribe position/left/top/visibility en el style inline. Hay que
        // borrarlos: si no, al expandir el sidebar el sub-menú seguiría siendo
        // `position: fixed` y aparecería flotando en mitad de la pantalla.
        for (const prop of ['position', 'left', 'top', 'right', 'bottom', 'visibility', 'width']) {
            flyout.panel.style[prop] = '';
        }
    }

    return {
        id: config.id,
        placement: config.placement === 'right' ? 'right' : 'left',
        listeners: [],

        init() {
            // Cerrar el drawer al navegar. Con wire:navigate Alpine NO se destruye, así
            // que el listener hay que quitarlo a mano o se irían acumulando.
            const onNavigated = () => {
                this.$store.koreSidebar.closeMobile(this.id);
                this.closeFloating();
            };

            document.addEventListener('livewire:navigated', onNavigated);
            this.listeners.push(() => document.removeEventListener('livewire:navigated', onNavigated));
        },

        destroy() {
            this.closeFloating();
            this.listeners.forEach((remove) => remove());
            this.listeners = [];
            this.$store.koreSidebar.unwatchViewport(this.id);
        },

        // --- Sub-menús ---

        /**
         * El estado vive en el DOM (`data-kore-open`) y no en Alpine porque el servidor
         * ya emite abierto el item que contiene la ruta activa. Si lo gobernara Alpine,
         * el menú se pintaría cerrado y se abriría de golpe al arrancar el JS — justo el
         * parpadeo que este diseño existe para evitar.
         */
        toggleItem(event) {
            const item = event.currentTarget.closest('[data-kore-has-children]');

            if (!item) return;

            const isOpen = item.getAttribute('data-kore-open') === 'true';

            item.setAttribute('data-kore-open', isOpen ? 'false' : 'true');
            event.currentTarget.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        },

        setItemOpen(item, open) {
            item.setAttribute('data-kore-open', open ? 'true' : 'false');

            const trigger = item.querySelector(':scope > [aria-expanded]');

            if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        },

        // --- Modo solo-iconos ---

        /**
         * ¿Está el sidebar reducido a iconos AHORA MISMO?
         *
         * Con rail o expand-on-hover devuelve false: esos modos ya se ensanchan solos al
         * pasar el cursor, así que un flyout encima sería redundante y molesto.
         */
        isIconOnly() {
            const sidebar = this.$store.koreSidebar;

            if (sidebar.isMobile(this.id)) return false;
            if (!sidebar.isCollapsed(this.id)) return false;

            return !this.$el.hasAttribute('data-hover-expand');
        },

        // --- Flyout y tooltip (solo con el sidebar en iconos) ---

        onItemEnter(event) {
            // Volver a entrar cancela un cierre pendiente: es lo que permite cruzar el
            // hueco entre el icono y el panel sin que se cierre en las narices.
            clearTimeout(closeTimer);

            if (!this.isIconOnly()) return;

            const item = event.currentTarget;
            const trigger = item.querySelector(':scope > a, :scope > button');

            if (!trigger) return;

            const submenu = item.querySelector(':scope > .kore-sidebar-submenu');

            // Con sub-items sale el sub-menú flotando; sin ellos, basta el label.
            if (submenu) {
                this.openFlyout(item, trigger, submenu);

                return;
            }

            // Un item sin hijos también cierra las ramas hermanas: si no, pasar de
            // «Ajustes» a «Panel» dejaría el flyout de Ajustes colgado en pantalla.
            // Ojo, sí conserva los ancestros — este item puede vivir DENTRO de un flyout.
            this.closeSiblingsOf(item);
            this.showTooltip(trigger);
        },

        onItemLeave(event) {
            // focusout se dispara también al saltar de un hijo a otro DENTRO del mismo item
            // (tabulando por el flyout, por ejemplo). Si el foco sigue dentro, no cerramos:
            // si no, el flyout se cerraría bajo los pies de quien lo navega con el teclado.
            if (event?.relatedTarget && event.currentTarget?.contains(event.relatedTarget)) return;

            const item = event?.currentTarget ?? null;

            clearTimeout(closeTimer);
            closeTimer = setTimeout(() => {
                // Se cierra SOLO la rama de este item. Si cerráramos todo, salir de un
                // sub-menú anidado se llevaría por delante el panel del padre, que es
                // precisamente donde está el cursor de camino a otra opción.
                item ? this.closeBranch(item) : this.closeFloating();
            }, CLOSE_DELAY);
        },

        /**
         * Los flyouts se apilan, no se sustituyen.
         *
         * Un sub-menú anidado (Ajustes → Seguridad) vive DENTRO del panel de su padre, así
         * que abrirlo no puede cerrar ese panel: sería cerrar el suelo que estás pisando.
         * Al abrir se cierran solo los flyouts que NO contienen a este item, es decir, las
         * ramas hermanas.
         */
        openFlyout(item, trigger, panel) {
            this.closeSiblingsOf(item);

            // El tooltip pertenece al item por el que acabas de pasar. Sin esto, al ir de
            // «Mensajes» a «Ajustes» se abría el flyout con el tooltip de Mensajes todavía
            // en pantalla: hay un solo tooltip compartido y nadie lo estaba apagando.
            this.hideTooltip();

            if (flyouts.some((f) => f.panel === panel)) return; // ya abierto

            panel.setAttribute('data-flyout', 'true');

            const cleanup = startFloating(trigger, panel, {
                placement: this.placement === 'right' ? 'left-start' : 'right-start',
                offset: 8,
            });

            flyouts.push({ item, panel, cleanup });
        },

        closeSiblingsOf(item) {
            flyouts = flyouts.filter((flyout) => {
                // Un panel que contiene al item es un ancestro suyo: se queda abierto.
                if (flyout.panel.contains(item)) return true;

                teardownFlyout(flyout);

                return false;
            });
        },

        /** Cierra el flyout de este item y todo lo que colgara de él. */
        closeBranch(item) {
            flyouts = flyouts.filter((flyout) => {
                if (flyout.item === item || item.contains(flyout.item)) {
                    teardownFlyout(flyout);

                    return false;
                }

                return true;
            });

            this.hideTooltip();
        },

        showTooltip(trigger) {
            const tooltip = this.$refs.tooltip;
            const label = trigger.querySelector('.kore-sidebar-label')?.textContent?.trim();

            if (!tooltip || !label) return;

            // Soltar el anterior antes de reapuntar: startFloating deja corriendo un
            // autoUpdate (listeners de scroll/resize) que hay que cancelar, o se van
            // acumulando uno por cada icono sobre el que pases el ratón.
            this.hideTooltip();

            tooltip.textContent = label;
            tooltip.setAttribute('data-show', 'true');

            tooltipCleanup = startFloating(trigger, tooltip, {
                placement: this.placement === 'right' ? 'left' : 'right',
                offset: 12,
            });
        },

        hideTooltip() {
            stopFloating(tooltipCleanup);
            tooltipCleanup = null;

            const tooltip = this.$refs.tooltip;

            if (tooltip) tooltip.removeAttribute('data-show');
        },

        /** Cierra TODO: es lo que se quiere al pulsar Escape o al navegar. */
        closeFloating() {
            clearTimeout(closeTimer);

            flyouts.forEach(teardownFlyout);
            flyouts = [];

            this.hideTooltip();
        },

        // --- Teclado ---

        onKeydown(event) {
            if (event.key === 'Escape') {
                this.closeFloating();
                this.$store.koreSidebar.closeMobile(this.id);

                return;
            }

            const keys = ['ArrowDown', 'ArrowUp', 'Home', 'End', 'ArrowRight', 'ArrowLeft'];

            if (!keys.includes(event.key)) return;

            const items = this.focusableItems();
            const index = items.indexOf(document.activeElement);

            // El foco está en el sidebar pero no en un item (p.ej. algo del header):
            // no secuestramos las flechas.
            if (index === -1) return;

            const current = items[index];
            const owner = current.closest('[data-kore-has-children]');
            const ownsCurrent = owner && owner.querySelector(':scope > a, :scope > button') === current;

            // Las flechas ←→ abren y cierran; en un sidebar a la derecha, el sentido natural
            // se invierte.
            const forward = this.placement === 'right' ? 'ArrowLeft' : 'ArrowRight';
            const backward = this.placement === 'right' ? 'ArrowRight' : 'ArrowLeft';

            switch (event.key) {
                case 'ArrowDown':
                    this.focusAt(items, index + 1);
                    break;

                case 'ArrowUp':
                    this.focusAt(items, index - 1);
                    break;

                case 'Home':
                    this.focusAt(items, 0);
                    break;

                case 'End':
                    this.focusAt(items, items.length - 1);
                    break;

                case forward:
                    if (!ownsCurrent) return;

                    if (owner.getAttribute('data-kore-open') !== 'true') {
                        this.setItemOpen(owner, true);
                    } else {
                        // Ya abierto: la flecha entra en el sub-menú.
                        this.focusAt(this.focusableItems(), index + 1);
                    }
                    break;

                case backward:
                    if (ownsCurrent && owner.getAttribute('data-kore-open') === 'true') {
                        this.setItemOpen(owner, false);
                        break;
                    }

                    // Dentro de un sub-menú: la flecha sube al padre.
                    if (owner) {
                        const parentTrigger = owner.querySelector(':scope > a, :scope > button');

                        if (parentTrigger) parentTrigger.focus();
                    }
                    break;

                default:
                    return;
            }

            event.preventDefault();
        },

        /**
         * Items que se pueden enfocar ahora mismo.
         *
         * Excluye los que viven en un sub-menú cerrado: siguen en el DOM, y sin este
         * filtro las flechas saltarían a items invisibles.
         */
        focusableItems() {
            const candidates = this.$el.querySelectorAll('a[href]:not([tabindex="-1"]), button:not([disabled])');

            return Array.from(candidates).filter((el) => !this.isHidden(el));
        },

        isHidden(el) {
            let node = el.parentElement;

            while (node && node !== this.$el) {
                if (node.classList.contains('kore-sidebar-submenu')) {
                    // En modo iconos los sub-menús no se despliegan en línea (salen en flyout).
                    if (this.isIconOnly() && !node.hasAttribute('data-flyout')) return true;

                    if (node.parentElement?.getAttribute('data-kore-open') !== 'true') return true;
                }

                node = node.parentElement;
            }

            return false;
        },

        focusAt(items, index) {
            const target = items[Math.max(0, Math.min(index, items.length - 1))];

            if (target) target.focus();
        },
    };
}
