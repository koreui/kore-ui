<?php

use KoreUi\Charts\Time\TimeInterval;
use KoreUi\Charts\Time\TimeTicks;

/**
 * El listón es el mismo que se le puso al eje Y: los ticks salen del algoritmo de d3, así que
 * tienen que dar exactamente lo que da d3. El fixture está generado con `d3-time` de verdad
 * (`TZ=Europe/Madrid node gen-time-ticks.mjs`), no con lo que yo crea que hace d3.
 */
$madrid = new DateTimeZone('Europe/Madrid');

$at = fn (string $when) => new DateTimeImmutable($when, new DateTimeZone('Europe/Madrid'));

/**
 * Las dos únicas divergencias con d3, y las dos son DECISIONES.
 *
 * 1. **La semana empieza en lunes.** d3 la empieza en domingo (`timeWeek` = `timeSunday`).
 *    ISO-8601 dice lunes, y es lo que espera quien lee un gráfico en español.
 *
 * 2. **No bajamos del segundo.** d3 sigue hasta el milisegundo. Un gráfico de negocio con el
 *    SVG renderizado por el servidor y actualizado por un round-trip de Livewire no llega ahí
 *    ni de lejos, y fingir que sí sería mentir sobre lo que la arquitectura puede hacer.
 */
$divergencias = [
    '2026-01-01T00:00:00|2026-03-01T00:00:00' => 'la semana empieza en lunes, no en domingo',
    '2026-02-14T00:00:00|2026-02-14T00:00:03' => 'no bajamos del segundo',
];

$fixture = json_decode(file_get_contents(__DIR__.'/Fixtures/d3-time-ticks.json'), true);

// Envuelto en un array: Pest despliega un dataset asociativo como parámetros CON NOMBRE.
$paridad = array_map(
    fn (array $case) => [$case],
    array_values(array_filter(
        $fixture,
        fn (array $case) => ! isset($divergencias[$case['start'].'|'.$case['stop']]),
    )),
);

it('da exactamente los mismos ticks que d3', function (array $case) use ($at) {
    $ticks = TimeTicks::ticks($at($case['start']), $at($case['stop']), $case['count']);

    $nuestros = array_map(fn (DateTimeImmutable $d) => $d->format('Y-m-d H:i:s'), $ticks);

    expect($nuestros)->toBe($case['ticks']);
})->with($paridad);

describe('donde nos separamos de d3, a propósito', function () use ($at) {
    it('empieza la semana en lunes, no en domingo', function () use ($at) {
        // d3 arrancaría el 4 de enero, que es domingo.
        $ticks = TimeTicks::ticks($at('2026-01-01'), $at('2026-03-01'), 8);

        expect($ticks[0]->format('Y-m-d'))->toBe('2026-01-05');
        expect($ticks[0]->format('N'))->toBe('1');   // lunes

        foreach ($ticks as $tick) {
            expect($tick->format('N'))->toBe('1');
        }
    });

    it('no baja del segundo', function () use ($at) {
        // d3 daría 16 ticks de 200 ms. Nosotros damos segundos y ya está.
        $ticks = TimeTicks::ticks($at('2026-02-14'), $at('2026-02-14 00:00:03'), 10);

        expect($ticks)->toHaveCount(4);
        expect($ticks[1]->format('H:i:s'))->toBe('00:00:01');
    });
});

