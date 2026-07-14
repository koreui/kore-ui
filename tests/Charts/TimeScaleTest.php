<?php

use KoreUi\Charts\ChartFrame;
use KoreUi\Charts\Format;
use KoreUi\Charts\Marks\BarMark;
use KoreUi\Charts\Marks\LineMark;
use KoreUi\Charts\Plot;
use KoreUi\Charts\Scales\BandScale;
use KoreUi\Charts\Scales\LinearXScale;
use KoreUi\Charts\Scales\TimeScale;
use KoreUi\Charts\Time\TimeFormat;

$at = fn (string $when) => new DateTimeImmutable($when, new DateTimeZone('Europe/Madrid'));

/** Un frame ya montado, para no repetirlo veinte veces. */
$plot = function (array $data, array $marks, ?array $xAxis = null) {
    $frame = new ChartFrame('c1', $data, 'fecha');

    if ($xAxis !== null) {
        $frame->axes['x'] = $xAxis;
    }

    foreach ($marks as $mark) {
        $frame->add($mark);
    }

    return new Plot(
        $frame,
        new Format,
        timeFormat: new TimeFormat('es'),
        xTickCount: $xAxis['ticks'] ?? null,
    );
};

/**
 * Esto es la razón de ser del eje temporal, y no es una mejora estética: es una corrección de
 * honestidad.
 *
 * Hasta 1.6.0 el eje X colocaba cada punto por su ORDINAL en el array. Un sensor que estuvo tres
 * días caído se dibujaba con sus lecturas pegadas una a otra, como si no hubiera pasado nada. El
 * gráfico se veía perfectamente bien y mentía.
 */
describe('la posición la decide el VALOR, no el sitio en el array', function () use ($at, $plot) {
    it('un hueco de tres días ocupa tres días de ancho', function () use ($at, $plot) {
        $p = $plot(
            [
                ['fecha' => $at('2026-02-01'), 'v' => 10],
                ['fecha' => $at('2026-02-02'), 'v' => 20],
                // aquí el sensor estuvo caído: 3, 4 y 5 de febrero no existen
                ['fecha' => $at('2026-02-06'), 'v' => 30],
            ],
            [new LineMark('v')],
        );

        expect($p->x)->toBeInstanceOf(TimeScale::class);

        $puntos = $p->series[0]['points'];

        // Cinco días de rango. El primero en el 0, el segundo a un día (20 %), el tercero al
        // final. Si se colocaran por ordinal, el segundo estaría en el 50 %.
        expect($puntos[0][0])->toBe(0.0);
        expect($puntos[1][0])->toBe(20.0);
        expect($puntos[2][0])->toBe(100.0);
    });

    it('con categorías sigue colocando por ordinal, que ahí es lo correcto', function () {
        $frame = new ChartFrame('c1', [
            ['mes' => 'Ene', 'v' => 10],
            ['mes' => 'Feb', 'v' => 20],
            ['mes' => 'Mar', 'v' => 30],
        ], 'mes');
        $frame->add(new LineMark('v'));

        $p = new Plot($frame);

        expect($p->x)->toBeInstanceOf(BandScale::class);
        expect(array_column($p->series[0]['points'], 0))->toBe([0.0, 50.0, 100.0]);
    });

    it('ordena las filas por fecha: el orden del array es el orden de dibujado', function () use ($at, $plot) {
        // Sin ordenar, `Path::line()` uniría los puntos tal como vienen y dibujaría un garabato
        // que va y vuelve en el tiempo. Y la búsqueda binaria del tooltip, que asume xs
        // ascendente, devolvería cualquier cosa.
        $p = $plot(
            [
                ['fecha' => $at('2026-02-03'), 'v' => 30],
                ['fecha' => $at('2026-02-01'), 'v' => 10],
                ['fecha' => $at('2026-02-02'), 'v' => 20],
            ],
            [new LineMark('v')],
        );

        expect($p->series[0]['values'])->toBe([10.0, 20.0, 30.0]);
        expect(array_column($p->series[0]['points'], 0))->toBe([0.0, 50.0, 100.0]);

        // Y el payload sale ascendente, que es lo que la búsqueda binaria necesita.
        $xs = $p->payload()['xs'];
        expect($xs)->toBe([0.0, 50.0, 100.0]);
    });

    it('una fila sin fecha es un hueco, no un punto en el origen', function () use ($at, $plot) {
        $p = $plot(
            [
                ['fecha' => $at('2026-02-01'), 'v' => 10],
                ['fecha' => null, 'v' => 20],
                ['fecha' => $at('2026-02-03'), 'v' => 30],
            ],
            [new LineMark('v')],
        );

        // La fila sin fecha se va al final y no se pinta. Colocarla en el 0 dibujaría un pico
        // contra el eje Y que nunca existió.
        expect($p->series[0]['points'][2])->toBeNull();
    });
});

