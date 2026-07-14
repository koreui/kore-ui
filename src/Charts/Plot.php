<?php

namespace KoreUi\Charts;

use KoreUi\Charts\Marks\Mark;
use KoreUi\Charts\Scales\BandScale;
use KoreUi\Charts\Scales\LinearScale;
use KoreUi\Charts\Scales\LinearXScale;
use KoreUi\Charts\Scales\TimeScale;
use KoreUi\Charts\Scales\XScale;
use KoreUi\Charts\Time\TimeFormat;

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

    /**
     * El eje X.
     *
     * ⚠️ Hasta 1.6.0 esto era un `BandScale` **concreto**, y por tanto la posición de un punto
     * era su ORDINAL en el array. Con categorías es lo correcto; con fechas es una mentira. Ver
     * `KoreUi\Charts\Scales\XScale`.
     */
    public readonly XScale $x;

    /** @var list<string> */
    public readonly array $categories;

    /** @var list<array<string, mixed>> */
    public readonly array $series;

    /** @var list<array{medium: string, series: list<array<string, mixed>>}> */
    public readonly array $layers;

    /** @var list<array{value: float, label: string, pos: float}> */
    public readonly array $yTicks;

    /** @var list<array{label: string, context: string|null, pos: float, width: float, room: float}> */
    public readonly array $xTicks;

    public readonly float $zero;

    public function __construct(
        public readonly ChartFrame $frame,
        private readonly Format $format = new Format,
        private readonly int $tickCount = 5,
        private readonly float $barPadding = 0.2,
        private readonly int $maxXLabels = 12,
        ?TimeFormat $timeFormat = null,
        private readonly ?int $xTickCount = null,
        private readonly int $continuousXTicks = 6,
        /**
         * El dominio del eje Y, fijado a mano.
         *
         * `Domain::fromSeries()` los aceptaba desde el primer día y los trata como un CONTRATO —no
         * los redondea por debajo de lo que pides—, pero nadie se los pasaba nunca. En un gráfico
         * en vivo dejan de ser un lujo: un eje que se reescala cada dos segundos porque el dato
         * subió un punto es ilegible.
         */
        private readonly ?float $yMin = null,
        private readonly ?float $yMax = null,
        /**
         * El tramo visible, en % del dominio COMPLETO: `[0, 100]` es todo.
         *
         * Dos números, no dos fechas — y eso es lo que hace que el zoom no necesite ni una escala
         * en JavaScript. Ver `Scales\XScale::window()`.
         *
         * @var array{0: float, 1: float}|null
         */
        public readonly ?array $window = null,
    ) {
        // Antes de calcular nada: una marca que no se va a pintar se avisa, no se descarta.
        $frame->validate();

        $marks = $frame->marks();
        $type = $frame->xScaleType();

        // El X crudo, y el orden en que hay que leer las filas.
        //
        // ⚠️ En una escala continua, el orden del array ES el orden de dibujado: `Path::line()`
        // une los puntos tal como vienen. Una serie de fechas sin ordenar no dibuja una línea,
        // dibuja un garabato que va y vuelve en el tiempo — y la búsqueda binaria del tooltip,
        // que asume `xs` ascendente, devolvería cualquier cosa. Con categorías no aplica: ahí el
        // orden en que las escribes ES el eje, y reordenarlo sería cambiarle el gráfico a quien
        // no ha pedido nada.
        $xRaw = $type === 'time' ? $frame->xDates() : $frame->xRaw();
        $order = $this->rowOrder($type, $xRaw);

        $xRaw = $this->reorder($xRaw, $order);
        $values = array_map(
            fn (Mark $mark) => $this->reorder($frame->values($mark), $order),
            $marks,
        );

        // El eje X primero, y con la ventana ya aplicada. El orden importa: el dominio del eje Y
        // se calcula sobre las filas que SE VEN, así que hay que saber cuáles son antes.
        $x = $this->buildXScale($type, $xRaw, $timeFormat ?? new TimeFormat);

        if ($window !== null) {
            $x = $x->window((float) $window[0], (float) $window[1]);
        }

        $this->x = $x;

        // ⚠️ Al ampliar un tramo, el eje Y se REESCALA sobre lo que se ve.
        //
        // Si no, ampliar una semana de un año deja el gráfico aplastado contra el suelo: el eje
        // sigue llegando hasta el máximo anual, que ya no está en pantalla. Es lo que ECharts
        // llama `filterMode: 'filter'`, y es la decisión de diseño del zoom, no un detalle.
        //
        // Las filas de fuera NO se borran —el trazo tiene que seguir saliendo por el borde—: solo
        // se enmascaran para el dominio.
        // El dominio de una cascada NO es el de sus saltos: es el de la SUMA CORRIDA. Una barra
        // flota hasta `acumulado + salto`, que puede pasar de largo cualquier salto suelto. Es el
        // mismo problema que un apilado, un piso al lado.
        $domainInput = $frame->hasWaterfall()
            ? $this->waterfallBreakpoints($frame, $marks, $values)
            : $this->stackedValues($frame, $window === null ? $values : $this->maskOutside($values));

        $this->domain = Domain::fromSeries(
            $domainInput,
            includeZero: $frame->requiresZero(),
            min: $yMin,
            max: $yMax,
            tickCount: $tickCount,
        );

        $this->empty = $frame->isEmpty() || $this->domain->empty;

        $this->y = LinearScale::vertical($this->domain->toArray());

        // Las etiquetas de fila (tooltip y tabla accesible). En un eje temporal salen de la
        // escala, ya formateadas y enteras: «14 feb 2026», no «14» — un tooltip habla de un punto
        // concreto y fuera de todo contexto.
        $this->categories = $this->x instanceof TimeScale
            ? array_map(fn (int $row) => $this->x->labelAt($row), array_keys($xRaw))
            : $this->reorder($frame->categories(), $order);

        $this->zero = $this->y->zero();

        $this->yTicks = $this->buildYTicks();

        // ⚠️ El número de ticks del eje X NO significa lo mismo en las dos escalas, y confundirlo
        // sale caro:
        //
        //   - En una BANDA es un TOPE: hay N categorías y se pintan como mucho `maxXLabels`,
        //     saltando el resto. Doce está bien: si sobran, se saltan más.
        //   - En una escala CONTINUA es un OBJETIVO: pedir doce ticks para una semana da uno cada
        //     doce horas, y se pisan unos con otros. Medido en un móvil: catorce etiquetas
        //     solapadas en un gráfico de cinco puntos.
        //
        // Así que el defecto de una escala continua es más bajo, y a propósito. Cuando las
        // etiquetas no caben, la respuesta de un gráfico que no puede medir texto es **pedir
        // menos ticks**, nunca truncarlos ni rotarlos.
        $this->xTicks = $this->addLabelRoom($this->x->ticks(
            $this->xTickCount ?? ($this->x instanceof BandScale ? $this->maxXLabels : $this->continuousXTicks),
        ));

        $this->series = $this->buildSeries($marks, $values);
        $this->layers = $this->buildLayers();
    }

    /**
     * Cuánto sitio tiene cada etiqueta del eje X antes de chocar con su vecina, en % del área.
     *
     * El servidor no puede medir texto, pero sí sabe DÓNDE cae cada tick — y con eso basta para
     * que dos etiquetas no se pisen: cada una se acota a la distancia hasta su vecina más cercana.
     * Una etiqueta larga («Coste de ventas», «/api/pedidos») se recorta con puntos suspensivos en
     * vez de meterse encima de la de al lado.
     *
     * Es la pieza que faltaba. El `width` (en `ch`) ya evitaba que una etiqueta se saliera de la
     * TARJETA; esto evita que se solape con la de al lado — que es un choque distinto, y el que se
     * veía en un móvil con categorías largas o con un eje de tiempo denso.
     *
     * El 0,9 deja un respiro: dos etiquetas centradas y pegadas a su vecina se tocarían justo en
     * el borde, y un pelín de aire se lee mucho mejor.
     *
     * @param  list<array{label: string, context: string|null, pos: float, width: float}>  $ticks
     * @return list<array{label: string, context: string|null, pos: float, width: float, room: float}>
     */
    private function addLabelRoom(array $ticks): array
    {
        $n = count($ticks);

        foreach ($ticks as $i => $tick) {
            $gaps = [];

            if ($i > 0) {
                $gaps[] = abs($tick['pos'] - $ticks[$i - 1]['pos']);
            }

            if ($i < $n - 1) {
                $gaps[] = abs($ticks[$i + 1]['pos'] - $tick['pos']);
            }

            // Un solo tick tiene toda la anchura para él.
            $ticks[$i]['room'] = $gaps === [] ? 100.0 : round(min($gaps) * 0.9, 2);
        }

        return $ticks;
    }

    /**
     * En qué orden hay que leer las filas para que el eje X vaya de menos a más.
     *
     * Con categorías, la identidad: el orden que escribiste es el eje.
     *
     * @param  list<mixed>  $xRaw
     * @return list<int>
     */
    private function rowOrder(string $type, array $xRaw): array
    {
        $order = array_keys($xRaw);

        if ($type === 'band' || $order === []) {
            return $order;
        }

        usort($order, function (int $a, int $b) use ($xRaw) {
            $x = $xRaw[$a];
            $y = $xRaw[$b];

            // Una fila sin X no se puede colocar: al final, donde no estorba.
            if ($x === null || $y === null) {
                return ($x === null ? 1 : 0) <=> ($y === null ? 1 : 0);
            }

            return $x <=> $y;
        });

        return $order;
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @param  list<int>  $order
     * @return list<T>
     */
    private function reorder(array $items, array $order): array
    {
        return array_map(fn (int $row) => $items[$row] ?? null, $order);
    }

    /**
     * Los valores de las filas que quedan fuera de la ventana, a `null`.
     *
     * **Solo para calcular el dominio del eje Y.** Las filas siguen ahí y siguen dibujándose: el
     * recorte del zoom es visual (`clip-path`), no de dato — si se borraran, el trazo se cortaría
     * en seco contra el borde en vez de salir por él, y se vería un escalón donde no lo hay.
     *
     * @param  list<list<float|null>>  $values
     * @return list<list<float|null>>
     */
    private function maskOutside(array $values): array
    {
        return array_map(
            fn (array $serie) => array_map(
                fn ($value, int $row) => $this->isVisible($row) ? $value : null,
                $serie,
                array_keys($serie),
            ),
            $values,
        );
    }

    private function isVisible(int $row): bool
    {
        $pos = $this->x->positionAt($row);

        return $pos !== null && $pos >= -0.01 && $pos <= 100.01;
    }

    /**
     * Qué escala rige el eje X.
     *
     * @param  list<mixed>  $xRaw
     */
    private function buildXScale(string $type, array $xRaw, TimeFormat $timeFormat): XScale
    {
        // El padding solo existe si hay barras: una línea no necesita hueco a los lados.
        $padding = $this->frame->usesBands() ? $this->barPadding : 0.0;

        $present = array_values(array_filter($xRaw, fn ($value) => $value !== null));

        // Sin ni un valor de X no hay escala continua que construir. Se cae a bandas, que es lo
        // que hace un gráfico vacío de todas formas.
        if ($type !== 'band' && $present !== []) {
            if ($type === 'time') {
                return new TimeScale($xRaw, min($present), max($present), $padding, $timeFormat);
            }

            $numbers = array_map(fn ($value) => is_numeric($value) ? (float) $value : null, $xRaw);
            $finite = array_values(array_filter($numbers, fn ($v) => $v !== null && is_finite($v)));

            if ($finite !== []) {
                return new LinearXScale($numbers, min($finite), max($finite), $padding, $this->format);
            }
        }

        // Con barras, el eje X son BANDAS y la línea se ancla al centro de la suya, o no
        // coincidiría con ellas. Sin barras, los puntos se reparten de borde a borde: si se
        // anclaran al centro de una banda imaginaria, media banda quedaría vacía a cada lado
        // y con 6 categorías se perdería el 16 % del ancho del gráfico.
        return new BandScale(
            array_map('strval', $xRaw),
            padding: $padding,
            point: ! $this->frame->usesBands(),
        );
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
    /**
     * Cuánto mide la canaleta del eje Y, en `ch`.
     *
     * El servidor no puede medir texto, pero sí contar caracteres — y `ch` deja que el
     * navegador haga la conversión con la fuente real. Lo que NO se puede hacer es contar
     * cada carácter como un `ch`: medido a 12px (1ch = 7,56px), un dígito mide 7,56 (=1ch,
     * exacto, porque las etiquetas usan `tabular-nums` y ahí todas las cifras miden lo que la
     * cifra "0", que es la definición de `ch`), pero el punto mide 3,56 (0,47ch) y el espacio
     * 3,38 (0,45ch). Contándolos como 1ch, "7.000 €" pedía 7,5ch cuando ocupa 5,9 — y la
     * canaleta se comía 31px de nada, empujando el gráfico entero a la derecha.
     *
     * Ojo con el "%": mide 1,47ch, MÁS que un dígito. Contarlo como 1 se quedaría corto y la
     * etiqueta se saldría por la izquierda, fuera de la tarjeta.
     *
     * Los pesos son cotas superiores de lo medido, no lo medido: la fuente la pone la app, no
     * nosotros. Pasarse un par de píxeles es barato; quedarse corto saca la etiqueta de la
     * tarjeta.
     *
     * ⚠️ Esto sólo cuadra si el `ch` se resuelve con la MISMA fuente con la que se pinta la
     * etiqueta. Por eso `.kore-chart-gutter-y` fija su propio `font-size: 0.75rem` en el CSS:
     * heredaba los 16px del contenedor y resolvía 1ch = 10,08px en vez de 7,56 — un 33% de
     * más, encima de lo anterior.
     */
    public function yGutter(): float
    {
        $longest = 0.0;

        foreach ($this->yTicks as $tick) {
            $longest = max($longest, TextWidth::ch($tick['label']));
        }

        // El mínimo evita una canaleta ridícula cuando las etiquetas son de un solo dígito.
        return round(max($longest, 2.0), 2);
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
                $x = $this->x->positionAt($row);

                // Un hueco es un hueco venga de donde venga: de que no haya valor, o de que la
                // fila no traiga fecha. En los dos casos el trazo se parte; colocar la fila sin
                // fecha en el 0 dibujaría un pico contra el eje Y que nunca existió.
                $points[] = ($value === null || $x === null)
                    ? null
                    : [round($x, 2), round($this->y->at($value), 2)];
            }

            $points = $this->breakOnGaps($points, $mark);

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

            if ($mark->type() === 'gauge') {
                $serie['gauge'] = $this->gaugeGeometry($mark, $serieValues);
            }

            if ($mark->type() === 'funnel') {
                $serie['funnel'] = $this->funnelStages($serieValues);
                $serie['highlight'] = $mark->highlight;
            }

            if ($mark->type() === 'waterfall') {
                [$serie['bars'], $serie['connectors']] = $this->layoutWaterfall($mark, $serieValues);

                // La etiqueta de una barra es lo que ESA barra vale, no siempre su salto: en un
                // total, la etiqueta es el acumulado. Sale del layout, que ya lo ha calculado.
                $serie['labels'] = array_map(
                    fn (array $bar) => $this->format->apply($bar['value'], $decimals),
                    $serie['bars'],
                );
                $serie['connectors_on'] = $mark->connectors;
            }

            $out[] = $serie;
        }

        return $out;
    }

    /**
     * Parte el trazo donde el hueco entre dos puntos sea mayor que `max-gap`.
     *
     * ## Por qué hace falta, si un `null` ya partía la línea
     *
     * Porque un `null` es una fila que **existe y no tiene valor**. Lo que no partía nada era una
     * fila que sencillamente **no está**: ahí la línea cruzaba el hueco dibujando una curva suave
     * por encima de un rato en el que no hubo dato. Y con `curve="monotone"`, además, se inventaba
     * un swoop que *parece* dato.
     *
     * Es la misma mentira que arregló el eje temporal, un piso más arriba: entonces el hueco
     * desaparecía porque los puntos se colocaban por su ordinal; ahora el hueco **se ve**, pero el
     * trazo lo tapa.
     *
     * ## Cómo
     *
     * Se mete un `null` ENTRE los dos puntos. `Path::line()` ya sabe partir ahí —y la curva
     * monótona ya reinicia sus tangentes— así que no hay geometría nueva: sólo hay que decirle
     * dónde.
     *
     * El umbral viene en unidades del EJE (segundos en uno temporal), así que se convierte a
     * porcentaje una sola vez: el dominio ya está en el espacio 0–100.
     *
     * @param  list<array{0: float, 1: float}|null>  $points
     * @return list<array{0: float, 1: float}|null>
     */
    private function breakOnGaps(array $points, Mark $mark): array
    {
        if ($mark->maxGap === null || $mark->medium() !== Mark::SVG) {
            return $points;
        }

        // Cuánto es el umbral, en % del área. En una escala de bandas no hay respuesta posible:
        // las categorías son equidistantes por definición y no hay hueco que medir. `validate()`
        // ya lo ha impedido, pero por si alguien construye el Plot a mano.
        $span = $this->x->domainSpan();

        if ($span === null || $span <= 0.0) {
            return $points;
        }

        $threshold = ($mark->maxGap / $span) * 100.0;

        $out = [];
        $previous = null;

        foreach ($points as $point) {
            if ($point !== null && $previous !== null && ($point[0] - $previous[0]) > $threshold) {
                $out[] = null;   // aquí no hubo dato, y la línea no va a fingir que sí
            }

            $out[] = $point;

            if ($point !== null) {
                $previous = $point;
            }
        }

        return $out;
    }

    /**
     * A qué nivel deja la suma corrida una etapa de la cascada.
     *
     * Un SALTO la mueve por su valor. Un TOTAL la fija a un absoluto:
     *
     *  - si traes el valor (el acumulado que ya calculaste en tu SQL), se usa ése — así el primer
     *    total puede ser el saldo de apertura y no queda clavado en cero;
     *  - si no lo traes (`0` o `null`), se usa el acumulado hasta aquí — así el total final sale
     *    solo, sin que tengas que repetir la suma.
     *
     * Es la regla de Excel, pero perdonando el caso más común: dejar el total final vacío.
     */
    private function waterfallLevel(float $running, float|null|int $delta, bool $isTotal): float
    {
        if (! $isTotal) {
            return $running + (float) ($delta ?? 0.0);
        }

        $own = (float) ($delta ?? 0.0);

        return $own != 0.0 ? $own : $running;
    }

    /**
     * Los valores que el DOMINIO de una cascada tiene que abarcar: el cero y cada acumulado.
     *
     * @param  list<Mark>  $marks
     * @param  list<list<float|null>>  $values
     * @return list<list<float|null>>
     */
    private function waterfallBreakpoints(ChartFrame $frame, array $marks, array $values): array
    {
        foreach ($marks as $i => $mark) {
            if ($mark->type() !== 'waterfall') {
                continue;
            }

            $totals = $frame->waterfallTotals($mark);
            $running = 0.0;
            $out = [0.0];

            foreach ($values[$i] as $row => $delta) {
                $running = $this->waterfallLevel($running, $delta, $totals[$row] ?? false);
                $out[] = $running;
            }

            return [$out];
        }

        return $values;
    }

    /**
     * Dónde va cada barra de una cascada, y los conectores entre ellas.
     *
     * Cada barra FLOTA: empieza donde acabó la anterior. La geometría es la misma barra flotante
     * que `layoutBars()` ya calculaba para un apilado —`at($base + $valor)`—, sólo que aquí la base
     * es la suma corrida.
     *
     *  - Una SUBIDA va del acumulado al acumulado + salto, en verde.
     *  - Una BAJADA, ídem con el salto negativo, en rojo.
     *  - Un TOTAL va del cero al acumulado, en neutro, y no mueve la suma. Es un descansillo.
     *
     * El conector entre dos barras vive en el nivel donde se tocan —la suma corrida en esa
     * frontera— así que enlaza el final del flujo de una con el principio de la siguiente.
     *
     * @param  list<float|null>  $deltas
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, float>>}
     */
    private function layoutWaterfall(Marks\WaterfallMark $mark, array $deltas): array
    {
        $totals = $this->frame->waterfallTotals($mark);
        $bandwidth = $this->x->bandwidth();

        $running = 0.0;
        $bars = [];
        $flow = [];   // [row => [center, nivelDelFlujoDespuésDeEstaBarra]]

        foreach ($deltas as $row => $delta) {
            $center = $this->x->positionAt($row);

            if ($center === null) {
                continue;
            }

            if ($totals[$row] ?? false) {
                $from = 0.0;
                $to = $this->waterfallLevel($running, $delta, true);
                $running = $to;
                $variant = 'total';
                $shown = $to;
            } else {
                $d = (float) ($delta ?? 0.0);
                $from = $running;
                $to = $running + $d;
                $running = $to;
                $variant = $d >= 0.0 ? 'up' : 'down';
                $shown = $d;
            }

            $yTop = $this->y->at(max($from, $to));
            $yBottom = $this->y->at(min($from, $to));

            $bars[] = [
                'index' => $row,
                'x' => round($center - $bandwidth / 2, 2),
                'w' => round(max($bandwidth, 0.01), 2),
                'y' => round($yTop, 2),
                // Un salto de cero mide sub-píxel: se le da un mínimo para que la línea del cambio
                // nulo no desaparezca. `layoutBars` no lo necesita porque un 0 no dibuja barra; aquí
                // sí, porque una etapa sin cambio sigue siendo una etapa.
                'h' => round(max(abs($yBottom - $yTop), 0.4), 2),
                'variant' => $variant,
                'value' => $shown,
                'cap' => 1,
            ];

            $flow[] = [$center, $this->y->at($running)];
        }

        return [$bars, $mark->connectors ? $this->waterfallConnectors($flow, $bandwidth) : []];
    }

    /**
     * Las líneas que enlazan una barra con la siguiente, al nivel donde se tocan.
     *
     * @param  list<array{0: float, 1: float}>  $flow  [centro, nivelY del flujo] por barra dibujada
     * @return list<array{x: float, w: float, y: float}>
     */
    private function waterfallConnectors(array $flow, float $bandwidth): array
    {
        $out = [];

        for ($i = 1, $n = count($flow); $i < $n; $i++) {
            [$centerA] = $flow[$i - 1];
            [$centerB] = $flow[$i];
            $level = $flow[$i - 1][1];   // el flujo DESPUÉS de la barra A es donde arranca la B

            $x1 = $centerA + $bandwidth / 2;
            $x2 = $centerB - $bandwidth / 2;

            $out[] = [
                'x' => round($x1, 2),
                'w' => round(max($x2 - $x1, 0.0), 2),
                'y' => round($level, 2),
            ];
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
                    $center = $this->x->positionAt($row);

                    if ($value === null || $center === null) {
                        continue;   // un hueco no es una barra de altura cero
                    }

                    // Desde el CENTRO del dato, no desde el borde de su banda: en una escala
                    // continua no hay banda de la que salir. (Antes se buscaba la posición por
                    // el TEXTO de la categoría, y `array_flip` se queda con la última ocurrencia:
                    // dos filas con la misma etiqueta apilaban sus barras en el mismo sitio, sin
                    // avisar. Ese bug muere aquí.)
                    $left = $center - $bandwidth / 2 + $g * $slotWidth;

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

    /**
     * La geometría de un gauge: el arco de fondo, el arco del valor, y el número.
     *
     * Un gauge enseña UN número —el de la primera fila con dato—, así que aquí no hay serie: hay un
     * valor, su fracción del rango, y dos arcos. La trigonometría la pone `Arc::open()`; esto solo
     * decide los ángulos y el color de la banda.
     *
     * @param  list<float|null>  $values
     * @return array<string, mixed>
     */
    private function gaugeGeometry(Marks\GaugeMark $mark, array $values): array
    {
        // El primer valor real. Un gauge es un número; si le das una columna con muchas filas, se
        // queda con la primera que tenga dato.
        $value = 0.0;
        foreach ($values as $v) {
            if ($v !== null) {
                $value = (float) $v;
                break;
            }
        }

        $span = $mark->max - $mark->min;
        $fraction = $span > 0 ? max(0.0, min(1.0, ($value - $mark->min) / $span)) : 0.0;

        // El arco es simétrico respecto a las 12 en punto: el hueco queda centrado abajo.
        $sweep = deg2rad($mark->sweep);
        $start = -M_PI / 2 - $sweep / 2;
        $radius = 40.0;

        $color = $mark->colorFor($value);

        // Los decimales que hagan falta para no mentir: un SLA de 99,2 no puede salir «99». Salen
        // del propio valor, como en el resto del módulo.
        $decimals = Format::decimalsFor([$value]);

        return [
            'value' => $value,
            'formatted' => $this->format->apply($value, $decimals),
            'fraction' => round($fraction * 100, 1),
            'caption' => $mark->caption,
            'track' => Arc::open($start, $start + $sweep, $radius),
            'arc' => Arc::open($start, $start + $fraction * $sweep, $radius),
            'color' => $color !== null ? Palette::resolve($mark->slot, $color) : Palette::token($mark->slot),
            // Unos pellizcos radiales donde empieza cada banda (dónde acaba el verde y empieza el
            // ámbar). Es lo que convierte un anillo decorativo en un gauge de verdad: sin ellos, un
            // «73» dentro de un arco no dice si eso está bien o mal.
            'ticks' => $this->gaugeTicks($mark, $start, $sweep, $radius),
        ];
    }

    /**
     * Los pellizcos radiales que marcan el principio de cada banda de color.
     *
     * @return list<array{x1: float, y1: float, x2: float, y2: float}>
     */
    private function gaugeTicks(Marks\GaugeMark $mark, float $start, float $sweep, float $radius): array
    {
        $span = $mark->max - $mark->min;

        if ($span <= 0.0) {
            return [];
        }

        $out = [];

        foreach (array_keys($mark->thresholds) as $upTo) {
            // La última cota es el final del arco, no una frontera interior: no se marca.
            if ($upTo <= $mark->min || $upTo >= $mark->max) {
                continue;
            }

            $angle = $start + (($upTo - $mark->min) / $span) * $sweep;

            $out[] = [
                'x1' => round(50.0 + ($radius - 6.0) * cos($angle), 2),
                'y1' => round(50.0 + ($radius - 6.0) * sin($angle), 2),
                'x2' => round(50.0 + ($radius + 6.0) * cos($angle), 2),
                'y2' => round(50.0 + ($radius + 6.0) * sin($angle), 2),
            ];
        }

        return $out;
    }

    /**
     * Las etapas de un embudo: un trapecio por cada una, que se estrecha hacia la siguiente.
     *
     * El ancho de un trapecio ES el valor de la etapa, en % del máximo. El estrechamiento entre
     * dos etapas es la CAÍDA, y la ponemos también en número: cuánto queda del primero (conversión)
     * y cuánto se pierde en este paso.
     *
     * @param  list<float|null>  $values
     * @return list<array<string, mixed>>
     */
    private function funnelStages(array $values): array
    {
        $nums = array_map(fn ($v) => (float) ($v ?? 0.0), $values);
        $count = count($nums);
        $max = $count > 0 ? max($nums) : 0.0;
        $first = $nums[0] ?? 0.0;

        if ($count === 0 || $max <= 0.0) {
            return [];
        }

        // Un hueco vertical fino entre etapas: las hace distinguibles sin romper la silueta.
        $gap = $count > 1 ? min(1.5, 40.0 / $count) : 0.0;
        $bandHeight = 100.0 / $count;

        $out = [];

        foreach ($nums as $i => $value) {
            $topWidth = ($value / $max) * 100.0;
            // El trapecio se estrecha hasta la etapa siguiente; la última es un rectángulo (su
            // propio ancho arriba y abajo), que es el recuento final.
            $bottomWidth = $i + 1 < $count ? (($nums[$i + 1] / $max) * 100.0) : $topWidth;

            $topHalf = $topWidth / 2;
            $bottomHalf = $bottomWidth / 2;

            $out[] = [
                'index' => $i,
                'label' => $this->categories[$i] ?? '',
                'value' => $value,
                'formatted' => $this->format->apply($value),
                // Conversión: cuánto queda del primero. Y caída: cuánto se pierde en este paso.
                'percent' => $first > 0 ? round(($value / $first) * 100, 1) : 0.0,
                'drop' => $i > 0 && $nums[$i - 1] > 0
                    ? round((($nums[$i - 1] - $value) / $nums[$i - 1]) * 100, 1)
                    : null,
                'top' => round($i * $bandHeight + $gap / 2, 2),
                'height' => round($bandHeight - $gap, 2),
                // El polígono del trapecio, en % de su propia caja (100 % ancho, `height` % alto).
                'clip' => sprintf(
                    'polygon(%s%% 0, %s%% 0, %s%% 100%%, %s%% 100%%)',
                    self::pct(50 - $topHalf),
                    self::pct(50 + $topHalf),
                    self::pct(50 + $bottomHalf),
                    self::pct(50 - $bottomHalf),
                ),
                // La rampa ORDINAL, repartida entre las etapas: la 1 es la más clara, la última la
                // más oscura. El color sólo dice «vas por aquí», no el valor — eso ya lo dice el ancho.
                'color' => Palette::ordinal(
                    $count > 1
                        ? (int) min(Palette::RAMP_STEPS, floor($i / ($count - 1) * (Palette::RAMP_STEPS - 1)) + 1)
                        : 1
                ),
            ];
        }

        return $out;
    }

    private static function pct(float $value): string
    {
        return rtrim(rtrim(number_format(round($value, 2), 2, '.', ''), '0'), '.') ?: '0';
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
    /**
     * Lo más estrecho que se puede poner la ventana del zoom, en % del dominio completo.
     *
     * Sin un suelo, se puede ampliar hasta un tramo más fino que la separación entre dos puntos
     * — y ahí no hay nada que dibujar. Se llega a algo como «viendo el 48,1 % – 48,3 %» y el
     * gráfico se queda vacío.
     *
     * El suelo son **dos separaciones medias**: lo justo para que quepa un segmento de línea, o
     * un par de barras. Lo calcula el servidor porque es el único que sabe cuántas filas hay.
     *
     * ⚠️ Esto **no garantiza** que la ventana tenga datos: en una serie con un hueco grande (un
     * sensor caído tres días) se puede ampliar dentro del hueco y no habrá nada. Y está bien que
     * así sea — el hueco es real. Lo que no puede pasar es que ahí **no haya cómo volver**, y de
     * eso se encarga el estado vacío, que sigue enseñando el botón de restablecer.
     */
    public function minWindow(): float
    {
        $rows = count($this->categories);

        if ($rows < 3) {
            return 100.0;   // con dos puntos o menos, ampliar no significa nada
        }

        return round(min(100.0, max(0.2, 200.0 / ($rows - 1))), 2);
    }

    public function payload(bool $series = true): array
    {
        // El tramo que se está viendo, en % del dominio COMPLETO.
        //
        // Es lo ÚNICO que el cliente necesita para componer un zoom sobre otro: si arrastras del
        // 20 % al 60 % de una vista que ya enseña [40, 80], la ventana nueva es una regla de tres.
        // Sin escalas, sin fechas, sin locales.
        //
        // Y va aquí, en el payload, y no en el `x-data`: el morph de Livewire reescribe el <script>
        // pero NO reinicializa el x-data, así que un `x-data` con la ventana dentro se quedaría con
        // la de antes del zoom.
        $out = [
            'window' => $this->window ?? [0.0, 100.0],
            // Lo más estrecho que el cliente puede pedir. Sin esto se amplía hasta un tramo más
            // fino que la separación entre dos puntos, y no queda nada que dibujar.
            'minWindow' => $this->minWindow(),
        ];

        // Un gráfico con zoom pero SIN tooltip no necesita el dato: solo la ventana. Y el dato es
        // caro — es una segunda copia entera en el DOM, y a 2.000 puntos pesa más que el <path>.
        if (! $series) {
            return $out;
        }

        // Ascendente por construcción: las filas se ordenaron por X en el constructor. La
        // búsqueda binaria del cliente depende de eso.
        $out['xs'] = array_map(
            fn (int $row) => round($this->x->positionAt($row) ?? 0.0, 2),
            array_keys($this->categories),
        );

        $out['labels'] = $this->categories;

        $out['series'] = array_values(array_map(fn (array $s) => [
            'id' => $s['id'],
            'name' => $s['name'],
            'slot' => $s['slot'],
            'labels' => $s['labels'],
        ], array_filter($this->series, fn ($s) => $s['type'] !== 'donut')));

        return $out;
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
