<?php

namespace KoreUi\Charts\Scales;

use KoreUi\Charts\Format;
use KoreUi\Charts\Ticks;

/**
 * El eje X sobre números: 0, 10, 20… Lo que necesita un scatter.
 *
 * Los ticks salen del MISMO algoritmo que el eje Y (`Ticks`, el puerto de d3), así que un eje X
 * lineal dice números redondos por la misma razón que el eje Y ya los dice. No hay dos
 * implementaciones: hay una, usada dos veces.
 */
final class LinearXScale extends ContinuousXScale
{
    private Format $format;

    /** @param list<float|null> $values */
    public function __construct(
        array $values,
        float $min,
        float $max,
        float $padding = 0.0,
        ?Format $format = null,
    ) {
        parent::__construct($values, $min, $max, $padding);

        $this->format = $format ?? new Format;
    }

    public function ticks(int $count): array
    {
        $values = Ticks::ticks($this->scale->domainMin, $this->scale->domainMax, $count);
        $decimals = Ticks::decimals(Ticks::step($this->scale->domainMin, $this->scale->domainMax, $count));

        $out = [];

        foreach ($values as $value) {
            $pos = round($this->scale->at($value), 2);

            $label = $this->format->apply($value, $decimals);

            $out[] = [
                'label' => $label,
                'context' => null,
                'pos' => $pos,
                'width' => $this->tickWidth($label),
            ];
        }

        return $out;
    }
}
