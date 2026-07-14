// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * El gráfico, montado con Alpine DE VERDAD.
 *
 * `tests/js/chart.test.js` prueba la lógica del componente pasándole un `$el` de mentira. Eso
 * está bien para la búsqueda binaria o el formato, pero es **estructuralmente incapaz** de ver
 * un bug entero: la fuga de scope de Alpine (la explica `alpine-scope.test.js`).
 *
 * El bug real, en la página de demo: cinco gráficos dentro del x-data del layout. Cada uno
 * hacía `this.payload = …` en `init()`, y como `payload` no estaba declarada en el objeto del
 * componente, Alpine escribía los cinco payloads **en el x-data del layout**. Ganaba el último.
 * Resultado: los cinco gráficos enseñaban en el tooltip los datos del quinto — sin un solo
 * error en consola.
 *
 * Por eso este test monta lo que rompía: varios gráficos ANIDADOS dentro de otro x-data.
 */

// jsdom no trae ResizeObserver, y autoUpdate() de floating-ui lo necesita.
globalThis.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
};

const payload = (id, name, labels) => JSON.stringify({
    xs: [10, 50, 90],
    labels: ['Ene', 'Feb', 'Mar'],
    series: [{ id: `${id}-s1`, name, slot: 1, labels }],
});

function chartHtml(id, name, labels) {
    return `
        <div data-kore-chart="${id}" x-data="KoreChart({ id: '${id}' })">
            <div class="kore-chart-plot" x-ref="plot">
                <div data-kore-serie="${id}-s1"></div>
                <script type="application/json" data-kore-chart-payload="${id}">${payload(id, name, labels)}</script>
                <div class="kore-chart-crosshair" x-ref="crosshair"></div>
                <div class="kore-chart-tooltip" x-ref="tooltip"></div>
            </div>
        </div>`;
}

let Alpine;

beforeEach(async () => {
    vi.resetModules();
    document.body.innerHTML = '';
    delete window.Alpine;   // si no, cada test avisa de que Alpine ya estaba arrancado

    Alpine = (await import('alpinejs')).default;
    const KoreChart = (await import('../../resources/js/ui/chart.js')).default;

    Alpine.data('KoreChart', KoreChart);

    // ⚠️ Lo que rompía: los gráficos NO son el x-data más externo. En una app de verdad
    // (un layout con `x-data="{ sidebarOpen: false }"`) nunca lo son.
    document.body.innerHTML = `
        <div id="layout" x-data="{ sidebarOpen: false }">
            ${chartHtml('kore-chart-1', 'Ingresos', ['1.240 €', '3.180 €', '2.470 €'])}
            ${chartHtml('kore-chart-2', 'Resultado', ['1.200 €', '-800 €', '400 €'])}
        </div>`;

    Alpine.start();
    await new Promise((resolve) => queueMicrotask(resolve));
});

const dataOf = (id) => Alpine.$data(document.querySelector(`[data-kore-chart="${id}"]`));

describe('varios gráficos en la misma página', () => {
    it('cada uno lee SU payload, no el del último que se inicializó', () => {
        expect(dataOf('kore-chart-1').payload.series[0].name).toBe('Ingresos');
        expect(dataOf('kore-chart-2').payload.series[0].name).toBe('Resultado');
    });

    it('no escriben nada en el x-data del layout', () => {
        // Si `payload`, `_root`, `_reference` o `_floating` aparecen aquí, la fuga ha vuelto.
        // Se lee el objeto de datos crudo, no el $data: el Proxy que fusiona los scopes no
        // expone descriptores, así que Object.keys() sobre él siempre devuelve [].
        const layout = document.getElementById('layout')._x_dataStack[0];

        expect(Object.keys(layout)).toEqual(['sidebarOpen']);
    });

    it('la raíz que guarda cada uno es la SUYA', () => {
        // De esto depende la leyenda: reapply() busca las series dentro de _root. Con la fuga,
        // el gráfico 1 ocultaba las series del gráfico 2.
        expect(dataOf('kore-chart-1')._root.dataset.koreChart).toBe('kore-chart-1');
        expect(dataOf('kore-chart-2')._root.dataset.koreChart).toBe('kore-chart-2');
    });

    it('ocultar una serie en uno no toca al otro', () => {
        dataOf('kore-chart-1').toggleSeries('kore-chart-1-s1');

        const serie = (id) => document.querySelector(`[data-kore-serie="${id}-s1"]`);

        expect(serie('kore-chart-1').getAttribute('data-hidden')).toBe('true');
        expect(serie('kore-chart-2').hasAttribute('data-hidden')).toBe(false);
    });
});
