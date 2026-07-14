<?php

use KoreUi\Charts\ChartFrame;
use KoreUi\Charts\Format;
use KoreUi\Charts\Marks\BarMark;
use KoreUi\Charts\Marks\LineMark;
use KoreUi\Charts\Plot;
use KoreUi\Charts\Time\TimeFormat;

$at = fn (string $when) => new DateTimeImmutable($when, new DateTimeZone('Europe/Madrid'));

/**
 * El zoom.
 *
 * El cliente manda **dos porcentajes del dominio completo**, y el servidor hace el resto. Eso es
 * todo el diseño, y es lo que hace que el zoom no cueste ni una escala en JavaScript: recortar el
 * espacio 0–100 a un tramo es un **remapeo afín**, y da igual que debajo haya categorías, fechas o
 * números.
 */
$plot = function (array $data, array $marks, ?array $window = null, ?string $x = 'fecha') {
    $frame = new ChartFrame('c1', $data, $x);

    foreach ($marks as $mark) {
        $frame->add($mark);
    }

    return new Plot(
        $frame,
        new Format,
        timeFormat: new TimeFormat('es'),
        window: $window,
    );
};

// Diez días, con el valor subiendo. El día 5 es el pico.
$diez = [];
for ($i = 0; $i < 10; $i++) {
    $diez[] = [
        'fecha' => (new DateTimeImmutable('2026-02-01', new DateTimeZone('Europe/Madrid')))->modify("+{$i} days"),
        'v' => $i === 5 ? 500 : 10 + $i,
    ];
}

describe('la ventana estira el eje', function () use ($plot, $diez) {
    it('sin ventana, el primer punto va al 0 y el último al 100', function () use ($plot, $diez) {
        $p = $plot($diez, [new LineMark('v')]);

        expect(array_column($p->series[0]['points'], 0))
            ->toBe([0.0, 11.11, 22.22, 33.33, 44.44, 55.56, 66.67, 77.78, 88.89, 100.0]);
    });

    it('con ventana, el tramo pedido ocupa todo el ancho', function () use ($plot, $diez) {
        // El tercio de en medio del dominio: los días 4, 5, 6 y 7.
        $p = $plot($diez, [new LineMark('v')], [100 / 3, 200 / 3]);

        $xs = array_column($p->series[0]['points'], 0);

        // El día 4 (que estaba en el 33,33) ahora está en el 0; el día 7 (66,67) en el 100.
        expect($xs[3])->toBe(0.0);
        expect($xs[6])->toBe(100.0);
    });

    it('las filas de fuera NO se borran: se quedan con una posición fuera del área', function () use ($plot, $diez) {
        // Es lo que hace que el trazo siga SALIENDO por el borde en vez de cortarse en seco
        // contra él. El recorte del zoom es visual (clip-path), no de dato: si se borraran las
        // filas de fuera, se vería un escalón donde no lo hay.
        $p = $plot($diez, [new LineMark('v')], [100 / 3, 200 / 3]);

        $xs = array_column($p->series[0]['points'], 0);

        expect($xs[0])->toBeLessThan(0.0);
        expect($xs[9])->toBeGreaterThan(100.0);

        // Y el <path> arranca fuera del área, no en el borde.
        expect($p->series[0]['d'])->toStartWith('M-');
    });
});

