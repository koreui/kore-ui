<?php

namespace KoreUi\Charts\Scales;

use KoreUi\Charts\TextWidth;

/**
 * Cuánto ocupa la etiqueta de un tick, en `ch`.
 *
 * Es lo que permite ACOTAR la etiqueta dentro del área de trazado sin medir un solo píxel: el
 * servidor cuenta caracteres, emite el ancho en `ch`, y el CSS hace
 *
 *     left: clamp(0, kx% - ancho/2, 100% - ancho)
 *
 * — o sea, la centra sobre su tick, pero nunca la deja salirse. El navegador resuelve el `ch`
 * con la fuente de verdad.
 *
 * ⚠️ Esto **sustituye** al truco anterior de anclar por el borde la primera y la última
 * etiqueta (`data-edge`). Aquel funcionaba porque las únicas etiquetas que se salían eran las de
 * los extremos… en una escala de bandas. En una escala continua, un tick puede caer en el 98,9 %
 * —ni centrado ni en el borde— y ahí el umbral no salta y media etiqueta se va fuera de la
 * tarjeta. Medido: 5 px fuera, y con ellos scroll horizontal en toda la página.
 *
 * El clamp no tiene umbrales que afinar: es exacto por construcción, y encima resuelve de paso
 * los dos casos que el otro trataba a mano (el «EneFeb» de una banda y la etiqueta que se metía
 * debajo de la canaleta del eje Y en una escala de punto).
 */
trait TickBox
{
    /** El ancho del tick es el del más largo de sus dos renglones: la etiqueta y su contexto. */
    protected function tickWidth(string $label, ?string $context = null): float
    {
        return max(
            TextWidth::ch($label),
            $context === null ? 0.0 : TextWidth::ch($context),
            2.0,   // un mínimo, o un tick de un dígito daría una caja ridícula
        );
    }
}
