<?php

use KoreUi\Charts\ChartFrame;
use KoreUi\Charts\Format;
use KoreUi\Charts\Marks\HeatmapMark;
use KoreUi\Charts\Marks\LineMark;
use KoreUi\Charts\Plot;

$heatmap = function (array $data, int $buckets = 5) {
    $frame = new ChartFrame('c1', $data, 'col');
    $frame->add((new HeatmapMark('v'))->withRow('fila')->withBuckets($buckets));

    return (new Plot($frame, new Format))->series[0]['heatmap'];
};

// Una matriz 2×3 con valores conocidos.
$matriz = [
    ['fila' => 'A', 'col' => 'X', 'v' => 0],
    ['fila' => 'A', 'col' => 'Y', 'v' => 50],
    ['fila' => 'A', 'col' => 'Z', 'v' => 100],
    ['fila' => 'B', 'col' => 'X', 'v' => 25],
    ['fila' => 'B', 'col' => 'Y', 'v' => 75],
    ['fila' => 'B', 'col' => 'Z', 'v' => 100],
];

describe('la matriz', function () use ($heatmap, $matriz) {
    it('saca columnas y filas únicas del formato largo', function () use ($heatmap, $matriz) {
        $hm = $heatmap($matriz);

        expect($hm['cols'])->toBe(['X', 'Y', 'Z']);
        expect($hm['rows'])->toBe(['A', 'B']);
        expect($hm['cells'])->toHaveCount(6);
    });

    it('coloca cada celda en su cruce', function () use ($heatmap, $matriz) {
        // 3 columnas → 33,3 % cada una; 2 filas → 50 % cada una.
        $cells = $heatmap($matriz)['cells'];

        // A×X: fila 0, col 0.
        expect($cells[0]['x'])->toBe(0.0);
        expect($cells[0]['y'])->toBe(0.0);
        expect(round($cells[0]['w'], 1))->toBe(33.3);
        expect($cells[0]['h'])->toBe(50.0);

        // B×Z: fila 1 (y=50), col 2 (x≈66,7).
        expect(round($cells[5]['x'], 1))->toBe(66.7);
        expect($cells[5]['y'])->toBe(50.0);
    });
});

describe('el color se cuantiza', function () use ($heatmap, $matriz) {
    it('el mínimo al escalón más claro, el máximo al más oscuro', function () use ($heatmap, $matriz) {
        $cells = $heatmap($matriz)['cells'];

        expect($cells[0]['bucket'])->toBe(1);   // 0 → el más claro
        expect($cells[2]['bucket'])->toBe(7);   // 100 → el más oscuro (los buckets se estiran a 7)
    });

    it('reparte los buckets sobre los siete tonos', function () use ($heatmap, $matriz) {
        // Con 5 buckets, el 50 % cae en el del medio, que estirado a 7 tonos es el 4.
        expect($heatmap($matriz, 5)['cells'][1]['bucket'])->toBe(4);
    });

    it('un hueco de verdad NO es un cero: se queda sin color', function () {
        // Si para un cruce no hay dato, la celda no existe. Y si el valor es null, no lleva bucket:
        // se ve el fondo de la rejilla. Pintarlo del tono más claro diría «poco», y es «nada».
        $frame = new ChartFrame('c', [
            ['fila' => 'A', 'col' => 'X', 'v' => 10],
            ['fila' => 'A', 'col' => 'Y', 'v' => null],
        ], 'col');
        $frame->add((new HeatmapMark('v'))->withRow('fila'));

        $cells = (new Plot($frame, new Format))->series[0]['heatmap']['cells'];

        expect($cells[1]['bucket'])->toBeNull();
    });
});

describe('las etiquetas de columna se adelgazan', function () use ($heatmap, $matriz) {
    it('24 columnas no se pintan las 24: se muestra una de cada dos', function () use ($heatmap) {
        $data = [];
        for ($h = 0; $h < 24; $h++) {
            $data[] = ['fila' => 'L', 'col' => str_pad((string) $h, 2, '0', STR_PAD_LEFT), 'v' => $h];
        }

        $ticks = $heatmap($data)['colTicks'];

        // 24 / ceil(24/13)=2 → 12 etiquetas, sin forzar la última (00, 02, … 22).
        expect($ticks)->toHaveCount(12);
        expect($ticks[0]['label'])->toBe('00');
        expect($ticks[1]['label'])->toBe('02');

        // Y cada una lleva el hueco hasta la siguiente MOSTRADA, para no pisarse.
        expect($ticks[0]['room'])->toBeGreaterThan(0.0);
    });

    it('pocas columnas se muestran todas', function () use ($heatmap, $matriz) {
        expect($heatmap($matriz)['colTicks'])->toHaveCount(3);
    });
});

it('un heatmap no comparte gráfico con otras marcas', function () {
    $frame = new ChartFrame('c1', [['fila' => 'A', 'col' => 'X', 'v' => 1, 'w' => 2]], 'col');
    $frame->add((new HeatmapMark('v'))->withRow('fila'));
    $frame->add(new LineMark('w'));

    $mensaje = null;

    try {
        new Plot($frame);
    } catch (InvalidArgumentException $e) {
        $mensaje = $e->getMessage();
    }

    expect($mensaje)->toContain('no comparte gráfico');
});

it('de punta a punta, desde el Blade — y el hover va por delegación', function () {
    $data = "[
        ['dia' => 'Lun', 'hora' => '09', 'n' => 40],
        ['dia' => 'Lun', 'hora' => '10', 'n' => 80],
        ['dia' => 'Mar', 'hora' => '09', 'n' => 20],
        ['dia' => 'Mar', 'hora' => '10', 'n' => 60],
    ]";

    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="hora">
            <x-kore::chart.heatmap y="n" row="dia" :buckets="4" />
        </x-kore::chart>
    BLADE)->__toString();

    expect($html)->toContain('kore-chart-heatmap-cell');
    expect($html)->toContain('data-bucket');
    // Un SOLO pointermove en la rejilla, no uno por celda: la delegación es lo que hace viable un
    // heatmap de miles de celdas.
    expect($html)->toContain('onHeatmapMove($event)');
    expect(substr_count($html, 'onHeatmapMove'))->toBe(1);
    // Y las celdas llevan su data-* para que el manejador las lea.
    expect($html)->toContain('data-heat-cell');
    expect($html)->toContain('data-r="Lun"');
});
