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

/**
 * El dueño tiene que identificarse por VALOR, no por referencia.
 *
 * El overlay manager pasaba `this` —el componente Alpine— y el body se quedaba
 * fijo para siempre: cada expresión de Alpine construye un proxy nuevo del
 * mismo componente, así que el objeto que llegaba a `unlockScroll` nunca era el
 * que había registrado `lockScroll` y el `Set` no lo encontraba. Ningún error,
 * ningún síntoma hasta que el usuario intenta desplazar la página.
 *
 * Se rechaza en vez de convertir a cadena: dos objetos distintos darían el
 * mismo `[object Object]` y se pisarían el lock entre ellos, que es un fallo
 * aún más difícil de ver. Estas funciones no forman parte de la API pública de
 * la librería, así que el único que puede provocar esto es el propio código de
 * KoreUi.
 */
function exigirClave(owner, funcion) {
    if (typeof owner === 'string' || typeof owner === 'symbol') return;

    throw new TypeError(
        `${funcion}() necesita una clave estable (cadena o símbolo) y ha recibido ` +
        `${typeof owner}. Un objeto no vale: no se puede garantizar que sea el mismo ` +
        'en el lock y en el unlock.'
    );
}

export function lockScroll(owner) {
    exigirClave(owner, 'lockScroll');

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
    exigirClave(owner, 'unlockScroll');

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

/**
 * ¿Hay alguien por encima de este dueño?
 *
 * El `Set` conserva el orden de inserción, así que la lista de dueños del lock
 * es también el orden de las capas: el último en tomarlo es el que está arriba.
 * Es la única fuente de verdad sobre qué tapa a qué sin que un componente tenga
 * que conocer a los demás.
 *
 * La usa el drawer del sidebar para decidir si un `Escape` es suyo o de un modal
 * abierto encima. Sin esto, los dos escuchaban en `window`, ninguno marcaba el
 * evento, y una sola pulsación cerraba las dos cosas a la vez.
 *
 * Un dueño que no está registrado —un drawer con `overlay: false`, que no
 * bloquea el scroll— cede ante cualquier otro que sí lo esté: si algo tiene el
 * body tomado, está por encima de él.
 */
export function hayDuenoPorEncima(owner) {
    exigirClave(owner, 'hayDuenoPorEncima');

    const capas = [...owners];
    const posicion = capas.indexOf(owner);

    return posicion === -1
        ? capas.length > 0
        : posicion < capas.length - 1;
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
