<?php

namespace KoreUi\Charts;

use KoreUi\Charts\Marks\Mark;
use KoreUi\Charts\Scales\BandScale;
use KoreUi\Charts\Scales\LinearScale;

/**
 * De un `ChartFrame` (qué se ha pedido) a la geometría (dónde va cada cosa).
 *
 * Todo sale en **porcentajes del área de trazado**, nunca en píxeles. El servidor no
 * conoce el ancho del contenedor y no le hace falta: el navegador escala. De ahí que el
 * resize no cueste ni una línea de JavaScript.
 *
 * Está fuera del Blade a propósito. Una vista no se puede testear a fondo; esto sí.
 */
final class Plot
{
    public readonly bool $empty;

    public readonly Domain $domain;

    public readonly LinearScale $y;

    public readonly BandScale $x;

    /** @var list<string> */
    public readonly array $categories;

    /** @var list<array<string, mixed>> */
    public readonly array $series;

    /** @var list<array{medium: string, series: list<array<string, mixed>>}> */
    public readonly array $layers;

    /** @var list<array{value: float, label: string, pos: float}> */
    public readonly array $yTicks;

    /** @var list<array{label: string, pos: float, index: int}> */
    public readonly array $xTicks;

    public readonly float $zero;

    public function __construct(
        public readonly ChartFrame $frame,
        private readonly Format $format = new Format,
        private readonly int $tickCount = 5,
        private readonly float $barPadding = 0.2,
        private readonly int $maxXLabels = 12,
    ) {
        // Antes de calcular nada: una marca que no se va a pintar se avisa, no se descarta.
        $frame->validate();

        $this->categories = $frame->categories();
        $marks = $frame->marks();

        $values = array_map(fn (Mark $mark) => $frame->values($mark), $marks);

        $this->domain = Domain::fromSeries(
            $this->stackedValues($frame, $values),
            includeZero: $frame->requiresZero(),
            tickCount: $tickCount,
        );

        $this->empty = $frame->isEmpty() || $this->domain->empty;

        $this->y = LinearScale::vertical($this->domain->toArray());

        // Con barras, el eje X son BANDAS y la línea se ancla al centro de la suya, o no
        // coincidiría con ellas. Sin barras, los puntos se reparten de borde a borde: si se
        // anclaran al centro de una banda imaginaria, media banda quedaría vacía a cada lado
        // y con 6 categorías se perdería el 16 % del ancho del gráfico.
        $this->x = new BandScale(
            $this->categories,
            padding: $frame->hasBars() ? $barPadding : 0.0,
            point: ! $frame->hasBars(),
        );
        $this->zero = $this->y->zero();

        $this->yTicks = $this->buildYTicks();
        $this->xTicks = $this->buildXTicks();
        $this->series = $this->buildSeries($marks, $values);
        $this->layers = $this->buildLayers();
    }

    /**
     * El dominio de un apilado no es el de sus series: es el de sus SUMAS.
     *
     * Si no se suman antes de calcular el dominio, la pila se sale del gráfico por arriba —
     * el eje se calcula contra el segmento más alto, no contra la torre.
     *
     * @param  list<list<float|null>>  $values
     * @return list<list<float|null>>
     */
    private function stackedValues(ChartFrame $frame, array $values): array
    {
        $marks = $frame->marks();
        $stacks = $frame->stacks();

        if ($stacks === []) {
            return $values;
        }

        $out = [];
        $stacked = [];

        foreach ($marks as $i => $mark) {
            if ($mark->type() === 'bar' && $mark->stack !== null) {
                foreach ($values[$i] as $row => $value) {
                    $stacked[$mark->stack][$row] = ($stacked[$mark->stack][$row] ?? 0.0) + (float) ($value ?? 0.0);
                }

                continue;
            }

            $out[] = $values[$i];
        }

        foreach ($stacked as $totals) {
            $out[] = array_values($totals);
        }

        return $out;
    }

    /** @return list<array{value: float, label: string, pos: float}> */
    private function buildYTicks(): array
    {
        $ticks = $this->domain->ticks($this->tickCount);
        $decimals = Ticks::decimals(Ticks::step($this->domain->min, $this->domain->max, $this->tickCount));

        return array_map(fn (float $value) => [
            'value' => $value,
            'label' => $this->format->apply($value, $decimals),
            'pos' => round($this->y->at($value), 2),
        ], $ticks);
    }

    /**
     * El ancho de la canaleta del eje Y, en `ch`.
     *
     * La columna del grid es `auto`, pero las etiquetas van en `position: absolute` —y tienen
     * que ir, porque cada una se coloca a la altura de su tick— así que **no aportan anchura**:
     * la columna colapsaría y las etiquetas se saldrían por la izquierda, fuera de la tarjeta.
     *
     * El servidor no puede medir texto, pero sí puede CONTAR CARACTERES. La unidad `ch` deja
     * que el navegador haga la conversión a píxeles con la fuente real. Y como los ticks usan
     * `tabular-nums`, todos los dígitos miden lo mismo, así que la cuenta es exacta.
     */
    public function yGutter(): float
    {
        $longest = 0;

        foreach ($this->yTicks as $tick) {
            $longest = max($longest, mb_strlen($tick['label']));
        }

        // +0.5 de margen: el signo menos y el símbolo de moneda no son dígitos y pueden
        // medir algo más que un `ch`.
        return round(max($longest + 0.5, 2), 1);
    }

