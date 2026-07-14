<?php

namespace KoreUi\Charts;

use InvalidArgumentException;

/**
 * Datos en vivo.
 *
 * ## Lo que NO hay que construir, porque ya funciona
 *
 * **El morph de Livewire ES el mecanismo de actualización.** Cambias el dato en PHP, Livewire
 * actualiza el atributo `d` del `<path>` **sin recrear el nodo**, y ya está. Sin `wire:ignore`,
 * sin `chart.update()`, sin una instancia de JavaScript que proteger.
 *
 * Eso no es un detalle: es el issue #20103 de Filament («el gráfico parpadea en cada polling»),
 * abierto porque cada refresco destruye y recrea una instancia de Chart.js. Nosotros no podemos
 * tener ese bug — no hay instancia que destruir.
 *
 * ## Lo que sí hay que construir: saber cuándo NO refrescar
 *
 * Un `wire:poll` a secas refresca siempre. Y hay tres momentos en que refrescar es hostil:
 *
 *  - **Mientras lees un tooltip.** El dato se mueve bajo el cursor y el número que estabas
 *    mirando cambia mientras lo miras.
 *  - **Con la pestaña oculta.** Diez pestañas abiertas son diez renders por segundo en tu
 *    servidor, para nadie.
 *  - **Con el zoom puesto.** Has ampliado para mirar algo concreto; que se te mueva el suelo
 *    debajo es exactamente lo que no quieres.
 *
 * Por eso el refresco lo conduce el gráfico (`$wire.$refresh()`), no un `wire:poll` ciego.
 *
 * ## Por qué la LÍNEA no se anima, y las barras sí
 *
 * Las barras y los puntos son `<div>` con la posición en custom properties: una transición CSS
 * sobre `top`/`height` funciona en todas partes y hace lo correcto.
 *
 * La línea es un `<path>`, y animarla exigiría `transition: d`. **Medido en los tres motores:**
 * Firefox interpola, **WebKit ni siquiera lo soporta** (`CSS.supports('d')` devuelve `false`) y
 * Chromium dice soportarlo pero da un salto seco.
 *
 * Y hay una razón mejor para no hacerlo aunque funcionara: en una ventana deslizante, interpolar
 * `d` lleva el punto *i* hasta el valor del punto *i+1* — o sea, **la onda tiembla en el sitio en
 * vez de desplazarse**. Es la animación equivocada. Un motor de canvas tiene el mismo problema y
 * lo resuelve igual: redibujando.
 */
final class Stream
{
    /**
     * Cada cuánto refrescar, en milisegundos.
     *
     * Acepta «5s», «500ms» o un número.
     */
    public static function interval(string|int $every): int
    {
        $ms = match (true) {
            is_int($every) => $every,
            preg_match('/^(\d+(?:\.\d+)?)\s*ms$/i', trim($every), $m) === 1 => (int) round((float) $m[1]),
            preg_match('/^(\d+(?:\.\d+)?)\s*s$/i', trim($every), $m) === 1 => (int) round((float) $m[1] * 1000),
            ctype_digit(trim((string) $every)) => (int) $every,
            default => throw new InvalidArgumentException(
                "koreUi: «{$every}» no es un intervalo. Escribe «5s», «500ms» o un número de milisegundos."
            ),
        };

        // ⚠️ El suelo no es prudencia: es aritmética.
        //
        // Un refresco es un round-trip COMPLETO de Livewire — query, Blade, morph—, y cuesta entre
        // 30 y 80 ms de servidor más la red. Por debajo de medio segundo los refrescos se solapan,
        // Livewire los encola, y el gráfico va cada vez más por detrás mientras tu servidor arde.
        //
        // Y el cuello de botella real no es ni siquiera ése: es el HTML. Medido, 2.000 puntos son
        // 25 kB gzip POR REFRESCO, y son N renders para N clientes. No hay ninguna arquitectura
        // que dibuje en el servidor que aguante 10 Hz — ni ésta, ni ninguna. Ver docs/chart/streaming.md.
        if ($ms < 500) {
            throw new InvalidArgumentException(
                "koreUi: {$ms} ms es demasiado rápido para un gráfico que dibuja el servidor. Un refresco es un "
                .'round-trip completo de Livewire (30–80 ms de servidor más la red), así que por debajo de medio '
                .'segundo los refrescos se solapan y se encolan. El techo honesto es 1 Hz, y el defecto sensato '
                .'son 5 s — que es lo que usa Filament. Si de verdad necesitas más, lo que hay que cambiar no es '
                .'este número: es el formato de cable. Está explicado en docs/chart/streaming.md.'
            );
        }

        return $ms;
    }
}
