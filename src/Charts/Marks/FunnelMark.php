<?php

namespace KoreUi\Charts\Marks;

/**
 * Un embudo (funnel): cuánta gente sobrevive cada paso de un proceso.
 *
 * Visitas → registros → carrito → compra. Cada etapa es un trapecio centrado, más estrecho que
 * el anterior; el estrechamiento ES la caída, y la etiqueta la pone en número.
 *
 * ## Ordinal, no categórico
 *
 * Las etapas van EN ORDEN, y cambiar el orden cambia el significado. Por eso el color sale de la
 * rampa ORDINAL (`--kore-ord-*`), no de la categórica: la categórica dice «estas cosas son
 * distintas», la ordinal dice «estas cosas van en esta secuencia». Un embudo con la paleta
 * categórica está codificando mal — y además el color no lleva aquí el peso de la información: eso
 * ya lo hace la geometría (el ancho del trapecio). El color sólo dice «vas por aquí».
 */
final class FunnelMark extends Mark
{
    /** El ancho de un trapecio ES el valor, así que la escala arranca en cero por definición. */
    public function requiresZero(): bool
    {
        return true;
    }

    /** Cajas HTML con `clip-path`, no trazo: un trapecio es un polígono de cuatro puntos. */
    public function medium(): string
    {
        return self::HTML;
    }

    public function type(): string
    {
        return 'funnel';
    }
}
