<?php

namespace KoreUi\Charts\Scales;

use DateTimeImmutable;
use DateTimeZone;
use KoreUi\Charts\Time\TimeFormat;
use KoreUi\Charts\Time\TimeTicks;

/**
 * El eje X sobre fechas.
 *
 * Es una `LinearScale` sobre el **epoch** —que es monótono y no sabe de husos ni de cambios de
 * hora— con los **ticks generados por calendario**, que es donde el huso y el cambio de hora sí
 * importan. Esa separación es todo el diseño:
 *
 *  - **Colocar** un punto: aritmética de epoch. Un segundo es un segundo siempre.
 *  - **Elegir** dónde va un tick: aritmética de calendario. Un día puede medir 23 o 25 horas.
 *
 * Mezclar las dos es de donde salen los ejes desplazados una hora durante media estación.
 */
final class TimeScale extends ContinuousXScale
{
    private readonly array $dates;

    private readonly DateTimeZone $timezone;

    private readonly TimeFormat $format;

    /**
     * @param  list<DateTimeImmutable|null>  $dates  el X de cada fila
     */
    public function __construct(
        array $dates,
        DateTimeImmutable $min,
        DateTimeImmutable $max,
        float $padding = 0.0,
        ?TimeFormat $format = null,
    ) {
        parent::__construct(
            array_map(fn (?DateTimeImmutable $date) => $date === null ? null : (float) $date->getTimestamp(), $dates),
            (float) $min->getTimestamp(),
            (float) $max->getTimestamp(),
            $padding,
        );

        $this->dates = $dates;
        $this->timezone = $min->getTimezone();
        $this->format = $format ?? new TimeFormat;
    }

    public function window(float $from, float $to): static
    {
        if ($to - $from <= 0.0) {
            return $this;
        }

        // Las MISMAS fechas, con otro dominio. Los puntos de fuera se quedan con una posición
        // negativa o mayor que 100, y el trazo sigue saliendo por el borde: el recorte es visual,
        // no de dato. Y los ticks se regeneran solos sobre el rango nuevo — que es la mitad del
        // valor de hacer el zoom en el servidor: al ampliar una semana, el eje pasa de decir
        // meses a decir días, sin que nadie tenga que portar `TimeTicks` a JavaScript.
        return new self($this->dates, $this->at($from), $this->at($to), $this->padding, $this->format);
    }

    public function ticks(int $count): array
    {
        $ticks = TimeTicks::ticks($this->at(0.0), $this->at(100.0), $count);

        $out = [];
        $previous = null;

        foreach ($ticks as $date) {
            $pos = round($this->scale->at((float) $date->getTimestamp()), 2);
            $label = $this->format->tick($date, $previous);

            $out[] = [
                'label' => $label['label'],
                'context' => $label['context'],
                'pos' => $pos,
                'width' => $this->tickWidth($label['label'], $label['context']),
            ];

            $previous = $date;
        }

        return $out;
    }

    /** El zoom manda un porcentaje; aquí vuelve a ser una fecha. */
    public function invert(float $position): DateTimeImmutable
    {
        return $this->at($position);
    }

    /** La fecha de una fila, para el tooltip y la tabla accesible. */
    public function dateAt(int $row): ?DateTimeImmutable
    {
        return $this->dates[$row] ?? null;
    }

    public function labelAt(int $row): string
    {
        $date = $this->dateAt($row);

        return $date === null ? '' : $this->format->row($date);
    }

    private function at(float $position): DateTimeImmutable
    {
        $timestamp = (int) round($this->scale->invert($position));

        return (new DateTimeImmutable('@'.$timestamp))->setTimezone($this->timezone);
    }
}
