<?php

namespace KoreUi\Charts;

/**
 * Cómo se escribe un número en un eje o en un tooltip.
 *
 * **Esto vive en PHP a propósito, y es una decisión de arquitectura, no una comodidad.** El
 * payload que viaja al navegador lleva las etiquetas YA FORMATEADAS. Si viajaran números
 * crudos, el JavaScript tendría que saber de monedas, de locales y de separadores — es
 * decir, habría que portar esta clase a JS y mantener dos implementaciones sincronizadas
 * para siempre. Formateando aquí, el bundle no lleva ni un byte de `Intl`.
 *
 * No se usa `ext-intl`: no está garantizada en todos los hostings de Laravel, y un gráfico
 * no puede depender de una extensión opcional para escribir "1.234,56 €".
 */
final class Format
{
    public function __construct(
        public readonly string $style = 'number',       // number|currency|percent|compact
        public readonly ?int $decimals = null,          // null = se deduce del paso del eje
        public readonly string $currency = '€',
        public readonly bool $currencyAfter = true,     // "12 €" (es) vs "$12" (en)
        public readonly string $decimalSeparator = ',',
        public readonly string $thousandsSeparator = '.',
        public readonly string $prefix = '',
        public readonly string $suffix = '',
    ) {}

    /** @param array<string, mixed> $config */
    public static function fromConfig(?string $style, array $config = []): self
    {
        return new self(
            style: $style ?: 'number',
            decimals: $config['decimals'] ?? null,
            currency: $config['currency'] ?? '€',
            currencyAfter: $config['currency_after'] ?? true,
            decimalSeparator: $config['decimal_separator'] ?? ',',
            thousandsSeparator: $config['thousands_separator'] ?? '.',
            prefix: $config['prefix'] ?? '',
            suffix: $config['suffix'] ?? '',
        );
    }

    /**
     * Cuántos decimales necesita una serie para no mentir.
     *
     * El eje deduce sus decimales del paso entre ticks. Una serie no tiene paso, así que sin
     * esto se escribía con cero decimales: un sensor que marca 21,4 °C aparecía en el tooltip
     * como "21". El valor no era falso, pero tampoco era el que había.
     *
     * Se calcula UNA vez por serie y se aplica a todos sus valores, no valor a valor: una
     * columna con "21,4" encima de "23" se lee peor que una con "21,4" encima de "23,0".
     * Es la misma razón por la que un eje usa los mismos decimales en todos sus ticks.
     *
     * @param  list<float|int|null>  $values
     */
    public static function decimalsFor(array $values, int $max = 2): int
    {
        $needed = 0;

        foreach ($values as $value) {
            if ($value === null || ! is_finite((float) $value)) {
                continue;
            }

            for ($places = $needed; $places < $max; $places++) {
                // Se compara con una tolerancia relativa: 21.4 no es exactamente 21.4 en coma
                // flotante, y `round($v, 1) == $v` sería falso para valores grandes.
                if (abs(round((float) $value, $places) - (float) $value) <= 1e-9 * max(1.0, abs((float) $value))) {
                    break;
                }
            }

            $needed = max($needed, $places);
        }

        return $needed;
    }

    /**
     * @param  int|null  $decimals  los que exige el paso del eje, si no se han fijado a mano
     */
    public function apply(float|int|null $value, ?int $decimals = null): string
    {
        if ($value === null || ! is_finite((float) $value)) {
            return '—';   // un hueco se escribe como hueco, no como "0"
        }

        $value = (float) $value;
        $places = $this->decimals ?? $decimals ?? 0;

        $body = match ($this->style) {
            'compact' => $this->compact($value),
            'percent' => $this->number($value, $places).' %',
            'currency' => $this->currencyAfter
                ? $this->number($value, $places).' '.$this->currency
                : $this->currency.$this->number($value, $places),
            default => $this->number($value, $places),
        };

        return $this->prefix.$body.$this->suffix;
    }

    /**
     * 1.200 → "1,2k". Un eje con "1.200.000" repetido cinco veces no se lee: se descifra.
     */
    private function compact(float $value): string
    {
        $abs = abs($value);

        [$divisor, $unit] = match (true) {
            $abs >= 1e12 => [1e12, 'B'],
            $abs >= 1e9 => [1e9, 'MM'],
            $abs >= 1e6 => [1e6, 'M'],
            $abs >= 1e3 => [1e3, 'k'],
            default => [1.0, ''],
        };

        $scaled = $value / $divisor;

        // Un decimal sólo si aporta: "1,2k" sí, pero "12,0k" es ruido.
        $places = $unit !== '' && abs($scaled) < 100 && fmod($scaled, 1.0) != 0.0 ? 1 : 0;

        return $this->number($scaled, $places).$unit;
    }

    private function number(float $value, int $decimals): string
    {
        // -0 existe en coma flotante y se imprime como "-0". Nadie quiere eso en un eje.
        if ($value == 0.0) {
            $value = 0.0;
        }

        return number_format($value, max(0, $decimals), $this->decimalSeparator, $this->thousandsSeparator);
    }
}
