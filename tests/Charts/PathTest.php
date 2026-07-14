<?php

use KoreUi\Charts\Path;

describe('línea', function () {
    it('dibuja una polilínea', function () {
        $d = Path::line([[0, 100], [50, 50], [100, 0]]);

        expect($d)->toBe('M0 100 L50 50 L100 0');
    });

    it('redondea a 2 decimales: en un espacio 0-100 el tercero es invisible y cuesta kilobytes', function () {
        $d = Path::line([[0.123456, 99.987654], [100, 0]]);

        expect($d)->toBe('M0.12 99.99 L100 0');
    });

    it('no arrastra ceros a la derecha', function () {
        expect(Path::line([[10.0, 20.50], [30, 40]]))->toBe('M10 20.5 L30 40');
    });
});

describe('huecos', function () {
    // Un null NO es un cero: es "no hay dato". La diferencia entre dibujar una caída a cero
    // y cortar la línea es la diferencia entre mentir y no mentir.

    it('parte la línea en dos trazos', function () {
        $d = Path::line([[0, 10], [25, 20], null, [75, 30], [100, 40]]);

        expect($d)->toBe('M0 10 L25 20 M75 30 L100 40');
    });

    it('no dibuja una caída a cero', function () {
        $d = Path::line([[0, 10], null, [100, 40]]);

        expect($d)->not->toContain('L100 40 L');
        expect(substr_count($d, 'M'))->toBe(2);
    });

    it('trata INF y NAN como huecos, en vez de envenenar el path entero', function () {
        // Un solo NaN en el `d` hace que el navegador descarte el <path> COMPLETO, en
        // silencio. Mejor perder un punto que la serie.
        $d = Path::line([[0, 10], [50, NAN], [100, 40]]);

        expect($d)->not->toContain('NAN');
        expect($d)->not->toContain('nan');
        expect(substr_count($d, 'M'))->toBe(2);
    });

    it('dibuja un punto suelto entre dos huecos', function () {
        $d = Path::line([null, [50, 20], null]);

        expect($d)->toBe('M50 20 L50 20');
    });

    it('devuelve una cadena vacía si no hay ni un dato', function () {
        expect(Path::line([null, null]))->toBe('');
    });
});

describe('área', function () {
    it('cierra contra la línea base', function () {
        $d = Path::area([[0, 20], [100, 40]], baseline: 100);

        expect($d)->toBe('M0 20 L100 40 L100 100 L0 100 Z');
    });

    it('parte el área en sub-áreas cuando hay huecos', function () {
        $d = Path::area([[0, 20], [25, 30], null, [75, 40], [100, 50]], baseline: 100);

        expect(substr_count($d, 'Z'))->toBe(2);
    });

    it('no dibuja un área de un solo punto: no tiene superficie', function () {
        expect(Path::area([[50, 20]], baseline: 100))->toBe('');
    });
});

describe('curva monótona', function () {
    // La razón de ser de la monótona: una spline normal (cardinal, catmull-rom) inventa
    // extremos entre los puntos — dibuja un máximo donde no hay ningún dato. En un gráfico
    // de negocio eso no es un problema estético, es un problema de honestidad.

    it('emite Béziers cúbicas', function () {
        $d = Path::line([[0, 100], [25, 40], [50, 60], [75, 20], [100, 30]], Path::MONOTONE);

        expect($d)->toStartWith('M0 100 C');
        expect(substr_count($d, 'C'))->toBe(4);   // una por segmento
    });

    it('no se sale del rango de los puntos: no inventa máximos donde no los hay', function () {
        // Un pico seguido de una bajada. Una curva mal hecha se pasaría por encima del 20
        // (el máximo real) al llegar al pico.
        $points = [[0, 100], [25, 100], [50, 20], [75, 100], [100, 100]];
        $d = Path::line($points, Path::MONOTONE);

        // Se extraen todas las coordenadas Y del path (puntos de control incluidos).
        preg_match_all('/[\d.-]+ ([\d.-]+)/', $d, $m);
        $ys = array_map('floatval', $m[1]);

        // Ningún punto de control puede subir por encima del pico (y=20 es lo más alto en
        // pantalla, porque la Y va invertida) ni bajar del suelo.
        expect(min($ys))->toBeGreaterThanOrEqual(20.0);
        expect(max($ys))->toBeLessThanOrEqual(100.0);
    });

    it('con dos puntos es una recta: una curva de dos puntos no existe', function () {
        expect(Path::line([[0, 10], [100, 20]], Path::MONOTONE))->toBe('M0 10 L100 20');
    });

    it('reinicia las tangentes en cada hueco', function () {
        // Si no reiniciara, se inventaría una tangente para saltarse el hueco y dibujaría
        // una curva por encima de un vacío.
        $d = Path::line([[0, 10], [25, 20], [50, 15], null, [75, 30], [90, 35], [100, 40]], Path::MONOTONE);

        expect(substr_count($d, 'M'))->toBe(2);
    });
});

describe('escalonada', function () {
    it('mantiene el valor hasta el punto medio y luego salta', function () {
        $d = Path::line([[0, 10], [100, 20]], Path::STEP);

        expect($d)->toBe('M0 10 L50 10 L50 20 L100 20');
    });
});