describe('las barras en un eje continuo', function () use ($at, $plot) {
    it('sacan su ancho del hueco MÍNIMO, no del medio', function () use ($at, $plot) {
        // Con el hueco medio, dos lecturas más juntas que la media producirían barras solapadas
        // — y una barra que tapa a otra no es un gráfico apretado: es un dato escondido.
        $p = $plot(
            [
                ['fecha' => $at('2026-02-01'), 'v' => 10],
                ['fecha' => $at('2026-02-02'), 'v' => 20],   // un día
                ['fecha' => $at('2026-02-06'), 'v' => 30],   // cuatro días
            ],
            [new BarMark('v')],
        );

        // Rango: 5 días = 100 %. El hueco mínimo es 1 día = 20 %. Con el padding de 0,2 → 16 %.
        expect($p->x->bandwidth())->toBe(16.0);

        // Y ninguna barra pisa a la siguiente.
        $barras = $p->series[0]['bars'];

        for ($i = 1; $i < count($barras); $i++) {
            expect($barras[$i]['x'])->toBeGreaterThanOrEqual($barras[$i - 1]['x'] + $barras[$i - 1]['w']);
        }
    });

    it('centra la barra en su fecha', function () use ($at, $plot) {
        $p = $plot(
            [
                ['fecha' => $at('2026-02-01'), 'v' => 10],
                ['fecha' => $at('2026-02-03'), 'v' => 20],
            ],
            [new BarMark('v')],
        );

        $barras = $p->series[0]['bars'];
        $ancho = $p->x->bandwidth();

        // Centro de la primera = 0 %; centro de la segunda = 100 %.
        expect($barras[0]['x'] + $ancho / 2)->toBe(0.0);
        expect($barras[1]['x'] + $ancho / 2)->toBe(100.0);
    });
});

describe('los ticks del eje', function () use ($at, $plot) {
    it('caen en fronteras del calendario, no cada N filas', function () use ($at, $plot) {
        // Con el adelgazado por salto (que es lo que hace una escala de bandas), 90 días con 12
        // etiquetas dan los días 1, 8, 15, 22… — que es exactamente el «1.224» que Ticks se
        // enorgullece de haber eliminado del eje Y. El eje X tenía el defecto que el eje Y ya no
        // tiene.
        $data = [];

        for ($i = 0; $i < 90; $i++) {
            $data[] = ['fecha' => $at('2026-01-01')->modify("+{$i} days"), 'v' => $i];
        }

        $p = $plot($data, [new LineMark('v')]);

        // Con el objetivo por defecto (6), noventa días piden ticks MENSUALES: el 1 de enero, el
        // 1 de febrero y el 1 de marzo. Ni uno cae en «cada N filas».
        expect(array_column($p->xTicks, 'label'))->toBe(['2026', 'feb.', 'mar.']);

        // Y si pides más, te los da — pero es una pista, no un contrato.
        $masTicks = $plot($data, [new LineMark('v')], ['ticks' => 12]);

        expect(count($masTicks->xTicks))->toBeGreaterThan(count($p->xTicks));

        foreach ($masTicks->xTicks as $tick) {
            expect($tick['pos'])->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual(100.0);
        }
    });

    it('lleva una segunda línea de contexto cuando el mes cambia', function () use ($at, $plot) {
        // Es el agujero de d3: un eje del 10 al 20 de enero no dice en ninguna parte de qué mes
        // habla, porque ningún tick cae en un día 1. La segunda línea lo arregla.
        $data = [];

        for ($i = 0; $i < 40; $i++) {
            $data[] = ['fecha' => $at('2026-01-10')->modify("+{$i} days"), 'v' => $i];
        }

        $p = $plot($data, [new LineMark('v')]);

        // El primero SIEMPRE lleva contexto: es el que sitúa todo el eje.
        expect($p->xTicks[0]['context'])->not->toBeNull();

        // Y no lo repiten todos: sería ruido, no información.
        $conContexto = count(array_filter($p->xTicks, fn ($t) => $t['context'] !== null));
        expect($conContexto)->toBeLessThan(count($p->xTicks));
    });
});

