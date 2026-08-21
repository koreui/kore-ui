<?php

use Symfony\Component\Finder\Finder;

/**
 * Ningún texto de interfaz escrito a mano dentro de una vista.
 *
 * Durante seis lotes la librería mezcló idiomas en la misma pantalla: el botón
 * decía «Añadir» y el desplegable de al lado, «No options found». Y no era solo
 * el idioma — un texto incrustado en el Blade **no se puede cambiar sin publicar
 * la vista**, así que quien montara la librería en inglés, en catalán o en
 * portugués tenía que copiar los componentes enteros.
 *
 * Todo lo que llega al usuario —`aria-label`, `placeholder`, `title`,
 * `aria-roledescription` y los valores por defecto de las props de texto— sale
 * ahora de `kore-ui.form.translations` o `kore-ui.ui.translations`.
 *
 * Este cepo mira lo contrario de lo que parece: no busca palabras inglesas —esa
 * lista siempre se queda corta, y de hecho la primera versión se dejó fuera
 * «Rating», «Min» y «Max»— sino **literales sin interpolar**. Da igual el
 * idioma: si el texto está escrito a mano en la vista, no se puede traducir.
 */
it('ningún texto de interfaz está incrustado en una vista', function () {
    $problemas = [];

    // Lo que sí puede ir literal, porque no es texto que nadie lea:
    //   · valores de atributos que son datos («#000000», «0 0 24 24»)
    //   · fragmentos de expresiones de Alpine que el regex parte por la mitad
    $exentos = '/^(#[0-9a-fA-F]{3,8}|[\d\s.,-]+|.*(===|\?|@js\(|\(item\)|\(\)).*)$/';

    $vistas = Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php');

    foreach ($vistas as $vista) {
        $contenido = preg_replace('/\{\{--.*?--\}\}/s', '', $vista->getContents());
        $relativa = str_replace(realpath(__DIR__.'/../../').'/', '', $vista->getRealPath());

        foreach (preg_split('/\R/', $contenido) as $n => $linea) {
            // Un literal es lo que NO lleva `{{ }}`, `$` ni `@js(` dentro.
            //
            // El `(?<![:.\w])` de delante descarta los bindings de Alpine:
            // `:placeholder="currentPlaceholder"` acaba en «placeholder» y el
            // valor es una EXPRESIÓN, no un texto.
            $patron = '/(?<![:.\w-])(aria-label|placeholder|aria-roledescription)=["\']([^"\'{}$]+?)["\']/';

            if (! preg_match_all($patron, $linea, $coincidencias, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($coincidencias as [, $atributo, $texto]) {
                if (preg_match($exentos, trim($texto))) {
                    continue;
                }

                $problemas[] = sprintf('%s:%d  %s="%s"', $relativa, $n + 1, $atributo, $texto);
            }
        }
    }

    expect($problemas)->toBe([], implode("\n", array_merge(
        ['Texto de interfaz que no se puede traducir sin publicar la vista:'],
        $problemas,
        ['', 'Sácalo a kore-ui.ui.translations o kore-ui.form.translations.'],
    )));
});

/**
 * Y las props de texto tampoco pueden traer su valor por defecto escrito.
 *
 * `'filterPlaceholder' => 'Filter...'` en el `@props` es igual de intraducible
 * que un literal en el atributo, y encima cuesta más de ver.
 */
it('ninguna prop de texto trae su valor por defecto escrito a mano', function () {
    $problemas = [];

    $vistas = Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php');

    foreach ($vistas as $vista) {
        $contenido = $vista->getContents();
        $relativa = str_replace(realpath(__DIR__.'/../../').'/', '', $vista->getRealPath());

        // Solo dentro del bloque `@props([...])`.
        if (! preg_match('/@props\(\[(.*?)\]\)/s', $contenido, $m)) {
            continue;
        }

        // `optionLabel` y `optionValue` no son texto de interfaz: son el NOMBRE
        // de la clave que el componente busca dentro de cada opción.
        $noSonTexto = ['optionLabel', 'optionValue'];

        $patron = "/'(\w*(?:[Ll]abel|[Pp]laceholder|[Tt]ext|[Tt]itle))'\s*=>\s*'([^']+)'/";

        if (! preg_match_all($patron, $m[1], $coincidencias, PREG_SET_ORDER)) {
            continue;
        }

        foreach ($coincidencias as [, $prop, $valor]) {
            if (in_array($prop, $noSonTexto, true)) {
                continue;
            }

            $problemas[] = sprintf('%s  %s => \'%s\'', $relativa, $prop, $valor);
        }
    }

    expect($problemas)->toBe([], implode("\n", array_merge(
        ['Props de texto con el valor por defecto escrito en la vista:'],
        $problemas,
        ['', 'Pon null y resuélvelo con config() dentro del @php.'],
    )));
});
