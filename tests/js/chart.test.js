import { beforeEach, describe, expect, it, vi } from 'vitest';

// Se mockea floating.js: lo que hay que probar no es el posicionamiento (que ya tiene sus
// tests) sino la lógica del gráfico — encontrar el punto bajo el ratón y ocultar series.
const { setPoint } = vi.hoisted(() => ({ setPoint: vi.fn() }));

vi.mock('../../resources/js/utils/floating.js', () => ({
    startFloating: () => Object.assign(() => {}, { update: vi.fn() }),
    stopFloating: vi.fn(),
    virtualReference: () => ({ setPoint, getBoundingClientRect: () => ({}) }),
}));

const KoreChart = (await import('../../resources/js/ui/chart.js')).default;

const PAYLOAD = {
    xs: [10, 30, 50, 70, 90],
    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May'],
    series: [
        { id: 'c1-s1', name: 'Ingresos', slot: 1, labels: ['1.240 €', '3.180 €', '2.470 €', '4.910 €', '6.120 €'] },
        { id: 'c1-s2', name: 'Gastos', slot: 2, labels: ['800 €', '1.500 €', '1.100 €', '900 €', '1.700 €'] },
    ],
};

function makeChart({ width = 200, left = 0 } = {}) {
    const nodes = [
        { id: 'c1-s1', attrs: {} },
        { id: 'c1-s2', attrs: {} },
    ];

    // El payload es MUTABLE a propósito: es lo que un morph de Livewire reescribe en el DOM.
    const state = { payload: PAYLOAD };

    const chart = KoreChart({ id: 'c1' });

    chart.$el = {
        contains: () => false,
        querySelector: () => ({ textContent: JSON.stringify(state.payload) }),
        querySelectorAll: () => nodes.map((node) => ({
            getAttribute: () => node.id,
            setAttribute: (k, v) => { node.attrs[k] = v; },
            removeAttribute: (k) => { delete node.attrs[k]; },
        })),
    };

    chart.$refs = {
        plot: { getBoundingClientRect: () => ({ left, top: 0, width, height: 100 }) },
        crosshair: { style: { setProperty: vi.fn(), _values: {} } },
        tooltip: {},
    };

    chart.$nextTick = (fn) => fn();
    chart.init();

    // `morph()` es lo que hace Livewire: reescribe el HTML del servidor (el <script> del
    // payload incluido) y dispara el hook. Lo que NO hace es reinicializar el x-data.
    const morph = (payload, el) => {
        if (payload) state.payload = payload;
        chart.onMorphed(el);
    };

    return { chart, nodes, morph };
}

beforeEach(() => {
    globalThis.window = { Livewire: undefined };
    setPoint.mockClear();
});

describe('dónde se planta el tooltip', () => {
    it('la X se engancha al dato y la Y sigue al cursor', () => {
        // Antes se anclaba a `box.top`, o sea al BORDE SUPERIOR del plot: el tooltip acababa
        // siempre flotando por encima del gráfico, tapando el título. Sólo se ve mirándolo.
        const { chart } = makeChart({ width: 200, left: 0 });

        chart.onPointerMove({ clientX: 64, clientY: 137 });

        // El ratón está en el 32 %, pero el punto más cercano (xs[1]) está en el 30 % → 60px.
        // El tooltip habla de ESE punto, así que no tiembla mientras el ratón se mueve dentro
        // de la banda. La Y, en cambio, es la del cursor tal cual.
        expect(setPoint).toHaveBeenLastCalledWith(60, 137);
    });
});

describe('encontrar el punto bajo el ratón', function () {
    it('elige el punto más cercano, no el anterior', () => {
        const { chart } = makeChart({ width: 200 });

        // x=60px de 200 → 30 % → justo encima del segundo punto (xs[1] = 30)
        chart.onPointerMove({ clientX: 60 });

        expect(chart.hover.index).toBe(1);
        expect(chart.hover.label).toBe('Feb');
    });

    it('redondea al vecino más próximo por los dos lados', () => {
        const { chart } = makeChart({ width: 200 });

        chart.onPointerMove({ clientX: 42 });   // 21 % → más cerca de 30 que de 10
        expect(chart.hover.index).toBe(1);

        chart.onPointerMove({ clientX: 36 });   // 18 % → aún más cerca de 10
        expect(chart.hover.index).toBe(0);
    });

    it('no se sale por los bordes', () => {
        const { chart } = makeChart({ width: 200 });

        chart.onPointerMove({ clientX: -50 });
        expect(chart.hover.index).toBe(0);

        chart.onPointerMove({ clientX: 9999 });
        expect(chart.hover.index).toBe(4);
    });

    it('enseña las etiquetas YA formateadas por PHP, sin tocar un número', () => {
        // Si el JS tuviera que formatear, habría que portar Format a JS y mantener dos
        // implementaciones sincronizadas para siempre.
        const { chart } = makeChart();

        chart.onPointerMove({ clientX: 20 });

        expect(chart.hover.rows[0].value).toBe('1.240 €');
        expect(chart.hover.rows[1].name).toBe('Gastos');
    });

    it('no peta si el gráfico todavía no tiene ancho', () => {
        const { chart } = makeChart({ width: 0 });

        chart.onPointerMove({ clientX: 50 });

        expect(chart.hover).toBeNull();
    });
});

