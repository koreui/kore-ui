import { startFloating, stopFloating, virtualReference } from '../utils/floating.js';

/**
 * Todo el JavaScript del gráfico. Y es poco a propósito.
 *
 * Lo que este componente NO hace, porque el servidor ya lo hizo:
 *   - No calcula escalas ni dominios: la geometría viene resuelta de PHP.
 *   - No dibuja: el SVG ya está en el HTML de la respuesta.
 *   - No reacciona al resize: las coordenadas son porcentajes y el navegador escala solo.
 *   - No formatea números: las etiquetas viajan hechas desde PHP (cero `Intl` en el bundle).
 *   - No lee colores del tema: los colores son tokens CSS y el navegador los resuelve al
 *     cambiar de tema, sin ejecutar nada.
 *
 * Lo que sí hace: encontrar el punto bajo el ratón, y ocultar series.
 */

// El estado que escribe el CLIENTE no sobrevive al morph de Livewire. Está medido: el morph
// borra los data-* y los style inline que no vengan del servidor. Sin este hook, ocultar una
// serie y luego actualizar cualquier cosa de la página la haría reaparecer, mientras Alpine
// sigue creyendo que está oculta.
let morphHookRegistered = false;
const instances = new Set();

function ensureMorphHook() {
    if (morphHookRegistered) return;
    if (typeof window === 'undefined' || !window.Livewire || !window.Livewire.hook) return;

    morphHookRegistered = true;

    // Envuelto en try/catch: si esto lanza, se rompe el ciclo de morph de Livewire y
    // wire:loading se queda colgado para siempre.
    window.Livewire.hook('morphed', () => {
        try {
            instances.forEach((instance) => instance.reapply());
        } catch (e) {
            // ocultar una serie nunca puede tumbar el morph
        }
    });
}

