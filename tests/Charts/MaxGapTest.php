<?php

use KoreUi\Charts\ChartFrame;
use KoreUi\Charts\Duration;
use KoreUi\Charts\Format;
use KoreUi\Charts\Marks\LineMark;
use KoreUi\Charts\Path;
use KoreUi\Charts\Plot;
use KoreUi\Charts\Time\TimeFormat;

$at = fn (string $when) => new DateTimeImmutable($when, new DateTimeZone('Europe/Madrid'));

describe('Duration', function () {
    it('entiende las unidades', function () {
        expect(Duration::seconds('30s'))->toBe(30.0);
        expect(Duration::seconds('5m'))->toBe(300.0);
        expect(Duration::seconds('2h'))->toBe(7200.0);
        expect(Duration::seconds('7d'))->toBe(604800.0);
        expect(Duration::seconds('1w'))->toBe(604800.0);
        expect(Duration::seconds(45))->toBe(45.0);
        expect(Duration::seconds('90'))->toBe(90.0);
    });

    it('«30ms» no son 30 minutos', function () {
        // El orden de la regex importa: si «m» se probara antes que «ms», el umbral saldría 1.800
        // veces más grande de lo que pediste y no partiría nunca.
        expect(Duration::seconds('30ms'))->toBe(0.03);
    });

    it('lanza con cualquier otra cosa', function () {
        $mensaje = null;

        try {
            Duration::seconds('un ratito');
        } catch (InvalidArgumentException $e) {
            $mensaje = $e->getMessage();
        }

        expect($mensaje)->toContain('no es una duración');
    });
});

/**
 * El trazo no cruza un hueco de verdad.
 *
 * Un `null` explícito ya partía la línea desde el primer día. Lo que NO partía nada era una fila
 * que sencillamente **no existe**: ahí la línea cruzaba el hueco dibujando una curva suave por
 * encima de un rato en el que no hubo dato. Y con `curve="monotone"`, además, se inventaba un swoop
 * que *parece* dato.
 *
 * Es la misma mentira que arregló el eje temporal, un piso más arriba: entonces el hueco desaparecía
 * porque los puntos se colocaban por su ordinal; ahora el hueco se ve, pero el trazo lo tapa.
 */
describe('max-gap', function () use ($at) {
    $plot = function (array $data, ?float $maxGap, string $curve = Path::LINEAR) {
        $frame = new ChartFrame('c1', $data, 'fecha');
        $frame->add((new LineMark('v'))->withCurve($curve)->withMaxGap($maxGap));

        return new Plot($frame, new Format, timeFormat: new TimeFormat('es'));
    };

    // Un sensor que muestrea cada minuto y se cae media hora.
    $sensor = [];
    foreach ([0, 1, 2, 33, 34, 35] as $minuto) {
        $sensor[] = [
            'fecha' => (new DateTimeImmutable('2026-02-14 10:00', new DateTimeZone('Europe/Madrid')))
                ->modify("+{$minuto} minutes"),
            'v' => 10 + $minuto,
        ];
    }

    it('sin max-gap, la línea CRUZA el hueco — y eso es lo que estaba mal', function () use ($plot, $sensor) {
        $d = $plot($sensor, null)->series[0]['d'];

        // Un solo `M`: el trazo es continuo por encima de la media hora sin dato.
        expect(substr_count($d, 'M'))->toBe(1);
    });

    it('con max-gap, se parte', function () use ($plot, $sensor) {
        // Muestrea cada minuto: dos minutos de hueco ya es un dato perdido.
        $d = $plot($sensor, 120.0)->series[0]['d'];

        // Dos `M`: dos tramos, y entre medias nada. Que es lo que pasó.
        expect(substr_count($d, 'M'))->toBe(2);
    });

    it('no parte donde el hueco es normal', function () use ($plot, $sensor) {
        $puntos = $plot($sensor, 120.0)->series[0]['points'];

        // Seis filas, y un solo `null` insertado — el del hueco. Los tres primeros y los tres
        // últimos siguen unidos entre sí.
        expect(count($puntos))->toBe(7);
        expect($puntos[3])->toBeNull();
    });

    it('con la curva monótona, la tangente se reinicia en el hueco', function () use ($plot, $sensor) {
        // Sin partir, la monótona se inventa un swoop POR ENCIMA del vacío que parece dato.
        $con = $plot($sensor, 120.0, Path::MONOTONE)->series[0]['d'];

        expect(substr_count($con, 'M'))->toBe(2);
    });

    it('un umbral enorme no parte nada', function () use ($plot, $sensor) {
        expect(substr_count($plot($sensor, 86400.0)->series[0]['d'], 'M'))->toBe(1);
    });
});

it('en un eje de categorías, max-gap LANZA', function () {
    // Mide una DISTANCIA entre dos puntos, y entre dos categorías no hay ninguna: son equidistantes
    // por definición. Aceptarlo en silencio dejaría al usuario creyendo que su gráfico se parte en
    // los huecos, y no se partiría nunca.
    $frame = new ChartFrame('c1', [['m' => 'Ene', 'v' => 1], ['m' => 'Feb', 'v' => 2]], 'm');
    $frame->add((new LineMark('v'))->withMaxGap(60.0));

    $mensaje = null;

    try {
        new Plot($frame);
    } catch (InvalidArgumentException $e) {
        $mensaje = $e->getMessage();
    }

    expect($mensaje)->toContain('sólo tiene sentido en un eje continuo');
});

it('de punta a punta, desde el Blade', function () {
    $data = <<<'PHP'
    [
        ['t' => new DateTimeImmutable('2026-02-14 10:00'), 'v' => 10],
        ['t' => new DateTimeImmutable('2026-02-14 10:01'), 'v' => 12],
        ['t' => new DateTimeImmutable('2026-02-14 10:40'), 'v' => 40],
    ]
    PHP;

    $partido = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="t">
            <x-kore::chart.line y="v" curve="monotone" max-gap="5m" />
        </x-kore::chart>
    BLADE)->__toString();

    preg_match('/class="kore-chart-line" d="([^"]+)"/', $partido, $m);

    expect(substr_count($m[1], 'M'))->toBe(2);
});
