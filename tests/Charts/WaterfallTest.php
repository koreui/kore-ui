<?php

use KoreUi\Charts\ChartFrame;
use KoreUi\Charts\Format;
use KoreUi\Charts\Marks\LineMark;
use KoreUi\Charts\Marks\WaterfallMark;
use KoreUi\Charts\Plot;

/**
 * Una cascada es un apilado de una sola serie con la base moviéndose por fila. La geometría de la
 * barra flotante ya estaba escrita en `layoutBars()`; esto comprueba que flota bien.
 */
$plot = function (array $data, ?string $total = 'total', bool $connectors = true) {
    $frame = new ChartFrame('c1', $data, 'etapa');
    $frame->add(
        (new WaterfallMark('delta'))->withTotal($total)->withConnectors($connectors)
    );

    return new Plot($frame, new Format);
};

// El puente de un P&L: un saldo de apertura, dos subidas, dos bajadas y el resultado.
$puente = [
    ['etapa' => 'Inicial', 'delta' => 1000, 'total' => true],
    ['etapa' => 'Ventas', 'delta' => 500, 'total' => false],
    ['etapa' => 'Costes', 'delta' => -300, 'total' => false],
    ['etapa' => 'Impuestos', 'delta' => -120, 'total' => false],
    ['etapa' => 'Final', 'delta' => 0, 'total' => true],   // vacío: se calcula solo
];

describe('la barra flota', function () use ($plot, $puente) {
    it('cada barra empieza donde acabó la anterior', function () use ($plot, $puente) {
        $bars = $plot($puente)->series[0]['bars'];

        // Ventas (+500) sube DE 1000 A 1500. Su borde inferior está donde acabó el total inicial.
        // En porcentajes (dominio [0, 2000], Y invertida): 1500 → 25 %, 1000 → 50 %.
        expect($bars[1]['y'])->toBe(25.0);              // borde superior = 1500
        expect($bars[1]['y'] + $bars[1]['h'])->toBe(50.0);   // borde inferior = 1000
    });

    it('una bajada crece hacia abajo desde donde estaba', function () use ($plot, $puente) {
        $bars = $plot($puente)->series[0]['bars'];

        // Costes (−300): de 1500 a 1200. Superior 1500 → 25 %, inferior 1200 → 40 %.
        expect($bars[2]['variant'])->toBe('down');
        expect($bars[2]['y'])->toBe(25.0);
        expect(round($bars[2]['y'] + $bars[2]['h'], 1))->toBe(40.0);
    });
});

describe('los totales', function () use ($plot, $puente) {
    it('el primero usa su propio valor, y no queda clavado en cero', function () use ($plot, $puente) {
        // Éste es el fallo que se vio mirándolo: un total va del cero al acumulado, pero al
        // principio el acumulado ES cero. La regla de Excel lo arregla — si traes el valor, se usa.
        $bars = $plot($puente)->series[0]['bars'];

        expect($bars[0]['variant'])->toBe('total');
        expect($bars[0]['value'])->toBe(1000.0);
        expect($bars[0]['y'] + $bars[0]['h'])->toBe(100.0);   // arranca en el cero (abajo del todo)
    });

    it('el último se calcula solo cuando lo dejas vacío', function () use ($plot, $puente) {
        // 1000 + 500 − 300 − 120 = 1080. No hay que repetir la suma en el dato.
        $bars = $plot($puente)->series[0]['bars'];

        expect($bars[4]['variant'])->toBe('total');
        expect($bars[4]['value'])->toBe(1080.0);
    });

    it('la etiqueta de un total es el acumulado, no su salto', function () use ($plot, $puente) {
        expect($plot($puente)->series[0]['labels'])->toBe(['1.000', '500', '-300', '-120', '1.080']);
    });
});

describe('el color codifica polaridad', function () use ($plot, $puente) {
    it('sube verde, baja rojo, total neutro', function () use ($plot, $puente) {
        expect(array_column($plot($puente)->series[0]['bars'], 'variant'))
            ->toBe(['total', 'up', 'down', 'down', 'total']);
    });
});

describe('el dominio', function () use ($plot, $puente) {
    it('abarca la suma corrida, no los saltos sueltos', function () use ($plot, $puente) {
        // El pico del acumulado es 1500 (tras Ventas), no el salto mayor (1000). Si el dominio
        // saliera de los saltos, la barra se saldría por arriba. Es el mismo problema que un apilado.
        expect($plot($puente)->domain->max)->toBeGreaterThanOrEqual(1500.0);
    });
});

describe('los conectores', function () use ($plot, $puente) {
    it('hay uno menos que barras, al nivel donde se tocan', function () use ($plot, $puente) {
        $p = $plot($puente);
        $conn = $p->series[0]['connectors'];

        expect($conn)->toHaveCount(4);   // 5 barras, 4 enlaces

        // El primer conector está al nivel del acumulado tras la barra inicial (1000 → 50 %).
        expect($conn[0]['y'])->toBe(50.0);
    });

    it('se pueden apagar', function () use ($plot, $puente) {
        expect($plot($puente, connectors: false)->series[0]['connectors_on'])->toBeFalse();
    });
});

describe('sin totales, la cascada es puramente relativa', function () {
    it('cada barra flota sobre la anterior desde el cero', function () {
        $frame = new ChartFrame('c1', [
            ['etapa' => 'A', 'delta' => 100],
            ['etapa' => 'B', 'delta' => 50],
            ['etapa' => 'C', 'delta' => -30],
        ], 'etapa');
        $frame->add(new WaterfallMark('delta'));   // total = null

        $bars = (new Plot($frame, new Format))->series[0]['bars'];

        expect(array_column($bars, 'variant'))->toBe(['up', 'up', 'down']);
    });
});

it('una cascada no comparte gráfico con otras marcas', function () {
    $frame = new ChartFrame('c1', [['etapa' => 'A', 'delta' => 1, 'v' => 2]], 'etapa');
    $frame->add(new WaterfallMark('delta'));
    $frame->add(new LineMark('v'));

    $mensaje = null;

    try {
        new Plot($frame);
    } catch (InvalidArgumentException $e) {
        $mensaje = $e->getMessage();
    }

    expect($mensaje)->toContain('no comparte gráfico');
});

it('de punta a punta, desde el Blade', function () {
    $data = "[
        ['etapa' => 'Inicial', 'delta' => 1000, 'total' => true],
        ['etapa' => 'Ventas', 'delta' => 500, 'total' => false],
        ['etapa' => 'Final', 'delta' => 0, 'total' => true],
    ]";

    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="etapa">
            <x-kore::chart.waterfall y="delta" total="total" />
            <x-kore::chart.axis-y :ticks="4" />
        </x-kore::chart>
    BLADE)->__toString();

    expect($html)->toContain('data-variant="total"');
    expect($html)->toContain('data-variant="up"');
    expect($html)->toContain('kore-chart-connector');
});