describe('el eje Y se reescala sobre lo que se VE', function () use ($plot, $diez, $at) {
    it('sin zoom, el dominio llega hasta el pico', function () use ($plot, $diez) {
        $p = $plot($diez, [new LineMark('v')]);

        expect($p->domain->max)->toBeGreaterThanOrEqual(500.0);
    });

    it('con el pico fuera de la ventana, el eje Y baja', function () use ($plot, $diez) {
        // Ampliar una semana de un año y dejar el eje Y llegando al máximo ANUAL deja el gráfico
        // aplastado contra el suelo. Es lo que ECharts llama `filterMode: 'filter'`, y es LA
        // decisión de diseño del zoom, no un detalle.
        //
        // Los días 0..3 valen 10..13; el pico de 500 es el día 5, y queda fuera.
        $p = $plot($diez, [new LineMark('v')], [0.0, 33.33]);

        expect($p->domain->max)->toBeLessThan(100.0);
        expect($p->series[0]['values'][5])->toBe(500.0);   // el dato sigue ahí, solo que no cuenta
    });

    it('si en la ventana no hay ni un dato, sale el estado vacío', function () use ($plot, $at) {
        $data = [
            ['fecha' => $at('2026-02-01'), 'v' => 1],
            ['fecha' => $at('2026-03-01'), 'v' => 2],
        ];

        // Una ventana en medio del hueco: ni una fila cae dentro.
        $p = $plot($data, [new LineMark('v')], [40.0, 60.0]);

        expect($p->empty)->toBeTrue();
    });
});

describe('las tres escalas hacen lo mismo, porque es un remapeo afín', function () use ($plot, $diez) {
    it('en un eje de categorías', function () use ($plot) {
        $data = array_map(fn ($i) => ['m' => "M{$i}", 'v' => $i], range(0, 9));

        $p = new Plot(
            tap(new ChartFrame('c', $data, 'm'), fn ($f) => $f->add(new LineMark('v'))),
            window: [100 / 3, 200 / 3],
        );

        $xs = array_column($p->series[0]['points'], 0);

        expect($xs[3])->toBe(0.0);
        expect($xs[6])->toBe(100.0);
        expect($xs[0])->toBeLessThan(0.0);
    });

    it('en un eje lineal', function () {
        $data = array_map(fn ($i) => ['x' => $i * 10, 'y' => $i], range(0, 9));

        $frame = tap(new ChartFrame('c', $data, 'x'), fn ($f) => $f->add(new LineMark('y')));
        $frame->axes['x'] = ['scale' => 'linear'];

        $p = new Plot($frame, window: [100 / 3, 200 / 3]);

        $xs = array_column($p->series[0]['points'], 0);

        expect($xs[3])->toBe(0.0);
        expect($xs[6])->toBe(100.0);
    });
});

it('al ampliar, el eje temporal cambia de unidad SOLO — sin portar TimeTicks a JavaScript', function () use ($plot) {
    // Ésta es la mitad del valor de hacer el zoom en el servidor. Un año entero dice meses; al
    // ampliar una semana, el mismo eje pasa a decir días. Un zoom en el cliente tendría que
    // recalcular esos ticks — o sea, portar TimeTicks, TimeInterval y TimeFormat a JS y mantener
    // dos implementaciones idénticas para siempre.
    $data = [];

    for ($i = 0; $i < 365; $i++) {
        $data[] = [
            'fecha' => (new DateTimeImmutable('2026-01-01', new DateTimeZone('Europe/Madrid')))->modify("+{$i} days"),
            'v' => $i,
        ];
    }

    $completo = $plot($data, [new LineMark('v')]);
    $ampliado = $plot($data, [new LineMark('v')], [0.0, 2.0]);   // los primeros ~7 días

    // Un año entero: TRIMESTRES.
    expect($completo->xTicks[1]['label'])->toBe('abr.');   // trimestres

    // Una semana: días.
    expect(array_column($ampliado->xTicks, 'label'))->toContain('2');
    expect(count($ampliado->xTicks))->toBeLessThan(12);
});

it('las barras se ensanchan al ampliar', function () use ($plot, $diez) {
    $completo = $plot($diez, [new BarMark('v')]);
    $ampliado = $plot($diez, [new BarMark('v')], [0.0, 50.0]);

    // La mitad del dominio en el mismo ancho: las barras miden el doble.
    expect($ampliado->x->bandwidth())->toEqualWithDelta($completo->x->bandwidth() * 2, 0.001);
});

