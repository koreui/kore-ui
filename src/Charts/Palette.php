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

    /** Escalones de las rampas secuencial y ordinal. Siete, y no más: ver abajo. */
    public const RAMP_STEPS = 7;

    /** El token CSS del slot. El color nunca viaja como valor: viaja como referencia. */
    public static function token(int $slot): string
    {
        self::guard($slot);

        return "var(--kore-chart-{$slot})";
    }

    /**
     * En qué escalón de una rampa de 7 cae un valor.
     *
     * ⚠️ **El color se CUANTIZA, jamás se interpola aquí.** PHP no calcula colores: reparte el
     * valor en escalones y devuelve un número; el color lo pone el CSS con un token. Interpolar
     * en el servidor rompería el invariante que ordena todo el módulo —el color viaja como
     * token, no como valor— y con él se iría el repintado automático al cambiar de tema.
     *
     * Siete escalones porque por encima de siete el ojo deja de distinguirlos, y entonces ya no
     * es una escala: es un degradado bonito del que no se puede leer un valor.
     *
     * @return int 1..7
     */
    public static function bucket(float $value, float $min, float $max, int $steps = self::RAMP_STEPS): int
    {
        $steps = max(1, $steps);
        $span = $max - $min;

        // Todos los valores iguales: no hay escala que hacer. Al escalón de arriba, que es lo
        // que significa «todo está al máximo de lo que hay».
        if ($span <= 0.0 || ! is_finite($span)) {
            return $steps;
        }

        $bucket = (int) floor((($value - $min) / $span) * $steps) + 1;

        return max(1, min($steps, $bucket));
    }

    /** El token de un escalón de la rampa SECUENCIAL: el color ES la magnitud (heatmap, treemap). */
    public static function sequential(int $step): string
    {
        return 'var(--kore-seq-'.self::rampGuard($step).')';
    }

    /**
     * El token de un escalón de la rampa ORDINAL: el color solo dice ORDEN (embudo).
     *
     * No es un sinónimo de `sequential()`. Ahí el color codifica el valor; aquí el valor ya lo
     * codifica la geometría (el ancho del trapecio) y el color solo dice «esto va en una
     * secuencia». Usar la paleta categórica para un embudo está mal: la categórica dice «estas
     * cosas son distintas», no «estas cosas van en este orden».
     */
    public static function ordinal(int $step): string
    {
        return 'var(--kore-ord-'.self::rampGuard($step).')';
    }

    private static function rampGuard(int $step): int
    {
        if ($step < 1 || $step > self::RAMP_STEPS) {
            throw new InvalidArgumentException(
                "koreUi: una rampa tiene {$step} escalones fuera de rango; hay ".self::RAMP_STEPS.'. '
                .'Por encima de siete el ojo deja de distinguirlos y la escala deja de leerse.'
            );
        }

        return $step;
    }

    /**
     * El color de una serie, respetando un color explícito del usuario.
     *
     * Se acepta un token si el usuario lo pide A PROPÓSITO (una serie que literalmente significa
     * «errores» debería ir en rojo), pero nunca por defecto.
     *
     * ⚠️ **Una palabra suelta que no sea un token LANZA.** Y no es rigidez: hasta ahora se colaba
     * tal cual al CSS, así que `color="chart-4"` —que es lo primero que uno prueba— acababa en
     * `--kore-series: chart-4`, que no es un color válido, y **la serie no se pintaba**. Sin un
     * solo error. Medido en la demo: un gráfico de barras entero, invisible.
     *
     * Lo mismo con cualquier errata: `destructiv`, `rojo`, `blue-500`. Un color que no existe no
     * debe dejar el gráfico en blanco: debe decirlo.
     */
    public static function resolve(int $slot, ?string $color = null): string
    {
        if ($color === null || $color === '') {
            return self::token($slot);
        }

        $color = trim($color);

        if (self::isToken($color)) {
            return "var(--kore-{$color})";
        }

        // Un color CSS explícito: un hex, o una función — oklch(), rgb(), var(), color-mix()…
        if (str_starts_with($color, '#') || str_contains($color, '(')) {
            return $color;
        }

        throw new InvalidArgumentException(
            "koreUi: «{$color}» no es un color que el gráfico sepa pintar, y dejarlo pasar haría que la serie "
            .'desapareciera sin decir nada. Usa un token de kore (primary, destructive, success, warning, info, '
            .'chart-1…chart-8, seq-1…seq-7, ord-1…ord-7) o un color CSS explícito (#e11d48, oklch(…), var(…)).'
        );
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

    /**
     * Los tokens que el gráfico sabe pintar.
     *
     * Los semánticos, la paleta de datos y las dos rampas. `chart-4` faltaba, y era justo el que
     * uno prueba primero — se colaba tal cual al CSS y la serie se quedaba invisible.
     */
    private static function isToken(string $color): bool
    {
        if (in_array($color, [
            'primary', 'secondary', 'accent', 'destructive', 'success', 'warning', 'info', 'muted-fg',
        ], true)) {
            return true;
        }

        return (bool) preg_match(
            '/^(chart-[1-'.self::SLOTS.']|seq-[1-'.self::RAMP_STEPS.']|ord-[1-'.self::RAMP_STEPS.'])$/',
            $color,
        );
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
