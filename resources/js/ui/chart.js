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

// El morph de Livewire va en las DOS direcciones, y las dos hay que atenderlas:
//
//   → Lo que escribe el CLIENTE, el morph lo BORRA. Está medido: se lleva por delante los
//     data-* y los style inline que no vengan del servidor. Sin reaplicarlos, ocultas una
//     serie, actualizas cualquier cosa de la página, y la serie reaparece mientras Alpine
//     sigue creyendo que está oculta.
//
//   → Lo que escribe el SERVIDOR, Alpine NO lo RELEE. El morph preserva el elemento, así que
//     el x-data no se re-inicializa y init() no vuelve a correr. El <script> del payload sí se
//     refresca en el DOM (está fuera del wire:ignore, que solo protege la caja flotante), pero
//     `payload` se queda con la copia del render anterior.
let morphHookRegistered = false;
const instances = new Set();

function ensureMorphHook() {
    if (morphHookRegistered) return;
    if (typeof window === 'undefined' || !window.Livewire || !window.Livewire.hook) return;

    morphHookRegistered = true;

    // Envuelto en try/catch: si esto lanza, se rompe el ciclo de morph de Livewire y
    // wire:loading se queda colgado para siempre.
    window.Livewire.hook('morphed', (payload) => {
        try {
            const el = payload && payload.el;

            instances.forEach((instance) => instance.onMorphed(el));
        } catch (e) {
            // un gráfico nunca puede tumbar el morph
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

    // El tramo visible, en % del dominio COMPLETO. Lo manda el servidor en el payload.
    //
    // ⚠️ Se llama `view` y no `window` a propósito: dentro de una expresión de Alpine, una
    // propiedad llamada `window` SOMBREARÍA el objeto global del navegador.
    view: [0, 100],

    // El rectángulo que se está arrastrando, en % del ÁREA VISIBLE (no del dominio).
    brush: null,

    // La celda de un heatmap bajo el ratón: `{ row, col, value }` o null.
    cell: null,

    _root: null,
    _reference: null,
    _floating: null,
    _drag: null,
    _stream: null,
    _wireId: null,

    init() {
        // ⚠️ `$el` NO es siempre la raíz del componente: Alpine lo resuelve al elemento cuya
        // expresión se está evaluando. Dentro del x-on:click de un botón de la leyenda, `$el`
        // es EL BOTÓN. Si reapply() usara $el, buscaría las series dentro del botón, no
        // encontraría ninguna, y ocultar una serie no haría nada — sin ningún error.
        // Se guarda la raíz aquí, que es el único momento en que $el sí lo es.
        this._root = this.$el;

        this.payload = this._readPayload();
        this.view = this.payload?.window ?? [0, 100];

        instances.add(this);
        ensureMorphHook();

        if (config.stream?.every) {
            // El id del componente LIVEWIRE, no el del gráfico. Es la pieza que falta para darse
            // cuenta de que dos gráficos del mismo componente comparten refresco.
            this._wireId = this.$wire?.id ?? null;

            this._stream = setInterval(() => this._tick(), config.stream.every);
        }
    },

    destroy() {
        instances.delete(this);
        this._stopTooltip();

        // Sin esto, el temporizador sigue pidiendo refrescos de un componente que ya no existe —
        // y con `wire:navigate` eso significa un goteo eterno contra el servidor por cada gráfico
        // que el usuario haya visitado.
        clearInterval(this._stream);
        this._stream = null;
    },

    // ---- datos en vivo -----------------------------------------------------------
    //
    // El morph de Livewire YA ES el mecanismo de actualización: cambias el dato en PHP y el
    // atributo `d` del <path> se actualiza sin recrear el nodo. No hay nada que inventar ahí.
    //
    // Lo que sí hay que construir es saber **cuándo NO refrescar**. Un `wire:poll` a secas
    // refresca siempre, y hay tres momentos en que eso es hostil. Por eso el refresco lo conduce
    // el gráfico y no un `wire:poll` ciego.

    get streamPaused() {
        // 1. Mientras lees un tooltip. El dato se movería bajo el cursor y el número que estabas
        //    mirando cambiaría mientras lo miras.
        if (this.hover || this._drag) return true;

        // 2. Con la pestaña oculta. Diez pestañas abiertas son diez renders por segundo en tu
        //    servidor, para nadie. (`wire:poll` sí trae esto de serie; el resto, no.)
        if (typeof document !== 'undefined' && document.hidden) return true;

        // 3. Con el zoom puesto. Has ampliado para mirar algo concreto: que se te mueva el suelo
        //    debajo es exactamente lo que no quieres.
        if (this.zoomed) return true;

        // 4. ⚠️ Y mientras alguien esté leyendo CUALQUIER OTRO gráfico del mismo componente
        //    Livewire.
        //
        //    Un refresco no actualiza un gráfico: actualiza el COMPONENTE ENTERO. Así que dos
        //    gráficos en el mismo componente comparten el dato — y si el ratón está sobre el de
        //    al lado, mi temporizador le movería los números bajo el cursor. Es exactamente la
        //    pausa nº 1, vista desde el otro lado.
        return this._siblingBusy();
    },

    /** ¿Hay alguien leyendo otro gráfico del mismo componente Livewire? */
    _siblingBusy() {
        if (!this._wireId) return false;

        for (const other of instances) {
            if (other._wireId === this._wireId && (other.hover || other._drag)) return true;
        }

        return false;
    },

    /**
     * ¿Es este el gráfico que conduce el refresco de su componente?
     *
     * Un refresco actualiza el componente Livewire ENTERO. Con dos gráficos con stream dentro
     * habría dos temporizadores pidiendo exactamente lo mismo: el doble de round-trips contra el
     * servidor, y el dato avanzando al doble de velocidad. Conduce **uno solo** — el primero que
     * se montó— y los demás se limitan a repintarse con lo que traiga.
     *
     * (Los otros sí conservan sus transiciones y sus pausas: lo que no tienen es su propio
     * temporizador.)
     */
    _isStreamDriver() {
        // Sin componente Livewire no hay nada que coordinar: cada uno conduce el suyo.
        if (!this._wireId) return true;

        for (const other of instances) {
            if (other._wireId === this._wireId && other._stream) {
                // Por la RAÍZ, no por `$el`: fuera de una expresión de Alpine, `$el` no es la raíz
                // del componente. Es la misma trampa que init() lleva documentada desde el primer día.
                return other._root === this._root;
            }
        }

        return false;
    },

    _tick() {
        if (!this.$wire || !this._isStreamDriver() || this.streamPaused) return;

        const call = config.stream?.call;

        // Con `call`, se invoca el método que trae el dato nuevo — como `wire:poll.5s="tick"`.
        // Sin él, un `$refresh()` a secas: sirve cuando el dato lo produce el propio render (una
        // consulta), no cuando hay que avanzar un estado.
        if (call) {
            this.$wire[call]?.();
        } else {
            this.$wire.$refresh();
        }
    },

    // ---- puntero -----------------------------------------------------------------

    onPointerMove(event) {
        // El arrastre primero: mientras se está haciendo un brush o moviendo el slider, el tooltip
        // estorba — y además un gráfico con zoom pero SIN tooltip no lleva `xs` en el payload, así
        // que la guarda de abajo lo cortaría en seco.
        if (this.onDragMove(event)) {
            this.hover = null;
            this._stopTooltip();

            return;
        }

        if (!this.payload?.xs?.length) return;

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
        this.cell = null;
        this._stopTooltip();
    },

    /**
     * El hover de un heatmap, por DELEGACIÓN.
     *
     * Un mapa de calor de 365×7 son 2.555 celdas. Poner un listener por celda es letal (medido en
     * el informe: 30 ms por frame). Así que hay UN solo `pointermove` en la rejilla, y aquí se lee
     * el `data-*` de la celda que hay debajo del ratón — `closest()` la encuentra en O(1) porque el
     * evento nace en ella.
     */
    onHeatmapMove(event) {
        const target = event.target.closest('[data-heat-cell]');

        if (!target) {
            this.cell = null;
            this._stopTooltip();

            return;
        }

        this.cell = { row: target.dataset.r, col: target.dataset.c, value: target.dataset.v };

        // El tooltip se ancla al CENTRO de la celda, no al ratón: habla de esa celda, y no debe
        // temblar mientras el ratón se mueve dentro de ella.
        const box = target.getBoundingClientRect();
        this._positionTooltip(box.left + box.width / 2, box.top);
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

    // ---- morph -------------------------------------------------------------------

    /**
     * Después de que Livewire morphee: releer lo del servidor, reestampar lo del cliente.
     *
     * Lo segundo ya se hacía. Lo primero NO, y era un bug: cambiabas el dato con un wire:model,
     * el <path> se repintaba con los valores nuevos, y el tooltip seguía enseñando los VIEJOS
     * —sin ningún error— porque `payload` solo se leía en init() y el morph no reinicializa el
     * x-data.
     */
    onMorphed(el) {
        if (!this._root) return;

        // Solo si el morph tocó a ESTE gráfico. Releer el payload de los cinco gráficos de una
        // página cada vez que morphea cualquier cosa cuesta un JSON.parse por gráfico, y a 2.000
        // puntos el payload pesa 53 kB. `el` puede no venir, según el build de Livewire.
        const mine = !el
            || this._root === el
            || (typeof el.contains === 'function' && (this._root.contains(el) || el.contains(this._root)));

        if (!mine) return;

        const fresh = this._readPayload();

        if (fresh) {
            this.payload = fresh;

            // La ventana también viene del servidor: es él quien decide qué se ve, y el cliente
            // solo la refleja. Por eso el zoom NO necesita el hook del morph para sobrevivir —
            // su estado vive en el componente Livewire, no aquí.
            this.view = fresh.window ?? [0, 100];

            // El dataset nuevo puede ser más corto que el viejo: el índice sobre el que estaba
            // el cursor puede haber dejado de existir, y el tooltip leería un `undefined`.
            if (this.hover && this.hover.index >= fresh.xs.length) {
                this.onPointerLeave();
            }
        }

        this.reapply();
    },

    // ---- zoom --------------------------------------------------------------------
    //
    // Aquí NO hay ni una escala, ni una fecha, ni un formato. Sólo aritmética sobre porcentajes.
    //
    // Ésa es toda la idea: el cliente manda **dos números** (el tramo, en % del dominio completo)
    // y el servidor hace el resto — invierte el dominio, elige los ticks nuevos, reescala el eje Y
    // y devuelve el <path>. Livewire lo morphea. Un zoom en el cliente exigiría portar `Ticks`,
    // `Scales`, `Path` y `Format` a JavaScript y mantener DOS implementaciones de la geometría
    // idénticas para siempre. Esto son 60 líneas y cero riesgo de divergencia.
    //
    // Y de propina: al ampliar, el eje temporal cambia de unidad SOLO. Un año dice trimestres;
    // ampliada una semana, el mismo eje dice días. Eso lo hace `TimeTicks`, que está en PHP.

    get zoomed() {
        return this.view[0] > 0.01 || this.view[1] < 99.99;
    },

    /** De un % del área VISIBLE a un % del dominio COMPLETO. Es la única conversión que hay. */
    _toFull(percent) {
        const [from, to] = this.view;

        return from + (percent * (to - from)) / 100;
    },

    /** Manda la ventana al servidor. El estado vive en Livewire, no aquí. */
    _apply(from, to) {
        const model = config.zoom?.model;
        if (!model || !this.$wire) return;

        // ⚠️ Hay un SUELO, y lo pone el servidor: sin él se puede ampliar hasta un tramo más fino
        // que la separación entre dos puntos, y ahí no queda nada que dibujar. Se llega a algo
        // como «viendo el 48,1 % – 48,3 %» con el gráfico vacío.
        //
        // Cuando el tramo pedido es más estrecho que el suelo, no se descarta: se ENSANCHA
        // alrededor de su centro. Ignorar el gesto dejaría al usuario arrastrando sin que pasara
        // nada, que es peor que hacer algo razonable.
        const floor = this.payload?.minWindow ?? 0.1;

        if (to - from < floor) {
            const center = (from + to) / 2;

            from = center - floor / 2;
            to = center + floor / 2;

            // Reajustado contra los bordes, o al ampliar al principio del todo la ventana se
            // saldría por la izquierda y el clamp la aplastaría contra el 0.
            if (from < 0) [from, to] = [0, floor];
            if (to > 100) [from, to] = [100 - floor, 100];
        }

        // Redondeado, y no por elegancia: la ventana va en el query string con #[Url], y un píxel
        // de arrastre produce un 17.99999938120037 que acaba TAL CUAL en la barra de direcciones.
        // Dos decimales sobran para colocar un punto en un gráfico de 2.000 px.
        const clamp = (v) => Math.round(Math.max(0, Math.min(100, v)) * 100) / 100;

        this.$wire.$set(model, [clamp(from), clamp(to)]);
    },

    resetZoom() {
        const model = config.zoom?.model;
        if (model && this.$wire) this.$wire.$set(model, null);
    },

    /** Amplía (factor < 1) o reduce (factor > 1) alrededor del centro de lo que se ve. */
    zoomBy(factor) {
        const [from, to] = this.view;
        const center = (from + to) / 2;
        const half = ((to - from) * factor) / 2;

        if (half >= 50) {
            this.resetZoom();

            return;
        }

        // El suelo lo aplica `_apply()`: pulsar «+» veinte veces no puede dejar el gráfico vacío.
        this._apply(center - half, center + half);
    },

    /** Desplaza la ventana. Es el pan con teclado: las flechas sobre el slider. */
    nudge(percent) {
        const [from, to] = this.view;
        const span = to - from;
        const step = Math.max(-from, Math.min(100 - to, (span * percent) / 100));

        this._apply(from + step, to + step);
    },

    // ---- arrastrar ---------------------------------------------------------------

    onBrushDown(event) {
        if (event.button !== 0) return;

        const box = this.$refs.plot?.getBoundingClientRect();
        if (!box || box.width === 0) return;

        const at = ((event.clientX - box.left) / box.width) * 100;

        this._drag = { mode: 'brush', box, origin: at };
        this.brush = { from: at, to: at };

        // El puntero se captura, o sacar el ratón del gráfico a mitad de arrastre lo dejaría
        // colgado: el `pointerup` se dispararía en otro elemento y nunca llegaría aquí.
        event.currentTarget.setPointerCapture?.(event.pointerId);
    },

    onSliderDown(event, mode) {
        if (event.button !== 0) return;

        const track = event.currentTarget.closest('.kore-chart-slider');
        const box = track?.getBoundingClientRect();
        if (!box || box.width === 0) return;

        const at = ((event.clientX - box.left) / box.width) * 100;

        this._drag = { mode, box, origin: at, view: [...this.view] };

        track.setPointerCapture?.(event.pointerId);

        // Un clic suelto en la pista (no en la ventana) lleva la ventana ahí. Sin esto, pinchar
        // en la mitad derecha del contexto no haría absolutamente nada.
        if (mode === 'pan' && (at < this.view[0] || at > this.view[1])) {
            const half = (this.view[1] - this.view[0]) / 2;

            this._drag.origin = at;
            this._drag.view = [at - half, at + half];
            this.view = [Math.max(0, at - half), Math.min(100, at + half)];
        }
    },

    /** Un solo manejador de movimiento: el tooltip y el arrastre comparten el mismo puntero. */
    onDragMove(event) {
        if (!this._drag) return false;

        const { mode, box, origin } = this._drag;
        const at = ((event.clientX - box.left) / box.width) * 100;

        if (mode === 'brush') {
            this.brush = { from: Math.min(origin, at), to: Math.max(origin, at) };

            return true;
        }

        const [from, to] = this._drag.view;
        const delta = at - origin;

        if (mode === 'pan') {
            // El pan no deforma nada: la ventana se mueve, no se estira. Por eso el pan cabe en
            // esta arquitectura y el zoom continuo con rueda no.
            const shift = Math.max(-from, Math.min(100 - to, delta));

            this.view = [from + shift, to + shift];
        } else if (mode === 'from') {
            this.view = [Math.max(0, Math.min(to - 0.5, from + delta)), to];
        } else {
            this.view = [from, Math.min(100, Math.max(from + 0.5, to + delta))];
        }

        return true;
    },

    onDragEnd() {
        if (!this._drag) return;

        const { mode } = this._drag;
        const brush = this.brush;

        this._drag = null;
        this.brush = null;

        if (mode === 'brush') {
            // Un arrastre de menos de un 1 % es un clic, no un zoom.
            if (brush && brush.to - brush.from > 1) {
                this._apply(this._toFull(brush.from), this._toFull(brush.to));
            }

            return;
        }

        // El pan y el redimensionado ya han movido `view` en cada frame — eso es la
        // previsualización, y es puro compositor. Al soltar se pide UN round-trip, y el servidor
        // devuelve el trazo nítido, con sus ticks nuevos. Es el modelo de un mapa: el mosaico se
        // escala mientras arrastras y se redibuja al soltar.
        this._apply(this.view[0], this.view[1]);
    },

    // ---- payload -----------------------------------------------------------------

    _readPayload() {
        // Sobre `_root`, NUNCA sobre `$el`: esto se llama también desde onMorphed(), que corre
        // dentro de un hook de Livewire y no dentro de una expresión de Alpine — y ahí `$el` no
        // es la raíz. Es la misma trampa que init() ya documenta.
        if (!this._root) return null;

        // En un <script type="application/json">, no en un atributo: el JSON dentro de un
        // atributo HTML escapa cada comilla a &quot; — seis bytes por comilla, y a 2.000
        // puntos eso son decenas de kB de nada.
        const node = this._root.querySelector('[data-kore-chart-payload]');

        if (!node) return null;

        try {
            return JSON.parse(node.textContent);
        } catch (e) {
            return null;
        }
    },
});