it('el payload lleva la ventana, que es lo único que el cliente necesita para componer', function () use ($plot, $diez) {
    // Arrastrar del 20 % al 60 % de una vista que ya enseña [40, 80] es una regla de tres. Sin
    // escalas, sin fechas, sin locales — por eso el cliente no necesita ni una línea de
    // matemática de escalas.
    $p = $plot($diez, [new LineMark('v')], [40.0, 80.0]);

    expect($p->payload()['window'])->toBe([40.0, 80.0]);

    // Y sin zoom, la ventana es el dominio entero.
    expect($plot($diez, [new LineMark('v')])->payload()['window'])->toBe([0.0, 100.0]);
});

it('una ventana del revés o de ancho cero no rompe nada', function () use ($plot, $diez) {
    expect(fn () => $plot($diez, [new LineMark('v')], [60.0, 60.0]))->not->toThrow(Exception::class);
    expect(fn () => $plot($diez, [new LineMark('v')], [80.0, 20.0]))->not->toThrow(Exception::class);
});

describe('la marca <x-kore::chart.zoom>', function () {
    it('exige un wire:model: el estado del zoom vive en Livewire, no en el cliente', function () {
        // Y eso no es un capricho. Viviendo en el componente Livewire sale gratis que sobreviva al
        // morph, que se comparta por URL con #[Url] y que se testee con Livewire::test(). Un zoom
        // que viviera en Alpine necesitaría un hook del morph para no perderse.
        $mensaje = null;

        try {
            $this->blade(<<<'BLADE'
                <x-kore::chart :data="[['m' => 'Ene', 'v' => 1]]" x="m">
                    <x-kore::chart.line y="v" />
                    <x-kore::chart.zoom />
                </x-kore::chart>
            BLADE)->__toString();
        } catch (Throwable $e) {
            $mensaje = $e->getMessage();
        }

        expect($mensaje)->toContain('wire:model');
        expect($mensaje)->toContain('componente Livewire');
    });

    it('un donut no lleva zoom', function () {
        // El zoom recorta un tramo del eje X, y un donut no tiene eje X: sus porciones son un
        // reparto, no una secuencia.
        $mensaje = null;

        try {
            $this->blade(<<<'BLADE'
                <x-kore::chart :data="[['k' => 'A', 'v' => 1]]" x="k">
                    <x-kore::chart.donut y="v" />
                    <x-kore::chart.zoom wire:model.live="w" />
                </x-kore::chart>
            BLADE)->__toString();
        } catch (Throwable $e) {
            $mensaje = $e->getMessage();
        }

        expect($mensaje)->toContain('un donut no lleva');
    });

    it('pinta el slider de contexto con la serie ENTERA, no con la de la ventana', function () {
        // Un <path> son 17 nodos de DOM pase lo que pase. Un gráfico de contexto es literalmente
        // gratis en una arquitectura que dibuja en el servidor; en una de canvas es un segundo motor.
        $data = '[';
        for ($i = 0; $i < 20; $i++) {
            $data .= "['i' => {$i}, 'v' => ".($i * 3).'],';
        }
        $data .= ']';

        $html = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="i" :window="[40, 60]">
                <x-kore::chart.line y="v" />
                <x-kore::chart.zoom wire:model.live="ventana" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->toContain('kore-chart-slider-line');
        expect($html)->toContain('data-kore-chart-zoomed="true"');

        // El trazo del gráfico arranca FUERA del área (la ventana recorta), pero el del slider
        // arranca en el 0: es la serie completa.
        preg_match('/class="kore-chart-line" d="(M[^ ]+)/', $html, $principal);
        preg_match('/class="kore-chart-slider-line" d="(M[^ ]+)/', $html, $contexto);

        expect($principal[1])->toStartWith('M-');
        expect($contexto[1])->toBe('M0');
    });

    it('sin tooltip, el payload solo lleva la ventana', function () {
        // El dato es una segunda copia entera en el DOM, y a 2.000 puntos pesa más que el propio
        // <path>. Un gráfico con zoom pero sin tooltip no lo necesita.
        $frame = new ChartFrame('c', [['m' => 'Ene', 'v' => 1]], 'm');
        $frame->add(new LineMark('v'));

        $payload = (new Plot($frame, window: [10.0, 90.0]))->payload(series: false);

        expect($payload)->toBe(['window' => [10.0, 90.0]]);
    });
});
