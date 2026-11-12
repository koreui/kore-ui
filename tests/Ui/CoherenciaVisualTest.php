<?php

use Symfony\Component\Finder\Finder;

/**
 * El aspecto de la librería sale de los tokens, y de ningún otro sitio.
 *
 * Antes de la 3.0 cada plantilla escribía sus propias sombras, su anillo de foco
 * y su radio con las utilidades sueltas de Tailwind. Funcionaba, y por eso nadie
 * lo vio: 50 `shadow` a secas, 29 `rounded` sin token, el foco como `focus:` en
 * 37 sitios y como `focus-visible:` en 20. Dos componentes de la misma pantalla
 * se veían distinto sin que hubiera una sola decisión detrás.
 *
 * Esto es el cepo para que no vuelva a pasar con el próximo componente. No
 * comprueba que algo se vea bien —eso lo miden las capturas de `demo/e2e`—:
 * comprueba que nadie se salga del sistema.
 */
function buscarEnVistas(string $patron, array $excepto = []): array
{
    $hallazgos = [];

    $vistas = Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php');

    foreach ($vistas as $vista) {
        foreach (preg_split('/\R/', $vista->getContents()) as $n => $linea) {
            if (preg_match_all($patron, $linea, $m)) {
                foreach ($m[1] as $clase) {
                    if (! in_array($clase, $excepto, true)) {
                        $hallazgos[] = sprintf('%s:%d  %s', $vista->getRelativePathname(), $n + 1, $clase);
                    }
                }
            }
        }
    }

    return $hallazgos;
}

it('las sombras son niveles de elevación, no utilidades sueltas', function () {
    $sueltas = buscarEnVistas('/(?<![\w-])((?:[a-z-]+:)*shadow-(?:xs|sm|md|lg|xl|2xl))(?![\w-])/');

    expect($sueltas)->toBe([], "Usa shadow-kore-xs/sm/md/lg/xl:\n".implode("\n", $sueltas));
});

it('los radios salen de los tokens', function () {
    $sueltos = buscarEnVistas('/(?<![\w-])((?:[a-z-]+:)*rounded(?:-[trblse]{1,2})?-(?:xs|sm|md|lg|xl|2xl|3xl))(?![\w-])/');

    expect($sueltos)->toBe([], "Usa rounded-kore-sm/md/lg/xl:\n".implode("\n", $sueltos));
});

it('el foco se pinta de una sola manera', function () {
    // `kore-focus` para lo que se pulsa, `kore-control` para lo que se rellena.
    // Un `focus:ring-*` escrito a mano es un anillo que no sigue al token y que
    // además aparece con un clic de ratón.
    $anillos = buscarEnVistas('/(?<![\w-])((?:focus|focus-visible|focus-within):ring[a-z0-9\/-]*|ring-offset-[a-z0-9-]+)(?![\w-])/');

    expect($anillos)->toBe([], "Usa kore-focus, kore-focus-inset, kore-focus-within o kore-control:\n".implode("\n", $anillos));
});

it('nada que se pueda enfocar se queda sin señal de foco', function () {
    // `focus:outline-none` sin sustituto es un control invisible para quien
    // navega con teclado. Pasaba en el ojo de `password` y en el botón de
    // quitar un paso del spotlight.
    $sinSenal = buscarEnVistas('/(?<![\w-])((?:focus|focus-visible):outline-none)(?![\w-])/');

    expect($sinSenal)->toBe([], implode("\n", $sinSenal));
});

it('no quedan colores de la paleta de Tailwind fuera de los tokens', function () {
    $fijos = buscarEnVistas(
        '/(?<![\w-])((?:[a-z-]+:)*(?:bg|text|border|ring|fill|stroke)-(?:gray|slate|zinc|neutral|stone|red|green|blue|yellow|amber|orange|emerald|sky|indigo|violet|rose)-\d{2,3}(?:\/\d+)?)(?![\w-])/',
        // El sol y la luna del interruptor de tema: son el dibujo, no el tema.
        ['text-amber-500', 'text-slate-600'],
    );

    expect($fijos)->toBe([], "Un color fijo no cambia con el tema ni con los tokens del consumidor:\n".implode("\n", $fijos));
});

