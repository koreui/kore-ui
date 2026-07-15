<?php

namespace KoreUi\Charts;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use KoreUi\Charts\Marks\Mark;

/**
 * Todo lo que un gráfico ha recogido de sus hijos, y las decisiones que se derivan de ello.
 *
 * El frame no dibuja: responde preguntas. Qué series hay, si el dominio necesita el cero,
 * de qué tipo es el eje X, y en qué orden hay que pintar las capas.
 */
final class ChartFrame
{
    /** @var list<Mark> */
    private array $marks = [];

    /** @var array<string, mixed> */
    public array $axes = ['x' => null, 'y' => null];

    /** @var array<string, mixed>|null */
    public ?array $legend = null;

    /** @var array<string, mixed>|null */
    public ?array $tooltip = null;

    /** @var array{model: string, slider: bool}|null */
    public ?array $zoom = null;

    /** @var array{every: int, call: string|null, transition: bool}|null */
    public ?array $stream = null;

    public bool $grid = true;

    /** `vertical` (defecto) u `horizontal`. Sólo afecta al dibujo de las barras, no al dato. */
    public string $orientation = 'vertical';

    public function __construct(
        public readonly string $id,
        public readonly array $data = [],
        public readonly ?string $x = null,
    ) {}

    public function add(Mark $mark): Mark
    {
        // El slot se asigna por ORDEN DE REGISTRO, jamás por índice de serie visible. Si se
        // asignara por índice, ocultar la serie 2 repintaría la 3 con el color de la 2 y el
        // lector creería estar mirando otra cosa.
        $mark->withSlot(Palette::slotFor(count($this->marks) + 1));

        $this->marks[] = $mark;

        return $mark;
    }

    /**
     * Las marcas que se pintan.
     *
     * ⚠️ **Es lo que hay que usar en todas partes menos en `add()`.** El slot de color se
     * asigna sobre TODAS las marcas registradas (en `add()`), pero el dibujo, el dominio del
     * eje, la leyenda, el payload y la tabla accesible sólo miran las visibles. Ésa es la
     * separación que hace que ocultar una serie con `:show="false"` **no le cambie el color
     * a las de detrás**.
     *
     * @return list<Mark>
     */
    public function marks(): array
    {
        return array_values(array_filter($this->marks, fn (Mark $mark) => $mark->visible));
    }

    public function isEmpty(): bool
    {
        // Si todas las marcas están ocultas, el gráfico está vacío: estado vacío, no un
        // lienzo en blanco con ejes.
        return $this->marks() === [] || $this->data === [];
    }

