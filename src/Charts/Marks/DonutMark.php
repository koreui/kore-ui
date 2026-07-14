<?php

namespace KoreUi\Charts\Marks;

/**
 * Un donut.
 *
 * Vive en su propio SVG CUADRADO con escalado uniforme: un arco sí se deformaría con
 * preserveAspectRatio="none". Por eso un donut no comparte gráfico con las marcas
 * cartesianas — no comparte ni escalas ni caja.
 */
final class DonutMark extends Mark
{
    public float $innerRatio = 0.6;

    public float $padAngle = 1.0;

    /**
     * Al posarse sobre un arco, se enciende también su fila de la leyenda (y al revés).
     *
     * Es CSS puro: el arco y su fila comparten un `data-slice` y `:has()` recorre esa
     * relación. Encenderlo o apagarlo no añade ni quita un solo byte de JavaScript — sólo
     * pone o quita el `data-highlight` del que cuelgan las reglas.
     */
    public bool $highlight = true;

    public function requiresZero(): bool
    {
        return false;
    }

    public function medium(): string
    {
        return self::SVG;
    }

    public function type(): string
    {
        return 'donut';
    }

    public function withRatio(float $inner): self
    {
        $this->innerRatio = max(0.0, min(0.95, $inner));

        return $this;
    }

    public function withPad(float $degrees): self
    {
        $this->padAngle = max(0.0, $degrees);

        return $this;
    }

    public function withHighlight(bool $highlight): self
    {
        $this->highlight = $highlight;

        return $this;
    }
}