export default (config = {}) => ({
    hover: null,

    hidden: [],

    // ⚠️⚠️ TODAS estas propiedades TIENEN que estar declaradas aquí, aunque sólo se asignen
    // dentro de init(). Alpine no llama a los métodos con `this` = este objeto: los llama con
    // un Proxy que fusiona toda la pila de scopes, y su trampa `set` hace esto:
    //
    //     if (!target) target = objects[objects.length - 1];   // el x-data MÁS EXTERNO
    //
    // Es decir: `this.payload = …` sobre una propiedad no declarada NO se guarda en el
    // gráfico — se guarda en el x-data ancestro más externo de la página (el del layout),
    // que comparten TODOS los gráficos. Gana el último en inicializarse, y a partir de ahí
    // los cinco gráficos de una página enseñan los datos del quinto. Sin un solo error.
    // Es exactamente el bug que tuvo la demo. Ver tests/js/chart-alpine.test.js.
    payload: null,
    _root: null,
    _reference: null,
    _floating: null,

    init() {
        // ⚠️ `$el` NO es siempre la raíz del componente: Alpine lo resuelve al elemento cuya
        // expresión se está evaluando. Dentro del x-on:click de un botón de la leyenda, `$el`
        // es EL BOTÓN. Si reapply() usara $el, buscaría las series dentro del botón, no
        // encontraría ninguna, y ocultar una serie no haría nada — sin ningún error.
        // Se guarda la raíz aquí, que es el único momento en que $el sí lo es.
        this._root = this.$el;

        this.payload = this._readPayload();

        instances.add(this);
        ensureMorphHook();
    },

    destroy() {
        instances.delete(this);
        this._stopTooltip();
    },

    // ---- puntero -----------------------------------------------------------------

    onPointerMove(event) {
        if (!this.payload || !this.payload.xs.length) return;

        const plot = this.$refs.plot;
        if (!plot) return;

        const box = plot.getBoundingClientRect();
        if (box.width === 0) return;

        // Del píxel al espacio 0-100 del gráfico. Es la única conversión que hace el cliente.
        const percent = ((event.clientX - box.left) / box.width) * 100;
        const index = this._nearest(percent);

        this.hover = {
            index,
            label: this.payload.labels[index] ?? '',
            rows: this.payload.series
                .filter((serie) => !this.hidden.includes(serie.id))
                .map((serie) => ({
                    id: serie.id,
                    name: serie.name,
                    slot: serie.slot,
                    value: serie.labels[index] ?? '—',
                })),
        };

        const x = this.payload.xs[index];

        if (this.$refs.crosshair) {
            this.$refs.crosshair.style.setProperty('--kx', String(x));
        }

        // La X se ENGANCHA al dato (la del crosshair, no la del ratón: el tooltip habla de un
        // punto concreto, y moverse dentro de la misma banda no debe hacerlo temblar). La Y
        // sigue al cursor. Anclarlo al borde superior del plot, como estaba, lo dejaba siempre
        // flotando por encima del gráfico, encima del título.
        this._positionTooltip(box.left + (x / 100) * box.width, event.clientY);
    },

    onPointerLeave() {
        this.hover = null;
        this._stopTooltip();
    },

    /**
     * Búsqueda binaria sobre las X.
     *
     * Ésta es la razón de que el payload sea columnar (la idea es de uPlot): encontrar el
     * punto bajo el ratón es O(log n) y no depende de la geometría. Con 10.000 puntos son 14
     * comparaciones.
     */
    _nearest(percent) {
        const xs = this.payload.xs;
        let lo = 0;
        let hi = xs.length - 1;

        while (hi - lo > 1) {
            const mid = (lo + hi) >> 1;
            if (xs[mid] > percent) hi = mid;
            else lo = mid;
        }

        return Math.abs(xs[lo] - percent) <= Math.abs(xs[hi] - percent) ? lo : hi;
    },

    // ---- tooltip -----------------------------------------------------------------

    _positionTooltip(clientX, clientY) {
        const tooltip = this.$refs.tooltip;
        if (!tooltip) return;

        // El tooltip no cuelga de un elemento: cuelga de un punto de datos. Y un ratón que se
        // mueve no dispara scroll, resize ni mutación, así que autoUpdate nunca repintaría:
        // hay que pedírselo a mano en cada movimiento.
        if (!this._reference) {
            this._reference = virtualReference(this.$refs.plot);
            this._reference.setPoint(clientX, clientY);

            this.$nextTick(() => {
                this._floating = startFloating(this._reference, tooltip, {
                    placement: config.placement || 'top',
                    offset: 12,
                });
            });

            return;
        }

        this._reference.setPoint(clientX, clientY);
        this._floating?.update();
    },

    _stopTooltip() {
        stopFloating(this._floating);
        this._floating = null;
        this._reference = null;
    },

    // ---- leyenda -----------------------------------------------------------------

    toggleSeries(id) {
        this.hidden = this.hidden.includes(id)
            ? this.hidden.filter((current) => current !== id)
            : [...this.hidden, id];

        this.reapply();
    },

    isHidden(id) {
        return this.hidden.includes(id);
    },

    /**
     * Vuelve a estampar en el DOM lo que el servidor no sabe.
     *
     * Se llama al ocultar una serie y también después de cada morph de Livewire, porque el
     * morph reescribe el nodo con el HTML del servidor — que no lleva `data-hidden`.
     */
    reapply() {
        if (!this._root) return;

        this._root.querySelectorAll('[data-kore-serie]').forEach((node) => {
            const id = node.getAttribute('data-kore-serie');

            if (this.hidden.includes(id)) {
                node.setAttribute('data-hidden', 'true');
            } else {
                node.removeAttribute('data-hidden');
            }
        });
    },

    // ---- payload -----------------------------------------------------------------

    _readPayload() {
        // En un <script type="application/json">, no en un atributo: el JSON dentro de un
        // atributo HTML escapa cada comilla a &quot; — seis bytes por comilla, y a 2.000
        // puntos eso son decenas de kB de nada.
        const node = this.$el?.querySelector('[data-kore-chart-payload]');   // en init(), $el SÍ es la raíz

        if (!node) return null;

        try {
            return JSON.parse(node.textContent);
        } catch (e) {
            return null;
        }
    },
});
