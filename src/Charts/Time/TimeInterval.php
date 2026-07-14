<?php

namespace KoreUi\Charts\Time;

use DateTimeImmutable;

/*
 * Time interval logic derived from d3-time (src/interval.js, src/day.js).
 * Copyright 2010-2024 Mike Bostock. ISC License.
 * https://github.com/d3/d3-time/blob/main/LICENSE
 */

/**
 * Una unidad de calendario: segundo, minuto, hora, día, semana, mes o año.
 *
 * Sabe hacer tres cosas, y las tres son de CALENDARIO, no de aritmética de epoch:
 * truncar una fecha a su frontera (`floor`), avanzarla N unidades (`offset`) y enumerar las
 * fronteras que caen en un rango (`range`).
 *
 * ## Por qué esto en PHP sale bien y en JavaScript hay que parchearlo
 *
 * `DateTimeImmutable::modify('+1 day')` es aritmética de **calendario**: el 29 de marzo de 2026
 * en Madrid solo tiene 23 horas, y sumarle un día te deja en el 30 a medianoche, no a la una.
 * La zona horaria viaja dentro del objeto y no hay ningún momento en que la fecha sea un número
 * de milisegundos.
 *
 * d3 no puede hacer eso: `Date` de JavaScript **es** un número de milisegundos, así que sumar un
 * día son 86.400.000 ms — que en el cambio de hora te deja a las 23:00 o a la 01:00. Por eso
 * `d3-time` vuelve a hacer `floor()` DESPUÉS de cada `offset()`. Es un parche a una limitación
 * que PHP no tiene.
 *
 * Aun así **nosotros también re-truncamos**, y a propósito: hay zonas (Brasil, hasta 2019) donde
 * el salto es a medianoche, y ahí la medianoche del día del cambio **no existe**. Cuesta una
 * llamada y cubre el caso que nadie prueba.
 *
 * ## La semana empieza en lunes
 *
 * d3 la empieza en domingo (`timeWeek` = `timeSunday`). Aquí no: ISO-8601, y es lo que espera
 * quien lee un gráfico en español. Es una decisión, no un descuido.
 */
final class TimeInterval
{
    public const SECOND = 'second';

    public const MINUTE = 'minute';

    public const HOUR = 'hour';

    public const DAY = 'day';

    public const WEEK = 'week';

    public const MONTH = 'month';

    public const YEAR = 'year';

    /** Segundos NOMINALES de la unidad. Sirven SOLO para elegir el intervalo, jamás para generarlo. */
    private const DURATIONS = [
        self::SECOND => 1,
        self::MINUTE => 60,
        self::HOUR => 3600,
        self::DAY => 86400,
        self::WEEK => 604800,
        self::MONTH => 2592000,     // 30 días, como d3
        self::YEAR => 31536000,     // 365 días, como d3
    ];

    private function __construct(public readonly string $unit) {}

    public static function of(string $unit): self
    {
        if (! isset(self::DURATIONS[$unit])) {
            throw new \InvalidArgumentException("koreUi: «{$unit}» no es una unidad de tiempo.");
        }

        return new self($unit);
    }

    public static function second(): self
    {
        return new self(self::SECOND);
    }

    public static function minute(): self
    {
        return new self(self::MINUTE);
    }

    public static function hour(): self
    {
        return new self(self::HOUR);
    }

    public static function day(): self
    {
        return new self(self::DAY);
    }

    public static function week(): self
    {
        return new self(self::WEEK);
    }

    public static function month(): self
    {
        return new self(self::MONTH);
    }

    public static function year(): self
    {
        return new self(self::YEAR);
    }

    public function duration(): int
    {
        return self::DURATIONS[$this->unit];
    }

