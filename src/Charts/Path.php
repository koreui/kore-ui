<?php

namespace KoreUi\Charts;

/*
 * Monotone curve derived from d3-shape (src/curve/monotone.js), which implements
 * Steffen (1990), "A simple method for monotonic interpolation in one dimension".
 * Copyright 2010-2022 Mike Bostock. ISC License.
 * https://github.com/d3/d3-shape/blob/main/LICENSE
 */

/**
 * El atributo `d` de un <path>: el trazo de una serie.
 *
 * Dos cosas que parecen detalles y no lo son:
 *
 * **Los huecos parten el trazo.** Un `null` no es un cero: es "no hay dato". La línea tiene
 * que cortarse (`M` nuevo) y el área tiene que partirse en sub-áreas. Y la curva monótona
 * debe REINICIAR el cálculo de tangentes en cada hueco — si no, se inventa una tangente
 * para saltárselo y dibuja una curva por encima de un vacío, que es mentir sobre el dato.
 *
 * **La curva por defecto es monótona, no una spline cualquiera.** Una spline normal
 * (cardinal, catmull-rom) produce oscilaciones entre puntos: puede dibujar un máximo donde
 * no hay ningún dato. En un gráfico de negocio eso no es un problema estético, es un
 * problema de honestidad. La monótona de Steffen garantiza que entre dos puntos la curva no
 * se sale del rango de esos dos puntos.
 */
final class Path
{
    public const LINEAR = 'linear';

    public const MONOTONE = 'monotone';

    public const STEP = 'step';

    /**
     * @param  list<array{0: float, 1: float}|null>  $points  null = hueco
     */
    public static function line(array $points, string $curve = self::LINEAR): string
    {
        $segments = self::split($points);

        return trim(implode(' ', array_map(
            fn (array $segment) => self::segment($segment, $curve),
            $segments,
        )));
    }

    /**
     * El área bajo la curva, cerrada contra una línea base.
     *
     * @param  list<array{0: float, 1: float}|null>  $points
     * @param  float  $baseline  la Y del cierre (normalmente el cero de la escala)
     */
    public static function area(array $points, float $baseline, string $curve = self::LINEAR): string
    {
        $out = [];

        foreach (self::split($points) as $segment) {
            if (count($segment) < 2) {
                continue;   // un área de un solo punto no tiene superficie
            }

            $top = self::segment($segment, $curve);
            $last = $segment[count($segment) - 1];
            $first = $segment[0];

            $out[] = $top
                .' L'.self::n($last[0]).' '.self::n($baseline)
                .' L'.self::n($first[0]).' '.self::n($baseline)
                .' Z';
        }

        return trim(implode(' ', $out));
    }

    /**
     * Trocea la serie por los huecos, y de paso tira los puntos no finitos.
     *
     * Un NaN en el `d` hace que el navegador descarte el <path> entero, en silencio. Más
     * vale perder un punto que la serie completa.
     *
     * @param  list<array{0: float, 1: float}|null>  $points
     * @return list<list<array{0: float, 1: float}>>
     */
    private static function split(array $points): array
    {
        $segments = [];
        $current = [];

        foreach ($points as $point) {
            $valid = $point !== null
                && is_finite((float) $point[0])
                && is_finite((float) $point[1]);

            if (! $valid) {
                if ($current !== []) {
                    $segments[] = $current;
                    $current = [];
                }

                continue;
            }

            $current[] = [(float) $point[0], (float) $point[1]];
        }

        if ($current !== []) {
            $segments[] = $current;
        }

        return $segments;
    }

    /** @param list<array{0: float, 1: float}> $points */
    private static function segment(array $points, string $curve): string
    {
        $n = count($points);

        if ($n === 0) {
            return '';
        }

        if ($n === 1) {
            // Un punto suelto entre dos huecos no dibuja nada con un <path>. Se emite un
            // segmento de longitud cero: con stroke-linecap="round" se ve como un punto.
            return 'M'.self::n($points[0][0]).' '.self::n($points[0][1])
                .' L'.self::n($points[0][0]).' '.self::n($points[0][1]);
        }

        return match ($curve) {
            self::MONOTONE => self::monotone($points),
            self::STEP => self::step($points),
            default => self::linear($points),
        };
    }

