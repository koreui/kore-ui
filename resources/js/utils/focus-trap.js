/**
 * Focus trap mínimo, sin dependencias.
 *
 * La librería no incluye @alpinejs/focus: ese plugin arrastra focus-trap y
 * tabbable, que juntos se comen el presupuesto de bundle (37 kB gzip) por sí
 * solos. Lo que hace falta aquí cabe en unas líneas: mantener el tabulador
 * dentro del panel, llevar el foco al abrir y devolverlo al cerrar.
 *
 *   const trap = createFocusTrap(panel);
 *   trap.activate();   // al abrir
 *   trap.deactivate(); // al cerrar — devuelve el foco a donde estaba
 */

const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

function focusableWithin(root) {
    return Array.from(root.querySelectorAll(FOCUSABLE))
        // offsetParent descarta lo que está oculto; los `fixed` no tienen
        // offsetParent aunque sean visibles, de ahí la segunda comprobación.
        .filter(el => el.offsetParent !== null || getComputedStyle(el).position === 'fixed');
}

export function createFocusTrap(root) {
    let previouslyFocused = null;
    let onKeydown = null;

    return {
        activate() {
            if (!root || onKeydown) return;

            previouslyFocused = document.activeElement;

            onKeydown = (e) => {
                if (e.key !== 'Tab') return;

                // Si el panel ya no está en el documento, este trap no pinta
                // nada: seguiría secuestrando el tabulador de toda la página.
                // Pasa si el nodo desaparece sin que nadie llame a deactivate()
                // — un morph de Livewire que se lleve el contenedor, por ejemplo.
                if (!document.contains(root)) return;

                const items = focusableWithin(root);
                if (items.length === 0) return;

                const first = items[0];
                const last = items[items.length - 1];

                // Si el foco se escapó del panel, se recupera en el borde que toque.
                if (!root.contains(document.activeElement)) {
                    e.preventDefault();
                    (e.shiftKey ? last : first).focus();
                    return;
                }

                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            };

            document.addEventListener('keydown', onKeydown, true);

            // El panel puede estar en plena transición de entrada: se espera un
            // frame antes de buscar lo que se puede enfocar.
            requestAnimationFrame(() => {
                const items = focusableWithin(root);
                (items[0] || root).focus();
            });
        },

        deactivate() {
            if (onKeydown) {
                document.removeEventListener('keydown', onKeydown, true);
                onKeydown = null;
            }

            if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
                previouslyFocused.focus();
            }

            previouslyFocused = null;
        },
    };
}

export default createFocusTrap;