    /** La frontera de esta unidad en la que cae la fecha, o la propia fecha si ya está en una. */
    public function floor(DateTimeImmutable $date): DateTimeImmutable
    {
        return match ($this->unit) {
            self::SECOND => $date->setTime(
                (int) $date->format('G'),
                (int) $date->format('i'),
                (int) $date->format('s'),
            ),
            self::MINUTE => $date->setTime((int) $date->format('G'), (int) $date->format('i')),
            self::HOUR => $date->setTime((int) $date->format('G'), 0),
            self::DAY => $date->setTime(0, 0),
            // 'monday this week' sobre un lunes no lo mueve, así que es idempotente.
            self::WEEK => $date->modify('monday this week')->setTime(0, 0),
            self::MONTH => $date->setDate((int) $date->format('Y'), (int) $date->format('n'), 1)->setTime(0, 0),
            self::YEAR => $date->setDate((int) $date->format('Y'), 1, 1)->setTime(0, 0),
        };
    }

    /** La siguiente frontera, o la propia fecha si ya está en una. */
    public function ceil(DateTimeImmutable $date): DateTimeImmutable
    {
        $floor = $this->floor($date);

        return $floor == $date ? $date : $this->floor($this->offset($floor, 1));
    }

    /** Avanza N unidades. Aritmética de CALENDARIO: un día puede medir 23 o 25 horas. */
    public function offset(DateTimeImmutable $date, int $step = 1): DateTimeImmutable
    {
        return $date->modify(sprintf('%+d %s', $step, $this->unit));
    }

    /**
     * El número de esta unidad, contado desde un origen absoluto.
     *
     * Es lo que permite que «cada 2 días» signifique lo mismo aunque cambie la ventana: el tick
     * se ancla a un calendario, no al principio del rango. Sin esto, arrastrar el gráfico un día
     * haría saltar todas las etiquetas.
     *
     * ⚠️ **El día se cuenta en días UNIX, no en día del mes.** Contar el día del mes —que es lo
     * que hacía d3 hasta la 3.x, y le costó un bug— reinicia la cuenta cada mes: con «cada 2
     * días», el 31 y el 1 quedan pegados.
     *
     * Y se cuenta sobre el timestamp, no sobre la hora de pared. El ancla es arbitraria de todos
     * modos (una medianoche en Madrid cae en el día UTC anterior), así que se elige la de d3: si
     * el ancla va a ser convencional, que sea la misma convención que la del resto del mundo, y
     * así el test de paridad puede ser exacto.
     *
     * Las horas, minutos y segundos SÍ van en hora local, como en d3 — y da lo mismo, porque
     * 24, 60 y 60 son divisibles por todos los pasos de la tabla. Aquí no hay elección que hacer.
     */
    public function field(DateTimeImmutable $date): int
    {
        $wall = $date->getTimestamp() + $date->getOffset();

        return match ($this->unit) {
            self::SECOND => $wall,
            self::MINUTE => (int) floor($wall / 60),
            self::HOUR => (int) floor($wall / 3600),
            self::DAY => (int) floor($date->getTimestamp() / 86400),
            self::WEEK => (int) floor($date->getTimestamp() / 604800),
            self::MONTH => ((int) $date->format('Y')) * 12 + ((int) $date->format('n')) - 1,
            self::YEAR => (int) $date->format('Y'),
        };
    }

    /**
     * Las fronteras de esta unidad entre dos fechas, de $step en $step.
     *
     * @return list<DateTimeImmutable>
     */
    public function range(DateTimeImmutable $start, DateTimeImmutable $stop, int $step = 1): array
    {
        $step = max(1, $step);
        $date = $this->ceil($start);

        // Alinear con el calendario absoluto: el primer tick no es «el principio del rango»,
        // es la primera frontera cuyo campo sea múltiplo del paso. Con `cada 3 meses` eso son
        // enero, abril, julio y octubre — no «tres meses después de donde empiece la ventana».
        // El bucle da como mucho $step vueltas.
        for ($i = 0; $i < $step && $this->field($date) % $step !== 0; $i++) {
            $date = $this->floor($this->offset($date, 1));
        }

        $out = [];

        while ($date <= $stop) {
            $out[] = $date;

            $next = $this->floor($this->offset($date, $step));

            // Si no avanza, se sale. No debería pasar nunca — pero un bucle infinito dentro de
            // un render de Blade es un servidor caído, no un gráfico feo.
            if ($next <= $date) {
                break;
            }

            $date = $next;
        }

        return $out;
    }
}
