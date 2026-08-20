<?php

use Symfony\Component\Finder\Finder;

/**
 * Un `role` promete un modelo de interacción. Si no se cumple, estorba.
 *
 * Los dos casos de aquí salieron del lote de interacción, y los dos tienen la
 * misma forma: alguien escribió el rol que describía la PINTA del componente, no
 * lo que el componente hacía.
 *
 * - El carrusel llevaba `role="tablist"` con sus `role="tab"` y **cero**
 *   `role="tabpanel"`. Un lector anunciaba «pestaña 2 de 4» y al activarla no
 *   encontraba ningún panel asociado. Encima, con `numVisible` mayor que uno
 *   cada punto lleva a un GRUPO de diapositivas, así que la relación uno a uno
 *   que un tablist promete no podía existir.
 * - El speed-dial ponía `role="menuitem"` en el `<div>` envoltorio, con el
 *   `<button>` DENTRO. Un menuitem no puede contener un control: la relación se
 *   rompe y la acción deja de anunciarse como activable.
 *
 * Es un cepo para el próximo componente que se invente su semántica. Lo que
 * mide de verdad —qué anuncia un lector— está en
 * `demo/e2e/specs/46-interaccion-carrusel` y `48-interaccion-flotantes`.
 */
it('ninguna pestaña se queda sin panel', function () {
    $problemas = [];

    foreach (vistasDeComponentes() as $nombre => $contenido) {
        if (! str_contains($contenido, 'role="tab"') && ! str_contains($contenido, 'role="tablist"')) {
            continue;
        }

        // El panel puede estar en OTRO archivo del mismo componente: `tab` reparte
        // la lista en `index.blade.php` y los paneles en `panel.blade.php`. Por eso
        // se mira el componente entero y no cada vista por separado.
        if (! str_contains($contenido, 'role="tabpanel"')) {
            $problemas[] = sprintf('%s  usa role="tab" sin ningún role="tabpanel" al otro lado', $nombre);
        }
    }

    expect($problemas)->toBe([], implode("\n", array_merge(
        ['Pestañas que no llevan a ningún panel:'],
        $problemas,
        ['', 'Si los indicadores no controlan paneles, son botones: usa aria-current.'],
    )));
});

it('ningún elemento de menú contiene un control', function () {
    $problemas = [];

    $vistas = Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php');

    foreach ($vistas as $vista) {
        $contenido = sinComentariosBlade($vista->getContents());

        // Lo que se mira es la ETIQUETA que lleva el rol, no lo que hay debajo.
        //
        // El primer intento buscaba controles en el cuerpo del elemento, y daba
        // tres falsos positivos: en un menú con varios items seguidos, el cuerpo
        // de uno se comía la apertura del siguiente. Mirar el nombre de la
        // etiqueta responde a la misma pregunta sin ambigüedad.
        foreach (etiquetasConRol($contenido, 'menuitem') as [$linea, $etiqueta]) {
            if (preg_match('/^(button|a|input|select|textarea)$/i', $etiqueta)) {
                continue;
            }

            $problemas[] = sprintf(
                '%s:%d  role="menuitem" en un <%s>, no en el control',
                str_replace(realpath(__DIR__.'/../../').'/', '', $vista->getRealPath()),
                $linea,
                $etiqueta
            );
        }
    }

    expect($problemas)->toBe([], implode("\n", array_merge(
        ['Elementos de menú que envuelven un control en vez de serlo:'],
        $problemas,
        ['', 'Pon role="menuitem" en el <button> o el <a>, y role="none" en el envoltorio.'],
    )));
});

/** Las vistas agrupadas por componente: un `tab/` es una sola cosa en tres archivos. */
function vistasDeComponentes(): array
{
    $raiz = realpath(__DIR__.'/../../resources/views');
    $porComponente = [];

    foreach (Finder::create()->files()->in($raiz)->name('*.blade.php') as $vista) {
        $relativa = str_replace($raiz.'/', '', $vista->getRealPath());
        $clave = str_contains($relativa, '/')
            ? dirname($relativa)
            : $relativa;

        $porComponente[$clave] = ($porComponente[$clave] ?? '').sinComentariosBlade($vista->getContents());
    }

    return $porComponente;
}

/**
 * Fuera los comentarios de Blade, conservando la numeración de líneas.
 *
 * Uno solo que mencione un `<button>` de ejemplo bastaría para dar por bueno un
 * envoltorio que no lo es —o al revés—.
 */
function sinComentariosBlade(string $contenido): string
{
    return preg_replace_callback(
        '/\{\{--.*?--\}\}/s',
        fn ($c) => str_repeat("\n", substr_count($c[0], "\n")),
        $contenido
    );
}

/**
 * Nombre de la etiqueta que lleva un `role` concreto, y en qué línea.
 *
 * Los atributos van uno por línea en estas vistas, así que hay que retroceder
 * desde el `role=` hasta el `<` que abre el elemento. Un `role` dentro de una
 * expresión de Alpine —`:role="..."`— no cuenta: ahí el valor lo decide el
 * componente en tiempo de ejecución.
 *
 * @return array<int, array{int, string}>
 */
function etiquetasConRol(string $contenido, string $rol): array
{
    $salida = [];
    $lineas = preg_split('/\R/', $contenido);

    foreach ($lineas as $n => $linea) {
        if (! preg_match('/(?<![:\w-])role="'.$rol.'"/', $linea)) {
            continue;
        }

        for ($i = $n; $i >= 0 && $i > $n - 40; $i--) {
            if (preg_match('/<([a-zA-Z][a-zA-Z0-9-]*)/', $lineas[$i], $m)) {
                $salida[] = [$i + 1, $m[1]];
                break;
            }
        }
    }

    return $salida;
}