describe('ocultar series desde la leyenda', function () {
    it('marca la serie en el DOM', () => {
        const { chart, nodes } = makeChart();

        chart.toggleSeries('c1-s1');

        expect(chart.isHidden('c1-s1')).toBe(true);
        expect(nodes[0].attrs['data-hidden']).toBe('true');
        expect(nodes[1].attrs['data-hidden']).toBeUndefined();
    });

    it('la vuelve a enseñar al pulsar otra vez', () => {
        const { chart, nodes } = makeChart();

        chart.toggleSeries('c1-s1');
        chart.toggleSeries('c1-s1');

        expect(chart.isHidden('c1-s1')).toBe(false);
        expect(nodes[0].attrs['data-hidden']).toBeUndefined();
    });

    it('la serie oculta desaparece también del tooltip', () => {
        const { chart } = makeChart();

        chart.toggleSeries('c1-s1');
        chart.onPointerMove({ clientX: 20 });

        expect(chart.hover.rows).toHaveLength(1);
        expect(chart.hover.rows[0].name).toBe('Gastos');
    });

    it('sigue funcionando aunque Alpine cambie $el bajo los pies', () => {
        // Éste es un bug REAL que solo apareció en un navegador de verdad: `$el` no es la raíz
        // del componente, es el elemento cuya expresión se está evaluando. Dentro del
        // x-on:click de un botón de la leyenda, `$el` es EL BOTÓN — así que buscar las series
        // desde $el no encontraba ninguna y ocultar una serie no hacía absolutamente nada, sin
        // ningún error. Por eso la raíz se guarda en init().
        const { chart, nodes } = makeChart();

        // Alpine mueve $el al botón mientras evalúa su handler:
        chart.$el = { querySelectorAll: () => [], querySelector: () => null };

        chart.toggleSeries('c1-s1');

        expect(nodes[0].attrs['data-hidden']).toBe('true');
    });

    it('reaplica el estado tras un morph, o la serie oculta reaparecería', () => {
        // Medido: el morph de Livewire BORRA los data-* que escribe el cliente, porque el HTML
        // del servidor no los lleva. Sin reapply(), ocultar una serie y luego actualizar
        // cualquier cosa de la página la haría reaparecer mientras Alpine la cree oculta.
        const { chart, nodes, morph } = makeChart();

        chart.toggleSeries('c1-s2');
        delete nodes[1].attrs['data-hidden'];   // esto es lo que hace el morph

        morph();

        expect(nodes[1].attrs['data-hidden']).toBe('true');
    });
});

describe('el morph de Livewire, en la otra dirección', function () {
    const NUEVOS = {
        xs: [10, 30, 50, 70, 90],
        labels: ['Jun', 'Jul', 'Ago', 'Sep', 'Oct'],
        series: [
            { id: 'c1-s1', name: 'Ingresos', slot: 1, labels: ['9 €', '8 €', '7 €', '6 €', '5 €'] },
        ],
    };

    it('relee el payload, o el tooltip enseñaría los datos del render ANTERIOR', () => {
        // El bug: `payload` sólo se leía en init(), y el morph NO reinicializa el x-data (Alpine
        // preserva el elemento). El <script> del payload sí se refresca en el DOM —está fuera del
        // wire:ignore, que sólo protege la caja flotante—, así que el <path> se repintaba con los
        // datos nuevos mientras el tooltip seguía leyendo los viejos. Sin ningún error.
        const { chart, morph } = makeChart();

        expect(chart.payload.labels[1]).toBe('Feb');

        morph(NUEVOS);

        chart.onPointerMove({ clientX: 60 });   // 30 % → xs[1]

        expect(chart.hover.label).toBe('Jul');
        expect(chart.hover.rows[0].value).toBe('8 €');
    });

    it('suelta el hover si el dataset nuevo tiene menos filas', () => {
        // Si no, el índice sobre el que estaba el cursor deja de existir y el tooltip lee un
        // undefined. Es el caso normal de un gráfico en streaming con ventana deslizante.
        const { chart, morph } = makeChart();

        chart.onPointerMove({ clientX: 9999 });
        expect(chart.hover.index).toBe(4);

        morph({ xs: [10, 50], labels: ['Ene', 'Feb'], series: [] });

        expect(chart.hover).toBeNull();
    });

    it('ignora los morphs de otras partes de la página', () => {
        // Releer el payload de los cinco gráficos de una página cada vez que morphea cualquier
        // cosa cuesta un JSON.parse por gráfico — y a 2.000 puntos el payload pesa 53 kB.
        const { chart, morph } = makeChart();

        morph(NUEVOS, { contains: () => false });   // un morph de otro componente

        expect(chart.payload.labels[1]).toBe('Feb');
    });
});

