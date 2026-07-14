<?php

namespace KoreUi\Charts\Scales;

/**
 * Categorías → bandas del eje, en el espacio 0–100.
 *
 * Una barra ocupa una banda; una línea sobre un eje de categorías se ancla al CENTRO de la
 * banda. Si no, la línea y las barras del mismo gráfico no coincidirían.
 */
final class BandScale
{
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

    private function start(int $index): float
    {
        return $this->rangeMin + $index * $this->step + ($this->step - $this->bandwidth) / 2;
    }

    private function indexOf(string|int $category): int
    {
        return $this->index[(string) $category] ?? 0;
    }
}