describe('la elección del intervalo', function () use ($at) {
    it('elige la pareja (unidad, paso), no sólo la unidad', function () use ($at) {
        // Éste es el fallo de Chart.js: elige la unidad y deja stepSize en 1, así que para un
        // eje de un año genera 365 ticks de un día y luego los esconde con autoSkip.
        $paso = TimeTicks::interval($at('2024-01-01'), $at('2026-01-01'), 8);

        expect($paso->unit())->toBe(TimeInterval::MONTH);
        expect($paso->step)->toBe(3);
        expect((string) $paso)->toBe('3 month');
    });

    it('nunca elige «cada 4 horas» ni «cada 10 minutos»', function () use ($at) {
        // No son divisores decentes de 24 ni de 60: los ticks se descuadrarían al día siguiente.
        // Se recorre un abanico ancho de rangos y se comprueba que ninguno los produce.
        foreach ([1, 2, 3, 6, 12, 24, 48, 72] as $horas) {
            foreach ([4, 6, 8, 10, 12] as $count) {
                $paso = TimeTicks::interval($at('2026-02-14'), $at("2026-02-14 +{$horas} hours"), $count);

                expect([$paso->unit(), $paso->step])
                    ->not->toBe([TimeInterval::HOUR, 4])
                    ->not->toBe([TimeInterval::MINUTE, 10]);
            }
        }
    });

    it('por encima del año vuelve al algoritmo del eje Y', function () use ($at) {
        // Las décadas y los siglos no son unidades de calendario: son 1/2/5 × 10^n sobre años,
        // que es exactamente lo que Ticks ya sabía hacer.
        $paso = TimeTicks::interval($at('1900-01-01'), $at('2026-01-01'), 5);

        expect($paso->unit())->toBe(TimeInterval::YEAR);
        expect($paso->step)->toBe(20);
    });

    it('sirve también para agrupar la consulta — es el $__interval de Grafana', function () use ($at) {
        // Que las escalas vivan en el servidor tiene esta consecuencia casi inevitable: el mismo
        // intervalo que decide los ticks decide el GROUP BY. Nadie en Laravel lo expone.
        $paso = TimeTicks::interval($at('2026-01-01'), $at('2026-03-01'), 8);

        expect($paso->unit())->toBe(TimeInterval::WEEK);
        expect($paso->toDateInterval()->d)->toBe(7);
    });
});

describe('nice()', function () use ($at) {
    it('redondea el rango hasta caer en fronteras de calendario', function () use ($at) {
        // Un eje que empieza el 14 de marzo a las 03:47 no es un eje. Ocho meses de rango piden
        // ticks mensuales, así que la frontera es el día 1.
        [$lo, $hi] = TimeTicks::nice($at('2026-03-14 03:47:12'), $at('2026-11-02 19:03:44'), 6);

        expect($lo->format('Y-m-d H:i:s'))->toBe('2026-03-01 00:00:00');
        expect($hi->format('Y-m-d H:i:s'))->toBe('2026-12-01 00:00:00');
    });

    it('redondea a la frontera DEL INTERVALO ELEGIDO, no siempre al mes', function () use ($at) {
        // Ochenta días piden ticks semanales, así que la frontera es el lunes — no el día 1. Es
        // la misma idea que en el eje Y: se redondea al paso que se va a usar, no a un paso fijo.
        [$lo, $hi] = TimeTicks::nice($at('2026-03-14 03:47:12'), $at('2026-06-02 19:03:44'), 6);

        expect($lo->format('Y-m-d'))->toBe('2026-03-09');
        expect($lo->format('N'))->toBe('1');   // lunes
        expect($hi->format('N'))->toBe('1');
    });

    it('deja en paz un rango que ya está en frontera', function () use ($at) {
        [$lo, $hi] = TimeTicks::nice($at('2026-01-01'), $at('2026-07-01'), 6);

        expect($lo->format('Y-m-d'))->toBe('2026-01-01');
        expect($hi->format('Y-m-d'))->toBe('2026-07-01');
    });
});

describe('casos degenerados', function () use ($at) {
    it('un rango de longitud cero da un solo tick', function () use ($at) {
        expect(TimeTicks::ticks($at('2026-02-14'), $at('2026-02-14'), 5))->toHaveCount(1);
    });

    it('no devuelve nada si no se piden ticks', function () use ($at) {
        expect(TimeTicks::ticks($at('2026-01-01'), $at('2026-12-31'), 0))->toBe([]);
    });

    it('un rango del revés devuelve los ticks del revés', function () use ($at) {
        $ticks = TimeTicks::ticks($at('2026-07-01'), $at('2026-01-01'), 6);

        expect($ticks[0]->format('Y-m-d'))->toBe('2026-07-01');
        expect(end($ticks)->format('Y-m-d'))->toBe('2026-01-01');
    });
});
