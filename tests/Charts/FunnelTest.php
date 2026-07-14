<?php

use KoreUi\Charts\ChartFrame;
use KoreUi\Charts\Format;
use KoreUi\Charts\Marks\FunnelMark;
use KoreUi\Charts\Marks\LineMark;
use KoreUi\Charts\Plot;

$funnel = function (array $data) {
    $frame = new ChartFrame('c1', $data, 'etapa');
    $frame->add(new FunnelMark('n'));

    return (new Plot($frame, new Format))->series[0]['funnel'];
};

// Un embudo de conversión: de 12.000 visitas a 620 compras.
$conversion = [
    ['etapa' => 'Visitas', 'n' => 12000],
    ['etapa' => 'Registros', 'n' => 4800],
    ['etapa' => 'Carrito', 'n' => 1500],
    ['etapa' => 'Compra', 'n' => 620],
];

describe('el trapecio se estrecha hasta la siguiente etapa', function () use ($funnel, $conversion) {
    it('el fondo de una etapa es el techo de la siguiente', function () use ($funnel, $conversion) {
        // Es lo que hace que el embudo sea una silueta continua y no unos escalones sueltos.
        // El polígono de Registros abajo (30..70) coincide con el de Visitas abajo (30..70).
        $st = $funnel($conversion);

        // Visitas: arriba 0..100, abajo 30..70 (Registros vale 4800/12000 = 40 % → medio ancho 20).
        expect($st[0]['clip'])->toBe('polygon(0% 0, 100% 0, 70% 100%, 30% 100%)');

        // Registros: arriba 30..70 (su propio 40 %), abajo el ancho de Carrito.
        expect($st[1]['clip'])->toStartWith('polygon(30% 0, 70% 0,');
    });

    it('la última etapa es un rectángulo: su ancho arriba y abajo', function () use ($funnel, $conversion) {
        $st = $funnel($conversion);
        $ultima = end($st);

        // Compra: 620/12000 = 5,17 % → medio ancho 2,58. Arriba y abajo iguales.
        expect($ultima['clip'])->toContain('47.42% 0, 52.58% 0, 52.58% 100%, 47.42% 100%');
    });
});

describe('los números', function () use ($funnel, $conversion) {
    it('la conversión es cuánto queda del primero', function () use ($funnel, $conversion) {
        expect(array_column($funnel($conversion), 'percent'))->toBe([100.0, 40.0, 12.5, 5.2]);
    });

    it('la caída es cuánto se pierde en ESTE paso, no desde el primero', function () use ($funnel, $conversion) {
        // De 12.000 a 4.800 se pierde el 60 %. De 4.800 a 1.500, el 68,8 % — no el 87,5 % (que
        // sería la caída acumulada). Es la conversión paso a paso, que es lo que se acciona.
        $drops = array_column($funnel($conversion), 'drop');

        expect($drops[0])->toBeNull();          // la primera no cae de nada
        expect($drops[1])->toBe(60.0);
        expect($drops[2])->toBe(68.8);
        expect($drops[3])->toBe(58.7);
    });
});

describe('el color sale de la rampa ORDINAL', function () use ($funnel, $conversion) {
    it('no de la categórica: las etapas van en orden', function () use ($funnel, $conversion) {
        // La categórica dice «estas cosas son distintas»; la ordinal, «van en esta secuencia».
        // Un embudo con la categórica codifica mal.
        $colors = array_column($funnel($conversion), 'color');

        expect($colors[0])->toContain('--kore-ord-');
        expect($colors[0])->not->toContain('--kore-chart-');
    });

    it('se reparte entre las etapas que haya: la primera clara, la última oscura', function () use ($funnel, $conversion) {
        // Cuatro etapas → ord-1, ord-3, ord-5, ord-7. El degradado va con la secuencia.
        expect(array_column($funnel($conversion), 'color'))
            ->toBe(['var(--kore-ord-1)', 'var(--kore-ord-3)', 'var(--kore-ord-5)', 'var(--kore-ord-7)']);
    });

    it('con dos etapas, de la primera a la última de la rampa', function () use ($funnel) {
        $st = $funnel([['etapa' => 'A', 'n' => 100], ['etapa' => 'B', 'n' => 30]]);

        expect(array_column($st, 'color'))->toBe(['var(--kore-ord-1)', 'var(--kore-ord-7)']);
    });
});

it('el orden de las filas ES el orden del embudo, no se reordena', function () use ($funnel) {
    // A diferencia de un eje temporal, un embudo NO ordena por valor: la secuencia la pones tú.
    $st = $funnel([
        ['etapa' => 'Primera', 'n' => 100],
        ['etapa' => 'Segunda', 'n' => 200],   // sube, y está bien: es el orden que diste
        ['etapa' => 'Tercera', 'n' => 50],
    ]);

    expect(array_column($st, 'label'))->toBe(['Primera', 'Segunda', 'Tercera']);
});

it('un embudo no comparte gráfico con otras marcas', function () {
    $frame = new ChartFrame('c1', [['etapa' => 'A', 'n' => 1, 'v' => 2]], 'etapa');
    $frame->add(new FunnelMark('n'));
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
    $data = "[['etapa' => 'Visitas', 'n' => 1000], ['etapa' => 'Compra', 'n' => 250]]";

    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="etapa">
            <x-kore::chart.funnel y="n" />
        </x-kore::chart>
    BLADE)->__toString();

    expect($html)->toContain('kore-chart-funnel-stage');
    expect($html)->toContain('clip-path: polygon');
    expect($html)->toContain('var(--kore-ord-1)');
    expect($html)->toContain('−75 %');   // de 1000 a 250 se pierde el 75 %
});
