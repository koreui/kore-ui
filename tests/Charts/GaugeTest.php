<?php

use KoreUi\Charts\Arc;
use KoreUi\Charts\ChartFrame;
use KoreUi\Charts\Format;
use KoreUi\Charts\Marks\GaugeMark;
use KoreUi\Charts\Marks\LineMark;
use KoreUi\Charts\Plot;

$gauge = function (float $value, array $thresholds = [], array $opts = []) {
    $frame = new ChartFrame('c1', [['v' => $value]], null);
    $mark = (new GaugeMark('v'))->withThresholds($thresholds);

    if (isset($opts['min'], $opts['max'])) {
        $mark->withRange($opts['min'], $opts['max']);
    }
    if (isset($opts['sweep'])) {
        $mark->withSweep($opts['sweep']);
    }

    $frame->add($mark);

    return (new Plot($frame, new Format))->series[0]['gauge'];
};

describe('el color lo pone la banda', function () use ($gauge) {
    $bandas = [60 => 'success', 85 => 'warning', 100 => 'destructive'];

    it('un valor bajo va en la primera banda', function () use ($gauge, $bandas) {
        expect($gauge(42, $bandas)['color'])->toBe('var(--kore-success)');
    });

    it('un valor medio, en la del medio', function () use ($gauge, $bandas) {
        expect($gauge(73, $bandas)['color'])->toBe('var(--kore-warning)');
    });

    it('un valor alto, en la última', function () use ($gauge, $bandas) {
        expect($gauge(94, $bandas)['color'])->toBe('var(--kore-destructive)');
    });

    it('un valor que se sale por arriba se queda en la última banda', function () use ($gauge, $bandas) {
        expect($gauge(120, $bandas)['color'])->toBe('var(--kore-destructive)');
    });

    it('sin bandas, el color de la paleta — y es decorativo', function () use ($gauge) {
        // Sin rangos, un gauge es un stat tile con un anillo. No se prohíbe, pero no es un gauge.
        expect($gauge(68)['color'])->toBe('var(--kore-chart-1)');
    });
});

describe('la fracción y el arco', function () use ($gauge) {
    it('el arco llega hasta la fracción del rango', function () use ($gauge) {
        expect($gauge(50)['fraction'])->toBe(50.0);
        expect($gauge(0)['fraction'])->toBe(0.0);
        expect($gauge(100)['fraction'])->toBe(100.0);
    });

    it('respeta un dominio propio', function () use ($gauge) {
        // Un SLA de 99,2 sobre [98, 100] está al 60 % del arco.
        expect($gauge(99.2, [], ['min' => 98, 'max' => 100])['fraction'])->toBe(60.0);
    });

    it('un valor en el mínimo no dibuja arco', function () use ($gauge) {
        expect($gauge(0)['arc'])->toBe('');
    });

    it('recorta un valor que se sale del rango', function () use ($gauge) {
        expect($gauge(150)['fraction'])->toBe(100.0);
        expect($gauge(-50)['fraction'])->toBe(0.0);
    });
});

describe('el número', function () use ($gauge) {
    it('no pierde los decimales — un SLA de 99,2 no es «99»', function () use ($gauge) {
        expect($gauge(99.2, [], ['min' => 98, 'max' => 100])['formatted'])->toBe('99,2');
    });

    it('un entero sale sin decimales', function () use ($gauge) {
        expect($gauge(73)['formatted'])->toBe('73');
    });
});

describe('los pellizcos de banda', function () use ($gauge) {
    it('hay uno por cada frontera interior', function () use ($gauge) {
        // Tres bandas → dos fronteras (60 y 85). La cota final (100) es el borde del arco, no una
        // frontera, así que no se marca.
        expect($gauge(50, [60 => 'success', 85 => 'warning', 100 => 'destructive'])['ticks'])->toHaveCount(2);
    });

    it('sin bandas, ni un pellizco', function () use ($gauge) {
        expect($gauge(50)['ticks'])->toBe([]);
    });
});

it('el semicírculo es un arco más corto que el velocímetro', function () use ($gauge) {
    // Los dos llenan al 50 %, pero el de 180° recorre menos ángulo, así que su path es más corto.
    $velocimetro = $gauge(50, [], ['sweep' => 270]);
    $semicirculo = $gauge(50, [], ['sweep' => 180]);

    expect(strlen($semicirculo['arc']))->toBeLessThan(strlen($velocimetro['arc']));
});

it('un gauge no comparte gráfico con otras marcas', function () {
    $frame = new ChartFrame('c1', [['v' => 1, 'w' => 2]], null);
    $frame->add(new GaugeMark('v'));
    $frame->add(new LineMark('w'));

    $mensaje = null;

    try {
        new Plot($frame);
    } catch (InvalidArgumentException $e) {
        $mensaje = $e->getMessage();
    }

    expect($mensaje)->toContain('no comparte gráfico');
});

it('Arc::open traza un arco abierto, sin cerrarlo', function () {
    // Un gauge es una línea curva para TRAZAR, no un anillo relleno: no lleva el `L…Z` del donut.
    $path = Arc::open(-M_PI / 2, 0.0, 40.0);

    expect($path)->toStartWith('M')->toContain('A');
    expect($path)->not->toContain('Z');
});

it('de punta a punta, desde el Blade', function () {
    $html = $this->blade(<<<'BLADE'
        <x-kore::chart :data="[['uso' => 88]]">
            <x-kore::chart.gauge y="uso" :thresholds="[60 => 'success', 85 => 'warning', 100 => 'destructive']" caption="CPU" />
        </x-kore::chart>
    BLADE)->__toString();

    expect($html)->toContain('kore-chart-gauge');
    expect($html)->toContain('--kore-gauge: var(--kore-destructive)');   // 88 > 85 → banda roja
    expect($html)->toContain('kore-chart-gauge-value');
    expect($html)->toContain('>CPU<');
});