    /** @param list<array{0: float, 1: float}> $points */
    private static function linear(array $points): string
    {
        $d = '';

        foreach ($points as $i => [$x, $y]) {
            $d .= ($i ? ' L' : 'M').self::n($x).' '.self::n($y);
        }

        return $d;
    }

    /** @param list<array{0: float, 1: float}> $points */
    private static function step(array $points): string
    {
        $d = 'M'.self::n($points[0][0]).' '.self::n($points[0][1]);

        for ($i = 1; $i < count($points); $i++) {
            [$x0, $y0] = $points[$i - 1];
            [$x1, $y1] = $points[$i];
            $mid = ($x0 + $x1) / 2;

            $d .= ' L'.self::n($mid).' '.self::n($y0)
                .' L'.self::n($mid).' '.self::n($y1)
                .' L'.self::n($x1).' '.self::n($y1);
        }

        return $d;
    }

    /**
     * Curva monótona (Steffen 1990), expresada como Béziers cúbicas.
     *
     * La clave está en `(sign(s0) + sign(s1))`: cuando la pendiente cambia de signo —o sea,
     * cuando el punto es un máximo o un mínimo local— la suma es 0, la tangente se aplana y
     * la curva NO se pasa de largo. Ése es todo el secreto de que no invente extremos.
     *
     * @param  list<array{0: float, 1: float}>  $points
     */
    private static function monotone(array $points): string
    {
        $n = count($points);

        if ($n === 2) {
            return self::linear($points);
        }

        $sign = fn (float $x): float => $x < 0 ? -1.0 : 1.0;

        // Pendiente en un punto interior, limitada para no sobrepasar a los vecinos.
        $slope3 = function (int $i) use ($points, $sign): float {
            [$x0, $y0] = $points[$i - 1];
            [$x1, $y1] = $points[$i];
            [$x2, $y2] = $points[$i + 1];

            $h0 = $x1 - $x0;
            $h1 = $x2 - $x1;

            if ($h0 == 0.0 || $h1 == 0.0) {
                return 0.0;
            }

            $s0 = ($y1 - $y0) / $h0;
            $s1 = ($y2 - $y1) / $h1;
            $p = ($s0 * $h1 + $s1 * $h0) / ($h0 + $h1);

            $t = ($sign($s0) + $sign($s1)) * min(abs($s0), abs($s1), 0.5 * abs($p));

            return is_finite($t) ? $t : 0.0;
        };

        // Pendiente en un extremo: se deriva de la del vecino, no se inventa.
        $slope2 = function (int $i, int $j, float $t) use ($points): float {
            $h = $points[$j][0] - $points[$i][0];

            return $h != 0.0 ? (3 * ($points[$j][1] - $points[$i][1]) / $h - $t) / 2 : $t;
        };

        $bezier = function (int $i, int $j, float $t0, float $t1) use ($points): string {
            [$x0, $y0] = $points[$i];
            [$x1, $y1] = $points[$j];
            $dx = ($x1 - $x0) / 3;

            return ' C'.self::n($x0 + $dx).' '.self::n($y0 + $dx * $t0)
                .' '.self::n($x1 - $dx).' '.self::n($y1 - $dx * $t1)
                .' '.self::n($x1).' '.self::n($y1);
        };

        $d = 'M'.self::n($points[0][0]).' '.self::n($points[0][1]);

        $t0 = 0.0;

        for ($i = 1; $i < $n; $i++) {
            if ($i < $n - 1) {
                $t1 = $slope3($i);
                $t0 = $i === 1 ? $slope2(0, 1, $t1) : $t0;
            } else {
                $t1 = $slope2($n - 2, $n - 1, $t0);
            }

            $d .= $bezier($i - 1, $i, $t0, $t1);
            $t0 = $t1;
        }

        return $d;
    }

    /**
     * Redondeo a 2 decimales.
     *
     * En un espacio 0–100 el segundo decimal ya es una centésima de la altura del gráfico:
     * invisible. Y a 2.000 puntos, cada decimal de más son kilobytes de HTML.
     */
    private static function n(float $value): string
    {
        $rounded = round($value, 2);

        return rtrim(rtrim(number_format($rounded, 2, '.', ''), '0'), '.') ?: '0';
    }
}
