<?php

namespace KoreUi\Charts;

use InvalidArgumentException;

/**
 * «30s», «5m», «2h», «7d» → segundos.
 *
 * Existe para `max-gap`, que en un eje temporal se escribe en tiempo y no en píxeles ni en
 * porcentajes: el usuario sabe cada cuánto muestrea su sensor; no sabe qué fracción del dominio
 * es eso.
 */
final class Duration
{
    private const UNITS = [
        'ms' => 0.001,
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
        'w' => 604800,
    ];

    /** Un número se toma tal cual: son las unidades del eje (segundos en uno temporal). */
    public static function seconds(string|int|float $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim($value);

        if (is_numeric($value)) {
            return (float) $value;
        }

        // El orden importa: «ms» tiene que probarse antes que «m», o «30ms» se leería como 30
        // minutos y el umbral saldría 1.800 veces más grande de lo que pediste.
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(ms|[smhdw])$/i', $value, $m) === 1) {
            return (float) $m[1] * self::UNITS[strtolower($m[2])];
        }

        throw new InvalidArgumentException(
            "koreUi: «{$value}» no es una duración. Escribe «500ms», «30s», «5m», «2h», «7d», «1w» o un número."
        );
    }
}
