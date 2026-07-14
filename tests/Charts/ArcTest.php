<?php

use KoreUi\Charts\Arc;

describe('donut', function () {
    it('reparte las porciones en proporción al valor', function () {
        $slices = Arc::slices([25, 25, 50]);

        expect($slices)->toHaveCount(3);
        expect($slices[0]['fraction'])->toBe(0.25);
        expect($slices[2]['fraction'])->toBe(0.5);
    });

    it('empieza a las 12 en punto y va en el sentido del reloj', function () {
        $slices = Arc::slices([50, 50], padAngle: 0);

        expect($slices[0]['start'])->toBe(-M_PI / 2);
        expect($slices[0]['end'])->toEqualWithDelta(M_PI / 2, 1e-9);
    });

    it('deja el agujero del donut', function () {
        $slices = Arc::slices([100], innerRatio: 0.6);

        // Dos arcos (el de fuera y el de dentro) → dos comandos A por medio anillo.
        expect(substr_count($slices[0]['path'], 'A'))->toBeGreaterThanOrEqual(2);
    });
});

describe('la trampa del 100 %', function () {
    // Un arco SVG de 360° es degenerado: el punto inicial coincide con el final y el
    // navegador NO PINTA NADA. Es el bug que tiene medio internet, y sale justo en el caso
    // más probable de una demo: una sola categoría.

    it('parte la vuelta completa en dos arcos, o no se pintaría nada', function () {
        $slices = Arc::slices([100]);

        expect($slices)->toHaveCount(1);

        // Dos sub-paths: dos medias vueltas.
        expect(substr_count($slices[0]['path'], 'M'))->toBe(2);
        expect($slices[0]['fraction'])->toBe(1.0);
    });

    it('lo mismo cuando las demás porciones valen cero', function () {
        $slices = Arc::slices([100, 0, 0]);

        expect($slices)->toHaveCount(1);
        expect(substr_count($slices[0]['path'], 'M'))->toBe(2);
    });
});

describe('casos degenerados', function () {
    it('no devuelve nada si no hay valores', function () {
        expect(Arc::slices([]))->toBe([]);
    });

    it('no devuelve nada si todo suma cero', function () {
        expect(Arc::slices([0, 0]))->toBe([]);
    });

    it('ignora negativos, INF y NAN: una porción no puede medir menos que nada', function () {
        $slices = Arc::slices([50, -20, NAN, INF, 50]);

        expect($slices)->toHaveCount(2);
        expect($slices[0]['fraction'])->toBe(0.5);
    });

    it('nunca produce coordenadas no finitas', function () {
        foreach (Arc::slices([1, 2, 3], padAngle: 4) as $slice) {
            expect($slice['path'])->not->toContain('NAN');
            expect($slice['path'])->not->toContain('INF');
        }
    });
});

describe('separación entre porciones', function () {
    it('se la quita a la porción, no se la suma al círculo', function () {
        // Si el hueco se sumara, los ángulos pasarían de 360° y la última porción se
        // solaparía con la primera.
        $slices = Arc::slices([50, 50], padAngle: 4);

        $total = 0.0;
        foreach ($slices as $slice) {
            $total += $slice['end'] - $slice['start'];
        }

        expect($total)->toBeLessThan(2 * M_PI);
    });

    it('no separa una porción de sí misma', function () {
        $slices = Arc::slices([100], padAngle: 10);

        expect($slices[0]['end'] - $slices[0]['start'])->toEqualWithDelta(2 * M_PI, 1e-9);
    });
});
