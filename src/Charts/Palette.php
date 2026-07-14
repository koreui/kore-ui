<?php

namespace KoreUi\Charts;

use InvalidArgumentException;

/**
 * Qué color le toca a cada serie.
 *
 * Tres reglas que no se negocian, y que están aquí y no en el Blade precisamente para que
 * no se puedan saltar:
 *
 * **1. El color sigue a la ENTIDAD, no a su posición.** El slot se asigna por orden de
 * registro de la marca, jamás por índice de las series visibles. Si se asignara por índice,
 * ocultar la serie 2 repintaría la 3 con el color de la 2, y el lector creería que está
 * mirando otra cosa.
 *
 * **2. Los tokens semánticos están reservados.** `--kore-success` significa "esto va bien".
 * Usarlo para la serie 2 le está diciendo al lector que la serie 2 va bien. Por eso la
 * paleta de datos es una escala aparte (`--kore-chart-*`) y no un alias de la semántica.
 *
 * **3. La novena serie no existe.** La paleta no se cicla: repetir el color de la serie 1
 * en la 9 es peor que no pintarla, porque el lector ya no puede distinguirlas. Con más de
 * ocho series, la respuesta es agrupar en "Otros" o usar small multiples.
 */
final class Palette
{
    public const SLOTS = 8;

    /**
     * ⚠️ En scatter, burbujas y small multiples CUALQUIER par de series puede quedar uno al
     * lado del otro, así que hay que distinguir los ocho entre sí — y la paleta solo aguanta
     * cinco: con seis o más, magenta y teal colapsan al mismo color bajo deuteranopia
     * (ΔE 2.4, medido). Para barras y líneas no aplica: ahí solo se tocan los vecinos.
     */
    public const SCATTER_SLOTS = 5;

    /** El subconjunto que sí sobrevive al daltonismo cuando todos los pares cuentan. */
    public const SCATTER_ORDER = [1, 2, 3, 6, 8];   // azul, ámbar, teal, rojo, verde

    /** El token CSS del slot. El color nunca viaja como valor: viaja como referencia. */
    public static function token(int $slot): string
    {
        self::guard($slot);

        return "var(--kore-chart-{$slot})";
    }

    /**
     * El color de una serie, respetando un color explícito del usuario.
     *
     * Se acepta un token semántico si el usuario lo pide A PROPÓSITO (una serie que
     * literalmente significa "errores" debería ir en rojo), pero nunca por defecto.
     */
    public static function resolve(int $slot, ?string $color = null): string
    {
        if ($color === null || $color === '') {
            return self::token($slot);
        }

        return self::isToken($color) ? "var(--kore-{$color})" : $color;
    }

    /** El slot que le toca a la marca número N (1-based). */
    public static function slotFor(int $position, bool $scatter = false): int
    {
        if ($scatter) {
            if ($position > self::SCATTER_SLOTS) {
                throw new InvalidArgumentException(
                    "koreUi: un scatter no admite más de ".self::SCATTER_SLOTS." series. Con seis o más, "
                    ."dos de los colores son indistinguibles para una persona con deuteranopia. "
                    ."Agrupa en «Otros», o usa small multiples."
                );
            }

            return self::SCATTER_ORDER[$position - 1];
        }

        self::guard($position);

        return $position;
    }

    private static function isToken(string $color): bool
    {
        return in_array($color, [
            'primary', 'secondary', 'accent', 'destructive', 'success', 'warning', 'info', 'muted-fg',
        ], true);
    }

    private static function guard(int $slot): void
    {
        if ($slot < 1 || $slot > self::SLOTS) {
            throw new InvalidArgumentException(
                "koreUi: la paleta de datos tiene ".self::SLOTS." colores y no se cicla (se ha pedido el {$slot}). "
                ."Repetir el color de la serie 1 en la novena es peor que no pintarla: el lector deja de poder "
                ."distinguirlas. Agrupa el resto en «Otros», o usa small multiples."
            );
        }
    }
}
