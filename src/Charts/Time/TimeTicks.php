<?php

namespace KoreUi\Charts\Time;

use DateTimeImmutable;
use KoreUi\Charts\Ticks;

/*
 * Tick-interval selection derived from d3-time (src/ticks.js).
 * Copyright 2010-2024 Mike Bostock. ISC License.
 * https://github.com/d3/d3-time/blob/main/LICENSE
 */

/**
 * Los valores redondos de un eje de tiempo.
 *
 * `Ticks` (el eje Y) elige un paso de la forma `f × 10^n` con `f ∈ {1,2,5,10}`. En el tiempo eso
 * no vale: no hay 10 meses en un año ni 100 segundos en un minuto. Lo que hay es una **tabla de
 * pares (unidad, paso) que sí son fronteras legibles de un calendario**.
 *
 * Tres cosas de esta tabla que parecen arbitrarias y no lo son:
 *
 * 1. **No existe «cada 4 horas» ni «cada 10 minutos».** No son divisores decentes de 24 ni de
 *    60: un eje cada 4 horas pondría un tick a las 00, 04, 08… y otro a las 20, y al día
 *    siguiente empezaría descuadrado. La pareja (unidad, paso) se elige **junta**, no «primero
 *    la unidad y luego a ver el paso».
 *
 *    Ése es exactamente el fallo de Chart.js: elige la unidad y deja `stepSize: 1`. Para un eje
 *    de un año genera **365 ticks de un día** y luego los esconde con `autoSkip`.
 *
 * 2. **El criterio de desempate es la MEDIA GEOMÉTRICA**, igual que en `Ticks::spec()`. El error
 *    de un paso es multiplicativo, así que la frontera justa entre «cada hora» y «cada 3 horas»
 *    es √3 horas, no 2. Es la misma idea, con otra tabla.
 *
 * 3. **Los extremos delegan en `Ticks`.** Por encima del año no hay unidad de calendario que
 *    valga: se vuelve a `1/2/5 × 10^n` sobre AÑOS (y de ahí salen los ejes por décadas y por
 *    siglos). Por debajo del segundo, lo mismo. O sea que las dos ramas extremas de esta clase
 *    ya estaban escritas.
 */
final class TimeTicks
{
    /** [unidad, paso, duración nominal en segundos]. Ordenada por duración. */
    private const INTERVALS = [
        [TimeInterval::SECOND, 1, 1],
        [TimeInterval::SECOND, 5, 5],
        [TimeInterval::SECOND, 15, 15],
        [TimeInterval::SECOND, 30, 30],
        [TimeInterval::MINUTE, 1, 60],
        [TimeInterval::MINUTE, 5, 300],
        [TimeInterval::MINUTE, 15, 900],
        [TimeInterval::MINUTE, 30, 1800],
        [TimeInterval::HOUR, 1, 3600],
        [TimeInterval::HOUR, 3, 10800],
        [TimeInterval::HOUR, 6, 21600],
        [TimeInterval::HOUR, 12, 43200],
        [TimeInterval::DAY, 1, 86400],
        [TimeInterval::DAY, 2, 172800],
        [TimeInterval::WEEK, 1, 604800],
        [TimeInterval::MONTH, 1, 2592000],
        [TimeInterval::MONTH, 3, 7776000],
        [TimeInterval::YEAR, 1, 31536000],
    ];

    /**
     * El intervalo con el que hay que recorrer este rango para que salgan ~$count ticks.
     *
     * **Y también con el que hay que agrupar la consulta.** Es el `$__interval` de Grafana, que
     * calcula lo mismo con un `switch` de 30 umbrales hechos a mano. Nadie en el ecosistema
     * Laravel lo expone, y sale casi gratis porque las escalas viven en el servidor:
     *
     * ```php
     * $paso = TimeTicks::interval($desde, $hasta, count: 8);   // → «1 day»
     *
     * Order::selectRaw('DATE_TRUNC(?, created_at) AS bucket, SUM(total)', [$paso->unit()])
     *      ->groupBy('bucket');
     * ```
     */
    public static function interval(DateTimeImmutable $start, DateTimeImmutable $stop, int $count = 10): TimeStep
    {
        $seconds = abs($stop->getTimestamp() - $start->getTimestamp());
        $target = $count > 0 ? $seconds / $count : 0;

        if ($target <= 0) {
            return new TimeStep(TimeInterval::second(), 1);
        }

        // bisectRight sobre la columna de duraciones: cuántos intervalos son <= al objetivo.
        $i = 0;
        foreach (self::INTERVALS as $candidate) {
            if ($candidate[2] > $target) {
                break;
            }
            $i++;
        }

        // Por encima de la tabla: años, con el paso que diga el algoritmo del eje Y (1, 2, 5,
        // 10, 20, 50…). De aquí salen los ejes por décadas y por siglos.
        if ($i === count(self::INTERVALS)) {
            $years = Ticks::step(
                $start->getTimestamp() / 31536000,
                $stop->getTimestamp() / 31536000,
                $count,
            );

            return new TimeStep(TimeInterval::year(), max(1, (int) round(abs($years))));
        }

        // Por debajo de la tabla: segundos. (d3 baja a milisegundos; un gráfico de negocio en
        // HTML renderizado por el servidor no llega ahí, y fingir que sí sería mentir.)
        if ($i === 0) {
            return new TimeStep(TimeInterval::second(), 1);
        }

        // El desempate: la media geométrica. `target / anterior < siguiente / target` es lo
        // mismo que `target < sqrt(anterior * siguiente)`, sin arrastrar el error del sqrt.
        $previous = self::INTERVALS[$i - 1];
        $next = self::INTERVALS[$i];

        $chosen = ($target / $previous[2]) < ($next[2] / $target) ? $previous : $next;

        return new TimeStep(TimeInterval::of($chosen[0]), $chosen[1]);
    }

    /**
     * Las fronteras de calendario que caen dentro del rango.
     *
     * @return list<DateTimeImmutable>
     */
    public static function ticks(DateTimeImmutable $start, DateTimeImmutable $stop, int $count = 10): array
    {
        if ($count <= 0) {
            return [];
        }

        $reverse = $stop < $start;

        if ($reverse) {
            [$start, $stop] = [$stop, $start];
        }

        if ($start == $stop) {
            return [$start];
        }

        $step = self::interval($start, $stop, $count);
        $out = $step->interval->range($start, $stop, $step->step);

        return $reverse ? array_reverse($out) : $out;
    }

    /**
     * Redondea el rango HACIA FUERA hasta caer en fronteras de calendario.
     *
     * Un eje que empieza el 14 de marzo a las 03:47 no es un eje. Éste empieza el 1 de marzo.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    public static function nice(DateTimeImmutable $start, DateTimeImmutable $stop, int $count = 10): array
    {
        if ($start == $stop) {
            return [$start, $stop];
        }

        $reverse = $stop < $start;

        if ($reverse) {
            [$start, $stop] = [$stop, $start];
        }

        $step = self::interval($start, $stop, $count);

        $lo = $step->interval->floor($start);
        $hi = $step->interval->ceil($stop);

        return $reverse ? [$hi, $lo] : [$lo, $hi];
    }
}