    /** ¿Alguna marca necesita el cero en el dominio? Basta una barra para que sí. */
    public function requiresZero(): bool
    {
        foreach ($this->marks() as $mark) {
            if ($mark->requiresZero()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Todas las marcas del gráfico comparten UNA escala X.
     *
     * Una barra necesita bandas; una línea sobre valores continuos necesita una escala
     * lineal. Si alguien mezcla las dos, no se adivina: se lanza. Adivinar aquí produce un
     * gráfico que se ve bien y miente.
     */
    public function hasBars(): bool
    {
        foreach ($this->marks() as $mark) {
            if ($mark->type() === 'bar') {
                return true;
            }
        }

        return false;
    }

    /**
     * ¿Hay algún trazo SVG (una línea, un área)?
     *
     * Importa para una sola cosa, y es de las que no se ven venir: **si hay trazo, en un gráfico
     * en vivo no se puede animar NADA**. El `<path>` no se puede animar —medido en los tres
     * motores: WebKit ni siquiera soporta `transition: d`— así que salta de golpe. Y todo lo que
     * se mueva despacio mientras el trazo salta **se despega de él**.
     *
     * Medido: con los puntos animados, el peor se iba a **8,36 % del área** de la curva sobre la
     * que se supone que está — unos 24 px. Se veía a la legua.
     */
    public function hasTrace(): bool
    {
        foreach ($this->marks() as $mark) {
            if ($mark->medium() === Mark::SVG && $mark->type() !== 'donut') {
                return true;
            }
        }

        return false;
    }

    public function hasWaterfall(): bool
    {
        foreach ($this->marks() as $mark) {
            if ($mark->type() === 'waterfall') {
                return true;
            }
        }

        return false;
    }

    /** ¿El gráfico se dibuja transpuesto (categoría a la izquierda, valor abajo)? */
    public function isHorizontal(): bool
    {
        return $this->orientation === 'horizontal';
    }

    /**
     * ¿El eje X es de bandas?
     *
     * Lo pide cualquier marca que ocupe una banda entera: las barras y las cascadas. Una línea
     * o un área, no — sus puntos se reparten de borde a borde.
     */
    public function usesBands(): bool
    {
        return $this->hasBars() || $this->hasWaterfall();
    }

    /**
     * Las filas que son TOTALES de una cascada.
     *
     * Un total va del cero hasta la suma acumulada y no mueve la suma corrida: es un descansillo,
     * no un salto. `null` en la columna → no es total.
     *
     * @return list<bool>
     */
    public function waterfallTotals(\KoreUi\Charts\Marks\WaterfallMark $mark): array
    {
        if ($mark->total === null) {
            return array_fill(0, count($this->data), false);
        }

        return array_map(
            fn ($row) => (bool) $this->raw($row, $mark->total),
            array_values($this->data),
        );
    }

    public function hasHeatmap(): bool
    {
        foreach ($this->marks() as $mark) {
            if ($mark->type() === 'heatmap') {
                return true;
            }
        }

        return false;
    }

    /**
     * Los valores CRUDOS de una columna cualquiera, una por fila del dato.
     *
     * Es lo que necesita un heatmap para la FILA de cada celda: el `x` del gráfico ya da la
     * columna, pero la fila viene de otra columna más.
     *
     * @return list<mixed>
     */
    public function column(?string $field): array
    {
        if ($field === null) {
            return array_fill(0, count($this->data), null);
        }

        return array_map(fn ($row) => $this->raw($row, $field), array_values($this->data));
    }

    public function hasFunnel(): bool
    {
        foreach ($this->marks() as $mark) {
            if ($mark->type() === 'funnel') {
                return true;
            }
        }

        return false;
    }

    public function hasGauge(): bool
    {
        foreach ($this->marks() as $mark) {
            if ($mark->type() === 'gauge') {
                return true;
            }
        }

        return false;
    }

    public function hasDonut(): bool
    {
        foreach ($this->marks() as $mark) {
            if ($mark->type() === 'donut') {
                return true;
            }
        }

        return false;
    }

    /**
     * Un donut no comparte gráfico con nada.
     *
     * Vive en su propio SVG cuadrado, sin ejes ni escalas cartesianas. Así que una línea, un
     * eje o un tooltip dentro de un donut **no se pintan**: hasta ahora se descartaban en
     * silencio, que es la peor respuesta posible — escribes una marca, el gráfico decide por
     * su cuenta que no vale, y sales creyendo que la tienes.
     *
     * (El tooltip, además, no le hace ninguna falta: la leyenda del donut ya imprime la
     * etiqueta, el valor y el porcentaje de cada porción de forma permanente.)
     *
     * Es la misma regla que ya aplicamos al mezclar escalas: no se adivina, se lanza.
     * Adivinar aquí produce un gráfico que se ve bien y miente.
     */
    public function validate(): void
    {
        // `max-gap` mide una DISTANCIA entre dos puntos, y entre dos categorías no hay distancia
        // que medir: son equidistantes por definición. Pedirlo ahí no es un matiz que se pueda
        // ignorar en silencio — es que el usuario cree que su gráfico se va a partir en los huecos
        // y no se va a partir nunca.
        foreach ($this->marks() as $mark) {
            if ($mark->maxGap !== null && $this->xScaleType() === 'band') {
                throw new InvalidArgumentException(
                    'koreUi: `max-gap` sólo tiene sentido en un eje continuo (fechas o números): mide la '
                    .'distancia entre dos puntos, y entre dos categorías no hay ninguna — son equidistantes '
                    .'por definición. Pásale fechas de verdad a `x`, o quita el `max-gap`.'
                );
            }
        }

        if ($this->hasHeatmap() && count($this->marks()) > 1) {
            throw new InvalidArgumentException(
                'koreUi: un mapa de calor (<x-kore::chart.heatmap>) llena una matriz de celdas — no comparte '
                .'gráfico con otras marcas. Sácalas a su propio <x-kore::chart>.'
            );
        }

        if ($this->hasFunnel() && count($this->marks()) > 1) {
            throw new InvalidArgumentException(
                'koreUi: un embudo (<x-kore::chart.funnel>) enseña las etapas de un proceso, una debajo de '
                .'otra — no comparte gráfico con otras marcas. Sácalas a su propio <x-kore::chart>.'
            );
        }

        if ($this->hasGauge() && count($this->marks()) > 1) {
            throw new InvalidArgumentException(
                'koreUi: un gauge (<x-kore::chart.gauge>) enseña un número contra un objetivo — no comparte '
                .'gráfico con otras marcas. Vive en su propio SVG cuadrado, sin ejes. Sácalas a su propio '
                .'<x-kore::chart>.'
            );
        }

        if ($this->hasWaterfall() && count($this->marks()) > 1) {
            throw new InvalidArgumentException(
                'koreUi: una cascada (<x-kore::chart.waterfall>) no comparte gráfico con otras marcas: cada '
                .'barra flota sobre la suma de las anteriores, así que superponerle una línea o unas barras '
                .'normales mezclaría dos sistemas de coordenadas. Sácalas a su propio <x-kore::chart>.'
            );
        }

        // Horizontal es una transposición de la capa de barras, no un motor nuevo: un trazo SVG (línea
        // o área) tendría que invertir cada coordenada de su `d`, y un donut/gauge no tiene ejes que
        // transponer. En vez de mantener dos geometrías del trazo, se acota a lo que la transposición
        // sí sabe hacer bien: barras (sueltas, agrupadas o apiladas).
        if ($this->isHorizontal()) {
            $otras = array_values(array_unique(array_filter(
                array_map(fn (Mark $mark) => $mark->type(), $this->marks()),
                fn (string $type) => $type !== 'bar',
            )));

            if ($otras !== []) {
                throw new InvalidArgumentException(
                    'koreUi: `orientation="horizontal"` sólo transpone barras ('.implode(', ', $otras).' no cabe). '
                    .'Una línea o un área tendría que invertir su trazo entero, y un donut o un gauge no tiene ejes '
                    .'que transponer. Deja esas marcas en un gráfico vertical.'
                );
            }
        }

        if (! $this->hasDonut()) {
            return;
        }

        if (count($this->marks()) > 1) {
            $otras = array_values(array_filter(
                array_map(fn (Mark $mark) => $mark->type(), $this->marks()),
                fn (string $type) => $type !== 'donut',
            ));

            throw new InvalidArgumentException(
                'koreUi: un donut no comparte gráfico con otras marcas ('.implode(', ', $otras).'): '
                .'vive en su propio SVG cuadrado, sin ejes ni escalas. Sácalas a su propio <x-kore::chart>.'
            );
        }

        if ($this->tooltip !== null) {
            throw new InvalidArgumentException(
                'koreUi: el donut no lleva <x-kore::chart.tooltip>, y no es un olvido: su leyenda ya imprime '
                .'la etiqueta, el valor y el porcentaje de cada porción. Al posarte sobre un arco se enciende '
                .'su fila de la leyenda, sin nada de JavaScript.'
            );
        }

        if ($this->axes['x'] !== null || $this->axes['y'] !== null) {
            throw new InvalidArgumentException(
                'koreUi: un donut no tiene ejes, así que <x-kore::chart.axis-x> y <x-kore::chart.axis-y> no pintan nada.'
            );
        }

        if ($this->zoom !== null) {
            throw new InvalidArgumentException(
                'koreUi: un donut no lleva <x-kore::chart.zoom>. El zoom recorta un tramo del eje X, y un donut '
                .'no tiene eje X: sus porciones son un reparto, no una secuencia.'
            );
        }
    }

    /**
     * Las capas de dibujo, en orden y agrupadas por medio.
     *
     * El contrato de una API de marcas es que **el orden de las marcas es el orden de
     * pintado**: si escribes la barra y luego la línea, la línea va encima. Pero las barras
     * son HTML y las líneas son SVG, que viven en planos distintos. La solución es emitir
     * una capa por cada TRAMO CONTIGUO de marcas del mismo medio (svg, html, svg...), todas
     * en `position:absolute` — el orden del DOM es el orden de pintado.
     *
     * @return list<array{medium: string, marks: list<Mark>}>
     */
    public function layers(): array
    {
        $layers = [];

        foreach ($this->marks() as $mark) {
            $last = $layers === [] ? null : $layers[count($layers) - 1];

            if ($last !== null && $last['medium'] === $mark->medium()) {
                $layers[count($layers) - 1]['marks'][] = $mark;

                continue;
            }

            $layers[] = ['medium' => $mark->medium(), 'marks' => [$mark]];
        }

        return $layers;
    }

    /**
     * Los valores de una serie, alineados con las categorías del eje X.
     *
     * @return list<float|null>
     */
    public function values(Mark $mark): array
    {
        return array_map(
            fn ($row) => $this->value($row, $mark->field),
            array_values($this->data),
        );
    }

    /**
     * Las etiquetas del eje X, una por fila.
     *
     * ⚠️ **El `(string)` de aquí es donde muere cualquier tipo que no sea una categoría.** Un
     * `Carbon`, un `DateTime`, un entero: todo sale convertido en texto, y a partir de ahí el
     * gráfico solo sabe colocarlo por su ORDINAL. Por eso `xRaw()` existe: la escala necesita
     * el valor, no su nombre.
     *
     * @return list<string>
     */
    public function categories(): array
    {
        if ($this->x === null) {
            return array_map('strval', array_keys(array_values($this->data)));
        }

        return array_map(
            fn ($row) => (string) ($this->raw($row, $this->x) ?? ''),
            array_values($this->data),
        );
    }

    /**
     * El X de cada fila, SIN convertir.
     *
     * Es lo que necesita una escala continua: la fecha, no su etiqueta.
     *
     * @return list<mixed>
     */
    public function xRaw(): array
    {
        if ($this->x === null) {
            return array_keys(array_values($this->data));
        }

        return array_map(
            fn ($row) => $this->raw($row, $this->x),
            array_values($this->data),
        );
    }

    /**
     * El X de cada fila como fecha inmutable, o null si esa fila no la tiene.
     *
     * La zona horaria importa y no es cosmética: un pedido de las 23:30 en Madrid es de las
     * 22:30 en UTC, o sea de **otro día**. Un gráfico diario que lea las fechas en UTC pone ese
     * pedido en la barra de ayer. Por eso `timezone` es una prop del eje, y por eso la
     * conversión se hace aquí, una vez, antes de que nadie mire el calendario.
     *
     * Sin `timezone`, se respeta la que traiga el dato. Eloquent las entrega en la zona de la
     * aplicación, así que en el caso normal ya viene bien.
     *
     * @return list<DateTimeImmutable|null>
     */
    public function xDates(): array
    {
        $name = $this->axes['x']['timezone'] ?? null;
        $zone = $name === null ? null : new DateTimeZone($name);

        return array_map(
            function ($value) use ($zone) {
                if (! $value instanceof DateTimeInterface) {
                    return null;
                }

                $date = DateTimeImmutable::createFromInterface($value);

                return $zone === null ? $date : $date->setTimezone($zone);
            },
            $this->xRaw(),
        );
    }

    /**
     * De qué tipo es el eje X: `band`, `time` o `linear`.
     *
     * `auto` (el defecto) detecta fechas y solo fechas. **Nunca promociona a `linear` por su
     * cuenta**, y eso es deliberado: unos años escritos como enteros (2022, 2023, 2024) son
     * *categorías*, no una recta numérica, y colocarlos en una escala lineal cambiaría el
     * gráfico de quien no ha pedido nada. `linear` hay que escribirlo.
     */
    public function xScaleType(): string
    {
        // Un donut no tiene ejes. `validate()` ya lo prohíbe, pero el Plot pregunta antes.
        if ($this->hasDonut()) {
            return 'band';
        }

        $requested = $this->axes['x']['scale'] ?? 'auto';

        if (! in_array($requested, ['auto', 'band', 'time', 'linear'], true)) {
            throw new InvalidArgumentException(
                "koreUi: «{$requested}» no es una escala de eje X. Las que hay: auto, band, time, linear."
            );
        }

        if ($requested === 'auto') {
            return $this->looksTemporal() ? 'time' : 'band';
        }

        if ($requested === 'time' && ! $this->looksTemporal()) {
            throw new InvalidArgumentException(
                'koreUi: has pedido un eje X temporal, pero la columna «'.($this->x ?? '?').'» no trae fechas. '
                .'Pasa objetos DateTime o Carbon, no cadenas ya formateadas: si le das el texto, el gráfico solo '
                .'puede colocar los puntos por su orden, y los huecos del calendario desaparecen.'
            );
        }

        return $requested;
    }

    /** Hay fechas, y todas las filas que tienen X la tienen. */
    private function looksTemporal(): bool
    {
        if ($this->x === null || $this->data === []) {
            return false;
        }

        $found = false;

        foreach ($this->xRaw() as $value) {
            if ($value === null) {
                continue;
            }

            if (! $value instanceof DateTimeInterface) {
                return false;
            }

            $found = true;
        }

        return $found;
    }

    /** Los apilados, agrupados por nombre de pila. @return array<string, list<Mark>> */
    public function stacks(): array
    {
        $stacks = [];

        foreach ($this->marks() as $mark) {
            if ($mark->type() === 'bar' && $mark->stack !== null) {
                $stacks[$mark->stack][] = $mark;
            }
        }

        return $stacks;
    }

    private function value(mixed $row, string $field): ?float
    {
        $raw = $this->raw($row, $field);

        if ($raw === null || ! is_numeric($raw) || ! is_finite((float) $raw)) {
            return null;
        }

        return (float) $raw;
    }

    /** Acepta arrays, objetos y modelos Eloquent — y una Closure como accesor. */
    private function raw(mixed $row, string|callable $field): mixed
    {
        if (is_callable($field) && ! is_string($field)) {
            return $field($row);
        }

        if (is_array($row)) {
            return $row[$field] ?? null;
        }

        if (is_object($row)) {
            return $row->{$field} ?? null;
        }

        throw new InvalidArgumentException(
            'koreUi: cada fila de :data tiene que ser un array o un objeto; se ha recibido '.gettype($row).'.'
        );
    }
}
