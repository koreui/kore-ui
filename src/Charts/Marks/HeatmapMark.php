<?php

namespace KoreUi\Charts\Marks;

/**
 * Un mapa de calor: una matriz de columna × fila, donde el COLOR es el valor.
 *
 * Actividad por hora y día de la semana, retención por cohortes, un calendario de commits. Cada
 * celda es un cruce, y su color dice cuánto.
 *
 * ## Tres canales, no uno
 *
 * A diferencia del resto de marcas —que tienen un solo `field`—, un heatmap necesita tres: la
 * columna (el `x` del gráfico), la FILA (`row`) y el VALOR (`y`). Cada fila del dato es una celda.
 * El formato es «largo», el que escupe un `GROUP BY` de SQL:
 *
 *     ['dia' => 'Lun', 'hora' => 9, 'commits' => 12]
 *
 * ## El color se CUANTIZA, no se interpola
 *
 * El valor cae en uno de N escalones (`--kore-seq-*`, la rampa secuencial de la Fase 1) y la celda
 * lleva un `data-bucket`; el color lo pone el CSS. PHP no calcula un solo color — así el tema sigue
 * cambiando sin ejecutar JavaScript, que es el invariante que ordena todo el módulo. Y de paso, una
 * escala de 7 tonos se lee: un degradado continuo obliga a mirar la leyenda para cada celda.
 */
final class HeatmapMark extends Mark
{
    /** La columna que da la FILA de cada celda. La columna (el eje X) es el `x` del gráfico. */
    public ?string $row = null;

    /** Cuántos escalones de color. Más de siete no se distinguen; menos de tres no es una escala. */
    public int $buckets = 5;

    /** El grosor no representa nada aquí: el valor es el COLOR, no una longitud. */
    public function requiresZero(): bool
    {
        return false;
    }

    /** Celdas HTML posicionadas en %, como las barras. */
    public function medium(): string
    {
        return self::HTML;
    }

    public function type(): string
    {
        return 'heatmap';
    }

    public function withRow(?string $row): static
    {
        $this->row = $row;

        return $this;
    }

    public function withBuckets(int $buckets): static
    {
        $this->buckets = max(3, min(\KoreUi\Charts\Palette::RAMP_STEPS, $buckets));

        return $this;
    }
}