describe('la escala', function () use ($at, $plot) {
    it('se elige sola cuando hay fechas', function () use ($at, $plot) {
        $p = $plot([['fecha' => $at('2026-02-01'), 'v' => 1]], [new LineMark('v')]);

        expect($p->x)->toBeInstanceOf(TimeScale::class);
    });

    it('NO promociona unos años enteros a escala lineal por su cuenta', function () {
        // 2022, 2023, 2024 son categorías, no una recta numérica. Colocarlas en una escala
        // lineal le cambiaría el gráfico a quien no ha pedido nada.
        $frame = new ChartFrame('c1', [
            ['ano' => 2022, 'v' => 1],
            ['ano' => 2024, 'v' => 3],
        ], 'ano');
        $frame->add(new LineMark('v'));

        expect((new Plot($frame))->x)->toBeInstanceOf(BandScale::class);
    });

    it('pero la usa si se la pides', function () {
        $frame = new ChartFrame('c1', [
            ['x' => 0, 'y' => 1],
            ['x' => 10, 'y' => 3],
            ['x' => 40, 'y' => 2],
        ], 'x');
        $frame->axes['x'] = ['scale' => 'linear'];
        $frame->add(new LineMark('y'));

        $p = new Plot($frame);

        expect($p->x)->toBeInstanceOf(LinearXScale::class);
        expect(array_column($p->series[0]['points'], 0))->toBe([0.0, 25.0, 100.0]);
    });

    it('avisa si pides un eje temporal y le das texto', function () {
        // Éste es EL error que hay que hacer imposible: si le das la fecha ya formateada, el
        // gráfico sólo puede colocar los puntos por su orden — y los huecos del calendario
        // desaparecen sin que nadie se entere.
        $frame = new ChartFrame('c1', [['fecha' => '1 feb', 'v' => 1]], 'fecha');
        $frame->axes['x'] = ['scale' => 'time'];
        $frame->add(new LineMark('v'));

        $mensaje = null;

        try {
            new Plot($frame);
        } catch (InvalidArgumentException $e) {
            $mensaje = $e->getMessage();
        }

        expect($mensaje)->toContain('no trae fechas');
        expect($mensaje)->toContain('cadenas ya formateadas');
    });
});

describe('invert(): lo que hará posible el zoom sin una escala en JavaScript', function () use ($at, $plot) {
    it('devuelve la fecha que hay bajo un porcentaje', function () use ($at, $plot) {
        $p = $plot(
            [
                ['fecha' => $at('2026-02-01'), 'v' => 1],
                ['fecha' => $at('2026-02-11'), 'v' => 2],
            ],
            [new LineMark('v')],
        );

        // El cliente manda un 50 %; el servidor lo convierte en una fecha. Cero escalas en JS.
        expect($p->x->invert(50.0)->format('Y-m-d'))->toBe('2026-02-06');
        expect($p->x->invert(0.0)->format('Y-m-d'))->toBe('2026-02-01');
        expect($p->x->invert(100.0)->format('Y-m-d'))->toBe('2026-02-11');
    });

    it('en una escala de bandas devuelve la fila más cercana', function () {
        $frame = new ChartFrame('c1', [
            ['m' => 'Ene', 'v' => 1],
            ['m' => 'Feb', 'v' => 2],
            ['m' => 'Mar', 'v' => 3],
        ], 'm');
        $frame->add(new LineMark('v'));

        expect((new Plot($frame))->x->invert(49.0))->toBe(1);
    });
});
