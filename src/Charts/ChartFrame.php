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

    /** @return list<Mark> */
    public function marks(): array
    {
        return $this->marks;
    }

    public function isEmpty(): bool
    {
        return $this->marks === [] || $this->data === [];
    }

    /** ¿Alguna marca necesita el cero en el dominio? Basta una barra para que sí. */
    public function requiresZero(): bool
    {
        foreach ($this->marks as $mark) {
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
        foreach ($this->marks as $mark) {
            if ($mark->type() === 'bar') {
                return true;
            }
        }

        return false;
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

        foreach ($this->marks as $mark) {
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

        foreach ($this->marks as $mark) {
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
