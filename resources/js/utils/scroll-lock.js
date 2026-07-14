/**
 * Body scroll lock, compartido por todo lo que se abre por encima de la página
 * (overlay manager, drawer móvil del sidebar…).
 *
 * Vive fuera de los componentes porque el lock es un recurso GLOBAL: si el
 * drawer del sidebar y un modal lo tomaran cada uno por su cuenta, el primero
 * en cerrarse devolvería el scroll al body con el otro todavía abierto. Se
 * lleva por tanto un conteo de dueños, y el body solo se libera cuando se va
 * el último.
 *
 * El truco de `position: fixed` + `top: -scrollY` es el único que funciona en
 * Safari iOS; el precio es que hay que restaurar el scroll a mano al soltar.
 */

const owners = new Set();
let savedScrollY = 0;

export function lockScroll(owner) {
    if (owners.has(owner)) return;

    const isFirst = owners.size === 0;
    owners.add(owner);

    if (!isFirst) return;

    savedScrollY = window.scrollY;
    document.body.style.position = 'fixed';
    document.body.style.top = `-${savedScrollY}px`;
    document.body.style.left = '0';
    document.body.style.right = '0';
}

export function unlockScroll(owner) {
    // delete() devuelve false si este dueño no tenía el lock: nada que soltar.
    if (!owners.delete(owner)) return;
    if (owners.size > 0) return;

    apply(true);
}

/**
 * Suelta el lock SIN restaurar el scroll. Es lo que hace falta tras una
 * navegación con wire:navigate: el body que llega es nuevo (no tiene los
 * estilos) y `savedScrollY` pertenece a la página anterior, así que
 * restaurarlo saltaría a una posición arbitraria de la página nueva.
 */
export function releaseScrollLock() {
    owners.clear();
    apply(false);
}

export function isScrollLocked() {
    return owners.size > 0;
}

function apply(restoreScroll) {
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';

    if (restoreScroll) window.scrollTo(0, savedScrollY);

    savedScrollY = 0;
}

// Guard: el bundle también se importa desde tests en Node, donde no hay DOM.
if (typeof document !== 'undefined') {
    document.addEventListener('livewire:navigated', () => {
        if (isScrollLocked()) releaseScrollLock();
    });
}