    /**
     * Las etiquetas del eje X, saltando de N en N cuando no caben.
     *
     * El servidor no puede medir texto, así que no puede saber si dos etiquetas colisionan.
     * Lo que sí puede es no emitir más de las que caben razonablemente. Rotarlas NO es una
     * opción: `transform: rotate()` no ocupa layout, la fila del grid no crecería y las
     * etiquetas se saldrían de la caja.
     *
     * @return list<array{label: string, pos: float, index: int}>
     */
    private function buildXTicks(): array
    {
        $count = count($this->categories);

        if ($count === 0) {
            return [];
        }

        $stride = (int) max(1, ceil($count / max(1, $this->maxXLabels)));
        $out = [];

        foreach ($this->categories as $i => $label) {
            if ($i % $stride !== 0 && $i !== $count - 1) {
                continue;
            }

            $out[] = [
                'label' => $label,
                'pos' => round($this->x->centerAt($i), 2),
                'index' => $i,
            ];
        }

        return $out;
    }

    /**
     * @param  list<Mark>  $marks
     * @param  list<list<float|null>>  $values
     * @return list<array<string, mixed>>
     */
    private function buildSeries(array $marks, array $values): array
    {
        $bars = $this->layoutBars($marks, $values);
        $out = [];

        foreach ($marks as $i => $mark) {
            $serieValues = $values[$i];

            // Una vez por serie, no una por valor.
            $decimals = Format::decimalsFor($serieValues);

            $points = [];
            foreach ($serieValues as $row => $value) {
                $points[] = $value === null
                    ? null
                    : [round($this->x->centerAt($row), 2), round($this->y->at($value), 2)];
            }

            $serie = [
                'id' => $this->frame->id.'-s'.($i + 1),
                'name' => $mark->name(),
                'type' => $mark->type(),
                'medium' => $mark->medium(),
                'slot' => $mark->slot,
                'color' => Palette::resolve($mark->slot, $mark->color),
                'values' => $serieValues,
                // Los decimales salen de la propia serie: sin esto, un sensor a 21,4 °C se
                // escribía "21" en el tooltip y en la tabla accesible.
                'labels' => array_map(fn ($v) => $this->format->apply($v, $decimals), $serieValues),
                'points' => $points,
                'd' => null,
                'area' => null,
                'bars' => $bars[$i] ?? [],
                'dots' => [],
            ];

            if ($mark->type() === 'line') {
                $serie['d'] = Path::line($points, $mark->curve);

                // Los puntos son opt-in: un <div> por punto escala 1:1 con el dato. Medido:
                // con 10.000 puntos, mover el crosshair cuesta 2,9 ms por frame.
                if ($mark->dots) {
                    $serie['dots'] = array_values(array_filter(array_map(
                        fn ($p, $row) => $p === null ? null : ['x' => $p[0], 'y' => $p[1], 'index' => $row],
                        $points,
                        array_keys($points),
                    )));
                }
            }

            if ($mark->type() === 'area') {
                $serie['d'] = Path::line($points, $mark->curve);
                $serie['area'] = Path::area($points, $this->zero, $mark->curve);
            }

            if ($mark->type() === 'donut') {
                $serie['slices'] = $this->donutSlices($mark, $serieValues);
                $serie['highlight'] = $mark->highlight;
            }

            $out[] = $serie;
        }

        return $out;
    }

