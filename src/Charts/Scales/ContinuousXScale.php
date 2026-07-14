<?php

namespace KoreUi\Charts\Scales;

/**
 * Un eje X donde la posición de un dato la decide su VALOR, no su sitio en el array.
 *
 * Es la diferencia entera entre un eje honesto y uno que miente. En una `BandScale`, la fila 5
 * cae en la banda 5 — da igual lo que valga. Aquí, si entre dos lecturas pasaron tres días, se
 * ven tres días de separación.
 *
 * La lineal y la temporal solo se diferencian en **cómo se eligen los ticks**. Todo lo demás
 * —colocar un punto, medir una barra, invertir una posición— es lo mismo, y vive aquí.
 */
abstract class ContinuousXScale implements XScale
{
    use TickBox;

    protected LinearScale $scale;

    protected float $bandwidth;

    protected readonly float $padding;

    /**
     * @param  list<float|null>  $values  el X de cada fila, ya convertido a número
     * @param  float  $padding  proporción del hueco que queda libre entre barras (0–1)
     */
    public function __construct(
        protected readonly array $values,
        float $min,
        float $max,
        float $padding = 0.0,
    ) {
        $this->scale = new LinearScale($min, $max);
        $this->padding = $padding;
        $this->bandwidth = $this->deriveBandwidth($padding);
    }

    /**
     * El dominio que hay debajo de un tramo del área.
     *
     * Aquí se usa `LinearScale::invert()`, que llevaba escrita desde el primer día **sin que la
     * llamara nadie**. Es lo que hace que el zoom no necesite ni una escala en JavaScript: el
     * cliente manda dos porcentajes y el servidor los convierte en un dominio.
     *
     * @return array{0: float, 1: float}
     */
    protected function domainOf(float $from, float $to): array
    {
        return [$this->scale->invert($from), $this->scale->invert($to)];
    }

    /**
     * ⚠️ Devuelve `null` si la fila no tiene X.
     *
     * No es la posición 0: una fila sin fecha no es un dato en el origen del eje, es un hueco.
     * Colocarla en el 0 dibujaría un pico contra el eje Y que nunca existió.
     */
    public function positionAt(int $row): ?float
    {
        $value = $this->values[$row] ?? null;

        return $value === null ? null : $this->scale->at($value);
    }

    public function bandwidth(): float
    {
        return $this->bandwidth;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function invert(float $position): mixed
    {
        return $this->scale->invert($position);
    }

    /**
     * El ancho de una barra: el HUECO MÍNIMO entre dos puntos consecutivos, menos el padding.
     *
     * En una escala de bandas el ancho lo da la banda, que es siempre la misma. Aquí no hay
     * bandas: los puntos caen donde caen. Si se cogiera el hueco medio, dos lecturas seguidas
     * más juntas que la media producirían barras **solapadas** — y una barra que tapa a otra no
     * es un gráfico apretado, es un gráfico que esconde un dato.
     */
    private function deriveBandwidth(float $padding): float
    {
        $positions = [];

        foreach ($this->values as $value) {
            if ($value !== null) {
                $positions[] = $this->scale->at($value);
            }
        }

        sort($positions);

        $gap = null;

        for ($i = 1, $n = count($positions); $i < $n; $i++) {
            $delta = $positions[$i] - $positions[$i - 1];

            if ($delta > 0 && ($gap === null || $delta < $gap)) {
                $gap = $delta;
            }
        }

        // Un solo punto, o todos en el mismo instante: no hay hueco del que deducir nada, así
        // que se reparte el ancho entre las filas, como haría una banda.
        $gap ??= 100.0 / max(1, count($positions));

        return max($gap * (1.0 - max(0.0, min(0.95, $padding))), 0.01);
    }
}
