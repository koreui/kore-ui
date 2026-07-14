import { beforeEach, describe, expect, it, vi } from 'vitest';

// Se mockea floating.js: lo que hay que probar no es el posicionamiento (que ya tiene sus
// tests) sino la lógica del gráfico — encontrar el punto bajo el ratón y ocultar series.
vi.mock('../../resources/js/utils/floating.js', () => ({
    startFloating: () => Object.assign(() => {}, { update: vi.fn() }),
    stopFloating: vi.fn(),
    virtualReference: () => ({ setPoint: vi.fn(), getBoundingClientRect: () => ({}) }),
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

    const chart = KoreChart({ id: 'c1' });

    chart.$el = {
        querySelector: () => ({ textContent: JSON.stringify(PAYLOAD) }),
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

    return { chart, nodes };
}

beforeEach(() => {
    globalThis.window = { Livewire: undefined };
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
        const { chart, nodes } = makeChart();

        chart.toggleSeries('c1-s2');
        delete nodes[1].attrs['data-hidden'];   // esto es lo que hace el morph

        chart.reapply();

        expect(nodes[1].attrs['data-hidden']).toBe('true');
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
