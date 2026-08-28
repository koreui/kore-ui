<?php

namespace KoreUi\Core\Support;

/**
 * Las banderas de aspecto —borde, sombra, relleno, densidad— y de dónde sale su
 * valor cuando la etiqueta no dice nada.
 *
 * **Por qué existe.** Cada superficie de la librería resolvía su aspecto por su
 * cuenta, y no todas de la misma manera: `card` leía `ui.card.bordered`,
 * `navbar` leía su propia sección con otro `??`, `stats` pintaba el borde y la
 * sombra fijos sin preguntar a nadie, y el `bordered` de `table` era un prop
 * declarado que **no se usaba en ninguna parte** — un interruptor que no
 * encendía nada. Quien quería la librería entera sin sombras no tenía dónde
 * decirlo: había que repetir `:shadow="false"` etiqueta por etiqueta.
 *
 * **La cascada**, de más fuerte a más débil:
 *
 * 1. El prop de la etiqueta. `<x-kore::card :shadow="false">` manda siempre.
 * 2. `config('kore-ui.ui.<componente>.<bandera>')` — el ajuste de ese componente.
 * 3. `config('kore-ui.ui.look.<bandera>')` — el ajuste de TODA la librería.
 * 4. El defecto que el propio componente considera razonable.
 *
 * Los niveles 2 y 3 valen `null` de fábrica, que significa «no opino»: así el
 * aspecto de siempre no cambia hasta que alguien decide cambiarlo. Un `false`
 * en el nivel 3 apaga la bandera en todas partes de una vez; un `true` en el 2
 * la devuelve a un componente concreto.
 *
 * ```php
 * // config/kore-ui.php — la librería entera plana, salvo las tarjetas
 * 'ui' => [
 *     'look' => ['shadow' => false],
 *     'card' => ['shadow' => true],
 * ],
 * ```
 */
final class Look
{
    /**
     * Las banderas que participan de la cascada. Fuera de esta lista, un nombre
     * es una errata: `config()` devolvería `null` para siempre y la bandera se
     * quedaría en su defecto sin que nadie se entere.
     */
    public const BANDERAS = ['bordered', 'shadow', 'padding', 'compact'];

    /**
     * @param  string  $componente  la clave bajo `ui.` (por ejemplo `card`). Con un
     *                               punto dentro se toma como ruta completa bajo
     *                               `kore-ui.`, para los que no viven en `ui`
     *                               —`shell.navbar`, `datatable`…—.
     * @param  string  $bandera  una de las de BANDERAS
     * @param  bool|null  $prop  lo que traía la etiqueta; `null` es «no dice nada»
     * @param  bool  $defecto  lo que el componente hace cuando nadie opina
     */
    public static function resolver(string $componente, string $bandera, ?bool $prop, bool $defecto): bool
    {
        if ($prop !== null) {
            return $prop;
        }

        if (! in_array($bandera, self::BANDERAS, true)) {
            throw new \InvalidArgumentException(
                "«{$bandera}» no es una bandera de aspecto. Las que hay: " . implode(', ', self::BANDERAS) . '.'
            );
        }

        $ruta = str_contains($componente, '.') ? $componente : "ui.{$componente}";

        $delComponente = config("kore-ui.{$ruta}.{$bandera}");

        if ($delComponente !== null) {
            return (bool) $delComponente;
        }

        $global = config("kore-ui.ui.look.{$bandera}");

        return $global !== null ? (bool) $global : $defecto;
    }
}
