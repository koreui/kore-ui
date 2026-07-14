<?php

namespace KoreUi\Charts\Time;

use DateInterval;

/**
 * Un intervalo elegido: la unidad y cuántas de ellas. «Cada 3 meses», «cada 15 minutos».
 *
 * Existe como objeto —y no como dos parámetros sueltos— porque **también sirve fuera del
 * gráfico**: es la unidad con la que hay que agrupar la consulta. Ver `TimeTicks::interval()`.
 */
final class TimeStep
{
    public function __construct(
        public readonly TimeInterval $interval,
        public readonly int $step = 1,
    ) {}

    /** 'day', 'month', 'hour'… — lo que espera un `DATE_TRUNC` o un `date_trunc`. */
    public function unit(): string
    {
        return $this->interval->unit;
    }

    /** Un `DateInterval` de PHP, por si hay que iterar el rango. */
    public function toDateInterval(): DateInterval
    {
        return DateInterval::createFromDateString(sprintf('%d %s', $this->step, $this->interval->unit));
    }

    /** «cada 3 meses» */
    public function __toString(): string
    {
        return sprintf('%d %s', $this->step, $this->interval->unit);
    }
}
