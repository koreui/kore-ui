<?php

use KoreUi\Charts\ChartFrame;
use KoreUi\Charts\Format;
use KoreUi\Charts\Marks\AreaMark;
use KoreUi\Charts\Path;
use KoreUi\Charts\Plot;

/**
 * Área apilada: cada banda se apoya en la suma de las de debajo, no en el cero. Es el mismo
 * mecanismo de `stack` que las barras, extendido al área — la línea base deja de ser plana y pasa a
 * ser la curva acumulada.
 */
$plot = function (array $data, ?string $stack = 's') {
    $frame = new ChartFrame('c1', $data, 'x');
    $frame->add(new AreaMark('a', stack: $stack));
    $frame->add(new AreaMark('b', stack: $stack));

    return new Plot($frame, new Format);
};

// Dos series. Apiladas, la cima de cada fila es la SUMA: 40 y 60.
$datos = [
    ['x' => 'Ene', 'a' => 10, 'b' => 30],
    ['x' => 'Feb', 'a' => 20, 'b' => 40],
];

describe('el apilado', function () use ($plot, $datos) {
    it('el eje llega a la CIMA de la pila, no al mayor sumando', function () use ($plot, $datos) {
        // El mayor valor suelto es 40 (b). Pero la pila llega a 60 (20+40): el eje debe alcanzarlo.
        expect($plot($datos)->domain->max)->toBeGreaterThanOrEqual(60.0);
    });

    it('la banda de abajo se apoya en el cero; la de arriba, en la de abajo', function () use ($plot, $datos) {
        $p = $plot($datos);

        // El borde de arriba de cada área son sus `points` (donde se ve la banda).
        $abajo = $p->series[0]['points'];   // a (primera declarada = abajo)
        $arriba = $p->series[1]['points'];   // b (encima)

        // Con dominio [0,60] e Y invertida: 0→100, 10→83,33, 40→33,33.
        // 'a' en Ene llega a 10 → y 83,33.
        expect($abajo[0][1])->toBe(83.33);
        // 'b' en Ene apila 30 SOBRE 10 → cima 40 → y 33,33. NO 30 desde el cero (que sería 50).
        expect($arriba[0][1])->toBe(33.33);
    });

    it('marca la serie como apilada, para que el CSS le suba la opacidad', function () use ($plot, $datos) {
        $p = $plot($datos);

        expect($p->series[0]['stacked'])->toBeTrue();
        expect($p->series[1]['stacked'])->toBeTrue();
        // Y el fill es una banda cerrada (Z), no una línea.
        expect($p->series[1]['area'])->toContain('Z');
    });

    it('sin stack, cada área sigue dibujándose desde el cero (superpuestas)', function () {
        $frame = new ChartFrame('c1', [['x' => 'Ene', 'a' => 10, 'b' => 30]], 'x');
        $frame->add(new AreaMark('a'));
        $frame->add(new AreaMark('b'));
        $p = new Plot($frame, new Format);

        expect($p->series[0]['stacked'])->toBeFalse();
        expect($p->series[1]['stacked'])->toBeFalse();
        // 'b' vale 30; superpuesta desde el cero, su cima es 30 (no 40). Dominio [0,30]: y 0.
        expect($p->series[1]['points'][0][1])->toBe(0.0);
    });
});

describe('Path::areaBetween', function () {
    it('cierra la banda: borde de arriba hacia delante, borde de abajo del revés', function () {
        $d = Path::areaBetween(
            [[0.0, 0.0], [100.0, 0.0]],      // arriba
            [[0.0, 100.0], [100.0, 100.0]],  // abajo
        );

        // Arriba (M0 0 → L100 0), baja al borde de abajo (L100 100), lo recorre del revés
        // (L0 100) y cierra (Z).
        expect($d)->toBe('M0 0 L100 0 L100 100 L0 100 Z');
    });

    it('un hueco en cualquiera de los dos bordes parte la banda', function () {
        $d = Path::areaBetween(
            [[0.0, 10.0], null, [20.0, 10.0], [30.0, 10.0]],
            [[0.0, 90.0], [10.0, 90.0], [20.0, 90.0], [30.0, 90.0]],
        );

        // El null de arriba corta: la primera banda es de un solo punto (no dibuja), la segunda va
        // de x=20 a x=30. Debe haber UN solo tramo cerrado.
        expect(substr_count($d, 'Z'))->toBe(1);
        expect($d)->toContain('M20 10');
    });
});

it('de punta a punta, desde el Blade', function () {
    $data = "[
        ['mes' => 'Ene', 'organico' => 40, 'pago' => 20],
        ['mes' => 'Feb', 'organico' => 55, 'pago' => 30],
        ['mes' => 'Mar', 'organico' => 50, 'pago' => 45],
    ]";

    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="mes">
            <x-kore::chart.area y="organico" label="Orgánico" stack="trafico" />
            <x-kore::chart.area y="pago" label="De pago" stack="trafico" />
        </x-kore::chart>
    BLADE)->__toString();

    // Las dos bandas llevan la marca de apilado, para la opacidad del CSS.
    expect(substr_count($html, 'data-kore-stacked="true"'))->toBe(2);
    // Y son bandas cerradas.
    expect($html)->toContain('kore-chart-area');
});
