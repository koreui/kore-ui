<?php

namespace KoreUi\Charts;

/**
 * Los arcos de un donut.
 *
 * No es un puerto del `arc` de d3: su matemática de esquinas redondeadas y separación
 * angular resuelve problemas que aquí no tenemos. Un donut son dos arcos y dos rectas.
 *
 * ⚠️ **La trampa clásica del donut**, y por eso tiene su propio test: cuando una porción
 * vale el 100 %, el arco es de 360°, el punto inicial coincide con el final y **el
 * navegador no pinta nada**. Un arco SVG de vuelta completa es degenerado por definición.
 * Hay que partirlo en dos medias vueltas. Es el bug que tiene medio internet.
 *
 * A diferencia del resto del motor, el donut vive en un SVG CUADRADO con escalado uniforme:
 * un arco sí se deformaría con `preserveAspectRatio="none"`.
 */
final class Arc
{
    /** Un donut se dibuja desde arriba (12 en punto) y en el sentido del reloj. */
    private const START_ANGLE = -M_PI / 2;

    /**
     * @param  list<float>  $values
     * @param  float  $innerRatio  0 = tarta, 0.6 = donut
     * @param  float  $padAngle  hueco entre porciones, en grados
     * @return list<array{path: string, start: float, end: float, fraction: float, value: float}>
     */
    public static function slices(
        array $values,
        float $innerRatio = 0.6,
        float $padAngle = 0.0,
        float $cx = 50.0,
        float $cy = 50.0,
        float $radius = 50.0,
    ): array {
        $values = array_values(array_map(
            fn ($v) => is_numeric($v) && is_finite((float) $v) && (float) $v > 0 ? (float) $v : 0.0,
            $values,
        ));

        $total = array_sum($values);

        if ($total <= 0) {
            return [];
        }

        $innerRatio = max(0.0, min(0.95, $innerRatio));
        $pad = deg2rad(max(0.0, $padAngle));
        $outer = $radius;
        $inner = $radius * $innerRatio;

        // Cuántas porciones tienen algo que enseñar: solo entre ellas hay separación.
        $visible = count(array_filter($values, fn (float $v) => $v > 0));

        $slices = [];
        $angle = self::START_ANGLE;

        foreach ($values as $value) {
            if ($value <= 0) {
                continue;
            }

            $sweep = ($value / $total) * 2 * M_PI;

            // La separación se le quita a la porción, no se le suma al círculo: si no, la
            // suma de los ángulos pasaría de 360° y la última porción se solaparía.
            $gap = $visible > 1 ? min($pad, $sweep * 0.5) : 0.0;
            $start = $angle + $gap / 2;
            $end = $angle + $sweep - $gap / 2;

            $slices[] = [
                'path' => self::path($start, $end, $inner, $outer, $cx, $cy),
                'start' => $start,
                'end' => $end,
                'fraction' => $value / $total,
                'value' => $value,
            ];

            $angle += $sweep;
        }

        return $slices;
    }

    /** El path de una porción. Con la vuelta completa partida en dos, o no se pintaría. */
    public static function path(float $start, float $end, float $inner, float $outer, float $cx = 50.0, float $cy = 50.0): string
    {
        $sweep = $end - $start;

        // 360° (o más): el punto inicial y el final coinciden y el arco es degenerado.
        // Se parte por la mitad. Éste es EL bug del donut al 100 %.
        if ($sweep >= 2 * M_PI - 1e-9) {
            $mid = $start + M_PI;

            return self::ring($start, $mid, $inner, $outer, $cx, $cy)
                .' '.self::ring($mid, $start + 2 * M_PI, $inner, $outer, $cx, $cy);
        }

        return self::ring($start, $end, $inner, $outer, $cx, $cy);
    }

    /**
     * Un arco ABIERTO, para TRAZAR (no rellenar). Lo que necesita un gauge.
     *
     * Un gauge es una línea curva con grosor y las puntas redondeadas (`stroke-linecap: round`),
     * no un anillo relleno como una porción de donut. Sale más limpio y con mucho menos path.
     *
     * Un gauge nunca da la vuelta entera (eso es un donut), así que aquí no hace falta partir el
     * arco degenerado de 360°.
     */
    public static function open(float $start, float $end, float $radius, float $cx = 50.0, float $cy = 50.0): string
    {
        // Un arco de longitud cero (valor en el mínimo) no dibuja nada, y está bien: no hay valor
        // que enseñar. Con `stroke-linecap: round` saldría un punto, que tampoco molesta.
        if (abs($end - $start) < 1e-9) {
            return '';
        }

        $large = ($end - $start) > M_PI ? 1 : 0;

        [$x0, $y0] = self::point($cx, $cy, $radius, $start);
        [$x1, $y1] = self::point($cx, $cy, $radius, $end);

        return 'M'.self::n($x0).' '.self::n($y0)
            .' A'.self::n($radius).' '.self::n($radius).' 0 '.$large.' 1 '.self::n($x1).' '.self::n($y1);
    }

    private static function ring(float $start, float $end, float $inner, float $outer, float $cx, float $cy): string
    {
        $large = ($end - $start) > M_PI ? 1 : 0;

        [$x0, $y0] = self::point($cx, $cy, $outer, $start);
        [$x1, $y1] = self::point($cx, $cy, $outer, $end);

        // Tarta: no hay agujero, se cierra contra el centro.
        if ($inner <= 0) {
            return 'M'.self::n($x0).' '.self::n($y0)
                .' A'.self::n($outer).' '.self::n($outer).' 0 '.$large.' 1 '.self::n($x1).' '.self::n($y1)
                .' L'.self::n($cx).' '.self::n($cy).' Z';
        }

        [$x2, $y2] = self::point($cx, $cy, $inner, $end);
        [$x3, $y3] = self::point($cx, $cy, $inner, $start);

        return 'M'.self::n($x0).' '.self::n($y0)
            .' A'.self::n($outer).' '.self::n($outer).' 0 '.$large.' 1 '.self::n($x1).' '.self::n($y1)
            .' L'.self::n($x2).' '.self::n($y2)
            .' A'.self::n($inner).' '.self::n($inner).' 0 '.$large.' 0 '.self::n($x3).' '.self::n($y3)
            .' Z';
    }

    /** @return array{0: float, 1: float} */
    private static function point(float $cx, float $cy, float $radius, float $angle): array
    {
        return [$cx + $radius * cos($angle), $cy + $radius * sin($angle)];
    }

    private static function n(float $value): string
    {
        return rtrim(rtrim(number_format(round($value, 2), 2, '.', ''), '0'), '.') ?: '0';
    }
}