it('un campo es un campo en todos los componentes de formulario', function (string $etiqueta) {
    // El borde, la sombra, el halo de foco y el estado de error salen de
    // `kore-control`. Si uno lo pinta por su cuenta, deja de moverse con los demás.
    $this->blade($etiqueta)->assertSee('kore-control', false);
})->with([
    'input'       => ['<x-kore::input name="a" />'],
    'input addon' => ['<x-kore::input name="a" prefix="€" />'],
    'textarea'    => ['<x-kore::textarea name="a" />'],
    'password'    => ['<x-kore::password name="a" />'],
    'number'      => ['<x-kore::number name="a" />'],
    'maskable'    => ['<x-kore::maskable name="a" mask="##" />'],
    'select'      => ['<x-kore::select name="a" :options="[]" />'],
    'datepicker'  => ['<x-kore::datepicker name="a" />'],
    'time-picker' => ['<x-kore::time-picker name="a" />'],
    'color'       => ['<x-kore::color-picker name="a" />'],
    'tag-input'   => ['<x-kore::tag-input name="a" />'],
    'input-otp'   => ['<x-kore::input-otp name="a" />'],
    'key-value'   => ['<x-kore::key-value name="a" />'],
    'editor'      => ['<x-kore::editor name="a" />'],
]);

it('y marca el error igual en todos', function (string $etiqueta) {
    $html = (string) $this->blade($etiqueta);

    expect(str_contains($html, 'kore-invalid') || str_contains($html, 'aria-invalid="true"'))->toBeTrue();
})->with([
    'input'       => ['<x-kore::input name="a" error="Mal" />'],
    'textarea'    => ['<x-kore::textarea name="a" error="Mal" />'],
    'password'    => ['<x-kore::password name="a" error="Mal" />'],
    'number'      => ['<x-kore::number name="a" error="Mal" />'],
    'select'      => ['<x-kore::select name="a" :options="[]" error="Mal" />'],
    'datepicker'  => ['<x-kore::datepicker name="a" error="Mal" />'],
    'time-picker' => ['<x-kore::time-picker name="a" error="Mal" />'],
    'tag-input'   => ['<x-kore::tag-input name="a" error="Mal" />'],
]);

it('la casilla es la misma dentro y fuera de <x-kore::checkbox>', function () {
    // La selección de filas de la tabla, el árbol y el transfer pintaban la
    // casilla del navegador. Ahora comparten la del componente.
    $this->blade('<x-kore::checkbox name="a" />')->assertSee('kore-checkmark', false);

    foreach (['datatable/datatable', 'datatable/column-select', 'components/tree/index', 'components/transfer/index'] as $vista) {
        expect(file_get_contents(__DIR__."/../../resources/views/{$vista}.blade.php"))->toContain('kore-checkmark');
    }
});

it('declara en la hoja de estilos todo lo que las plantillas nombran', function () {
    // Una utilidad `kore-*` que no existe no da error: es una clase sin regla.
    $css = file_get_contents(__DIR__.'/../../resources/css/kore-theme.css');

    preg_match_all('/@utility (kore-[a-z-]+)/', $css, $m);
    $declaradas = $m[1];

    $usadas = [];
    $vistas = Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php');

    foreach ($vistas as $vista) {
        preg_match_all('/(?<![\w-])(kore-(?:transition|duration|focus|control|panel|pop|fade|slide|drawer|press|option|checkmark|radiomark)[a-z-]*)(?![\w-])/', $vista->getContents(), $u);
        $usadas = array_merge($usadas, $u[1]);
    }

    expect(array_values(array_diff(array_unique($usadas), $declaradas)))->toBe([]);

    // Y los niveles de elevación, que son tokens de @theme y no utilidades.
    foreach (['xs', 'sm', 'md', 'lg', 'xl', 'solid'] as $nivel) {
        expect($css)->toContain("--shadow-kore-{$nivel}: var(--kore-shadow-{$nivel});");
    }
});

/**
 * Un panel cerrado no cuesta nodos… salvo el del dropdown, y es a propósito.
 *
 * El panel de un select, un calendario, un selector de hora o de color lo
 * dibuja Alpine: se puede montar la primera vez que se abre. Un calendario
 * cerrado eran 152 nodos; en un formulario de 48 campos, dos tercios del DOM
 * eran paneles que nadie había abierto.
 *
 * El del dropdown es el slot del consumidor, que pinta el servidor. Livewire lo
 * actualiza en cada re-render a través del `x-teleport`, y no puede hacerlo
 * dentro de un `x-if`: el menú se quedaría con el contenido de la primera vez
 * que se abrió. Se probó, y el botón «Restablecer» del selector de columnas de
 * la tabla dejó de aparecer.
 */
it('monta al abrir los paneles que dibuja Alpine', function (string $vista) {
    $html = file_get_contents(__DIR__."/../../resources/views/components/{$vista}.blade.php");

    expect($html)->toMatch('/<template x-if="montado">\s*<template x-teleport="body">/');
})->with(['select', 'datepicker', 'time-picker', 'color-picker']);

it('y deja montado el del dropdown, que es contenido del servidor', function () {
    $html = file_get_contents(__DIR__.'/../../resources/views/components/dropdown/index.blade.php');

    expect($html)->toContain('<template x-teleport="body">')
        ->not->toContain('x-if="montado"');
});

