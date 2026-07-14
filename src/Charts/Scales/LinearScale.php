<?php

namespace KoreUi\Charts\Scales;

/**
 * Dominio (el dato) → rango (el espacio del gráfico, siempre 0–100).
 *
 * Nunca píxeles. El servidor no conoce el ancho del contenedor y no lo necesita: el
 * navegador escala. Ésta es la razón de que el resize no cueste ni una línea de JavaScript.
 */
final class LinearScale
{
    private float $span;

    public function __construct(
        public readonly float $domainMin,
        public readonly float $domainMax,
        public readonly float $rangeMin = 0.0,
        public readonly float $rangeMax = 100.0,
    ) {
        // Un dominio plano dividiría por cero y sembraría NaN en el atributo `d`. Y un solo
        // NaN hace que el navegador descarte el <path> ENTERO, en silencio y sin un error.
        $span = $domainMax - $domainMin;
        $this->span = $span == 0.0 ? 1.0 : $span;
    }

    /** @param array{0: float, 1: float} $domain */
    public static function make(array $domain, float $rangeMin = 0.0, float $rangeMax = 100.0): self
    {
        return new self((float) $domain[0], (float) $domain[1], $rangeMin, $rangeMax);
    }

    /** Y invertida: en la pantalla el 0 está arriba, pero el valor más alto va arriba. */
    public static function vertical(array $domain): self
    {
        return new self((float) $domain[0], (float) $domain[1], 100.0, 0.0);
    }

    public function at(float $value): float
    {
        return $this->rangeMin + (($value - $this->domainMin) / $this->span) * ($this->rangeMax - $this->rangeMin);
    }

    public function invert(float $position): float
    {
        $spread = $this->rangeMax - $this->rangeMin;

        if ($spread == 0.0) {
            return $this->domainMin;
        }

        return $this->domainMin + (($position - $this->rangeMin) / $spread) * $this->span;
    }

    /** Dónde cae el cero. Es lo que necesita una barra para saber de dónde crece. */
    public function zero(): float
    {
        return $this->at(max($this->domainMin, min(0.0, $this->domainMax)));
    }
}
