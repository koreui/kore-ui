<?php

namespace KoreUi\Charts;

/**
 * El dominio del eje Y: de qué valor a qué valor.
 *
 * Es la clase donde viven todos los casos que rompen un gráfico en producción, y por eso
 * es la que más tests tiene:
 *
 *  - **Serie vacía o toda nula.** No hay dato: el gráfico debe enseñar su estado vacío, no
 *    dividir por cero.
 *  - **Dominio plano** (todos los valores iguales, o un solo punto). Sin tratar, la escala
 *    divide por cero y siembra NaN en el atributo `d`. Un solo NaN hace que el navegador
 *    descarte el <path> ENTERO, en silencio y sin lanzar ningún error: la serie desaparece
 *    y nadie sabe por qué.
 *  - **El cero.** Una BARRA siempre necesita el 0 en el dominio, porque su longitud ES el
 *    valor: una barra que empieza en 40 miente sobre la proporción. Una LÍNEA no lo
 *    necesita, y forzarlo aplasta la señal. Por eso cada marca declara si lo exige.
 *  - **Valores no finitos.** INF y NAN entran por la puerta de atrás desde cualquier
 *    cálculo del usuario. Se ignoran como si fueran huecos.
 */
final class Domain
{
    public function __construct(
        public readonly float $min,
        public readonly float $max,
        public readonly bool $empty = false,
    ) {}

    /**
     * @param  list<list<float|null>>  $series  una lista de valores por serie
     * @param  bool  $includeZero  lo exige cualquier marca cuya longitud represente el valor (barras)
     */
    public static function fromSeries(
        array $series,
        bool $includeZero = false,
        ?float $min = null,
        ?float $max = null,
        int $tickCount = 5,
        bool $nice = true,
    ): self {
        $values = [];

        foreach ($series as $serie) {
            foreach ($serie as $value) {
                if ($value === null || ! is_numeric($value) || ! is_finite((float) $value)) {
                    continue;   // hueco: no participa en el dominio
                }

                $values[] = (float) $value;
            }
        }

        if ($values === []) {
            return new self(0.0, 1.0, empty: true);
        }

        $lo = $min ?? min($values);
        $hi = $max ?? max($values);

        if ($includeZero) {
            $lo = min($lo, 0.0);
            $hi = max($hi, 0.0);
        }

        [$lo, $hi] = self::expandIfFlat($lo, $hi);

        if ($nice) {
            [$niceLo, $niceHi] = Ticks::nice($lo, $hi, $tickCount);

            // Un min/max explícito del usuario es un contrato, no una sugerencia: no se
            // redondea por debajo de lo que ha pedido.
            $lo = $min ?? $niceLo;
            $hi = $max ?? $niceHi;
        }

        return new self($lo, $hi);
    }

    /**
     * Un dominio plano no se puede escalar. Se abre un margen alrededor del valor.
     *
     * Si el valor es 0, el rango [-1, 1] sería absurdo (nadie tiene ventas negativas por
     * defecto), así que se usa [0, 1]. En cualquier otro caso se abre un ±10 % para que el
     * dato quede centrado y se lea como lo que es: una línea plana.
     *
     * @return array{0: float, 1: float}
     */
    private static function expandIfFlat(float $lo, float $hi): array
    {
        if ($lo != $hi) {
            return [$lo, $hi];
        }

        if ($lo == 0.0) {
            return [0.0, 1.0];
        }

        $margin = abs($lo) * 0.1;

        return $lo > 0
            ? [max(0.0, $lo - $margin), $hi + $margin]
            : [$lo - $margin, min(0.0, $hi + $margin)];
    }

    /** @return array{0: float, 1: float} */
    public function toArray(): array
    {
        return [$this->min, $this->max];
    }

    /** @return list<float> */
    public function ticks(int $count = 5): array
    {
        return Ticks::ticks($this->min, $this->max, $count);
    }

    public function spansZero(): bool
    {
        return $this->min < 0 && $this->max > 0;
    }
}