describe('sin payload', function () {
    it('no hace nada en vez de reventar', () => {
        const chart = KoreChart({ id: 'c1' });
        chart.$el = { querySelector: () => null, querySelectorAll: () => [] };
        chart.$refs = {};
        chart.init();

        expect(() => chart.onPointerMove({ clientX: 10 })).not.toThrow();
        expect(chart.hover).toBeNull();
    });
});

/**
 * El zoom.
 *
 * Todo el JavaScript del zoom es aritmética sobre porcentajes: ni una escala, ni una fecha, ni un
 * formato. El cliente manda DOS NÚMEROS y el servidor hace el resto — invierte el dominio, elige
 * los ticks nuevos, reescala el eje Y y devuelve el <path>.
 *
 * Un zoom en el cliente exigiría portar Ticks, Scales, Path y Format a JS y mantener dos
 * implementaciones de la geometría idénticas para siempre. Estos tests fijan que eso no pase.
 */
describe('zoom', function () {
    function makeZoom(view = [0, 100]) {
        const set = vi.fn();
        const chart = KoreChart({ id: 'c1', zoom: { model: 'ventana' } });

        chart.$el = {
            contains: () => false,
            querySelector: () => ({ textContent: JSON.stringify({ ...PAYLOAD, window: view }) }),
            querySelectorAll: () => [],
        };
        chart.$refs = { plot: { getBoundingClientRect: () => ({ left: 0, top: 0, width: 200, height: 100 }) } };
        chart.$wire = { $set: set };
        chart.$nextTick = (fn) => fn();
        chart.init();

        return { chart, set };
    }

    it('lee la ventana del payload, no del x-data', function () {
        // El morph reescribe el <script> del payload pero NO reinicializa el x-data. Una ventana
        // metida en el x-data se quedaría con la de ANTES del zoom.
        const { chart } = makeZoom([25, 75]);

        expect(chart.view).toEqual([25, 75]);
        expect(chart.zoomed).toBe(true);
    });

    it('compone un zoom sobre otro con una regla de tres', function () {
        // Arrastrar del 20 % al 60 % de una vista que ya enseña [40, 80] da [48, 64] del dominio
        // COMPLETO. Sin escalas, sin fechas, sin locales — por eso el cliente no necesita saber
        // qué hay debajo del eje.
        const { chart } = makeZoom([40, 80]);

        expect(chart._toFull(20)).toBeCloseTo(48);
        expect(chart._toFull(60)).toBeCloseTo(64);
    });

    it('el brush manda la ventana COMPUESTA, en % del dominio completo', function () {
        const { chart, set } = makeZoom([40, 80]);

        chart.onBrushDown({ button: 0, clientX: 40, pointerId: 1, currentTarget: {} });
        chart.onPointerMove({ clientX: 120 });
        chart.onDragEnd();

        // 40/200 = 20 % del área visible; 120/200 = 60 %. Sobre [40, 80] → [48, 64].
        expect(set).toHaveBeenCalledWith('ventana', [48, 64]);
    });

    it('un arrastre de menos de un 1 % es un clic, no un zoom', function () {
        const { chart, set } = makeZoom();

        chart.onBrushDown({ button: 0, clientX: 100, pointerId: 1, currentTarget: {} });
        chart.onPointerMove({ clientX: 101 });
        chart.onDragEnd();

        expect(set).not.toHaveBeenCalled();
    });

    it('redondea antes de mandar, o la URL se llena de ruido de coma flotante', function () {
        // La ventana va en el query string con #[Url]. Sin redondear, un píxel de arrastre produce
        // un 17.99999938120037 que acaba TAL CUAL en la barra de direcciones.
        const { chart, set } = makeZoom();

        chart.onBrushDown({ button: 0, clientX: 33.333333, pointerId: 1, currentTarget: {} });
        chart.onPointerMove({ clientX: 133.333333 });
        chart.onDragEnd();

        const [, ventana] = set.mock.calls[0];

        expect(ventana.every((n) => String(n).replace('-', '').split('.')[1]?.length <= 2 || Number.isInteger(n))).toBe(true);
    });

    it('amplía y reduce alrededor del centro', function () {
        const { chart, set } = makeZoom([20, 60]);

        chart.zoomBy(0.5);

        expect(set).toHaveBeenCalledWith('ventana', [30, 50]);
    });

    it('reducir hasta pasarse del dominio equivale a restablecer', function () {
        const { chart, set } = makeZoom([40, 60]);

        chart.zoomBy(20);

        expect(set).toHaveBeenCalledWith('ventana', null);
    });

    it('las flechas desplazan la ventana sin salirse del dominio', function () {
        const { chart, set } = makeZoom([80, 100]);

        chart.nudge(50);   // querría irse un 50 % de su ancho a la derecha, pero ya toca el borde

        expect(set).toHaveBeenCalledWith('ventana', [80, 100]);
    });

    it('sin zoom no hay a dónde desplazarse, y quedarse quieto es lo correcto', function () {
        const { chart } = makeZoom([0, 100]);

        expect(chart.zoomed).toBe(false);
    });
});