    /**
     * Dónde va cada barra, en porcentajes.
     *
     * Las barras que comparten pila se acumulan; las que no, se reparten el ancho de la
     * banda. Todo en % del área: el HTML las coloca con `left`/`width`/`top`/`height`.
     *
     * @param  list<Mark>  $marks
     * @param  list<list<float|null>>  $values
     * @return array<int, list<array<string, float|int|string>>>
     */
    private function layoutBars(array $marks, array $values): array
    {
        $barIndexes = [];
        foreach ($marks as $i => $mark) {
            if ($mark->type() === 'bar') {
                $barIndexes[] = $i;
            }
        }

        if ($barIndexes === []) {
            return [];
        }

        // Cada pila cuenta como UN grupo; cada barra suelta, también.
        $groups = [];
        foreach ($barIndexes as $i) {
            $key = $marks[$i]->stack !== null ? 'stack:'.$marks[$i]->stack : 'bar:'.$i;
            $groups[$key][] = $i;
        }

        $groupKeys = array_keys($groups);
        $groupCount = max(1, count($groupKeys));
        $bandwidth = $this->x->bandwidth();
        $slotWidth = $bandwidth / $groupCount;

        $out = [];
        $offsets = [];   // acumulado por (grupo, fila) para los apilados
        $cap = [];       // la PUNTA de cada columna: [grupo][fila] => [markIndex, posición]

        foreach ($groupKeys as $g => $key) {
            foreach ($groups[$key] as $markIndex) {
                foreach ($values[$markIndex] as $row => $value) {
                    if ($value === null) {
                        continue;   // un hueco no es una barra de altura cero
                    }

                    $left = $this->x->at($this->categories[$row] ?? (string) $row) + $g * $slotWidth;

                    $base = $offsets[$key][$row] ?? 0.0;
                    $top = $this->y->at($base + $value);
                    $bottom = $this->y->at($base);

                    $offsets[$key][$row] = $base + $value;

                    $out[$markIndex][] = [
                        'index' => $row,
                        'x' => round($left, 2),
                        'w' => round(max($slotWidth, 0.01), 2),
                        'y' => round(min($top, $bottom), 2),
                        'h' => round(abs($bottom - $top), 2),
                        'negative' => $value < 0 ? 1 : 0,
                        'cap' => 0,
                    ];

                    // La punta de la columna es el ÚLTIMO segmento con valor, no el último
                    // declarado: si en un mes falta la serie de arriba, la punta es la de
                    // debajo. Y un 0 no cuenta — dibuja un segmento de altura sub-píxel, y
                    // redondearlo dejaría cuadrado el que sí se ve.
                    if ($value != 0.0) {
                        $cap[$key][$row] = [$markIndex, count($out[$markIndex]) - 1];
                    }
                }
            }
        }

        // Sólo la punta se redondea. Si cada segmento lleva su propio redondeo, la pila se ve
        // como una torre de piezas sueltas en vez de como UNA barra partida en tramos.
        foreach ($cap as $rows) {
            foreach ($rows as [$markIndex, $position]) {
                $out[$markIndex][$position]['cap'] = 1;
            }
        }

        return $out;
    }

    /** @param list<float|null> $values */
    private function donutSlices(Mark $mark, array $values): array
    {
        $slices = Arc::slices(
            array_map(fn ($v) => (float) ($v ?? 0.0), $values),
            innerRatio: $mark->innerRatio,
            padAngle: $mark->padAngle,
        );

        foreach ($slices as $i => $slice) {
            $slices[$i]['label'] = $this->categories[$i] ?? '';
            $slices[$i]['formatted'] = $this->format->apply($slice['value']);
            $slices[$i]['percent'] = round($slice['fraction'] * 100, 1);
            $slices[$i]['slot'] = Palette::slotFor((($i) % Palette::SLOTS) + 1);
        }

        return $slices;
    }

    /** @return list<array{medium: string, series: list<array<string, mixed>>}> */
    private function buildLayers(): array
    {
        $layers = [];

        foreach ($this->series as $serie) {
            $last = $layers === [] ? null : $layers[count($layers) - 1];

            if ($last !== null && $last['medium'] === $serie['medium']) {
                $layers[count($layers) - 1]['series'][] = $serie;

                continue;
            }

            $layers[] = ['medium' => $serie['medium'], 'series' => [$serie]];
        }

        return $layers;
    }

    /**
     * El payload del tooltip: columnar, como uPlot.
     *
     * Las etiquetas viajan YA FORMATEADAS desde PHP. Si viajaran números crudos, el
     * JavaScript tendría que saber de monedas, locales y separadores — o sea, habría que
     * portar `Format` a JS y mantener dos implementaciones sincronizadas para siempre. Así
     * el bundle no lleva ni un byte de `Intl`.
     *
     * Y no se emite si no hay tooltip: a 2.000 puntos el payload pesa 53 kB, más que el
     * propio <path>. Es una segunda copia del dato en el DOM.
     */
    public function payload(): array
    {
        return [
            'xs' => array_map(fn (int $i) => round($this->x->centerAt($i), 2), array_keys($this->categories)),
            'labels' => $this->categories,
            'series' => array_values(array_map(fn (array $s) => [
                'id' => $s['id'],
                'name' => $s['name'],
                'slot' => $s['slot'],
                'labels' => $s['labels'],
            ], array_filter($this->series, fn ($s) => $s['type'] !== 'donut'))),
        ];
    }

    /**
     * Los datos, en una tabla.
     *
     * Un `<svg>` es tan mudo para un lector de pantalla como un `<canvas>`. La respuesta
     * correcta a "cómo hago accesible un gráfico" es servir los datos en una tabla — y
     * nosotros los tenemos en PHP, así que sale gratis y sin un byte de JavaScript. Nadie
     * en el ecosistema lo hace: PrimeVue lo insinúa en su doc y no lo implementa; en
     * Filament es un issue de prioridad alta sin resolver.
     *
     * @return array{headers: list<string>, rows: list<array{label: string, values: list<string>}>}
     */
    public function table(int $maxRows = 500): array
    {
        $series = array_values(array_filter($this->series, fn ($s) => $s['type'] !== 'donut'));

        $rows = [];
        foreach ($this->categories as $i => $category) {
            if ($i >= $maxRows) {
                break;
            }

            $rows[] = [
                'label' => $category,
                'values' => array_map(fn (array $s) => $s['labels'][$i] ?? '—', $series),
            ];
        }

        return [
            'headers' => array_map(fn (array $s) => $s['name'], $series),
            'rows' => $rows,
            'truncated' => count($this->categories) > $maxRows,
        ];
    }
}
