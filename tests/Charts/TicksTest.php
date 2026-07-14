<?php

use KoreUi\Charts\Ticks;

/**
 * El test que de verdad importa: PARIDAD CON d3.
 *
 * Las fixtures no están escritas a mano — se generaron ejecutando d3-array@3.2.4 sobre una
 * batería de casos elegidos para pillar donde los algoritmos ingenuos se rompen: decimales,
 * dominios diminutos, negativos, dominios invertidos y rangos enormes. Si esto pasa, el eje
 * de koreUi dice lo mismo que el de la librería de referencia del sector.
 */
dataset('d3', function () {
    $cases = json_decode(file_get_contents(__DIR__.'/Fixtures/d3-ticks.json'), true);

    foreach ($cases as $case) {
        yield "[{$case['start']}, {$case['stop']}] × {$case['count']}" => [$case];
    }
});

it('produce exactamente los mismos ticks que d3', function (array $case) {
    $mine = Ticks::ticks((float) $case['start'], (float) $case['stop'], (int) $case['count']);

    expect(count($mine))->toBe(count($case['ticks']));

    foreach ($case['ticks'] as $i => $expected) {
        expect($mine[$i])->toEqualWithDelta((float) $expected, 1e-9);
    }
})->with('d3');

it('produce exactamente el mismo paso que d3', function (array $case) {
    expect(Ticks::step((float) $case['start'], (float) $case['stop'], (int) $case['count']))
        ->toEqualWithDelta((float) $case['step'], 1e-9);
})->with('d3');

it('redondea el dominio igual que el .nice() de d3', function (array $case) {
    [$min, $max] = Ticks::nice((float) $case['start'], (float) $case['stop'], (int) $case['count']);

    expect($min)->toEqualWithDelta((float) $case['nice'][0], 1e-9);
    expect($max)->toEqualWithDelta((float) $case['nice'][1], 1e-9);
})->with('d3');

it('no arrastra el error de coma flotante en los decimales', function () {
    // El caso canónico: 0 + 3 × 0.1 da 0.30000000000000004. El truco del inverso entero
    // (3/10) lo evita. Si alguien "simplifica" spec() y quita esa rama, esto salta.
    $ticks = Ticks::ticks(0, 0.3, 3);

    expect($ticks)->toBe([0.0, 0.1, 0.2, 0.3]);
});

describe('casos degenerados', function () {
    it('devuelve un único tick cuando el dominio es un punto', function () {
        expect(Ticks::ticks(5, 5, 5))->toBe([5.0]);
    });

    it('no devuelve nada si no se piden ticks', function () {
        expect(Ticks::ticks(0, 100, 0))->toBe([]);
    });

    it('sobrevive a un dominio no finito en vez de colgarse', function () {
        expect(Ticks::ticks(0, INF, 5))->toBe([]);
        expect(Ticks::ticks(NAN, 10, 5))->toBe([]);
        expect(Ticks::nice(0, INF, 5))->toBe([0.0, INF]);
    });

    it('deja el dominio en paz si es un punto', function () {
        expect(Ticks::nice(5, 5, 5))->toBe([5.0, 5.0]);
    });
});

describe('decimales', function () {
    it('dice cuántos decimales hacen falta para no mentir en la etiqueta', function () {
        expect(Ticks::decimals(1000))->toBe(0);
        expect(Ticks::decimals(1))->toBe(0);
        expect(Ticks::decimals(0.5))->toBe(1);
        expect(Ticks::decimals(0.25))->toBe(1);
        expect(Ticks::decimals(0.1))->toBe(1);
        expect(Ticks::decimals(0.01))->toBe(2);
        expect(Ticks::decimals(0.001))->toBe(3);
    });

    it('no se rompe con un paso imposible', function () {
        expect(Ticks::decimals(0))->toBe(0);
        expect(Ticks::decimals(INF))->toBe(0);
    });
});
