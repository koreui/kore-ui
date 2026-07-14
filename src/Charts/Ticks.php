<?php

namespace KoreUi\Charts;

/*
 * Nice-tick algorithm derived from d3-array (src/ticks.js).
 * Copyright 2010-2023 Mike Bostock. ISC License.
 * https://github.com/d3/d3-array/blob/main/LICENSE
 */

/**
 * Los valores redondos de un eje.
 *
 * Un eje que dice "1.224" no es un eje: es un fallo de producto. Y es exactamente lo que
 * produce el reparto ingenuo (`paso = rango / nº_líneas`) que usa todo el ecosistema PHP
 * con licencia permisiva. De ahí que esto sea un puerto del algoritmo de d3, y no un
 * invento propio.
 *
 * Dos detalles del algoritmo que parecen caprichos y no lo son:
 *
 *  1. El paso siempre es `f × 10^n` con `f ∈ {1, 2, 5, 10}`, y el `f` se elige comparando
 *     contra las MEDIAS GEOMÉTRICAS de los candidatos (√2, √10, √50), no contra umbrales
 *     aritméticos. El error de un paso es multiplicativo, así que la frontera justa entre
 *     "2" y "5" es su media geométrica, no la aritmética.
 *
 *  2. Cuando el exponente es negativo, NO se multiplica por un paso fraccionario: se
 *     trabaja con su INVERSO ENTERO. Es decir, 0.1, 0.2, 0.3 se calcula como 1/10, 2/10,
 *     3/10 — nunca como 0 + 3 × 0.1. Por eso d3 jamás escupe un 0.30000000000000004, y
 *     por eso `inc` puede venir con signo negativo: el signo es la bandera de "esto es un
 *     inverso", no una dirección.
 */
final class Ticks
{
    /** Medias geométricas de los pares de candidatos (1,2), (2,5) y (5,10). */
    private const E2 = 1.4142135623730951;   // sqrt(2)
    private const E5 = 3.1622776601683795;   // sqrt(10)
    private const E10 = 7.0710678118654755;  // sqrt(50)

    /**
     * @return array{0: int, 1: int, 2: float} [i1, i2, inc] — si inc < 0, es el INVERSO del paso
     */
    private static function spec(float $start, float $stop, float $count): array
    {
        $step = $count > 0 ? ($stop - $start) / $count : 0.0;

        // Un dominio plano (o no finito) da un paso 0 → log10(0) es -INF → 10 ** -INF es 0
        // → división por cero. JavaScript se lo traga con Infinity y NaN; PHP lanza. Rango
        // vacío e incremento 0: ticks() devuelve [] y step() devuelve 0, igual que d3.
        if ($step <= 0 || ! is_finite($step)) {
            return [0, -1, 0.0];
        }

        $power = floor(log10($step));
        $error = $step / (10 ** $power);

        $factor = $error >= self::E10 ? 10 : ($error >= self::E5 ? 5 : ($error >= self::E2 ? 2 : 1));

        if ($power < 0) {
            $inc = (10 ** -$power) / $factor;
            $i1 = (int) round($start * $inc);
            $i2 = (int) round($stop * $inc);
            if ($i1 / $inc < $start) {
                $i1++;
            }
            if ($i2 / $inc > $stop) {
                $i2--;
            }
            $inc = -$inc;
        } else {
            $inc = (10 ** $power) * $factor;
            $i1 = (int) round($start / $inc);
            $i2 = (int) round($stop / $inc);
            if ($i1 * $inc < $start) {
                $i1++;
            }
            if ($i2 * $inc > $stop) {
                $i2--;
            }
        }

        // No cabe ni un tick con el count pedido: se reintenta pidiendo el doble.
        if ($i2 < $i1 && $count >= 0.5 && $count < 2) {
            return self::spec($start, $stop, $count * 2);
        }

        return [$i1, $i2, (float) $inc];
    }

    /**
     * Los valores redondos entre start y stop.
     *
     * `count` es una SUGERENCIA, no un contrato: el número real de ticks cae más o menos
     * entre count/2 y count*2. Ningún algoritmo de ticks bonitos puede prometer un número
     * exacto sin sacrificar precisamente la belleza. Por eso la prop del componente se
     * llamará `ticks` pero se documentará como una pista.
     *
     * @return list<float>
     */
    public static function ticks(float $start, float $stop, int $count = 10): array
    {
        if ($count <= 0 || ! is_finite($start) || ! is_finite($stop)) {
            return [];
        }

        if ($start === $stop) {
            return [$start];
        }

        $reverse = $stop < $start;
        [$i1, $i2, $inc] = $reverse
            ? self::spec($stop, $start, $count)
            : self::spec($start, $stop, $count);

        if ($i2 < $i1) {
            return [];
        }

        $n = $i2 - $i1 + 1;
        $out = [];

        for ($i = 0; $i < $n; $i++) {
            $index = $reverse ? $i2 - $i : $i1 + $i;
            $out[] = $inc < 0 ? $index / -$inc : $index * $inc;
        }

        return $out;
    }

    /** El paso entre ticks, con signo. */
    public static function step(float $start, float $stop, int $count = 10): float
    {
        $reverse = $stop < $start;

        $inc = $reverse
            ? self::spec($stop, $start, $count)[2]
            : self::spec($start, $stop, $count)[2];

        if ($inc === 0.0) {
            return 0.0;
        }

        return ($reverse ? -1 : 1) * ($inc < 0 ? 1 / -$inc : $inc);
    }

    /**
     * Redondea el dominio HACIA FUERA hasta caer en ticks.
     *
     * Itera porque redondear el dominio puede cambiar el paso elegido, y entonces el
     * redondeo anterior deja de ser válido. Se para cuando el paso se estabiliza.
     *
     * @return array{0: float, 1: float}
     */
    public static function nice(float $min, float $max, int $count = 10): array
    {
        if (! is_finite($min) || ! is_finite($max) || $min === $max) {
            return [$min, $max];
        }

        $start = min($min, $max);
        $stop = max($min, $max);
        $prestep = null;

        for ($i = 0; $i < 10; $i++) {
            $step = self::spec($start, $stop, $count)[2];

            if ($step === $prestep) {
                break;
            }

            if ($step > 0) {
                $start = floor($start / $step) * $step;
                $stop = ceil($stop / $step) * $step;
            } elseif ($step < 0) {
                // step es el INVERSO del paso: se multiplica y se divide, no al revés.
                $start = ceil($start * $step) / $step;
                $stop = floor($stop * $step) / $step;
            } else {
                break;
            }

            $prestep = $step;
        }

        return $min <= $max ? [$start, $stop] : [$stop, $start];
    }

    /**
     * Cuántos decimales hacen falta para escribir un tick de este paso sin mentir.
     *
     * Sin esto, un paso de 0.25 imprime "0.3" y el eje deja de sumar.
     */
    public static function decimals(float $step): int
    {
        $step = abs($step);

        if ($step <= 0 || ! is_finite($step)) {
            return 0;
        }

        return max(0, (int) -floor(log10($step) + 1e-12));
    }
}
