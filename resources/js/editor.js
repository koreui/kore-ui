/**
 * El editor, en su propio bundle.
 *
 * **Por qué no va con el resto.** El editor pesa 5,3 kB gzip de los 44 del
 * paquete: un octavo del JavaScript de la librería para un componente que la
 * mayoría de las páginas no usa. Aquí se carga solo cuando `<x-kore::editor>`
 * aparece en la página, y quien no lo use no paga nada.
 *
 * **El orden importa, y no se puede dar por supuesto.** Este archivo llega por
 * un `<script defer>` que el propio componente inyecta, así que puede ejecutarse
 * antes o después de que Alpine arranque:
 *
 * - Si Alpine todavía no ha arrancado, basta con esperar a `alpine:init`.
 * - Si ya arrancó —el editor llegó dentro de una respuesta de Livewire, o el
 *   navegador ordenó los scripts de otra manera—, registrar a secas no vale de
 *   nada: Alpine ya recorrió el DOM, se encontró un `x-data="KoreEditor(…)"` que
 *   no existía y dejó ese componente muerto. Hay que volver a inicializar esos
 *   nodos a mano.
 */
import KoreEditor from './form/editor.js';

const registrar = () => {
    if (!window.Alpine) return false;

    window.Alpine.data('KoreEditor', KoreEditor);

    return true;
};

/** Los editores que se quedaron sin componente por llegar tarde. */
const rescatar = () => {
    const huerfanos = [...document.querySelectorAll('[x-data^="KoreEditor"]')]
        .filter((el) => !el._x_dataStack);

    huerfanos.forEach((el) => window.Alpine.initTree(el));
};

if (window.__koreAlpineIniciado) {
    if (registrar()) rescatar();
} else {
    document.addEventListener('alpine:init', registrar);
}
