<?php

namespace KoreUi\Charts\Scales;

/**
 * Categorías → bandas del eje, en el espacio 0–100.
 *
 * Una barra ocupa una banda; una línea sobre un eje de categorías se ancla al CENTRO de la
 * banda. Si no, la línea y las barras del mismo gráfico no coincidirían.
 */
final class BandScale implements XScale
{
    use TickBox;

    private float $step;

    private float $bandwidth;

    /** @var array<string, int> */
    private array $index;

    /**
     * @param  list<string>  $categories
     * @param  float  $padding  proporción del paso que queda libre entre bandas (0–1)
     * @param  bool  $point  ver `centerAt()`
     */
    public function __construct(
        public readonly array $categories,
        public readonly float $padding = 0.2,
        public readonly float $rangeMin = 0.0,
        public readonly float $rangeMax = 100.0,
        public readonly bool $point = false,
    ) {
        $count = max(count($categories), 1);
        $this->step = ($rangeMax - $rangeMin) / $count;
        $this->bandwidth = $this->step * (1.0 - max(0.0, min(0.95, $padding)));
        $this->index = array_flip(array_values($categories));
    }

    /** Borde izquierdo de la banda. */
    public function at(string|int $category): float
    {
        return $this->start($this->indexOf($category));
    }

    /** Centro de la banda: donde se anclan las líneas y los puntos. */
    public function center(string|int $category): float
    {
        return $this->centerAt($this->indexOf($category));
    }

    /**
     * Dónde se ancla el punto número N de una línea.
     *
     * **Con barras**, al centro de su banda: si no, la línea y las barras del mismo gráfico
     * no coincidirían.
     *
     * **Sin barras**, en modo `point`, repartidos de borde a borde. Anclarlos al centro de
     * una banda imaginaria dejaría media banda vacía a cada lado — con 6 categorías, el 16 %
     * del ancho del gráfico desperdiciado y un área que parece cortada.
     */
    public function centerAt(int $index): float
    {
        if ($this->point) {
            $last = count($this->categories) - 1;

            return $last <= 0
                ? ($this->rangeMin + $this->rangeMax) / 2
                : $this->rangeMin + ($index / $last) * ($this->rangeMax - $this->rangeMin);
        }

        return $this->start($index) + $this->bandwidth / 2;
    }

    public function bandwidth(): float
    {
        return $this->bandwidth;
    }

    public function step(): float
    {
        return $this->step;
    }

    public function count(): int
    {
        return count($this->categories);
    }

    /** En una escala de bandas, la fila N cae en la banda N. Ésa es la definición. */
    public function positionAt(int $row): ?float
    {
        return $this->centerAt($row);
    }

    /**
     * El mismo eje de bandas, enseñando solo el tramo [from, to].
     *
     * No hay que recalcular ninguna banda: basta con estirar el RANGO. Si el tramo visible era
     * [20, 60], el 0–100 original pasa a mapearse en [−50, 200] — las bandas de fuera se quedan
     * con posiciones negativas o mayores que 100, que es exactamente lo que hace falta para que
     * el trazo siga saliendo por el borde en vez de cortarse en seco contra él.
     *
     * (Y el `bandwidth` se estira con él, porque sale del `step`, que sale del rango.)
     */
    public function window(float $from, float $to): static
    {
        $span = $to - $from;

        if ($span <= 0.0) {
            return $this;
        }

        $factor = 100.0 / $span;

        return new self(
            $this->categories,
            $this->padding,
            rangeMin: (0.0 - $from) * $factor,
            rangeMax: (100.0 - $from) * $factor,
            point: $this->point,
        );
    }

    /**
     * Las etiquetas, saltando de N en N cuando no caben.
     *
     * El servidor no puede medir texto, así que no puede saber si dos etiquetas chocan. Lo que
     * sí puede es no emitir más de las que caben razonablemente. Rotarlas NO es una opción:
     * `transform: rotate()` no ocupa layout, la fila del grid no crecería y se saldrían de la
     * caja.
     *
     * ⚠️ Esto es lo mejor que se puede hacer **con categorías**, y aun así es peor que un eje
     * de verdad: con 90 días, `$count = 12` escupe los días 1, 8, 15, 22… — que es exactamente
     * el «1.224» que `Ticks` se enorgullece de haber eliminado del eje Y. La respuesta a eso no
     * es afinar el salto: es que un eje de fechas **no debe ser una escala de bandas**. Ver
     * `TimeScale`.
     *
     * `$count` aquí es el TOPE de etiquetas, no un objetivo.
     */
    public function ticks(int $count): array
    {
        // Sólo las categorías que se VEN. Sin zoom son todas; con zoom, repartir las etiquetas
        // entre las noventa categorías cuando solo se ven diez dejaría el eje casi mudo.
        $visible = [];

        foreach ($this->categories as $i => $label) {
            $pos = $this->centerAt($i);

            if ($pos >= -0.01 && $pos <= 100.01) {
                $visible[] = [(string) $label, round($pos, 2)];
            }
        }

        $total = count($visible);

        if ($total === 0) {
            return [];
        }

        $stride = (int) max(1, ceil($total / max(1, $count)));
        $out = [];

        foreach ($visible as $i => [$label, $pos]) {
            if ($i % $stride !== 0 && $i !== $total - 1) {
                continue;
            }

            $out[] = [
                'label' => $label,
                'context' => null,
                'pos' => $pos,
                'width' => $this->tickWidth($label),
            ];
        }

        return $out;
    }

    /** De una posición 0–100 a la fila más cercana. Es lo que necesita el zoom. */
    public function invert(float $position): int
    {
        $total = count($this->categories);

        if ($total === 0) {
            return 0;
        }

        $best = 0;
        $distance = INF;

        for ($i = 0; $i < $total; $i++) {
            $delta = abs($this->centerAt($i) - $position);

            if ($delta < $distance) {
                $distance = $delta;
                $best = $i;
            }
        }

        return $best;
    }

    private function start(int $index): float
    {
        return $this->rangeMin + $index * $this->step + ($this->step - $this->bandwidth) / 2;
    }

    private function indexOf(string|int $category): int
    {
        return $this->index[(string) $category] ?? 0;
    }
}
