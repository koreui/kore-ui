<?php

namespace KoreUi\Charts\Time;

use Carbon\CarbonImmutable;
use DateTimeImmutable;

/**
 * Cómo se escribe la etiqueta de un tick de tiempo.
 *
 * ## La escalera (la idea es de d3)
 *
 * Cada fecha se formatea con la granularidad **inmediatamente inferior a la frontera más gruesa
 * que respeta**. Dicho en código: si `mes(d) < d`, esta fecha no cae en un día 1, así que lo
 * relevante de ella es el día. Si sí cae en un día 1, lo relevante es el mes.
 *
 * Resultado: en un eje de días, el día 1 imprime «feb» y el resto imprime «14». Cada tick decide
 * solo, sin estado.
 *
 * ## El agujero de d3, y por qué llevamos una segunda línea
 *
 * Esa escalera falla en un caso que es el más común de todos: **un eje del 10 al 20 de enero no
 * dice en ninguna parte de qué mes habla**, porque ningún tick cae en un día 1. Y un eje que
 * pone «10 11 12 13 …» y nada más no es un eje, es una lista de números.
 *
 * La respuesta es la de uPlot: una **segunda línea de contexto**, que se imprime en el primer
 * tick y cada vez que la unidad de arriba cambia. En un eje de días es el mes; en uno de horas,
 * el día; en uno de meses, el año. Cuesta un `<span>` y arregla el eje.
 */
final class TimeFormat
{
    public function __construct(private readonly ?string $locale = null) {}

    /**
     * La etiqueta de un tick, y su línea de contexto si toca.
     *
     * `$previous` es el tick anterior: el contexto solo se imprime cuando **cambia**. Repetir
     * «feb» debajo de los catorce días de febrero sería ruido, no información.
     *
     * @return array{label: string, context: string|null}
     */
    public function tick(DateTimeImmutable $date, ?DateTimeImmutable $previous = null): array
    {
        $granularity = $this->granularity($date);

        $label = match ($granularity) {
            'second' => $this->at($date)->translatedFormat('H:i:s'),
            'minute' => $this->at($date)->translatedFormat('H:i'),
            'hour' => $this->at($date)->translatedFormat('H:i'),
            'day' => $this->at($date)->translatedFormat('j'),
            'month' => $this->at($date)->translatedFormat('M'),
            'year' => $date->format('Y'),
        };

        return [
            'label' => $label,
            'context' => $this->context($date, $previous, $granularity),
        ];
    }

    /**
     * La etiqueta de una FILA, para el tooltip y la tabla accesible.
     *
     * Aquí no hay escalera que valga: el tooltip habla de un punto concreto y fuera de todo
     * contexto, así que la fecha va entera. «14» no le dice nada a nadie.
     */
    public function row(DateTimeImmutable $date): string
    {
        return TimeInterval::day()->floor($date) == $date
            ? $this->at($date)->translatedFormat('j M Y')
            : $this->at($date)->translatedFormat('j M Y, H:i');
    }

    /**
     * La unidad más fina que esta fecha NO respeta.
     *
     * `mes(d) != d` significa «esta fecha no cae en una frontera de mes», así que lo que la
     * distingue de sus vecinas es el día.
     */
    private function granularity(DateTimeImmutable $date): string
    {
        return match (true) {
            TimeInterval::minute()->floor($date) != $date => 'second',
            TimeInterval::hour()->floor($date) != $date => 'minute',
            TimeInterval::day()->floor($date) != $date => 'hour',
            TimeInterval::month()->floor($date) != $date => 'day',
            TimeInterval::year()->floor($date) != $date => 'month',
            default => 'year',
        };
    }

    /** La segunda línea: la unidad de arriba, y solo cuando cambia. */
    private function context(DateTimeImmutable $date, ?DateTimeImmutable $previous, string $granularity): ?string
    {
        [$format, $unit] = match ($granularity) {
            'second', 'minute', 'hour' => ['j M', TimeInterval::day()],
            'day' => ['M Y', TimeInterval::month()],
            'month' => ['Y', TimeInterval::year()],
            'year' => [null, null],
        };

        if ($format === null) {
            return null;   // un eje de años ya lleva el año en la etiqueta
        }

        // El primero siempre lo lleva: es el que da el contexto de todo el eje.
        if ($previous !== null && $unit->floor($previous) == $unit->floor($date)) {
            return null;
        }

        return $this->at($date)->translatedFormat($format);
    }

    /**
     * Carbon, no `IntlDateFormatter`.
     *
     * `Format` (el de los números) evita `ext-intl` a propósito, porque no se puede exigir una
     * extensión para pintar un gráfico. Aquí pasa lo mismo, y Carbon —que ya viene con Laravel—
     * traduce los meses con sus propias tablas, sin extensión ninguna.
     */
    private function at(DateTimeImmutable $date): CarbonImmutable
    {
        $carbon = CarbonImmutable::instance($date);

        return $this->locale === null ? $carbon : $carbon->locale($this->locale);
    }
}
