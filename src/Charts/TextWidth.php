<?php

namespace KoreUi\Charts;

/**
 * Cuánto ocupa un texto, en `ch`.
 *
 * El servidor **no puede medir texto**: no conoce la fuente, ni el tamaño, ni el navegador. Pero
 * sí puede CONTAR CARACTERES, y `ch` deja que el navegador haga la conversión a píxeles con la
 * fuente real. Es la pieza que permite maquetar un gráfico sin JavaScript.
 *
 * Lo que NO se puede hacer es contar cada carácter como un `ch`. Medido a 12 px (1ch = 7,56 px):
 *
 *  - una cifra mide **1,00 ch** — exacto, porque las etiquetas usan `tabular-nums` y ahí toda
 *    cifra mide lo que la cifra «0», que es la definición misma de `ch`;
 *  - el punto mide **0,47**, el espacio **0,45**;
 *  - y el «%» mide **1,47**, MÁS que una cifra.
 *
 * Contando todo a 1 ch, «7.000 €» pedía 7,5 ch cuando ocupa 5,9 — y la canaleta se comía 31 px
 * de nada, empujando el gráfico entero a la derecha.
 *
 * Los pesos son **cotas superiores** de lo medido, no lo medido: la fuente la pone la aplicación,
 * no nosotros. Pasarse un par de píxeles es barato; quedarse corto saca la etiqueta de la tarjeta.
 */
final class TextWidth
{
    public static function ch(string $text): float
    {
        $width = 0.0;

        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            $width += match (true) {
                // Con tabular-nums, toda cifra mide exactamente 1ch.
                ctype_digit($char) => 1.0,
                // Separadores y espacios (incluido el fino y el duro, que usa el formateo — y sí,
                // hacen falta los tres: las versiones de ICU no se ponen de acuerdo en cuál meten
                // antes del «€»).
                in_array($char, ['.', ',', ':', ' ', ' ', ' ', "'"], true) => 0.5,
                $char === '-' || $char === '−' || $char === '+' => 0.8,
                // Todo lo demás —moneda, %, la «M» de compacto, un mes abreviado, un sufijo
                // cualquiera— se estima por arriba.
                default => 1.3,
            };
        }

        return round($width, 2);
    }
}
