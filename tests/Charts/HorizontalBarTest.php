<?php

use KoreUi\Charts\ChartFrame;
use KoreUi\Charts\Format;
use KoreUi\Charts\Marks\BarMark;
use KoreUi\Charts\Marks\LineMark;
use KoreUi\Charts\Plot;

/**
 * Barras horizontales: el MISMO `layoutBars()`, transpuesto. La geometría se calcula una vez; esto
 * comprueba que al girarla el valor va al ancho y la categoría al alto, y que grupos, pilas y punta
 * siguen funcionando igual que en vertical.
 */
$plot = function (array $data, callable $marks) {
    $frame = new ChartFrame('c1', $data, 'cat');
    $frame->orientation = 'horizontal';
    $marks($frame);

    return new Plot($frame, new Format);
};

// Tres categorías, valores crecientes. Dominio [0, 30] con el cero incluido (una barra lo exige).
$simple = [
    ['cat' => 'A', 'v' => 10],
    ['cat' => 'B', 'v' => 20],
    ['cat' => 'C', 'v' => 30],
];

describe('la transposición', function () use ($plot, $simple) {
    it('el valor va al ancho y la categoría al alto', function () use ($plot, $simple) {
        $bars = $plot($simple, fn ($f) => $f->add(new BarMark('v')))->series[0]['bars'];

        // C (=30, el máximo) llega al 100 % del ancho y arranca del cero (izquierda).
        expect($bars[2]['x'])->toBe(0.0);
        expect($bars[2]['w'])->toBe(100.0);

        // A (=10) es un tercio del ancho.
        expect($bars[0]['x'])->toBe(0.0);
        expect(round($bars[0]['w']))->toBe(33.0);
    });

    it('la primera categoría queda ARRIBA', function () use ($plot, $simple) {
        $bars = $plot($simple, fn ($f) => $f->add(new BarMark('v')))->series[0]['bars'];

        // A arriba (y pequeña), C abajo (y grande). Es el orden de lectura de una lista.
        expect($bars[0]['y'])->toBeLessThan($bars[2]['y']);
        // Y cada barra ocupa el grosor de su banda, no del área entera.
        expect($bars[0]['h'])->toBeGreaterThan(0.0);
        expect($bars[0]['h'])->toBeLessThan(50.0);
    });

    it('una barra negativa crece hacia la IZQUIERDA', function () use ($plot) {
        $bars = $plot([
            ['cat' => 'Sube', 'v' => 40],
            ['cat' => 'Baja', 'v' => -20],
        ], fn ($f) => $f->add(new BarMark('v')))->series[0]['bars'];

        // Con dominio [-20, 40], el cero cae en el 33,3 %. La barra negativa va del cero hacia la
        // izquierda: su borde derecho es el cero, su izquierdo el valor.
        expect($bars[1]['x'])->toBeLessThan($bars[0]['x']);
        expect(round($bars[1]['x'] + $bars[1]['w'], 1))->toBe(33.3);   // termina en el cero
        expect($bars[1]['negative'])->toBe(1);
    });
});

describe('grupos y pilas, transpuestos', function () use ($plot) {
    $matriz = [
        ['cat' => 'A', 'x' => 10, 'y' => 30],
        ['cat' => 'B', 'x' => 20, 'y' => 20],
    ];

    it('agrupadas: dos series se reparten el ALTO de la banda', function () use ($plot, $matriz) {
        $plot = $plot($matriz, function ($f) {
            $f->add(new BarMark('x'));
            $f->add(new BarMark('y'));
        });

        $s0 = $plot->series[0]['bars'];
        $s1 = $plot->series[1]['bars'];

        // Misma fila (A): comparten la línea base del valor (x=0) pero NO la posición vertical: una
        // arriba de la banda, otra debajo. En vertical esto era «se reparten el ancho».
        expect($s0[0]['x'])->toBe(0.0);
        expect($s1[0]['x'])->toBe(0.0);
        expect($s0[0]['y'])->not->toBe($s1[0]['y']);
        // Y la altura de cada una es la mitad de la banda (dos grupos).
        expect(round($s0[0]['h'], 1))->toBe(round($s1[0]['h'], 1));
    });

    it('apiladas: la segunda serie arranca donde acaba la primera, a lo ANCHO', function () use ($plot, $matriz) {
        $plot = $plot($matriz, function ($f) {
            $f->add(new BarMark('x', stack: 'g'));
            $f->add(new BarMark('y', stack: 'g'));
        });

        $s0 = $plot->series[0]['bars'];
        $s1 = $plot->series[1]['bars'];

        // A: x=10 apilado con y=30. La segunda barra empieza donde terminó la primera (a la
        // derecha), no en el cero. Y comparten la misma banda vertical.
        expect($s0[0]['x'])->toBe(0.0);
        expect(round($s1[0]['x'], 1))->toBe(round($s0[0]['x'] + $s0[0]['w'], 1));
        expect($s0[0]['y'])->toBe($s1[0]['y']);
    });
});

