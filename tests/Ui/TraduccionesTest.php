<?php

use Symfony\Component\Finder\Finder;

/**
 * Todo texto que la librería pinta se puede cambiar desde la configuración.
 *
 * Cada clave vive en una sección `translations` de `config/kore-ui.php`, y las
 * vistas la leen con un valor de respaldo: `config('…translations.apply',
 * 'Aplicar')`. Ese respaldo es justo lo que esconde el fallo — una clave que no
 * existe en el archivo sigue pintando su texto, así que nadie nota que no está
 * hasta que alguien publica la configuración para traducirla y no la encuentra.
 */
function vistasDeLaLibreria(): Finder
{
    return Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php');
}

it('declara en la configuración cada clave de traducción que usan las vistas', function () {
    $faltan = [];

    foreach (vistasDeLaLibreria() as $vista) {
        preg_match_all("/config\('kore-ui\.([a-z_.]+\.translations\.[a-zA-Z0-9_]+)'/", $vista->getContents(), $m);

        foreach (array_unique($m[1]) as $clave) {
            // Un prefijo al que se le concatena el resto (`editor_` . $nombre):
            // esas se comprueban en el test de abajo.
            if (str_ends_with($clave, '_')) {
                continue;
            }

            if (config("kore-ui.{$clave}") === null) {
                $faltan[] = "{$clave}  ({$vista->getFilename()})";
            }
        }
    }

    expect($faltan)->toBe([], "Claves que las vistas usan y config/kore-ui.php no declara:\n".implode("\n", $faltan));
});

it('declara también las claves del editor, que se componen con un prefijo', function () {
    $vista = file_get_contents(__DIR__.'/../../resources/views/components/editor.blade.php');

    preg_match_all("/\\\$tr\('([a-z_]+)'/", $vista, $m);

    expect($m[1])->not->toBeEmpty();

    $faltan = array_filter(
        array_unique($m[1]),
        fn (string $nombre) => config("kore-ui.form.translations.editor_{$nombre}") === null,
    );

    expect(array_values($faltan))->toBe([]);
});

/**
 * El cepo para el texto escrito a mano en una plantilla.
 *
 * No hay forma barata de distinguir «texto visible» de «código» en un Blade, así
 * que esto no pretende encontrarlo todo: quita lo que seguro no es texto
 * —comentarios, bloques de PHP, expresiones, etiquetas— y mira lo que queda.
 * Lo que sobrevive con tres letras seguidas es, casi siempre, una palabra que
 * alguien escribió directamente.
 *
 * Así aparecieron «Apply» y «Cancel» en el calendario, «Drag & drop or browse»
 * en la subida y «Create» en el select: en inglés, en una librería que responde
 * en español, y sin forma de cambiarlos sin publicar las vistas.
 */
it('no deja texto visible escrito a mano en las plantillas', function () {
    $encontrados = [];

    $vaciar = fn (array $m) => str_repeat("\n", substr_count($m[0], "\n"));

    foreach (vistasDeLaLibreria() as $vista) {
        // Los bancos de pruebas no son parte de lo que ve un usuario.
        if (str_contains($vista->getRelativePathname(), 'e2e/')) {
            continue;
        }

        $s = $vista->getContents();

        foreach ([
            '/\{\{--.*?--\}\}/s',
            '/@php.*?@endphp/s',
            '/<\?php.*?\?>/s',
            '/<(script|style)[^>]*>.*?<\/\1>/s',
            '/\{\{.*?\}\}|\{!!.*?!!\}/s',
            '/<[^<>]*>/s',
        ] as $patron) {
            $s = preg_replace_callback($patron, $vaciar, $s);
        }

        foreach (preg_split('/\R/', $s) as $n => $linea) {
            $linea = trim($linea);

            // Una frase: dos palabras seguidas, o una sola con mayúscula inicial
            // ocupando la línea entera. Lo demás son restos de atributos
            // partidos en varias líneas, que no son texto.
            $esFrase = preg_match('/^[A-ZÁÉÍÓÚ][a-záéíóúñ]+( [A-Za-záéíóúñ&]+)+[.…]?$/u', $linea)
                || preg_match('/^[A-ZÁÉÍÓÚ][a-záéíóúñ]{2,}$/u', $linea);

            if ($esFrase && $linea !== 'Esc') {
                $encontrados[] = sprintf('%s:%d  %s', $vista->getRelativePathname(), $n + 1, $linea);
            }
        }
    }

    expect($encontrados)->toBe([], "Texto escrito en la plantilla; debería salir de una sección `translations`:\n".implode("\n", $encontrados));
});

it('lleva los atajos del calendario desde la configuración, no desde el JavaScript', function () {
    config()->set('kore-ui.form.translations.date_today', 'Avui');

    $this->blade('<x-kore::datepicker name="f" presets />')
        ->assertSee('Avui');

    // Y el bundle ya no los trae escritos en inglés.
    $js = file_get_contents(__DIR__.'/../../resources/js/form/datepicker.js');

    expect($js)->not->toContain("'Last 7 days'")
        ->not->toContain("'Yesterday'");
});

it('traduce la opción de crear de un select', function () {
    config()->set('kore-ui.form.translations.create', 'Afegir');

    $this->blade('<x-kore::select name="s" :options="[]" creatable searchable />')
        ->assertSee('Afegir')
        ->assertDontSee('Create "', false);
});

it('traduce la zona de subida', function () {
    config()->set('kore-ui.form.translations.upload_dropzone', 'Deixa anar els fitxers o :browse');
    config()->set('kore-ui.form.translations.upload_browse', 'tria');

    $this->blade('<x-kore::upload name="f" clearable />')
        ->assertSee('Deixa anar els fitxers o', false)
        ->assertSee('tria')
        ->assertDontSee('Drag &', false);
});

it('traduce la línea de restricciones de la subida', function () {
    config()->set('kore-ui.form.translations.upload_max_size', 'Limit {size} MB per fitxer');

    $this->blade('<x-kore::upload name="f" :max-size="2" :max-files="3" />')
        ->assertSee('Limit {size} MB per fitxer', false);

    $js = file_get_contents(__DIR__.'/../../resources/js/form/upload.js');

    expect($js)->not->toContain('MB per file')->not->toContain('Up to ${');
});