it('un select cerrado no apunta con aria-controls a una lista que no existe', function () {
    $this->blade('<x-kore::select name="a" :options="[]" />')
        ->assertDontSee(' aria-controls="', false)
        ->assertSee('x-bind:aria-controls="montado ?', false);
});

/**
 * Un `var(--kore-algo)` hacia un token que nadie declara no da error: la
 * propiedad se queda sin valor y el elemento sale sin fondo, o sin borde.
 *
 * Pasó durante la 3.0: un renombrado en bloque se llevó por delante
 * `--kore-field` y la casilla, el radio y el botón con contorno se quedaron
 * leyendo un token que ya no existía. A la vista apenas se notaba —el blanco de
 * la tarjeta de debajo hacía de fondo—.
 */
it('todo token que la hoja de estilos lee está declarado', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/kore-theme.css');

    // Se quitan los comentarios: ahí se nombran tokens de ejemplo.
    $sinComentarios = preg_replace('/\/\*.*?\*\//s', '', $css);

    preg_match_all('/(--kore-[a-z0-9-]+)\s*:/', $sinComentarios, $d);
    $declarados = array_unique($d[1]);

    // Solo los que se leen SIN valor de respaldo: `var(--kore-x, 4px)` es una
    // variable opcional que el consumidor o el servidor pueden poner.
    preg_match_all('/var\((--kore-[a-z0-9-]+)\)/', $sinComentarios, $u);

    // Estos dos los escribe el servidor en el `style` de cada marca del gráfico:
    // el color de la serie viaja como dato, no como token del tema.
    $delServidor = ['--kore-series', '--kore-gauge'];

    expect(array_values(array_diff(array_unique($u[1]), $declarados, $delServidor)))->toBe([]);
});

it('y toda clase de color de token que usan las plantillas existe en @theme', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/kore-theme.css');

    preg_match_all('/--color-kore-([a-z0-9-]+):/', $css, $c);
    $colores = $c[1];

    $usadas = buscarEnVistas('/(?<![\w-])(?:[a-z\[\]&_>*-]+:)*(?:bg|text|border|ring|fill|stroke|outline|divide|from|to|via|accent|caret|decoration)-kore-([a-z]+(?:-[a-z]+)*?)(?:-\d)?(?:\/\d+)?(?![\w-])/');

    $desconocidas = [];

    foreach ($usadas as $hallazgo) {
        $nombre = trim(substr($hallazgo, strrpos($hallazgo, '  ') + 2));

        // Los que se componen en PHP (`bg-kore-{$color}`) o llevan número
        // (`chart-1`) llegan aquí a medias: solo se comprueba el nombre entero.
        if (! in_array($nombre, $colores, true)
            && ! in_array("{$nombre}-1", $colores, true)
            && ! str_contains($hallazgo, '{$')
            && ! str_contains($hallazgo, '{{')) {
            $desconocidas[] = $hallazgo;
        }
    }

    expect($desconocidas)->toBe([], "Clases de color sin token en @theme inline:\n".implode("\n", array_unique($desconocidas)));
});

/**
 * Las filas que se mueven y sus asas son UNA pieza, no tres.
 *
 * Sortable, order-list y repeater pintaban cada uno su caja y su asa, y las
 * tres habían ido divergiendo. Ahora salen de `kore-row` y `kore-grip`.
 */
it('las listas reordenables comparten fila y asa', function () {
    $vistas = __DIR__.'/../../resources/views/components/';

    foreach (['sortable/item', 'order-list/index', 'repeater/item'] as $vista) {
        $html = file_get_contents($vistas.$vista.'.blade.php');

        expect($html)->toContain('kore-row')
            ->toContain('kore-grip')
            ->not->toContain('cursor-grab text-kore-muted-fg');
    }

    expect(file_get_contents($vistas.'key-value.blade.php'))->toContain('kore-grip');
});

/**
 * El aspecto de la barra del splitter es de la hoja de estilos.
 *
 * Se pintaba desde JavaScript, con `element.style`: un tema no podía cambiarle
 * el color ni el grosor, y `data-kore-motion="off"` no le llegaba.
 */
it('el splitter no pinta su barra desde JavaScript', function () {
    $js = file_get_contents(__DIR__.'/../../resources/js/ui/splitter.js');

    expect($js)->toContain('kore-splitter-gutter')
        ->not->toContain('style.background')
        ->not->toContain('style.opacity')
        ->not->toContain('style.transition');

    expect(file_get_contents(__DIR__.'/../../resources/css/kore-theme.css'))->toContain('.kore-splitter-gutter');
});
