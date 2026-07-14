<?php

namespace KoreUi\Charts;

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

    public bool $grid = true;

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

    /** @return list<string> */
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