describe('los ejes', function () use ($plot, $simple) {
    it('el valor saca sus ticks tumbados en X; la categoría, uno por banda en Y', function () use ($plot, $simple) {
        $p = $plot($simple, fn ($f) => $f->add(new BarMark('v')));

        // Ticks del valor: en horizontal, con posición y ancho (para acotar la etiqueta).
        $vt = $p->valueTicks();
        expect($vt)->not->toBeEmpty();
        expect($vt[0])->toHaveKeys(['value', 'label', 'pos', 'width']);
        // El primero es el cero, a la izquierda; el último, el máximo, a la derecha.
        expect($vt[0]['pos'])->toBe(0.0);
        expect(end($vt)['pos'])->toBe(100.0);

        // Ticks de categoría: uno por fila, centrado en su banda.
        $bt = $p->bandTicks();
        expect($bt)->toHaveCount(3);
        expect($bt[0]['label'])->toBe('A');
        expect($bt[0]['pos'])->toBeLessThan($bt[2]['pos']);   // A arriba, C abajo
    });

    it('en vertical, valueTicks y bandTicks están vacíos: no son su eje', function () {
        $frame = new ChartFrame('c1', [['cat' => 'A', 'v' => 1]], 'cat');
        $frame->add(new BarMark('v'));
        $p = new Plot($frame, new Format);

        expect($p->valueTicks())->toBe([]);
        expect($p->bandTicks())->toBe([]);
        expect($p->horizontal)->toBeFalse();
    });

    it('el eje del valor pide MENOS ticks en horizontal, para que quepan tumbados', function () use ($plot) {
        // Con [0..1240] el dominio vertical daría pasos de 200 (ocho ticks «0…1.400», que en un móvil
        // estrecho se pisan). Horizontal lo niceea a 4 → [0,1500] con pasos de 500: cuatro que caben.
        $vt = $plot([
            ['cat' => 'A', 'v' => 1240],
            ['cat' => 'B', 'v' => 430],
        ], fn ($f) => $f->add(new BarMark('v')))->valueTicks();

        expect(count($vt))->toBeLessThanOrEqual(5);
        // Y cada uno lleva el hueco hasta el vecino, para no pisarse: el mismo recorte que el eje X.
        expect($vt[0])->toHaveKey('room');
        expect($vt[1]['room'])->toBeGreaterThan(0.0);
    });

    it('la canaleta de categorías se acota a 22ch por muy larga que sea la etiqueta', function () use ($plot) {
        $p = $plot([
            ['cat' => str_repeat('Departamento de administración ', 3), 'v' => 5],
        ], fn ($f) => $f->add(new BarMark('v')));

        expect($p->bandGutter())->toBe(22.0);
    });
});

describe('las reglas', function () {
    it('horizontal sólo transpone barras: una línea se rechaza', function () {
        $frame = new ChartFrame('c1', [['cat' => 'A', 'v' => 1, 'w' => 2]], 'cat');
        $frame->orientation = 'horizontal';
        $frame->add(new BarMark('v'));
        $frame->add(new LineMark('w'));

        $mensaje = null;

        try {
            new Plot($frame);
        } catch (InvalidArgumentException $e) {
            $mensaje = $e->getMessage();
        }

        expect($mensaje)->toContain('horizontal');
        expect($mensaje)->toContain('line');
    });
});

it('de punta a punta, desde el Blade', function () {
    $data = "[
        ['depto' => 'Ventas', 'total' => 40],
        ['depto' => 'Soporte', 'total' => 80],
        ['depto' => 'Ingeniería', 'total' => 60],
    ]";

    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="depto" orientation="horizontal">
            <x-kore::chart.bar y="total" />
        </x-kore::chart>
    BLADE)->__toString();

    expect($html)->toContain('data-kore-chart-orientation="horizontal"');
    expect($html)->toContain('kore-chart-bar');
    // La categoría, a la izquierda.
    expect($html)->toContain('Ingeniería');
    // Y sin Alpine: horizontal es barras y CSS, nada de x-data.
    expect($html)->not->toContain('KoreChart(');
});
